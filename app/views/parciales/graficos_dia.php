<?php
// Parcial: gráficos del panel de control (sin librerías: HTML, CSS y SVG).
// Variables: $porDepartamento (filas de Visita::delDiaPorDepartamento), $porEstado [id_estado => cantidad],
//            $nombresEstado [id_estado => nombre]
$segmentos = [
    'programadas'   => Visita::ESTADO_PROGRAMADA,
    'reprogramadas' => Visita::ESTADO_REPROGRAMADA,
    'en_proceso'    => Visita::ESTADO_EN_PROCESO,
    'finalizadas' => Visita::ESTADO_FINALIZADA,
    'canceladas'  => Visita::ESTADO_CANCELADA,
];
$maximoDepto = max(array_column($porDepartamento, 'total') ?: [0]);
$totalDia    = array_sum($porEstado);
$deptosConVisitas = count(array_filter($porDepartamento, static fn ($d) => $d['total'] > 0));
?>
<section class="graficos-dia" aria-label="Gráficos de las visitas de hoy">
    <article class="panel grafico-panel">
        <div class="panel-cabecera">
            <h2>
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"/><path d="M7 8h8M7 12h12M7 16h5"/></svg>
                Visitas de hoy por departamento
            </h2>
            <span class="contador"><?= $deptosConVisitas ?> de <?= count($porDepartamento) ?> con visitas</span>
        </div>

        <ul class="grafico-leyenda" aria-hidden="true">
            <?php foreach ($segmentos as $clave => $idEstado): ?>
                <li><span class="muestra muestra-<?= e(Visita::COLORES_ESTADO[$idEstado]) ?>"></span><?= e($nombresEstado[$idEstado] ?? '') ?></li>
            <?php endforeach; ?>
        </ul>

        <ul class="grafico-barras">
            <?php foreach ($porDepartamento as $d): ?>
                <?php
                $partes = [];
                foreach ($segmentos as $clave => $idEstado) {
                    if ($d[$clave] > 0) {
                        $partes[] = $d[$clave] . ' ' . mb_strtolower($nombresEstado[$idEstado] ?? '', 'UTF-8');
                    }
                }
                $descripcion = $d['nombre_departamento'] . ': ' . $d['total'] . ($d['total'] === 1 ? ' visita' : ' visitas')
                             . ($partes ? ' (' . implode(', ', $partes) . ')' : '');
                ?>
                <li class="grafico-fila<?= $d['total'] === 0 ? ' grafico-fila-vacia' : '' ?>">
                    <span class="grafico-etiqueta"><?= e($d['nombre_departamento']) ?></span>
                    <span class="grafico-pista" role="img" aria-label="<?= e($descripcion) ?>" title="<?= e($descripcion) ?>">
                        <?php if ($maximoDepto > 0): ?>
                            <?php foreach ($segmentos as $clave => $idEstado): ?>
                                <?php if ($d[$clave] > 0): ?>
                                    <span class="grafico-segmento muestra-<?= e(Visita::COLORES_ESTADO[$idEstado]) ?>"
                                          style="width: <?= round($d[$clave] / $maximoDepto * 100, 2) ?>%"></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </span>
                    <span class="grafico-total"><?= (int) $d['total'] ?></span>
                </li>
            <?php endforeach; ?>
        </ul>

        <p class="nota-reporte">
            <?= $totalDia === 0
                ? 'Todavía no hay visitas registradas hoy.'
                : 'Una visita dirigida a varios departamentos se cuenta en cada uno de ellos.' ?>
        </p>
    </article>

    <article class="panel grafico-panel">
        <div class="panel-cabecera">
            <h2>
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12A9 9 0 1 1 12 3v9z"/><path d="M15 3.5A9 9 0 0 1 20.5 9H15z"/></svg>
                Estado de las visitas de hoy
            </h2>
        </div>

        <div class="grafico-dona-envoltura">
            <?php
            // Cada porción es un círculo con trazo discontinuo; la circunferencia mide 100,
            // así el largo de cada trazo es directamente su porcentaje.
            $acumulado = 0;
            $descripcionDona = [];
            foreach ($porEstado as $idEstado => $cantidad) {
                $descripcionDona[] = ($nombresEstado[$idEstado] ?? '') . ': ' . $cantidad;
            }
            ?>
            <svg class="grafico-dona" viewBox="0 0 42 42" role="img"
                 aria-label="<?= e('Visitas de hoy por estado. ' . implode(', ', $descripcionDona)) ?>">
                <circle class="dona-fondo" cx="21" cy="21" r="15.9155" />
                <?php foreach ($porEstado as $idEstado => $cantidad): ?>
                    <?php if ($totalDia > 0 && $cantidad > 0): $porcentaje = $cantidad / $totalDia * 100; ?>
                        <circle class="dona-porcion trazo-<?= e(Visita::COLORES_ESTADO[$idEstado]) ?>" cx="21" cy="21" r="15.9155"
                                stroke-dasharray="<?= round($porcentaje, 3) ?> <?= round(100 - $porcentaje, 3) ?>"
                                stroke-dashoffset="<?= round(25 - $acumulado, 3) ?>">
                            <title><?= e(($nombresEstado[$idEstado] ?? '') . ': ' . $cantidad) ?></title>
                        </circle>
                        <?php $acumulado += $porcentaje; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                <text x="21" y="21" class="dona-numero"><?= (int) $totalDia ?></text>
                <text x="21" y="26.5" class="dona-texto"><?= $totalDia === 1 ? 'visita' : 'visitas' ?></text>
            </svg>

            <ul class="dona-leyenda">
                <?php foreach ($porEstado as $idEstado => $cantidad): ?>
                    <li>
                        <span class="muestra muestra-<?= e(Visita::COLORES_ESTADO[$idEstado]) ?>"></span>
                        <span><?= e($nombresEstado[$idEstado] ?? '') ?></span>
                        <strong><?= (int) $cantidad ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </article>
</section>
