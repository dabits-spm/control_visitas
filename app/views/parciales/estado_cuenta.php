<?php
// Parcial: indicador visual del estado de una cuenta (Activo / Inactivo).
// Variable: $estadoCuenta (1 = activa, 0 = dada de baja)
$activa = (int) $estadoCuenta === 1;
?>
<span class="estado-cuenta <?= $activa ? 'estado-activo' : 'estado-inactivo' ?>">
    <span class="estado-punto" aria-hidden="true"></span><?= $activa ? 'Activo' : 'Inactivo' ?>
</span>
