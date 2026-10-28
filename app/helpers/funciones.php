<?php
// app/helpers/funciones.php
// Funciones auxiliares disponibles en todo el sistema (controladores y vistas),
// separadas por tema:

require_once __DIR__ . '/vista.php';     // e(), url(), recurso(), puede(), paginacion()...
require_once __DIR__ . '/fechas.php';    // fechaLarga(), horaDoce(), normalizarHora()...
require_once __DIR__ . '/entrada.php';   // entradaTexto(), entradaLista(), datosFormulario()
