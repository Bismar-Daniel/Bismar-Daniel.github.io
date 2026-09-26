# Hall of Armor en XAMPP

El portal está organizado por carpetas según su función:

- Páginas: `pages/` — `vista-general.html`, `ordenes-trabajo.html`, `automatizaciones.html`, `centro-control.html`, `vehiculos-clientes.html`, `inventario.html`, `hoja-calculo.html`, `dashboard.html`, `admin.html`, `soporte.html`, `chat.html` y `notificaciones.html`.
- Páginas VIMAX anteriores conservadas: `pages/legacy-vimax/`.
- Estructura compartida: `partials/app-shell.html`.
- Estilos: `css/hall.css`.
- Lógica común: `js/hall.js` y `js/page-shell.js`.
- Servidor y datos: `api/` y `database/schema.sql`.

La página inicial `index.html` abre `pages/vista-general.html`. Al iniciar, las secciones de órdenes, vehículos, inventario, caja, notificaciones, soporte y chat están vacías. Las automatizaciones comienzan apagadas. Sin XAMPP, los registros se guardan solo en el navegador; para una base de datos persistente, sirve la carpeta desde Apache de XAMPP y configura MySQL. No abras las páginas con doble clic.

El hosting de GitHub Pages solo ejecuta los archivos estáticos del sitio; no ejecuta PHP. El API PHP incluido es para el servidor Apache local de XAMPP. En GitHub Pages, utiliza el modo de demostración local del navegador.

## Preparación de la base de datos

1. Copia `app-web` dentro de `C:\xampp\htdocs\` (o crea un enlace/carpeta equivalente dentro de `htdocs`).
2. Inicia Apache y MySQL en el panel de XAMPP.
3. En phpMyAdmin, importa `database/schema.sql`. Esto crea la base `hall_of_armor` y las tablas del sistema.
4. Copia `api/config.example.php` como `api/config.php` y ajusta host, usuario y contraseña de MySQL. `config.php` está bloqueado por `.htaccess`.
5. Desde una consola de XAMPP, crea el administrador. Usa una contraseña larga y propia:

   ```powershell
   cd C:\xampp\htdocs\app-web
   php api\create-admin.php admin@hallofarmor.local "CAMBIA-ESTA-POR-UNA-CONTRASENA-LARGA" "Jefe de taller"
   ```

6. Abre `http://localhost/app-web/` e inicia sesión con esas credenciales.

El API exige sesión para leer y modificar órdenes, automatizaciones y movimientos de caja. La base MySQL empieza sin movimientos de caja; el saldo inicial calculado es Bs 0,00. La hoja de caja usa estas cinco columnas y en este orden: Fecha, Motivo, Ingresos, Egresos, Saldo. Los totales y el saldo acumulado se calculan desde los movimientos guardados. Las órdenes y preferencias de otras secciones conservan opciones locales cuando no está conectado el API.

## Integraciones pendientes

- Google Sheets: configura OAuth/Apps Script en el servidor y limita el acceso a la hoja. La pantalla permite guardar y abrir una URL y exportar movimientos como CSV; el envío automático de filas requiere esas credenciales.
- Chat en tiempo real: el chat es local al navegador; necesita un servicio WebSocket para sincronizar dispositivos.
- Automatizaciones físicas: los interruptores guardan preferencias; conectar sensores, actuadores y J.A.R.V.I.S. real requiere hardware y un servicio local.
- Publicación fuera de localhost: añade HTTPS, protección CSRF, política de contraseñas y permisos de red antes de exponer Apache.
