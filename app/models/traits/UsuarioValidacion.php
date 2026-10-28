<?php
// app/models/traits/UsuarioValidacion.php
require_once __DIR__ . '/../Visitante.php';   // mismas reglas de cédula, nombre, teléfono y correo

/**
 * Usuario: reglas de la contraseña y limpieza/validación de los datos de una cuenta (métodos estáticos).
 */
trait UsuarioValidacion
{
    /** Valida una contraseña nueva. Devuelve el mensaje de error o null si es aceptable. */
    public static function validarClaveNueva(string $clave, string $nombreUsuario = ''): ?string
    {
        if ($clave === '') {
            return 'Escriba la contraseña nueva.';
        }
        // password_hash (bcrypt) solo considera los primeros 72 bytes
        if (mb_strlen($clave) < 8 || strlen($clave) > 72) {
            return 'La contraseña debe tener entre 8 y 72 caracteres.';
        }
        if (!preg_match('/\p{L}/u', $clave) || !preg_match('/\d/', $clave)) {
            return 'La contraseña debe tener al menos una letra y un número.';
        }
        if (mb_strlen($nombreUsuario) >= 3 && str_contains(mb_strtolower($clave), mb_strtolower($nombreUsuario))) {
            return 'La contraseña no puede contener el nombre de usuario.';
        }
        return null;
    }

    /**
     * Huella corta del hash de la contraseña. Se guarda en la sesión al iniciarla:
     * si la contraseña cambia (por ejemplo, la restablece el administrador), la
     * sesión abierta deja de servir.
     */
    public static function huellaClave(string $hash): string
    {
        return substr(hash('sha256', $hash), 0, 20);
    }

    // ------------------------------------------------------------
    // Datos de la cuenta: limpieza y validación
    // ------------------------------------------------------------

    /** Limpia los datos del formulario de una cuenta (mismos formatos que los visitantes). */
    public static function normalizar(array $entrada): array
    {
        $texto = static fn (string $clave): string => trim(preg_replace('/\s+/u', ' ', entradaTexto($entrada, $clave)));
        $nombrePropio = static fn (string $valor): string => mb_convert_case(mb_strtolower($valor, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

        return [
            'cedula'   => Visitante::normalizarCedula(entradaTexto($entrada, 'cedula')),
            'nombre'   => $nombrePropio($texto('nombre')),
            'apellido' => $nombrePropio($texto('apellido')),
            'telefono' => Visitante::normalizarTelefono(entradaTexto($entrada, 'telefono')),
            'correo'   => mb_strtolower(trim(entradaTexto($entrada, 'correo')), 'UTF-8'),
            'user'     => mb_strtolower(trim(entradaTexto($entrada, 'user')), 'UTF-8'),
        ];
    }

    /**
     * Valida los datos de una cuenta. Usa las mismas reglas que los visitantes para
     * cédula, nombre, apellido, teléfono y correo, más el nombre de usuario.
     * @return array<string,string> errores por campo
     */
    public static function validar(array $datos): array
    {
        $errores = Visitante::validar($datos + ['procedencia' => '']);
        unset($errores['procedencia']);

        if ($datos['user'] === '') {
            $errores['user'] = 'El nombre de usuario es obligatorio.';
        } elseif (!preg_match('/^[a-z0-9][a-z0-9._-]{2,39}$/', $datos['user'])) {
            $errores['user'] = 'Use de 3 a 40 letras sin acentos, números, punto, guion o guion bajo (Ej: maria.perez).';
        }
        return $errores;
    }
}
