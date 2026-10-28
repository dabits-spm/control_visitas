<?php
// app/utilidades/LibroExcel.php

/**
 * Genera archivos de Excel (.xlsx) sin librerías externas.
 *
 * Un .xlsx es un ZIP con varios archivos XML. El ZIP se arma aquí mismo
 * (con gzdeflate, que PHP siempre trae), así que no hace falta activar la
 * extensión "zip" de XAMPP.
 *
 * Uso:
 *   $libro = new LibroExcel();
 *   $hoja  = $libro->agregarHoja('Resumen', [30, 12, 12]);   // anchos de columna
 *   $hoja->fila(['Reporte de visitas'], 'titulo');
 *   $hoja->fila(['Día', 'Total'], 'encabezado');
 *   $hoja->fila(['Lun 05/10/2026', 3]);
 *   $libro->descargar('reporte.xlsx');
 */

require_once __DIR__ . '/HojaExcel.php';
require_once __DIR__ . '/PartesLibroExcel.php';

class LibroExcel
{
    use PartesLibroExcel;   // XML de las partes del paquete

    /** Estilos disponibles (el índice coincide con <cellXfs> de estilos()). */
    public const ESTILOS = [
        'normal'     => 0,
        'titulo'     => 1,
        'subtitulo'  => 2,
        'encabezado' => 3,
        'dato'       => 4,
        'total'      => 5,
        'nota'       => 6,
        'negrita'    => 7,
    ];

    /** @var HojaExcel[] */
    private array $hojas = [];

    public function agregarHoja(string $nombre, array $anchos = []): HojaExcel
    {
        // Excel no admite estos caracteres en el nombre de una hoja y lo limita a 31
        $nombre = mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', ' ', $nombre), 0, 31) ?: 'Hoja' . (count($this->hojas) + 1);
        $hoja = new HojaExcel($nombre, $anchos);
        $this->hojas[] = $hoja;
        return $hoja;
    }

    /** Envía el archivo al navegador para descargarlo. */
    public function descargar(string $nombreArchivo): void
    {
        $contenido = $this->generar();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        // filename: versión sin acentos para navegadores antiguos; filename*: nombre exacto en UTF-8
        $simple = preg_replace('/[^A-Za-z0-9 ._-]/', '_', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombreArchivo) ?: 'reporte');
        header('Content-Disposition: attachment; filename="' . $simple . '"; filename*=UTF-8\'\'' . rawurlencode($nombreArchivo));
        header('Content-Length: ' . strlen($contenido));
        header('Cache-Control: no-store');
        echo $contenido;
        exit;
    }

    /** Devuelve el contenido binario del .xlsx. */
    public function generar(): string
    {
        if (!$this->hojas) {
            $this->agregarHoja('Hoja1');
        }

        $archivos = [
            '[Content_Types].xml'        => $this->tiposContenido(),
            '_rels/.rels'                => $this->relacionesRaiz(),
            'docProps/app.xml'           => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Control de Visitas</Application></Properties>',
            'docProps/core.xml'          => $this->propiedades(),
            'xl/workbook.xml'            => $this->libro(),
            'xl/_rels/workbook.xml.rels' => $this->relacionesLibro(),
            'xl/styles.xml'              => $this->estilos(),
        ];
        foreach ($this->hojas as $i => $hoja) {
            $archivos['xl/worksheets/sheet' . ($i + 1) . '.xml'] = $hoja->xml();
        }

        return self::zip($archivos);
    }


    // ------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------

    /** Escapa texto para XML y quita caracteres de control que Excel no acepta. */
    public static function xml(string $texto): string
    {
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $texto);
        return htmlspecialchars($texto, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /** Arma un archivo ZIP (método deflate) con los archivos indicados [ruta => contenido]. */
    private static function zip(array $archivos): string
    {
        $ahora = getdate();
        $horaDos  = ($ahora['hours'] << 11) | ($ahora['minutes'] << 5) | intdiv($ahora['seconds'], 2);
        $fechaDos = (max(0, $ahora['year'] - 1980) << 9) | ($ahora['mon'] << 5) | $ahora['mday'];

        $datos = '';
        $directorio = '';
        foreach ($archivos as $ruta => $contenido) {
            $comprimido = gzdeflate($contenido, 6);
            $crc        = crc32($contenido);
            $desplazamiento = strlen($datos);

            // Encabezado local + datos
            $datos .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, 8, $horaDos, $fechaDos,
                           $crc, strlen($comprimido), strlen($contenido), strlen($ruta), 0)
                    . $ruta . $comprimido;

            // Entrada del directorio central
            $directorio .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, 8, $horaDos, $fechaDos,
                                $crc, strlen($comprimido), strlen($contenido), strlen($ruta), 0, 0, 0, 0, 0, $desplazamiento)
                         . $ruta;
        }

        $fin = pack('VvvvvVVv', 0x06054b50, 0, 0, count($archivos), count($archivos),
                    strlen($directorio), strlen($datos), 0);

        return $datos . $directorio . $fin;
    }
}
