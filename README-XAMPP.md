# Hall of Armor en XAMPP

El portal está organizado por carpetas según su función:

- Páginas: `pages/` — `vista-general.html`, `ordenes-trabajo.html`, `automatizaciones.html`, `centro-control.html`, `vehiculos-clientes.html`, `inventario.html`, `hoja-calculo.html`, `dashboard.html`, `admin.html`, `soporte.html`, `chat.html` y `notificaciones.html`.
- Páginas VIMAX anteriores conservadas: `pages/legacy-vimax/`.
- Estructura compartida: `partials/app-shell.html`.
- Estilos: `css/hall.css`.
- Lógica común: `js/hall.js` y `js/page-shell.js`.
- Servidor y datos: `api/` y `database/schema.sql`.
- Asistente IA: `pages/jarvis.html`, `css/jarvis.css`, `js/jarvis.js` y el intermediario privado `api/jarvis.php`.

La página inicial `index.html` abre `pages/vista-general.html`. Al iniciar, las secciones de órdenes, vehículos, inventario, caja, notificaciones, soporte y chat están vacías. Las automatizaciones comienzan apagadas. Sin XAMPP, los registros se guardan solo en el navegador; para una base de datos persistente, sirve la carpeta desde Apache de XAMPP y configura MySQL. No abras las páginas con doble clic.

## Automatizaciones del taller

Cada rutina se activa desde `pages/automatizaciones.html` (o el centro de control) y ejecuta acciones reales, con un registro de eventos visible en esa misma página y avisos en Notificaciones:

- **Recepción inteligente**: al crear una orden, registra la solicitud (cliente, vehículo, tren motriz y síntoma) y deja la orden preparada para diagnóstico.
- **Diagnóstico de frenos**: genera una lista de verificación según el tren motriz (convencional, híbrido o eléctrico) y el síntoma reportado. Se abre desde «Diagnóstico» en Órdenes de trabajo; al completar todos los pasos, la orden pasa a «En reparación».
- **Recordatorio de mantenimiento**: programa la próxima inspección de cada vehículo (campo opcional en las fichas o botón «Programar») y avisa al cliente cuando falta una semana o menos, o cuando la fecha ya venció.
- **Alerta de inventario**: notifica cuando un repuesto queda con stock igual o inferior a su mínimo y avisa cuando vuelve a subir.
- **Informe diario J.A.R.V.I.S.**: al llegar a la hora de cierre configurada en Administrador (por defecto 18:00, con el portal abierto), genera el resumen del día: órdenes nuevas por estado e ingresos/egresos cobrados en caja. También se puede generar a mano con «Informe del día».
- **Sincronización con hoja**: con el conector de Google Sheets configurado, envía la caja a la hoja automáticamente al registrar movimientos y cada 10 minutos mientras el portal esté abierto.

El botón «Ejecutar rutinas» fuerza una pasada de recordatorios, alertas e informe pendiente sin esperar al reloj.

El hosting de GitHub Pages solo ejecuta los archivos estáticos del sitio; no ejecuta PHP. Sin una API común, los datos de GitHub Pages se guardan en el almacenamiento del navegador y solo están disponibles en ese mismo dispositivo y navegador. Para compartir y guardar registros entre varios dispositivos, configura MySQL y una API accesible para todos desde un servidor (XAMPP local solo es accesible desde los dispositivos conectados a esa red, salvo que configures acceso remoto).

## Preparación de la base de datos

1. Copia `app-web` dentro de `C:\xampp\htdocs\` (o crea un enlace/carpeta equivalente dentro de `htdocs`).
2. Inicia Apache y MySQL en el panel de XAMPP.
3. En phpMyAdmin, importa `database/schema.sql`. Esto crea la base `hall_of_armor` y las tablas del sistema.
4. Copia `api/config.example.php` como `api/config.php` y ajusta host, usuario y contraseña de MySQL. `config.php` está bloqueado por `.htaccess`.
   Para J.A.R.V.I.S. IA, crea una clave Gemini nueva en Google AI Studio e introdúcela solo en el archivo privado `api/config.php`, en `'gemini_api_key' => 'TU_CLAVE_NUEVA'`. No la pongas en JavaScript, HTML, Git ni `config.example.php`. También puedes configurarla como variable de entorno `GEMINI_API_KEY` para Apache. El chat requiere iniciar sesión como administrador.
5. Desde una consola de XAMPP, crea el administrador. Usa una contraseña larga y propia:

   ```powershell
   cd C:\xampp\htdocs\hall-of-armor
   php api\create-admin.php admin@hallofarmor.local "CAMBIA-ESTA-POR-UNA-CONTRASENA-LARGA" "Jefe de taller"
   ```

6. Abre `http://localhost/app-web/` e inicia sesión con esas credenciales.

## Cuentas y perfiles

El formulario de acceso permite entrar como usuario/cliente, mecánico, administrador o soporte. Los usuarios/ clientes se activan al registrarse; las solicitudes de mecánico y soporte quedan pendientes hasta que un administrador las apruebe desde `Administrador → Cuentas y permisos`. El registro público no permite crear administradores. La contraseña se guarda en MySQL usando `password_hash` y no se conserva en el navegador.

Si el sitio ya estaba instalado antes de agregar las cuentas, aplica una sola vez la migración `database/migrations/2026-10-11-user-roles.sql` desde phpMyAdmin. La migración crea la tabla de cuentas y copia los hashes de los administradores existentes sin borrar registros. Para una instalación nueva, `database/schema.sql` ya incluye las tablas. Crea o actualiza al administrador desde la consola de XAMPP con `php api\create-admin.php correo contraseña-larga [nombre]`.

El inicio de sesión con MySQL requiere abrir el portal mediante Apache, por ejemplo `http://localhost/hall-of-armor/`, no con doble clic ni desde GitHub Pages. GitHub Pages publica HTML, CSS y JavaScript, pero no ejecuta PHP ni puede conectarse a la base local de XAMPP; el acceso real a cuentas está disponible en la copia servida por XAMPP.

El API exige sesión para leer y modificar órdenes, automatizaciones y movimientos de caja. La base MySQL empieza sin movimientos de caja; el saldo inicial calculado es Bs 0,00. La hoja de caja usa estas cinco columnas y en este orden: Fecha, Motivo, Ingresos, Egresos, Saldo. Los totales y el saldo acumulado se calculan desde los movimientos guardados. Las órdenes y preferencias de otras secciones conservan opciones locales cuando no está conectado el API.

## Integraciones pendientes

- Google Sheets: publica `integrations/google-sheets/Code.gs` como aplicación web y configura la URL `/exec` y el token en la pantalla de caja. Sigue `integrations/google-sheets/README.md`. El envío automático solo funciona después de autorizar y desplegar ese conector.
- Chat en tiempo real: el chat es local al navegador; necesita un servicio WebSocket para sincronizar dispositivos.
- J.A.R.V.I.S. IA: requiere PHP en Apache y una clave Gemini privada del lado del servidor. El historial de conversación queda guardado en el navegador actual. GitHub Pages no ejecuta PHP; para usar el modelo desde una publicación web se necesita alojar el endpoint PHP en un servidor con HTTPS.
- Automatizaciones físicas: los interruptores guardan preferencias; conectar sensores, actuadores y J.A.R.V.I.S. real requiere hardware y un servicio local.
- Publicación fuera de localhost: añade HTTPS, protección CSRF, política de contraseñas y permisos de red antes de exponer Apache.
