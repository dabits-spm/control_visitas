# Control de Visitas 2.0 · Inces Los Colorados (PHP nativo · MVC · POO · PDO)

Sin frameworks: PHP puro, CSS puro y JavaScript puro. **Ningún archivo de código supera 200
líneas**: cada clase tiene una sola responsabilidad (ver «Arquitectura» más abajo).

## Estructura

```
control_visitas/
├── public/                       <- único directorio expuesto al navegador
│   ├── index.php                 (controlador frontal: sesión, anti-caché y despacho)
│   ├── .htaccess                 (reescritura de URLs)
│   └── assets/
│       ├── css/01-…15-*.css      (estilos por tema; se cargan en orden)
│       ├── js/01-…08-*.js        (scripts por tema; comparten el objeto global CV)
│       └── img/sede-inces-los-colorados.jpg (foto del inicio de sesión)
├── app/
│   ├── config/       config.php, Database.php, rutas.php (tabla de rutas),
│   │                 correo.php (SMTP), correo.ejemplo.php (plantilla para el repositorio)
│   ├── helpers/      funciones.php -> vista.php, fechas.php, entrada.php
│   ├── controllers/  Controlador.php (base) y un controlador por tarea (ver «Arquitectura»)
│   │   └── traits/   ControlSesion, BuscaVisita, GestionaRecepcionistas
│   ├── servicios/    lógica de negocio que usan los controladores
│   │   └── reportes/ periodos, reporte personalizado y exportación (.xlsx / .txt)
│   ├── models/       Auditoria, Departamento, Notificacion, Usuario, Visita, Visitante
│   │   └── traits/   consultas y escritura de cada modelo, separadas por tema
│   ├── utilidades/   CorreoSmtp (+ smtp/), AvisoVisita, RecordatorioCitas, ConfiguracionCorreo,
│   │                 LibroExcel, HojaExcel, PartesLibroExcel, TablaTexto
│   └── views/        layouts/, parciales/ y una carpeta por módulo (con secciones/ si es larga)
├── herramientas/
│   ├── verificar_estructura.php  (límite de 200 líneas, rutas, includes y sintaxis)
│   ├── probar_correo.php         (prueba la configuración de correo desde la consola)
│   └── enviar_recordatorios.php  (recordatorios de las citas, para el Programador de tareas)
└── database/
    ├── schema.sql                (instalación nueva)
    ├── datos_prueba.sql
    └── migracion_0X_*.sql        (actualizar una base de datos existente)
```

## Arquitectura

**Controladores** (`app/controllers/`): solo reciben la petición, revisan permisos y token CSRF,
llaman a un servicio o modelo y responden (vista, redirección o JSON).

| Controlador | Rutas |
|-------------|-------|
| `AuthController` | login, logout, sesion/estado |
| `CuentaController` | cuenta (Mi perfil), cuenta/clave |
| `DashboardController` | dashboard |
| `VisitaController` | visitas, visitas/programadas, visitas/crear, visitantes/buscar |
| `VisitaRegistroController` | visitas/guardar, visitas/aviso |
| `VisitaEstadoController` | visitas/estado, visitas/observaciones |
| `VisitaEdicionController` | visitas/editar, visitas/actualizar, visitas/eliminar (administrador) |
| `VisitanteController` | visitantes, visitantes/ver, visitantes/editar… |
| `ReporteController` | reportes, reportes/personalizado |
| `UsuarioController` / `UsuarioAccesoController` | Gestión de Usuarios: alta y edición / contraseña, baja y eliminación |
| `NotificacionController` | notificaciones, notificaciones/estado, notificaciones/recordatorios |
| `CorreoController` | correo y sus acciones (administrador) |

**Servicios** (`app/servicios/`): `ValidadorVisita` (reglas de fecha, hora y datos),
`RegistroVisita` (visitante + visita en una transacción), `CambioEstadoVisita`, `AvisoCita`
(aviso por correo), `EnvioCorreos` (prueba y avisos pendientes) y `reportes/` (`PeriodosReporte`,
`ReportePersonalizado`, `Exportador`, `ExportadorPeriodo`, `ExportadorPersonalizado`).

**Modelos** (`app/models/`): cada clase conserva las constantes y reglas de su tabla y reúne
traits por tema. Por ejemplo `Visita` usa `VisitaEscritura`, `VisitaEstados`, `VisitaCitas`,
`VisitaListados` y `VisitaEstadisticas`; el resto del sistema sigue usando `new Visita()`.

**Para mantener el límite:** ejecute `C:\xampp\php\php.exe herramientas\verificar_estructura.php`
después de cada cambio. Si un archivo se acerca a 200 líneas, separe una responsabilidad en un
servicio, un trait o un parcial de vista. Una hoja de estilos o un script nuevo se agrega con el
siguiente número (por ejemplo `16-tema.css`) y se carga solo.

## Rutas

| Método   | Ruta                     | Qué hace                                                        |
|----------|--------------------------|-----------------------------------------------------------------|
| GET      | `dashboard`              | Panel de control: resumen y gráficos del día                    |
| GET      | `visitas`                | Historial de visitas, 20 por página (`?q=`, `?estado=`, `?tipo=`, `?pagina=`) |
| GET      | `visitas/programadas`    | Citas pendientes (programadas y reprogramadas)                  |
| GET      | `visitas/crear`          | Formulario de registro                                          |
| POST     | `visitas/guardar`        | Guarda visitante + visita (y envía el aviso si es programada)   |
| POST     | `visitas/estado`         | Cambia el estado (para reprogramar: `fecha_visita` y `hora_entrada`) |
| POST     | `visitas/observaciones`  | Agrega o corrige las observaciones de una visita                |
| POST     | `visitas/aviso`          | Envía o reenvía el aviso por correo de una visita programada    |
| GET/POST | `visitas/editar`, `visitas/actualizar`, `visitas/eliminar` | Edición y borrado de visitas (administrador) |
| GET      | `visitantes`             | Listado paginado de visitantes (`?q=`, `?pagina=`)              |
| GET      | `visitantes/ver`         | Ficha del visitante con todas sus visitas (`?id=`)              |
| POST     | `visitantes/eliminar`    | Elimina un visitante sin visitas (administrador)                |
| GET      | `visitantes/buscar`      | Busca un visitante por cédula (JSON)                            |
| GET      | `reportes`, `reportes/personalizado` | Reportes; con `&formato=xlsx` o `&formato=txt` se descargan |
| GET      | `cuenta`                 | Mi perfil: tarjeta con los datos de la cuenta (solo lectura)    |
| GET/POST | `cuenta/clave`           | Cambiar la contraseña propia (solo administrador)               |
| GET      | `usuarios`               | Gestión de Usuarios: recepcionistas (`?q=`, `?estado=activos|inactivos`) |
| GET/POST | `usuarios/crear`, `usuarios/guardar` | Crear un recepcionista con sus datos y credenciales |
| GET/POST | `usuarios/editar`, `usuarios/actualizar` | Editar sus datos personales y su nombre de usuario |
| GET/POST | `usuarios/clave`         | Restablecer su contraseña                                       |
| POST     | `usuarios/estado`, `usuarios/eliminar` | Dar de baja / reactivar, o eliminar (si no registró visitas) |
| GET      | `notificaciones`         | Bandeja de citas y tareas pendientes del día                    |
| GET      | `notificaciones/estado`  | Pendientes en JSON (la campana se actualiza cada minuto)        |
| POST     | `notificaciones/recordatorios` | Envía los recordatorios por correo pendientes (CSRF en `X-CSRF-Token`) |
| GET      | `correo`                 | Configuración de correo (administrador): estado, cuenta, prueba y recordatorios |
| POST     | `correo/guardar`, `correo/probar` | Guarda `app/config/correo.php` · envía un correo de prueba |
| POST     | `correo/recordatorios`, `correo/avisos` | Envía ya los recordatorios de 24 h y los avisos pendientes |
| GET/POST | `login`                  | Inicio de sesión                                                |
| POST     | `logout`                 | Cierra la sesión (desde el modal de confirmación, con token CSRF) |
| GET      | `sesion/estado`          | `{"activa": true/false}` (protección de los botones Atrás/Adelante) |

## Reglas del registro de visitantes

Obligatorios: **cédula, nombre, apellido, teléfono y correo** (la procedencia es opcional).
Se validan en el navegador y otra vez en el servidor (`Visitante::validar()`):

- Cédula: **V** (venezolano) o **E** (extranjero) y de 6 a 10 dígitos. Se guarda siempre con
  letra y guion: `28.123.456` → `V-28123456`, `e8123456` → `E-8123456`. Sin letra se toma como V,
  así la misma persona nunca queda registrada dos veces.
- Nombre y apellido: solo letras, 2 a 60 caracteres. Se guardan con mayúscula inicial.
- Teléfono: venezolano (`0414-1234567`, `04141234567`, `+58 414 1234567` → se guarda `0414-1234567`)
  o extranjero con el código de país (`+57 300 1234567` → `+573001234567`).
- Correo: dirección válida, se guarda en minúsculas.

Si la cédula ya existe, el formulario se autocompleta con los datos guardados (no se duplica el
visitante). El **recepcionista** solo puede actualizar teléfono, correo y procedencia; el nombre y
el apellido los corrige un administrador o supervisor. Los cambios quedan en la auditoría.

## Roles y permisos

| Acción                                                        | Recepcionista | Administrador |
|---------------------------------------------------------------|:-------------:|:-------------:|
| Registrar visitas (al momento o programadas)                  | ✔ | ✔ |
| Corregir datos personales de los visitantes                   | ✔ | ✔ |
| Cambiar el estado de visitas programadas, reprogramadas o en proceso | ✔ | ✔ |
| Reprogramar citas                                             | ✔ | ✔ |
| Observaciones mientras la visita está activa                  | ✔ | ✔ |
| Observaciones de visitas finalizadas o canceladas             | ✘ | ✔ |
| Cambiar el estado de visitas finalizadas o canceladas         | ✘ | ✔ |
| Editar o eliminar visitas                                     | ✘ | ✔ |
| Eliminar visitantes (solo si no tienen visitas)               | ✘ | ✔ |
| Ver «Mi perfil» (datos de la propia cuenta, solo lectura)     | ✔ | ✔ |
| Cambiar la propia contraseña                                  | ✘ | ✔ |
| Gestión de Usuarios: crear, editar, restablecer contraseña, dar de baja, reactivar y eliminar recepcionistas | ✘ | ✔ |

Los permisos están en `Usuario::PERMISOS` (`app/models/Usuario.php`) y se comprueban en el
servidor, no solo ocultando botones.

## Estados de una visita

Programada → En proceso, **Reprogramada** o Cancelada · Reprogramada → En proceso, Reprogramada o Cancelada ·
En proceso → Finalizada o Cancelada. Finalizada y Cancelada solo las cambia el administrador
(reabrir, finalizar/cancelar, reprogramar).

- **Reprogramar** abre una ventana para elegir la nueva fecha y hora; se envía un correo con los
  nuevos datos.
- Las citas pendientes están en **Visitas Programadas** y no aparecen en el **Historial** hasta que
  ocurren (o se cancelan). Las que vinieron de una cita llevan la etiqueta gris «Cita programada».
- Las observaciones vacías se muestran como «Ninguna».

- Al **iniciar** una visita programada se guardan la fecha y hora reales de llegada (la cita
  original queda en la auditoría). Al **finalizar** se guarda la hora de salida.
- **Cierre automático:** una visita que sigue «Programada» o «En proceso» de un día anterior pasa a
  «Finalizada» sola (la del 30/10 se cierra a partir del 31/10). Queda marcada como
  «Cierre automático» y se puede editar sin hora de salida. Ocurre al abrir cualquier página del
  sistema, sin programar tareas en el servidor.

## Correo electrónico (SMTP)

El sistema envía correos **automáticamente** en estos casos:

| Evento | Correo |
|--------|--------|
| Se registra una visita programada | Aviso con fecha, hora, departamento(s), motivo y ubicación |
| Se reprograma una cita | Aviso con la nueva fecha y hora |
| Faltan 24 horas para la cita | Recordatorio (ver la sección siguiente) |

Si un envío falla, la visita se guarda igual y se puede reenviar con «Enviar aviso». XAMPP no trae
servidor de correo, así que el sistema se conecta directamente a Gmail (u otro servidor SMTP) con su
propia clase `app/utilidades/CorreoSmtp.php` (TLS/STARTTLS o SSL, sin Composer ni PHPMailer).

### Configurarlo desde el sistema (recomendado)

Inicie sesión como **administrador** y entre a **Configuración de correo** (barra lateral):

1. En la cuenta de Gmail que enviará los correos, active la verificación en dos pasos y cree una
   **contraseña de aplicación** en https://myaccount.google.com/apppasswords (la pantalla explica
   los pasos).
2. Escriba la cuenta y la contraseña de aplicación y pulse **Guardar configuración**.
3. Pulse **Enviar correo de prueba**. Si falla, la pantalla dice el motivo y muestra la
   conversación con el servidor (sin la contraseña).
4. Con **Enviar ahora los recordatorios pendientes** y **Enviar los avisos pendientes** salen de
   inmediato los correos de las citas que se registraron mientras el correo no estaba configurado.

Mientras falte algo, el Panel de Control y Notificaciones muestran el aviso «Los visitantes no están
recibiendo correos» con lo que falta exactamente.

### El archivo de configuración

**Toda la configuración está en un solo archivo: `app/config/correo.php`** (la pantalla anterior
lo escribe por usted).

| Parámetro | Qué es | Gmail |
|-----------|--------|-------|
| `host` | Servidor SMTP | `smtp.gmail.com` |
| `port` | Puerto | `587` con TLS · `465` con SSL |
| `encryption` | Cifrado: `'tls'`, `'ssl'` o `''` (sin cifrado) | `'tls'` |
| `username` | Cuenta que envía | `su_cuenta@gmail.com` |
| `password` | Contraseña de la cuenta | **contraseña de aplicación** (16 letras) |
| `from` | Remitente: `address` (vacío = `username`) y `name` | nombre que verá el visitante |
| `habilitado` | `false` desactiva todos los envíos sin borrar los datos | `true` |

Si prefiere editarlo a mano: escriba `username`, `password` y, si quiere, `from`.
Mientras `username` esté vacío el sistema funciona normalmente, solo que sin enviar correos.
También se puede probar desde la consola, en la carpeta del proyecto:
   `C:\xampp\php\php.exe herramientas\probar_correo.php su_correo@gmail.com`
   Muestra la configuración leída y, si falla, el motivo y la conversación con el servidor.

`correo.php` guarda una contraseña: está en `.gitignore` para que no se suba a un repositorio
(`correo.ejemplo.php` es la misma plantilla, sin datos). Un `correo.php` de versiones anteriores
(con `define('CORREO_HOST', ...)`) sigue funcionando.

## Recordatorio por correo 24 horas antes

Cada cita pendiente (Programada o Reprogramada) recibe un **recordatorio por correo 24 horas antes**
con la fecha, la hora, el departamento, el motivo y la ubicación (`INSTITUCION_UBICACION`).

- Cada cita recibe **un solo** recordatorio. Si se reprograma, recibe uno nuevo para la nueva fecha.
- Si la cita se registra o reprograma con menos de 24 horas de anticipación, el aviso que se envía
  en ese momento ya cuenta como recordatorio (no llegan dos correos seguidos).
- Las citas canceladas, finalizadas o iniciadas no reciben recordatorio.
- Si el envío falla, la cita queda pendiente y se intenta en la siguiente revisión.
- Cada envío queda en la auditoría (`RECORDATORIO`).

**¿Quién los envía?** XAMPP no tiene tareas programadas propias, así que hay dos formas:

1. **Sola, mientras alguien tenga el sistema abierto:** el navegador lo pide en segundo plano cada
   5 minutos (sin hacer esperar al usuario). La base de datos evita que dos pestañas o dos usuarios
   envíen el mismo recordatorio.
2. **Con el Programador de tareas de Windows (recomendado)**, para que salgan aunque nadie esté
   conectado (por ejemplo, de noche o en fin de semana). En una consola como administrador:

   ```
   schtasks /create /sc minute /mo 15 /tn "Control de Visitas - Recordatorios" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\control_visitas\herramientas\enviar_recordatorios.php"
   ```

   MySQL de XAMPP debe estar encendido. Para probarla a mano:
   `C:\xampp\php\php.exe herramientas\enviar_recordatorios.php`

## Notificaciones (administrador y recepcionista)

- **Al iniciar sesión** se abre la ventana «Pendientes de hoy» con las citas y tareas del día.
  Si no hay ninguna, aparece un aviso breve «No hay citas ni tareas pendientes para hoy».
- **Campana** en la barra superior y opción **Notificaciones** en la barra lateral, con el número
  de alertas (en rojo cuando hay citas sin actualizar). Se actualiza sola cada minuto.
- **Seguimiento por falta de cambio de estado:** si pasan **30 minutos** de la hora de una cita y
  sigue «Programada» o «Reprogramada», aparece un recordatorio flotante preguntando si la cita se
  realizó, con el enlace «Actualizar estado». A partir de los **60 minutos** se marca «Atrasada».
- La bandeja agrupa: citas sin actualizar, citas de hoy, avisos por correo sin enviar y visitas en
  curso por finalizar. Cada tarjeta tiene el selector de estado para atenderla ahí mismo.
- **Solo lo pendiente:** las notificaciones no se guardan en una tabla; se calculan en cada
  consulta a partir del estado de las visitas (`app/models/Notificacion.php`). Las visitas
  finalizadas o canceladas nunca aparecen, y una cita desaparece de la bandeja en cuanto se
  inicia, finaliza, cancela o reprograma para otro día.
- Las visitas en curso se muestran como tareas, pero no suman al número rojo de la campana.
- Los tiempos (30 y 60 minutos) se cambian en `Notificacion::MINUTOS_SEGUIMIENTO` y
  `Notificacion::MINUTOS_ATRASO`.

## Reportes

- **Por día, semana o mes** y **personalizado** (filtros y columnas a elección).
- Se descargan en **Excel (.xlsx)** —con filtros y encabezado fijo— o en **texto (.txt)** con tablas
  alineadas para el Bloc de notas, además de «Imprimir / PDF». Cada descarga queda en la auditoría.

## Gestión de Usuarios (administrador)

- Opción **Gestión de Usuarios** en la barra lateral, visible solo para el administrador.
- **Crear** recepcionistas con nombre, apellido, cédula, teléfono, correo, nombre de usuario y
  contraseña. Cédula, correo y usuario no se pueden repetir.
- **Editar** sus datos personales y su nombre de usuario; **restablecer** su contraseña.
- **Dar de baja / reactivar**: la cuenta dada de baja no puede iniciar sesión y conserva su historial.
  **Eliminar** solo es posible si el recepcionista no registró visitas.
- Si el administrador da de baja, elimina o cambia la contraseña de una cuenta, la sesión abierta de
  ese recepcionista se cierra en su siguiente clic y el login explica el motivo.
- El recepcionista solo ve **Mi perfil** (sus datos y el indicador Activo/Inactivo): no puede
  cambiar su contraseña ni sus datos.

## Seguridad de la sesión

- Ninguna página interna se guarda en la caché del navegador (`Cache-Control: no-store`, en
  `public/index.php`). Después de cerrar sesión, los botones **Atrás** y **Adelante** llevan al login.
- Si el navegador muestra una página desde su memoria («bfcache»), `01-sesion-barra.js` la oculta y pregunta al
  servidor (`sesion/estado`) si la sesión sigue activa; si no, va al login.
- **Cerrar sesión** está fijo al pie del menú lateral. Pide confirmación en una ventana modal y se
  hace por POST con token CSRF. Las demás pestañas abiertas también vuelven al login.

## Inicio de sesión y datos de la institución

El panel izquierdo del inicio de sesión muestra la foto de la sede con una capa oscura para leer el
texto, y centrados: «Control de Visitas», el nombre de la institución y «Sede Los Colorados»; al pie,
la ubicación. Todo se cambia en `app/config/config.php`: `INSTITUCION_NOMBRE_COMPLETO`,
`INSTITUCION_SEDE`, `INSTITUCION_UBICACION`, `INSTITUCION_FOTO_LOGIN` (ruta de la foto dentro de
`public/`), además de `INSTITUCION_NOMBRE` e `INSTITUCION_DIRECCION` (reportes y correos).

## Selector de hora (12 horas)

Los campos de hora (registrar o reprogramar una cita y editar una visita) usan formato de 12 horas
con AM/PM. El botón del reloj (o la flecha abajo) abre un panel para elegir hora, minutos y AM/PM con
clic, o se puede escribir directamente: `2:30 PM`, `2:30p`, `9 am`, `14:30`… Al salir del campo se
muestra completa («02:30 PM»). La fecha sigue con el calendario del navegador. El formulario envía la
hora en 24 horas y el servidor también acepta el formato con AM/PM.

## Puesta en marcha (XAMPP / Laragon)

1. Copiar la carpeta `control_visitas` dentro de `htdocs` (XAMPP) o `www` (Laragon).
2. En phpMyAdmin importar, **en este orden**: `database/schema.sql` y `database/datos_prueba.sql`.
   - **¿Ya tenía una versión anterior instalada?** No importe `schema.sql` (borra la base de datos):
     haga una copia (Exportar) y ejecute las migraciones que le falten, en orden:
     desde la 1.4 → `migracion_04_version_1_5.sql`, `migracion_05_version_1_6.sql`,
     `migracion_06_version_1_7.sql` y `migracion_07_version_1_8.sql`;
     desde la 1.5 → `migracion_05_version_1_6.sql`, `migracion_06_version_1_7.sql` y `migracion_07_version_1_8.sql`;
     desde la 1.6 → `migracion_06_version_1_7.sql` y `migracion_07_version_1_8.sql`;
     desde la 1.7 → solo `migracion_07_version_1_8.sql`;
     desde la 1.8 → no hace falta ninguna (la 1.9 no cambia la base de datos).
3. Revisar `DB_USER` y `DB_PASS` en `app/config/config.php`.
   `BASE_URL` se calcula sola según la carpeta donde esté el proyecto.
4. Abrir `http://localhost/control_visitas/public`
   - Usuarios de prueba: `admin` (Administrador) y `recepcion` (Recepcionista)
   - Contraseña: `Admin12345` (solo de prueba; cámbiela desde el menú de la cuenta).

## Si algo no carga

- **404 en todas las rutas menos la raíz**: Apache no está aplicando el `.htaccess`.
  Active `mod_rewrite` y ponga `AllowOverride All` para esa carpeta. Mientras tanto
  puede probar `.../public/index.php?url=login`.
- **"No se pudo conectar a la base de datos"**: revise `DB_*` en `config.php` y que
  `schema.sql` se haya importado.
- **Los estilos no se ven**: confirme que existen las hojas `public/assets/css/01-…15-*.css`.
- **El correo no sale**: ejecute `herramientas/probar_correo.php` (ver «Aviso por correo»).
  - «rechazó el usuario o la contraseña»: use una contraseña de aplicación de Gmail.
  - «certificate verify failed»: defina `CORREO_CA_ARCHIVO` en `correo.php`
    (en XAMPP suele ser `C:/xampp/apache/bin/curl-ca-bundle.crt`).
  - «No se pudo conectar»: revise la conexión a internet o si la red bloquea el puerto 587.
