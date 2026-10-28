<?php
// app/models/Notificacion.php

require_once __DIR__ . '/Visita.php';
require_once __DIR__ . '/../utilidades/CorreoSmtp.php';

/**
 * Notificaciones internas del sistema (administrador y recepcionista).
 *
 * No se guardan en una tabla: se calculan en cada consulta a partir del estado
 * actual de las visitas. Así la bandeja solo muestra lo PENDIENTE y una
 * notificación desaparece sola en cuanto la visita se atiende (cambia de estado):
 *
 *   seguimiento     Cita de hoy cuya hora pasó hace 30 minutos o más y sigue
 *                   «Programada» o «Reprogramada»: ¿se realizó? Actualice el estado.
 *                   A partir de los 60 minutos se marca como atrasada (urgente).
 *   cita_hoy        Cita de hoy pendiente que aún no llega a los 30 minutos de su hora.
 *   aviso_pendiente Cita próxima cuyo aviso por correo no se pudo enviar.
 *   en_curso        Visita de hoy «En proceso» (tarea: finalizarla cuando la persona salga).
 *
 * Las visitas finalizadas o canceladas nunca generan notificaciones.
 */
class Notificacion
{
    public const MINUTOS_SEGUIMIENTO = 30;   // primer recordatorio por falta de cambio de estado
    public const MINUTOS_ATRASO      = 60;   // desde aquí el recordatorio es urgente

    /** Orden y nombre de cada tipo (los primeros son los más importantes). */
    public const TIPOS = [
        'seguimiento'     => 'Citas sin actualizar',
        'cita_hoy'        => 'Citas de hoy',
        'aviso_pendiente' => 'Avisos por correo sin enviar',
        'en_curso'        => 'Visitas en curso por finalizar',
    ];

    /** Tipos que cuentan en el número rojo de la campana (las visitas en curso son tareas, no alertas). */
    public const TIPOS_ALERTA = ['seguimiento', 'cita_hoy', 'aviso_pendiente'];

    private Visita $visitas;

    public function __construct()
    {
        $this->visitas = new Visita();
    }

    /**
     * Notificaciones pendientes en este momento, de la más importante a la menos.
     * @return array<int, array{clave:string, tipo:string, urgente:bool, titulo:string, detalle:string, minutos:?int, visita:array}>
     */
    public function pendientes(?int $ahora = null): array
    {
        $ahora ??= time();
        $hoy   = date('Y-m-d', $ahora);
        $lista = [];
        $correoActivo = CorreoSmtp::estaConfigurado();
        $listadas     = [];   // una sola notificación por visita

        // 1. Citas de hoy pendientes: seguimiento (ya pasó su hora) o recordatorio del día
        foreach ($this->visitas->citasPendientesDelDia($hoy) as $v) {
            $minutos = (int) floor(($ahora - strtotime($v['fecha_visita'] . ' ' . $v['hora_entrada'])) / 60);
            $hora    = horaDoce($v['hora_entrada']);
            $detalle = $v['visitante'] . ' · ' . ($v['departamentos'] ?: 'Sin departamento') . ' · ' . $v['motivo_visita'];

            if ($minutos >= self::MINUTOS_SEGUIMIENTO) {
                $lista[] = $this->item('seguimiento', $v, $minutos >= self::MINUTOS_ATRASO,
                    "¿Se realizó la cita de las {$hora}?",
                    $detalle . ". Pasaron " . $this->duracion($minutos) . " de la hora pautada y sigue «{$v['estado']}»: "
                        . 'inicie, cancele o reprograme la cita.',
                    $minutos);
            } elseif ($minutos >= 0) {
                $lista[] = $this->item('cita_hoy', $v, false, "Es la hora de la cita de las {$hora}", $detalle, $minutos);
            } else {
                if ($correoActivo && empty($v['aviso_enviado'])) {
                    $detalle .= '. El aviso por correo no se pudo enviar';
                }
                $lista[] = $this->item('cita_hoy', $v, false, "Cita de hoy a las {$hora}", $detalle, $minutos);
            }
            $listadas[(int) $v['id_visita']] = true;
        }

        // 2. Citas próximas sin aviso por correo (solo si el correo está configurado)
        if ($correoActivo) {
            foreach ($this->visitas->citasSinAviso($hoy) as $v) {
                if (isset($listadas[(int) $v['id_visita']])
                    || strtotime($v['fecha_visita'] . ' ' . $v['hora_entrada']) <= $ahora) {
                    continue;   // ya figura entre las citas de hoy, o su hora pasó (importa el seguimiento)
                }
                $lista[] = $this->item('aviso_pendiente', $v, false,
                    'Aviso por correo sin enviar',
                    "Cita de {$v['visitante']} el " . fechaLarga($v['fecha_visita']) . ' a las ' . horaDoce($v['hora_entrada'])
                        . ": el visitante no recibió el aviso. Reenvíelo o verifique su correo ({$v['correo']}).",
                    null);
            }
        }

        // 3. Tareas del día: visitas en curso que falta finalizar
        foreach ($this->visitas->enCursoDelDia($hoy) as $v) {
            $minutos = (int) floor(($ahora - strtotime($v['fecha_visita'] . ' ' . $v['hora_entrada'])) / 60);
            $lista[] = $this->item('en_curso', $v, false,
                'Visita en curso desde las ' . horaDoce($v['hora_entrada']),
                $v['visitante'] . ' · ' . ($v['departamentos'] ?: 'Sin departamento') . '. Finalícela cuando la persona se retire.',
                $minutos);
        }

        // Orden: tipo (seguimiento primero), urgentes antes, luego por hora
        $orden = array_flip(array_keys(self::TIPOS));
        usort($lista, static fn ($a, $b) =>
            [$orden[$a['tipo']], !$a['urgente'], $a['visita']['fecha_visita'], $a['visita']['hora_entrada']]
            <=> [$orden[$b['tipo']], !$b['urgente'], $b['visita']['fecha_visita'], $b['visita']['hora_entrada']]);

        return $lista;
    }

    /**
     * Resumen para la campana y el menú lateral.
     * @return array{alertas:int, urgentes:int, seguimiento:int, tareas:int, total:int}
     */
    public static function resumir(array $pendientes): array
    {
        $resumen = ['alertas' => 0, 'urgentes' => 0, 'seguimiento' => 0, 'tareas' => 0, 'total' => count($pendientes)];
        foreach ($pendientes as $n) {
            if (in_array($n['tipo'], self::TIPOS_ALERTA, true)) {
                $resumen['alertas']++;
            } else {
                $resumen['tareas']++;
            }
            if ($n['tipo'] === 'seguimiento') {
                $resumen['seguimiento']++;
            }
            if ($n['urgente'] || $n['tipo'] === 'seguimiento') {
                $resumen['urgentes']++;
            }
        }
        return $resumen;
    }

    /** Datos mínimos de una notificación para enviarlos como JSON (sin datos personales de más). */
    public static function paraJson(array $n): array
    {
        return [
            'clave'   => $n['clave'],
            'tipo'    => $n['tipo'],
            'urgente' => $n['urgente'],
            'titulo'  => $n['titulo'],
            'detalle' => $n['detalle'],
            'id'      => (int) $n['visita']['id_visita'],
        ];
    }

    private function item(string $tipo, array $visita, bool $urgente, string $titulo, string $detalle, ?int $minutos): array
    {
        return [
            'clave'   => $tipo . '-' . $visita['id_visita'],
            'tipo'    => $tipo,
            'urgente' => $urgente,
            'titulo'  => $titulo,
            'detalle' => $detalle,
            'minutos' => $minutos,
            'visita'  => $visita,
        ];
    }

    /** "45 minutos", "1 hora y 10 minutos"... */
    private function duracion(int $minutos): string
    {
        if ($minutos < 60) {
            return $minutos . ' minutos';
        }
        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;
        return $horas . ($horas === 1 ? ' hora' : ' horas') . ($resto ? " y {$resto} minutos" : '');
    }
}
