<?php
// app/utilidades/HojaExcel.php
// Usada por LibroExcel (ver ese archivo).
require_once __DIR__ . '/LibroExcel.php';   // estilos y escape XML

/** Una hoja del libro. Se llena fila por fila. */
class HojaExcel
{
    private array $filas = [];        // XML de cada fila
    private array $combinadas = [];   // rangos "A1:F1"
    private ?string $autofiltro = null;
    private int $filaFija = 0;        // filas que quedan fijas al desplazarse

    public function __construct(public readonly string $nombre, private array $anchos = []) {}

    /** Número de la próxima fila (empieza en 1). */
    public function siguienteFila(): int
    {
        return count($this->filas) + 1;
    }

    /**
     * Agrega una fila. Los números se guardan como números (para poder sumarlos en Excel)
     * y el resto como texto. $estilo: clave de LibroExcel::ESTILOS, o un arreglo con un estilo por celda.
     */
    public function fila(array $valores, string|array $estilo = 'dato', ?float $alto = null): int
    {
        $numero = $this->siguienteFila();
        $celdas = '';
        foreach (array_values($valores) as $i => $valor) {
            $nombreEstilo = is_array($estilo) ? ($estilo[$i] ?? 'dato') : $estilo;
            $s   = LibroExcel::ESTILOS[$nombreEstilo] ?? 0;
            $ref = self::columna($i) . $numero;

            if ($valor === null || $valor === '') {
                $celdas .= '<c r="' . $ref . '" s="' . $s . '"/>';
            } elseif (is_int($valor) || is_float($valor)) {
                $celdas .= '<c r="' . $ref . '" s="' . $s . '"><v>' . $valor . '</v></c>';
            } else {
                $celdas .= '<c r="' . $ref . '" s="' . $s . '" t="inlineStr"><is><t xml:space="preserve">'
                    . LibroExcel::xml((string) $valor) . '</t></is></c>';
            }
        }
        $atributoAlto = $alto ? ' ht="' . $alto . '" customHeight="1"' : '';
        $this->filas[] = '<row r="' . $numero . '"' . $atributoAlto . '>' . $celdas . '</row>';
        return $numero;
    }

    public function filaVacia(): void
    {
        $this->filas[] = '<row r="' . $this->siguienteFila() . '"/>';
    }

    /** Combina celdas de una fila: combinar(1, 0, 5) une A1:F1. */
    public function combinar(int $fila, int $desdeColumna, int $hastaColumna): void
    {
        if ($hastaColumna > $desdeColumna) {
            $this->combinadas[] = self::columna($desdeColumna) . $fila . ':' . self::columna($hastaColumna) . $fila;
        }
    }

    /** Agrega el filtro de Excel a una tabla (flechas en el encabezado). */
    public function autofiltro(int $filaEncabezado, int $ultimaFila, int $columnas): void
    {
        $this->autofiltro = 'A' . $filaEncabezado . ':' . self::columna($columnas - 1) . max($ultimaFila, $filaEncabezado);
    }

    /** Deja fijas las filas hasta la indicada (por ejemplo, el encabezado de la tabla). */
    public function fijarHasta(int $fila): void
    {
        $this->filaFija = $fila;
    }

    public function xml(): string
    {
        $vista = '<sheetView workbookViewId="0" showGridLines="0">';
        if ($this->filaFija > 0) {
            $celda = 'A' . ($this->filaFija + 1);
            $vista .= '<pane ySplit="' . $this->filaFija . '" topLeftCell="' . $celda . '" activePane="bottomLeft" state="frozen"/>'
                    . '<selection pane="bottomLeft" activeCell="' . $celda . '" sqref="' . $celda . '"/>';
        }
        $vista .= '</sheetView>';

        $columnas = '';
        foreach ($this->anchos as $i => $ancho) {
            $columnas .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $ancho . '" customWidth="1"/>';
        }

        $combinadas = '';
        if ($this->combinadas) {
            $combinadas = '<mergeCells count="' . count($this->combinadas) . '">';
            foreach ($this->combinadas as $rango) {
                $combinadas .= '<mergeCell ref="' . $rango . '"/>';
            }
            $combinadas .= '</mergeCells>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'   // al imprimir, todas las columnas en el ancho de la hoja
            . '<sheetViews>' . $vista . '</sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . ($columnas ? '<cols>' . $columnas . '</cols>' : '')
            . '<sheetData>' . implode('', $this->filas) . '</sheetData>'
            . ($this->autofiltro ? '<autoFilter ref="' . $this->autofiltro . '"/>' : '')
            . $combinadas
            . '<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/>'
            . '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            . '</worksheet>';
    }

    /** Índice de columna (desde 0) a letra: 0 -> A, 25 -> Z, 26 -> AA. */
    public static function columna(int $indice): string
    {
        $letras = '';
        for ($n = $indice + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letras = chr(65 + ($n - 1) % 26) . $letras;
        }
        return $letras;
    }
}
