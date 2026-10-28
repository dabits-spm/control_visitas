# Cambios de la versión 2.0.0 — Refactorización de la arquitectura

Mismo funcionamiento, mismas pantallas y misma base de datos (no hay migración): se reorganizó el
código para que **ningún archivo supere 200 líneas** (antes había clases de hasta 849 líneas).

- **Modelos:** `Visita` (849 líneas), `Usuario` (393) y `Visitante` (336) se dividieron en traits por
  responsabilidad en `app/models/traits/`. Su uso no cambia (`new Visita()`, `Usuario::ROL_...`).
- **Controladores delgados:** `VisitaController` (633) se dividió en `VisitaController` (consultas),
  `VisitaRegistroController`, `VisitaEstadoController` y `VisitaEdicionController`; `ReporteController`
  (509), `UsuarioController` (261) y `AuthController` (218) también se separaron
  (`UsuarioAccesoController`, `CuentaController`). Las URL no cambian.
- **Servicios nuevos** (`app/servicios/`): la lógica de negocio salió de los controladores
  (validación de visitas, registro en transacción, cambio de estado, avisos por correo, reportes y
  exportación).
- **Rutas** en `app/config/rutas.php`. **Helpers** en `vista.php`, `fechas.php` y `entrada.php`.
- **Utilidades:** `CorreoSmtp` (conexión SMTP y mensaje MIME en `utilidades/smtp/`) y `LibroExcel`
  (`HojaExcel` y `PartesLibroExcel`) divididas. Vistas largas divididas en `secciones/`.
- **CSS y JavaScript:** `estilos.css` (2.102 líneas) pasó a 15 hojas por tema y `app.js` (959) a
  8 módulos que comparten el objeto `window.CV`. Se cargan solos y en orden.
- **Dependencias:** cada archivo carga al inicio todas las clases que usa, en lugar de depender de
  que otro archivo las haya cargado antes (comprobado con una revisión automática de todo el proyecto).
- **Nueva herramienta** `herramientas/verificar_estructura.php`: revisa el límite de 200 líneas, que
  cada ruta tenga su controlador y método, que cada include exista y la sintaxis de todo el PHP.
- Se conservan sin cambios: configuración SMTP, selector de hora AM/PM, login con la foto de la sede,
  barra lateral con «Cerrar sesión» al pie y su modal, roles y permisos, y la protección anti-caché.

---

# Cambios de la versión 1.9.1

## Configuración de correo desde el sistema (administrador)
- Nueva opción **Configuración de correo** en la barra lateral (solo administrador): se escriben la
  cuenta de Gmail y la contraseña de aplicación, y el sistema guarda `app/config/correo.php`.
  Incluye los pasos para crear la contraseña de aplicación de Gmail.
- **Correo de prueba** con el motivo del error y la conversación con el servidor si falla.
- Lista de las citas de las próximas 24 horas con el estado de su recordatorio (Enviado / Pendiente /
  Sin correo) y botones para enviar ya los recordatorios y los avisos que quedaron pendientes.
- Los mensajes «no está configurado» ahora dicen exactamente qué falta (cuenta, contraseña,
  remitente o envío desactivado). El Panel de Control y Notificaciones avisan cuando los visitantes
  no están recibiendo correos (con enlace para el administrador).
- La contraseña nunca se muestra; si se deja vacía al guardar, se conserva la anterior.

---

# Cambios de la versión 1.9.0

## Inicio de sesión con la foto de la sede
- El panel izquierdo muestra la foto real de la fachada (`public/assets/img/sede-inces-los-colorados.jpg`)
  con una superposición oscura para la lectura del texto.
- Centrados: «Control de Visitas», «Instituto Nacional de Capacitación y Educación Socialista - INCES»
  y «Sede Los Colorados». Al pie: «Inces Los Colorados, Valencia, Carabobo, Venezuela».

## Correo (SMTP) configurado en un solo archivo
- `app/config/correo.php` reúne host, port, encryption (TLS/SSL), username, password y from
  (dirección y nombre del remitente), además de `habilitado`, `ca_file` y `timeout`.
- Se siguen enviando automáticamente el aviso al crear una cita, el aviso al reprogramarla y el
  recordatorio de 24 horas. Sin usuario o sin contraseña el sistema no intenta enviar.
- Los `correo.php` de versiones anteriores siguen funcionando. `probar_correo.php` muestra la
  configuración leída y avisa si el puerto no corresponde al cifrado.

## Hora en formato de 12 horas (AM/PM)
- Nuevo selector de hora con reloj desplegable (hora, minutos y AM/PM) en el registro, la
  reprogramación y la edición de visitas; también se puede escribir la hora («2:30 PM»).
- El servidor acepta la hora en 12 o 24 horas.

## Sin cambios
- Barra lateral desplegable (Panel de Control, Historial de Visitas, Visitas Programadas, Visitantes,
  Reportes; luego Notificaciones y, para el administrador, Gestión de Usuarios) con «Cerrar sesión»
  al pie y modal de confirmación; roles y permisos; cabeceras anti-caché y bloqueo de Atrás/Adelante.
- Base de datos: la 1.9 no necesita migración.

---

# Cambios de la versión 1.8.0

## Recordatorio por correo 24 horas antes de cada cita
- Las citas programadas o reprogramadas reciben un recordatorio por correo 24 horas antes, con
  fecha, hora, departamento, motivo y ubicación (Inces Los Colorados, Valencia, Carabobo, Venezuela).
- Un solo recordatorio por cita; al reprogramar se envía uno nuevo. Si la cita se registra o
  reprograma con menos de 24 horas de anticipación, el aviso enviado en ese momento cuenta como
  recordatorio. Las canceladas, iniciadas o finalizadas no lo reciben. Si falla, se reintenta.
- Se envían solos mientras alguien tenga el sistema abierto (en segundo plano, cada 5 minutos) y,
  de forma recomendada, con el Programador de tareas de Windows (`herramientas/enviar_recordatorios.php`).
- Todos los correos (aviso, reprogramación y recordatorio) muestran ahora la fila «Ubicación».

## Notificaciones internas (administrador y recepcionista)
- Al iniciar sesión se abre «Pendientes de hoy» con las citas y tareas del día.
- Campana en la barra superior y opción «Notificaciones» en la barra lateral con el número de
  alertas; se actualizan solas cada minuto.
- Seguimiento: 30 minutos después de la hora de una cita sin cambio de estado aparece un
  recordatorio flotante («¿Se realizó la cita…?» · Actualizar estado); a los 60 minutos, «Atrasada».
- Bandeja de notificaciones con selector de estado en cada tarjeta.
- Solo se muestra lo pendiente: las notificaciones se calculan a partir del estado de las visitas,
  así que las finalizadas o canceladas nunca aparecen y cada cita desaparece al atenderse.
  Una sola notificación por visita.

## Base de datos
- Columna `recordatorio_enviado` en `visita` y tabla `proceso_automatico` (evita envíos duplicados).
  Para actualizar desde la 1.7: `database/migracion_07_version_1_8.sql`.

---

# Cambios de la versión 1.7.0

## Recepcionista: Mi perfil de solo lectura
- Se quitó la opción «Cambiar contraseña» del recepcionista (menú y acceso directo por URL).
- Nueva página **Mi perfil** (menú de la cuenta): tarjeta con Nombre, Apellido, Cédula, Teléfono,
  Correo e indicador visual de estado (Activo/Inactivo). Sin campos editables.

## Administrador: Gestión de Usuarios
- Nueva opción en la barra lateral, solo para el administrador.
- Crear recepcionistas (datos y credenciales), editar sus datos, restablecer su contraseña,
  darlos de baja o reactivarlos, y eliminar cuentas que no registraron visitas.
- El recepcionista no tiene acceso a ninguna de estas acciones (comprobado en el servidor).
- Al dar de baja, eliminar o restablecer la contraseña de una cuenta, su sesión abierta se
  cierra en el siguiente clic. El administrador conserva «Cambiar contraseña» en Mi perfil.
- Todas las acciones quedan en la auditoría.

## Base de datos
- Columna `correo` en `usuario` (única). Para actualizar desde la 1.6:
  `database/migracion_06_version_1_7.sql`. Las cuentas existentes quedan sin correo hasta que
  el administrador lo complete desde «Gestión de Usuarios».

---

# Cambios de la versión 1.6.1

- **Inicio de sesión:** el panel izquierdo muestra «Control de Visitas», «Inces Los Colorados», la
  insignia «Centro Formativo INCES» y, al pie, la ubicación «Inces Los Colorados, Valencia,
  Carabobo, Venezuela». Los textos se cambian en `app/config/config.php`.
- **Cerrar sesión** quedó fijo en la parte inferior del menú lateral y abre el modal de
  confirmación. Se quitó del menú de la cuenta (barra superior), que conserva «Cambiar contraseña».
- Se volvieron a verificar los permisos por rol y la protección de los botones Atrás/Adelante.
- Sin cambios en la base de datos: no hace falta ejecutar ninguna migración desde la 1.6.0.

---

# Cambios de la versión 1.6.0

## Seguridad de la sesión
- **Botones Atrás / Adelante:** después de cerrar sesión ya no muestran pantallas internas.
  Las páginas se envían sin caché (`no-store`) y, si el navegador las restaura de memoria, se
  comprueba la sesión con el servidor antes de mostrarlas; sin sesión, se va al login.
- **Cerrar sesión con confirmación:** ventana modal «¿Estás seguro de que deseas cerrar la sesión
  actual?» con «Cancelar» y «Sí, cerrar sesión». El cierre se hace por POST con token CSRF
  (antes era un enlace). Las demás pestañas abiertas también vuelven al login.

## Diseño
- **Barra lateral desplegable** con: Panel de Control, Historial de Visitas, Visitas Programadas,
  Visitantes y Reportes. Botón para contraerla (se recuerda); en celulares se abre encima.
- **Barra superior** con el título de la página y el menú de la cuenta (cambiar contraseña,
  cerrar sesión). Se quitaron los botones sueltos de la barra anterior.
- Ventanas modales para confirmar cambios de estado y eliminaciones (antes eran alertas del
  navegador), reprogramar citas y editar observaciones.

## Roles: Administrador y Recepcionista
- Se eliminó el rol Supervisor (la migración pasa esos usuarios a Recepcionista).
- Recepcionista: registra visitas, corrige datos personales de visitantes, cambia el estado de
  visitas activas y edita observaciones mientras la visita está activa. No edita ni elimina
  visitas ni cambia las finalizadas o canceladas.
- Administrador: además edita, elimina y cambia el estado de cualquier visita, edita
  observaciones en cualquier momento y elimina visitantes sin visitas.

## Visitas
- **Historial de Visitas:** de la más reciente a la más antigua, 20 por página, filtros por
  estado y tipo, búsqueda por cédula, nombre, departamento o teléfono. Las citas pendientes no
  aparecen hasta que ocurren o se cancelan; las que vinieron de una cita llevan la etiqueta
  gris «Cita programada».
- **Visitas Programadas** (nuevo): citas pendientes de la más próxima a la más lejana, con
  iniciar, **reprogramar** (nuevo estado «Reprogramada», con aviso por correo) y cancelar.
- **Observaciones:** se pueden agregar al registrar o después; vacías se muestran «Ninguna».
- **Panel de Control:** columna de observaciones; el selector de estado se bloquea en las
  visitas finalizadas o canceladas (salvo para el administrador).

## Visitantes
- Listado con Cédula, Nombre y Apellido, Teléfono, Correo y Procedencia, con búsqueda rápida.
- **Ficha del visitante** (nuevo): datos, última visita, próxima cita y el historial de todas
  sus visitas con sus observaciones; desde ahí se registra una visita o se corrigen sus datos.

## Base de datos
- Estado nuevo `5 = Reprogramada`; rol 3 (Supervisor) eliminado.
- Para actualizar una base de la versión 1.5: `database/migracion_05_version_1_6.sql`.

---

# Cambios de la versión 1.5.0

## Funciones nuevas
- **Gráficos en el panel de control:** visitas de hoy por departamento (barras por estado) y
  estado de las visitas del día (gráfico circular). Hechos con HTML/CSS/SVG: funcionan sin internet.
- **Cambiar contraseña** (menú de opciones): pide la actual; la nueva debe tener 8 a 72 caracteres,
  letras y números, y no contener el nombre de usuario. Queda en la auditoría.
- **Exportar reportes a Excel (.xlsx) y texto (.txt)**, por periodo y personalizado. El .xlsx se
  genera sin librerías ni la extensión zip de PHP.
- **Aviso por correo** al visitante de cada visita programada (fecha, hora, departamentos, motivo),
  con reenvío desde el listado y herramienta de prueba `herramientas/probar_correo.php`.
- **Cierre automático:** visitas «Programadas» o «En proceso» de días anteriores pasan a «Finalizada».
- **Listados paginados** (25 por página) con búsqueda y filtro en el servidor: visitas y visitantes.

## Correcciones
- La misma cédula escrita con o sin letra (`12345678` / `V-12345678`) ya no crea dos visitantes.
- Visitantes extranjeros: cédula `E-` y teléfonos internacionales (`+57 300 1234567`).
- Al iniciar una cita se guardan la fecha y hora reales de llegada (antes podía quedar con fecha
  futura y hora de salida anterior a la de entrada).
- El recepcionista ya no puede cambiar el nombre de un visitante desde el registro de visitas
  (solo teléfono, correo y procedencia); los cambios se registran en la auditoría.
- Visitante y visita se guardan en una sola transacción: si algo falla no queda nada a medias.
- Un departamento inexistente se rechaza con un mensaje claro.
- Formularios manipulados (campos enviados como lista) ya no provocan errores fatales ni guardan
  la palabra «Array».
- PHP y MariaDB usan la misma zona horaria (fecha de hoy, hora de salida, cierre automático).
- Cambio de estado protegido si dos personas cambian la misma visita a la vez.
- Una visita cerrada no puede editarse con fecha futura; una cita no se programa a más de un año.
- La auditoría muestra los nombres de los estados («Programada → En proceso»).

## Base de datos
- Columnas nuevas en `visita`: `cierre_automatico`, `aviso_enviado`; índice `idx_visita_estado_fecha`.
- Para actualizar una base existente: `database/migracion_04_version_1_5.sql`
  (también normaliza cédulas y teléfonos y une visitantes duplicados).
