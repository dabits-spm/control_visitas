<?php
// app/utilidades/TablaTexto.php

/**
 * Reportes en texto plano (.txt) con tablas alineadas, para abrir con el
 * Bloc de notas o imprimir en cualquier impresora.
 *
 * Uso:
 *   $txt = new TablaTexto();
 *   $txt->titulo('Reporte de visitas');
 *   $txt->linea('Periodo: 01/10/2026 al 23/10/2026');
 *   $txt->tabla(['Día', 'Total'], [['Lun 05/10/2026', 3]], ['izq', 'der']);
 *   $txt->descargar('reporte.txt');
 */
class TablaTexto
{
    private const ANCHO_MAXIMO_COLUMNA = 40;   // los textos más largos pasan a la línea siguiente

    private array $lineas = [];

    public function titulo(string $texto): void
    {
        $this->lineas[] = mb_strtoupper($texto, 'UTF-8');
        $this->lineas[] = str_repeat('=', self::ancho($texto));
    }

    public function subtitulo(string $texto): void
    {
        $this->lineas[] = '';
        $this->lineas[] = $texto;
        $this->lineas[] = str_repeat('-', self::ancho($texto));
    }

    public function linea(string $texto = ''): void
    {
        $this->lineas[] = $texto;
    }

    /** Lista "etiqueta: valor" con los valores alineados. */
    public function pares(array $pares): void
    {
        $ancho = max(array_map(static fn ($e) => self::ancho((string) $e), array_keys($pares)) ?: [0]);
        foreach ($pares as $etiqueta => $valor) {
            $this->lineas[] = self::rellenar($etiqueta . ':', $ancho + 1, false) . '  ' . $valor;
        }
    }

    /**
     * Tabla con bordes. $alineacion: 'izq' o 'der' por columna (los números a la derecha).
     * Si se indica $pie (ej. fila de totales), se separa con una línea doble.
     */
    public function tabla(array $encabezados, array $filas, array $alineacion = [], ?array $pie = null): void
    {
        $todas = array_merge([$encabezados], $filas, $pie ? [$pie] : []);

        // Ancho de cada columna: el texto más largo, sin pasar del máximo
        $anchos = [];
        foreach ($todas as $fila) {
            foreach (array_values($fila) as $i => $valor) {
                $largo = 0;
                foreach (explode("\n", (string) $valor) as $parte) {
                    $largo = max($largo, self::ancho($parte));
                }
                $anchos[$i] = max($anchos[$i] ?? 1, min($largo, self::ANCHO_MAXIMO_COLUMNA));
            }
        }

        $borde = '+' . implode('+', array_map(static fn ($a) => str_repeat('-', $a + 2), $anchos)) . '+';
        $doble = '+' . implode('+', array_map(static fn ($a) => str_repeat('=', $a + 2), $anchos)) . '+';

        $this->lineas[] = $borde;
        $this->agregarFila($encabezados, $anchos, []);
        $this->lineas[] = $doble;
        foreach ($filas as $fila) {
            $this->agregarFila($fila, $anchos, $alineacion);
        }
        if ($pie) {
            $this->lineas[] = $doble;
            $this->agregarFila($pie, $anchos, $alineacion);
        }
        $this->lineas[] = $borde;
    }

    /** Contenido final con saltos de línea de Windows (para el Bloc de notas). */
    public function generar(): string
    {
        $lineas = array_map(static fn ($l) => rtrim($l), $this->lineas);
        // La marca BOM ayuda a que los programas antiguos de Windows muestren bien los acentos
        return "\xEF\xBB\xBF" . implode("\r\n", $lineas) . "\r\n";
    }

    public function descargar(string $nombreArchivo): void
    {
        $contenido = $this->generar();
        header('Content-Type: text/plain; charset=utf-8');
        // filename: versión sin acentos para navegadores antiguos; filename*: nombre exacto en UTF-8
        $simple = preg_replace('/[^A-Za-z0-9 ._-]/', '_', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombreArchivo) ?: 'reporte');
        header('Content-Disposition: attachment; filename="' . $simple . '"; filename*=UTF-8\'\'' . rawurlencode($nombreArchivo));
        header('Content-Length: ' . strlen($contenido));
        header('Cache-Control: no-store');
        echo $contenido;
        exit;
    }

    // ------------------------------------------------------------

    /** Una fila puede ocupar varias líneas si algún texto es largo. */
    private function agregarFila(array $fila, array $anchos, array $alineacion): void
    {
        $celdas = [];
        $alto = 1;
        foreach (array_values($fila) as $i => $valor) {
            $celdas[$i] = self::envolver((string) $valor, $anchos[$i]);
            $alto = max($alto, count($celdas[$i]));
        }

        for ($l = 0; $l < $alto; $l++) {
            $partes = [];
            foreach ($anchos as $i => $ancho) {
                $texto = $celdas[$i][$l] ?? '';
                $partes[] = ' ' . self::rellenar($texto, $ancho, ($alineacion[$i] ?? 'izq') === 'der') . ' ';
            }
            $this->lineas[] = '|' . implode('|', $partes) . '|';
        }
    }

    /** Divide un texto en líneas de un ancho máximo (respetando las palabras cuando se puede). */
    private static function envolver(string $texto, int $ancho): array
    {
        $resultado = [];
        foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $texto)) as $parrafo) {
            $linea = '';
            foreach (preg_split('/\s+/u', trim($parrafo)) ?: [''] as $palabra) {
                // Palabras más largas que la columna se cortan
                while (self::ancho($palabra) > $ancho) {
                    if ($linea !== '') {
                        $resultado[] = $linea;
                        $linea = '';
                    }
                    $resultado[] = mb_substr($palabra, 0, $ancho, 'UTF-8');
                    $palabra = mb_substr($palabra, $ancho, null, 'UTF-8');
                }
                $prueba = $linea === '' ? $palabra : $linea . ' ' . $palabra;
                if (self::ancho($prueba) > $ancho) {
                    $resultado[] = $linea;
                    $linea = $palabra;
                } else {
                    $linea = $prueba;
                }
            }
            $resultado[] = $linea;
        }
        return $resultado ?: [''];
    }

    private static function rellenar(string $texto, int $ancho, bool $derecha): string
    {
        $espacios = str_repeat(' ', max(0, $ancho - self::ancho($texto)));
        return $derecha ? $espacios . $texto : $texto . $espacios;
    }

    private static function ancho(string $texto): int
    {
        return mb_strlen($texto, 'UTF-8');
    }
}
