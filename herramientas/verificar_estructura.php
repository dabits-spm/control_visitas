<?php
// herramientas/verificar_estructura.php
// ---------------------------------------------------------------------
// Revisa que el proyecto mantenga su estructura. Desde la carpeta del proyecto:
//   C:\xampp\php\php.exe herramientas\verificar_estructura.php
//
//  1. Ningún archivo de código (PHP, JS, CSS, SQL) supera 200 líneas.
//  2. Cada ruta de app/config/rutas.php apunta a un controlador y un método que existen.
//  3. Cada require/require_once con ruta fija apunta a un archivo que existe.
//  4. Ningún archivo PHP tiene errores de sintaxis.
// Termina con código 0 si todo está bien, o 1 si encontró problemas.
// ---------------------------------------------------------------------

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const LIMITE_LINEAS = 200;
$raiz = dirname(__DIR__);
$problemas = 0;

/** Archivos del proyecto con alguna de las extensiones indicadas (sin .git). */
function archivosDelProyecto(string $raiz, array $extensiones): array
{
    $lista = [];
    $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS));
    foreach ($iterador as $archivo) {
        $ruta = str_replace('\\', '/', $archivo->getPathname());
        if (!str_contains($ruta, '/.git/') && in_array(strtolower($archivo->getExtension()), $extensiones, true)) {
            $lista[] = $ruta;
        }
    }
    sort($lista);
    return $lista;
}

function relativa(string $ruta, string $raiz): string
{
    return ltrim(substr($ruta, strlen(str_replace('\\', '/', $raiz))), '/');
}

// --- 1. Tamaño de los archivos ---
$codigo = archivosDelProyecto($raiz, ['php', 'js', 'css', 'sql']);
$mayor = ['', 0];
foreach ($codigo as $archivo) {
    $lineas = count(file($archivo));
    if ($lineas > $mayor[1]) {
        $mayor = [$archivo, $lineas];
    }
    if ($lineas > LIMITE_LINEAS) {
        echo "TAMAÑO  " . relativa($archivo, $raiz) . " tiene {$lineas} líneas (máximo " . LIMITE_LINEAS . ")\n";
        $problemas++;
    }
}
echo count($codigo) . " archivos de código; el más grande: " . relativa($mayor[0], $raiz) . " ({$mayor[1]} líneas)\n";

// --- 2. Rutas ---
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $raiz . '/app/config/config.php';
$rutas = require $raiz . '/app/config/rutas.php';
$total = 0;
foreach ($rutas as $metodoHttp => $tabla) {
    foreach ($tabla as $url => [$clase, $accion]) {
        $total++;
        $archivo = $raiz . "/app/controllers/{$clase}.php";
        if (!is_file($archivo)) {
            echo "RUTA    {$metodoHttp} {$url}: no existe app/controllers/{$clase}.php\n";
            $problemas++;
        } elseif (!preg_match('/function\s+' . preg_quote($accion, '/') . '\s*\(/', file_get_contents($archivo))) {
            echo "RUTA    {$metodoHttp} {$url}: {$clase} no tiene el método {$accion}()\n";
            $problemas++;
        }
    }
}
echo "{$total} rutas revisadas\n";

// --- 3. Includes con ruta fija ---
$php = archivosDelProyecto($raiz, ['php']);
$includes = 0;
foreach ($php as $archivo) {
    $texto = file_get_contents($archivo);
    preg_match_all("/require(?:_once)?\s*\(?\s*(__DIR__|APP_PATH)\s*\.\s*'([^']+)'/", $texto, $coincidencias, PREG_SET_ORDER);
    foreach ($coincidencias as [, $base, $ruta]) {
        $includes++;
        $destino = ($base === '__DIR__' ? dirname($archivo) : $raiz . '/app') . $ruta;
        if (!is_file($destino) && !str_ends_with($ruta, '/correo.php')) {
            echo "INCLUDE " . relativa($archivo, $raiz) . " -> {$ruta} no existe\n";
            $problemas++;
        }
    }
}
echo "{$includes} includes revisados\n";

// --- 4. Sintaxis de los archivos PHP ---
$php_exe = PHP_BINARY;
$conError = 0;
foreach ($php as $archivo) {
    exec(escapeshellarg($php_exe) . ' -l ' . escapeshellarg($archivo) . ' 2>&1', $salida, $codigoSalida);
    if ($codigoSalida !== 0) {
        echo "SINTAXIS " . relativa($archivo, $raiz) . "\n";
        $conError++;
    }
    $salida = [];
}
$problemas += $conError;
echo count($php) . " archivos PHP revisados, {$conError} con errores de sintaxis\n";

echo $problemas === 0 ? "\nTodo en orden.\n" : "\nSe encontraron {$problemas} problemas.\n";
exit($problemas === 0 ? 0 : 1);
