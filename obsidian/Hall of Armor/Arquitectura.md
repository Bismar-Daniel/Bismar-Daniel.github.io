# Arquitectura

## Componentes

- **Interfaz:** HTML independiente en `pages/`, plantilla común en `partials/app-shell.html`, estilos en `css/hall.css` y lógica de taller en `js/hall.js`.
- **API:** PHP con PDO y cookie de sesión en `api/index.php`; el endpoint de J.A.R.V.I.S. es `api/jarvis.php`.
- **Persistencia:** MySQL/MariaDB llamada `hall_of_armor` en XAMPP. El esquema está en `database/schema.sql` y las actualizaciones idempotentes en `database/migrations/`.
- **Integraciones:** conector opcional de caja con Google Sheets y API Gemini del lado servidor.

## Flujo

1. El navegador solicita una vista independiente.
2. JavaScript consulta `me` para validar la sesión del servidor.
3. Cada lectura y escritura pasa por PHP y PDO hacia MySQL.
4. Si el API falla, la interfaz informa que no se guardó; no existe fallback de datos de aplicación en `localStorage`.

## Límites de despliegue

XAMPP en localhost es un entorno local. GitHub Pages solo sirve el frontend estático y no interpreta PHP ni ofrece MySQL. Para persistencia compartida se preparó Docker/Railway para ejecutar el sitio completo y la base en un servicio HTTPS; revisar [[Despliegue en Railway]] y [[Publicación y límites de GitHub Pages]].
