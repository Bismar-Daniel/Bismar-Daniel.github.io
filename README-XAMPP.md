# Hall of Armor · instalación local

Portal web para un solo taller de mecánica especializado en frenos convencionales, híbridos y eléctricos. La interfaz vive en `pages/`; el API PHP en `api/` recibe las operaciones y MySQL/MariaDB de XAMPP conserva la información. El navegador mantiene solo la interfaz en memoria y la cookie de sesión HTTP; no se utiliza `localStorage` para datos de la aplicación.

## Estructura

- `pages/`: vistas independientes del taller.
- `partials/app-shell.html`: navegación y contenedor compartidos.
- `css/`, `js/`: estilos y lógica del portal.
- `api/`: autenticación, operaciones MySQL y asistente J.A.R.V.I.S.
- `database/schema.sql`: esquema vacío para instalación nueva.
- `database/migrations/`: actualizaciones no destructivas para una base existente.
- `integrations/`: conectores opcionales.
- `obsidian/Hall of Armor/`: documentación Markdown enlazada para un vault de Obsidian.

## Inicio en XAMPP

1. Copia `app-web` dentro de `C:\xampp\htdocs\hall-of-armor`.
2. Inicia Apache y MySQL desde el panel de XAMPP.
3. Para una base nueva, importa `database/schema.sql` con phpMyAdmin. Para una instalación existente, selecciona `hall_of_armor` y ejecuta solo las migraciones que aún no hayas aplicado, incluida `database/migrations/2026-10-11-app-state.sql`.
4. Copia `api/config.example.php` como `api/config.php` y pon la configuración privada de MySQL. `config.php` está excluido de Git; nunca lo publiques ni incluyas credenciales en los archivos `.sql`.
5. Crea un administrador desde la terminal de XAMPP: `php api\create-admin.php admin@hallofarmor.local "CONTRASENA-LARGA-PROPIA" "Jefe de taller"`.
6. Abre `http://localhost/hall-of-armor/` e inicia sesión. Las escrituras del portal requieren sesión activa y API accesible.

No abras el sitio con doble clic (`file://`): el navegador no ejecuta PHP. Registros creados, cambios de estado, preferencias, mensajes, notificaciones, configuración del conector y el historial de J.A.R.V.I.S. se envían a MySQL; si falla el API, la operación se rechaza y no se crea una copia local falsa.

## Datos y permisos

Órdenes, clientes y vehículos relacionados se guardan en `work_orders`, `customers` y `vehicles`; caja en `cash_movements`; preferencias de rutinas en `automations`; usuarios y hashes de contraseña en `users`. El contenido de las demás secciones (fichas visibles, inventario operativo, notificaciones, tickets, chat, ajustes, registro de rutinas y conversaciones de J.A.R.V.I.S.) se guarda en `app_state` como JSON por módulo. La tabla `inventory_items` queda disponible para una futura migración de inventario a registros relacionales.

Los administradores tienen acceso a la configuración global; las cuentas de mecánico pueden operar el taller; usuario y soporte tienen acceso limitado al estado compartido. No se versionan datos reales de MySQL. `schema.sql` y las migraciones contienen estructura y datos de catálogo vacíos/desactivados, no un volcado de la base del taller.

## Caja y Google Sheets

MySQL es la fuente de verdad de caja. La hoja de cálculo es un destino de sincronización y usa estas columnas: Fecha, Motivo, Ingresos, Egresos y Saldo. Configura su URL, el endpoint de Apps Script y su token desde el portal con una cuenta autorizada; evita guardar secretos en GitHub. Consulta `integrations/google-sheets/README.md` para desplegar el conector.

## GitHub Pages

GitHub Pages solo sirve archivos estáticos; no ejecuta PHP y no puede alcanzar la base MySQL de tu PC. El código estático puede estar en GitHub, pero las funciones que consultan o guardan datos requieren que el API PHP se publique en un hosting con HTTPS y una base accesible, configurando el cliente para apuntar a ese servidor. XAMPP en `localhost` sirve para usarlo en esa PC (y, con red/configuración adecuada, en la red local), no como backend público de la página de GitHub.

## Obsidian

Abre `obsidian/Hall of Armor/` como vault o copia la carpeta dentro de uno existente. Empieza por `Inicio.md`; sus enlaces recorren arquitectura, páginas, API, datos, rutinas, puesta en marcha y publicación. Las notas no incluyen configuración privada.
