<?php
// app/servicios/reportes/Exportador.php

require_once __DIR__ . '/../../models/Visita.php';
require_once __DIR__ . '/../../models/Auditoria.php';
require_once __DIR__ . '/../../utilidades/LibroExcel.php';
require_once __DIR__ . '/../../utilidades/TablaTexto.php';

/**
 * Base de las descargas de reportes en Excel (.xlsx) y texto plano (.txt):
 * formatos admitidos, línea «Periodo · Generado el ... por ...» y registro en la auditoría.
 */
abstract class Exportador
{
    /** Formatos de descarga: ?formato=xlsx (Excel) o ?formato=txt (texto plano). */
    public const FORMATOS = ['xlsx', 'txt'];

    /** Formato pedido en la URL, o null si no se pidió una descarga. */
    public static function formatoPedido(): ?string
    {
        $formato = entradaTexto($_GET, 'formato');
        return in_array($formato, self::FORMATOS, true) ? $formato : null;
    }

    /** Línea con el periodo, la fecha de generación y el usuario. */
    protected static function lineaGenerado(string $desde, string $hasta): string
    {
        $usuario = $_SESSION['usuario'] ?? [];
        return 'Periodo: ' . fechaLarga($desde) . ' al ' . fechaLarga($hasta)
            . ' · Generado el ' . date('d/m/Y h:i A')
            . (!empty($usuario) ? ' por ' . $usuario['nombre'] . ' ' . $usuario['apellido'] : '');
    }

    protected static function registrarExportacion(string $formato, string $titulo): void
    {
        require_once APP_PATH . '/models/Auditoria.php';
        (new Auditoria())->registrar(
            'EXPORTAR',
            mb_substr($titulo . ' (' . strtoupper($formato) . ')', 0, 255),
            'visita',
            (int) $_SESSION['usuario']['id_usuario']
        );
    }
}
