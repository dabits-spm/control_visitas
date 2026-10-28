<?php
// app/servicios/RegistroVisita.php

require_once __DIR__ . '/ValidadorVisita.php';
require_once __DIR__ . '/../models/Visita.php';
require_once __DIR__ . '/../models/Visitante.php';
require_once __DIR__ . '/../models/Auditoria.php';

/**
 * Registro de una visita nueva desde el formulario: valida al visitante y la visita, y los
 * guarda juntos en una transacción (si la visita falla, tampoco cambian los datos del visitante).
 */
class RegistroVisita
{
    /**
     * Valida el formulario.
     * @return array [visitante, datos de la visita, departamentos, errores por campo]
     */
    public static function validar(array $post, int $idUsuario): array
    {
        $visitante = Visitante::normalizar($post);
        $tipo      = entradaTexto($post, 'tipo_visita');
        $errores   = Visitante::validar($visitante);

        if ($tipo === Visita::TIPO_AL_MOMENTO) {
            // Se usa la fecha y hora del servidor, no las que vengan del formulario
            $post['fecha_visita'] = date('Y-m-d');
            $post['hora_entrada'] = date('H:i');
        } elseif ($tipo !== Visita::TIPO_PROGRAMADA) {
            $errores['tipo_visita'] = 'Indique si la visita es al momento o programada.';
        }

        [$datos, $departamentos, $erroresVisita] = ValidadorVisita::leerDatos($post);
        if ($tipo !== Visita::TIPO_PROGRAMADA) {
            // Si el tipo no es válido, no tiene sentido reclamar por la fecha y la hora
            unset($erroresVisita['fecha_visita'], $erroresVisita['hora_entrada']);
        }
        $errores += $erroresVisita;

        // Una cita debe ser a futuro (y no más allá de un año)
        if ($tipo === Visita::TIPO_PROGRAMADA && !isset($errores['fecha_visita']) && !isset($errores['hora_entrada'])) {
            $errores += ValidadorVisita::validarCitaNueva($datos);
        }

        $datos['tipo_visita'] = $tipo;
        $datos['id_estado']   = $tipo === Visita::TIPO_AL_MOMENTO ? Visita::ESTADO_EN_PROCESO : Visita::ESTADO_PROGRAMADA;
        $datos['id_usuario']  = $idUsuario;
        return [$visitante, $datos, $departamentos, $errores];
    }

    /**
     * Guarda visitante (nuevo o actualizado) y visita en una transacción y lo deja en la auditoría.
     * @return array [id de la visita, mensaje para el usuario]
     * @throws Exception si no se pudo guardar
     */
    public static function guardar(array $visitante, array $datos, array $departamentos): array
    {
        $db = Database::getConexion();
        $cambios = [];
        try {
            $db->beginTransaction();
            $modeloVisitante = new Visitante();
            $existente = $modeloVisitante->buscarPorCedula($visitante['cedula']);

            if ($existente) {
                $datos['id_visitante'] = (int) $existente['id_visitante'];
                if (puede('visitantes.editar')) {
                    $cambios = Visitante::camposModificados($existente, $visitante);
                    $modeloVisitante->actualizar($datos['id_visitante'], $visitante);
                } else {
                    // Sin permiso solo se actualizan los datos de contacto; el nombre registrado se conserva
                    $cambios = Visitante::camposModificados($existente, $visitante, Visitante::CAMPOS_CONTACTO);
                    $modeloVisitante->actualizarContacto($datos['id_visitante'], $visitante);
                }
                $mensaje = $cambios
                    ? 'Visita registrada. Se actualizaron los datos del visitante (' . implode(', ', $cambios) . ').'
                    : 'Visita registrada para un visitante ya registrado.';
            } else {
                $datos['id_visitante'] = $modeloVisitante->crear($visitante);
                $mensaje = 'Visitante y visita registrados correctamente.';
            }

            $id_visita = (new Visita())->crear($datos, $departamentos);
            $db->commit();
        } catch (Exception $excepcion) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $excepcion;
        }

        self::auditar($id_visita, $datos, $visitante['cedula'], $cambios);
        return [$id_visita, $mensaje];
    }

    private static function auditar(int $id_visita, array $datos, string $cedula, array $cambios): void
    {
        $auditoria = new Auditoria();
        if ($cambios) {
            $auditoria->registrar('EDITAR',
                mb_substr("Visitante #{$datos['id_visitante']} (cédula {$cedula}) actualizado al registrar una visita: "
                    . implode(', ', $cambios), 0, 255),
                'visitante', $datos['id_usuario']);
        }
        $auditoria->registrar('CREAR',
            "Visita #{$id_visita} (" . Visita::TIPOS[$datos['tipo_visita']] . ") registrada para el visitante cédula {$cedula}",
            'visita', $datos['id_usuario']);
    }
}
