<?php
// app/helpers/entrada.php
// Lectura segura de los datos enviados por el navegador ($_GET / $_POST).

// ------------------------------------------------------------
// Lectura segura de datos enviados por el navegador
// ------------------------------------------------------------
// Un campo puede llegar como arreglo si alguien manipula el formulario
// (por ejemplo "nombre[]=x"). Estas funciones lo convierten en un valor
// vacío en lugar de provocar un error fatal o guardar la palabra "Array".

/** Texto de un campo de $_GET / $_POST; '' si no existe o no es texto. */
function entradaTexto(array $origen, string $clave): string
{
    $valor = $origen[$clave] ?? '';
    return is_string($valor) ? $valor : '';
}

/** Lista de textos de un campo múltiple (casillas "nombre[]"); [] si no es una lista. */
function entradaLista(array $origen, string $clave): array
{
    $valor = $origen[$clave] ?? [];
    return is_array($valor) ? array_values(array_filter($valor, 'is_string')) : [];
}

/**
 * Copia de un formulario enviado para volver a mostrarlo tras un error.
 * Solo conserva textos (y la lista de departamentos).
 */
function datosFormulario(array $entrada): array
{
    $limpio = [];
    foreach ($entrada as $clave => $valor) {
        if ($clave === 'departamentos') {
            $limpio[$clave] = entradaLista($entrada, $clave);
        } elseif (is_string($valor)) {
            $limpio[$clave] = $valor;
        }
    }
    unset($limpio['csrf']);
    return $limpio;
}
