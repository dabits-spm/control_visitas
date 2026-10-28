<?php
// app/controllers/traits/BuscaVisita.php

require_once __DIR__ . '/../../models/Visita.php';
require_once __DIR__ . '/../../models/Usuario.php';

/**
 * Comportamiento común de los controladores de visitas: buscar la visita indicada
 * (o volver con un mensaje si no existe) y saber si quien opera es administrador.
 */
trait BuscaVisita
{
    /** Busca una visita por su id; si no existe, vuelve a la página indicada con un mensaje. */
    protected function obtenerVisita(int $id_visita, string $volver): array
    {
        $visita = $id_visita > 0 ? (new Visita())->obtenerDetalle($id_visita) : null;

        if (!$visita) {
            $_SESSION['errores'] = ['La visita indicada no existe.'];
            $this->redirigir($volver);
        }
        return $visita;
    }

    protected function esAdministrador(): bool
    {
        return (int) ($_SESSION['usuario']['id_rol'] ?? 0) === Usuario::ROL_ADMINISTRADOR;
    }
}
