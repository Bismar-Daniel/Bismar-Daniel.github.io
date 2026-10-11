# API y sesiones

El API usa `api/index.php?action=...`, JSON y cookies HTTP de sesión con HttpOnly y SameSite=Strict.

## Rutas principales

- `POST register`, `POST login`, `POST logout`, `GET me`.
- `GET/PATCH users`: administración de cuentas.
- `GET/POST/PATCH orders`: órdenes de trabajo.
- `GET/PATCH automations`: estado de rutinas.
- `GET/POST cash-flow`: caja e ingresos/egresos.
- `GET state`, `PUT state`: módulos JSON de `app_state`, filtrados por rol.
- `POST ../api/jarvis.php`: solicitud a Gemini; clave únicamente del lado servidor.

## Comportamiento

PDO y parámetros preparados ejecutan consultas. Las rutas privadas validan en cada solicitud que la cuenta siga activa y el rol autorizado. El almacenamiento del cliente es temporal en memoria; identidad duradera en el navegador solo se representa mediante cookie opaca de sesión generada por PHP.

GitHub Pages no puede llamar a un PHP que no está desplegado. Para la publicación productiva hay que configurar la URL base del API, CORS/orígenes, HTTPS y cookies seguras sin poner claves en JavaScript.
