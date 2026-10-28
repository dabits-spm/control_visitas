<?php
// app/helpers/vista.php
// Funciones para las vistas: escapar HTML, URLs, permisos, paginación y avatares.

/** Escapa texto antes de imprimirlo en HTML (evita XSS). */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/** Indica si el usuario con sesión iniciada tiene un permiso: puede('visitas.eliminar'). */
function puede(string $permiso): bool
{
    return Usuario::rolPuede((int) ($_SESSION['usuario']['id_rol'] ?? 0), $permiso);
}

/** Construye una URL interna del sistema: url('visitas/crear'). */
function url(string $ruta = ''): string
{
    return BASE_URL . '/' . ltrim($ruta, '/');
}

/** Ruta de un archivo público (css, js, img). */
function recurso(string $ruta): string
{
    $archivo = dirname(APP_PATH) . '/public/' . ltrim($ruta, '/');
    // El número de versión obliga al navegador a recargar el archivo cuando cambia
    $version = is_file($archivo) ? filemtime($archivo) : 1;
    return url($ruta) . '?v=' . $version;
}

/** Convierte el nombre del estado en una clase CSS: "En proceso" -> "en-proceso". */
function claseEstado(string $estado): string
{
    $estado = mb_strtolower(trim($estado), 'UTF-8');
    return preg_replace('/[^a-z0-9]+/', '-', $estado);
}

// ------------------------------------------------------------
// Paginación de listados
// ------------------------------------------------------------

/**
 * Calcula los datos de una paginación.
 * @return array{pagina:int, paginas:int, total:int, porPagina:int, desplazamiento:int, desde:int, hasta:int}
 */
function paginacion(int $total, int $pagina, int $porPagina): array
{
    $paginas = max(1, (int) ceil($total / $porPagina));
    $pagina  = min(max(1, $pagina), $paginas);
    $desplazamiento = ($pagina - 1) * $porPagina;

    return [
        'pagina'         => $pagina,
        'paginas'        => $paginas,
        'total'          => $total,
        'porPagina'      => $porPagina,
        'desplazamiento' => $desplazamiento,
        'desde'          => $total === 0 ? 0 : $desplazamiento + 1,
        'hasta'          => min($total, $desplazamiento + $porPagina),
    ];
}

/** URL de un listado conservando sus filtros: urlConFiltros('visitas', ['q' => 'ana', 'pagina' => 2]). */
function urlConFiltros(string $ruta, array $parametros): string
{
    $parametros = array_filter($parametros, static fn ($v) => $v !== '' && $v !== null && $v !== 0 && $v !== []);
    return url($ruta) . ($parametros ? '?' . http_build_query($parametros) : '');
}

/** Iniciales para el avatar del visitante: "Miguel Parada" -> "MP". */
function iniciales(string $nombreCompleto): string
{
    $partes = preg_split('/\s+/', trim($nombreCompleto)) ?: [];
    $letras = '';
    foreach (array_slice($partes, 0, 2) as $parte) {
        $letras .= mb_strtoupper(mb_substr($parte, 0, 1, 'UTF-8'), 'UTF-8');
    }
    return $letras !== '' ? $letras : '?';
}

/**
 * Hojas de estilo del sistema, en orden: public/assets/css/NN-tema.css.
 * Cada hoja tiene un tema (base, barra lateral, tablas, formularios...); el número fija el orden.
 */
function hojasDeEstilo(): array
{
    return archivosPublicos('assets/css', 'css');
}

/** Scripts del sistema, en orden: public/assets/js/NN-tema.js (comparten el objeto global CV). */
function scriptsDelSistema(): array
{
    return archivosPublicos('assets/js', 'js');
}

/** Rutas públicas (con versión para la caché) de los archivos de una carpeta de public/, ordenados por nombre. */
function archivosPublicos(string $carpeta, string $extension): array
{
    $archivos = glob(dirname(APP_PATH) . "/public/{$carpeta}/*.{$extension}") ?: [];
    sort($archivos, SORT_STRING);
    return array_map(static fn (string $ruta): string => recurso($carpeta . '/' . basename($ruta)), $archivos);
}
