<?php
// app/models/traits/VisitanteValidacion.php

/**
 * Visitante: limpieza y validación de los datos personales (cédula, nombre, teléfono, correo).
 */
trait VisitanteValidacion
{
    /**
     * Limpia los datos escritos en el formulario para guardarlos de forma uniforme.
     *   cédula:   "v 12.345.678" -> "V-12345678"  |  "12345678" -> "V-12345678"  |  "e-8123456" -> "E-8123456"
     *   nombre:   "  miguel  angel " -> "Miguel Angel"
     *   teléfono: "0414 123 45 67" / "+58 414 1234567" -> "0414-1234567"  |  "+57 300 123 4567" -> "+573001234567"
     *   correo:   " Correo@Mail.com " -> "correo@mail.com"
     */
    public static function normalizar(array $entrada): array
    {
        $texto = static fn (string $clave): string => trim(preg_replace('/\s+/u', ' ', entradaTexto($entrada, $clave)));
        $nombrePropio = static fn (string $valor): string => mb_convert_case(mb_strtolower($valor, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

        return [
            'cedula'      => self::normalizarCedula(entradaTexto($entrada, 'cedula')),
            'nombre'      => $nombrePropio($texto('nombre')),
            'apellido'    => $nombrePropio($texto('apellido')),
            'telefono'    => self::normalizarTelefono(entradaTexto($entrada, 'telefono')),
            'correo'      => mb_strtolower(trim(entradaTexto($entrada, 'correo')), 'UTF-8'),
            'procedencia' => $texto('procedencia'),
        ];
    }

    /**
     * Una cédula sin letra se considera venezolana, así "12345678" y "V-12345678"
     * son la misma persona y nunca se registran dos veces.
     */
    public static function normalizarCedula(string $cedula): string
    {
        $cedula = mb_strtoupper(preg_replace('/[\s.\-]/', '', $cedula), 'UTF-8');
        if (preg_match('/^([VE])?(\d+)$/', $cedula, $partes)) {
            return ($partes[1] ?: 'V') . '-' . $partes[2];
        }
        return $cedula;
    }

    /** Teléfonos venezolanos como "0414-1234567"; los extranjeros con su código: "+573001234567". */
    public static function normalizarTelefono(string $telefono): string
    {
        $telefono = preg_replace('/[\s().\-]/', '', $telefono);
        if (str_starts_with($telefono, '00')) {
            $telefono = '+' . substr($telefono, 2);
        }
        if (preg_match('/^\+58(\d{10})$/', $telefono, $p)) {
            $telefono = '0' . $p[1];
        }
        if (preg_match('/^0(\d{3})(\d{7})$/', $telefono, $p)) {
            return '0' . $p[1] . '-' . $p[2];
        }
        return $telefono;
    }

    /**
     * Valida los datos ya normalizados. Todos son obligatorios salvo la procedencia.
     * @return array<string,string> errores por campo (vacío si todo está bien)
     */
    public static function validar(array $datos): array
    {
        $errores = [];
        $soloLetras = '/^\p{L}[\p{L}\s\'.-]*$/u';

        // Cédula: V (venezolano) o E (extranjero) y de 6 a 10 dígitos
        if ($datos['cedula'] === '') {
            $errores['cedula'] = 'La cédula es obligatoria.';
        } elseif (!preg_match('/^[VE]-\d{6,10}$/', $datos['cedula'])) {
            $errores['cedula'] = 'Escriba V o E y de 6 a 10 dígitos (Ej: V-28123456 o E-81234567).';
        }

        // Nombre
        if ($datos['nombre'] === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($datos['nombre']) < 2 || mb_strlen($datos['nombre']) > 60) {
            $errores['nombre'] = 'El nombre debe tener entre 2 y 60 caracteres.';
        } elseif (!preg_match($soloLetras, $datos['nombre'])) {
            $errores['nombre'] = 'El nombre solo puede contener letras.';
        }

        // Apellido
        if ($datos['apellido'] === '') {
            $errores['apellido'] = 'El apellido es obligatorio.';
        } elseif (mb_strlen($datos['apellido']) < 2 || mb_strlen($datos['apellido']) > 60) {
            $errores['apellido'] = 'El apellido debe tener entre 2 y 60 caracteres.';
        } elseif (!preg_match($soloLetras, $datos['apellido'])) {
            $errores['apellido'] = 'El apellido solo puede contener letras.';
        }

        // Teléfono: venezolano (0414-1234567) o extranjero con código de país (+573001234567)
        if ($datos['telefono'] === '') {
            $errores['telefono'] = 'El teléfono es obligatorio.';
        } elseif (!preg_match('/^(0\d{3}-\d{7}|\+(?!58)[1-9]\d{7,14})$/', $datos['telefono'])) {
            $errores['telefono'] = 'Ingrese un teléfono válido (Ej: 0414-1234567, o +57 300 1234567 si es extranjero).';
        }

        // Correo
        if ($datos['correo'] === '') {
            $errores['correo'] = 'El correo electrónico es obligatorio.';
        } elseif (mb_strlen($datos['correo']) > 100 || !filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores['correo'] = 'Ingrese un correo electrónico válido (Ej: nombre@correo.com).';
        }

        // Procedencia (opcional)
        if (mb_strlen($datos['procedencia']) > 100) {
            $errores['procedencia'] = 'La procedencia no puede superar los 100 caracteres.';
        }

        return $errores;
    }

    /**
     * Nombres de los campos que cambian entre los datos guardados y los nuevos
     * (para dejar constancia en la auditoría).
     */
    public static function camposModificados(array $anterior, array $nuevo, ?array $soloCampos = null): array
    {
        $etiquetas = ['cedula' => 'cédula', 'nombre' => 'nombre', 'apellido' => 'apellido',
                      'telefono' => 'teléfono', 'correo' => 'correo', 'procedencia' => 'procedencia'];
        $cambios = [];
        foreach ($soloCampos ?? array_keys($etiquetas) as $campo) {
            if ((string) ($anterior[$campo] ?? '') !== (string) ($nuevo[$campo] ?? '')) {
                $cambios[] = $etiquetas[$campo];
            }
        }
        return $cambios;
    }
}
