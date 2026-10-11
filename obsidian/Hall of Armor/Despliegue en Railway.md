# Despliegue en Railway

## Objetivo

Alojar PHP y MySQL en internet para que el taller y su administrador puedan iniciar sesión desde varios dispositivos. El código se conserva en el repositorio de GitHub; Railway ejecuta el sitio dinámico y la base. La dirección de Railway es la que se debe abrir para el sitio conectado.

## Archivos de despliegue

- `Dockerfile`: PHP 8.3 con Apache, PDO MySQL, cURL y mbstring.
- `railway.json`: builder Dockerfile, healthcheck y política de reinicio.
- `deploy/railway-entrypoint.sh`: espera a MySQL, aplica el esquema idempotente y luego inicia Apache en el puerto asignado.
- `api/config.php` local si existe; en la nube el API lee variables de entorno, no un archivo secreto versionado.

## Configuración del proyecto

1. Conectar en Railway el repositorio `Bismar-Daniel/Bismar-Daniel.github.io`, rama `main`.
2. Añadir el servicio MySQL y un volumen persistente. Verificar que el almacenamiento sobreviva a despliegues y revisar backups.
3. En el servicio web añadir estas referencias (cambia `MySQL` por el nombre exacto del servicio): `DB_HOST=${{MySQL.MYSQLHOST}}`, `DB_PORT=${{MySQL.MYSQLPORT}}`, `DB_NAME=${{MySQL.MYSQLDATABASE}}`, `DB_USER=${{MySQL.MYSQLUSER}}` y `DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}`. Mantener `DB_AUTO_MIGRATE=true`.
4. Añadir `GEMINI_API_KEY` como secreto solo si se usará el asistente.
5. Generar dominio HTTPS, esperar a que el healthcheck responda y crear el administrador con `php api/create-admin.php ...` en la Shell del servicio.
6. Abrir el dominio HTTPS de Railway desde cada dispositivo. La URL `github.io` sigue sirviendo una página estática y no es el acceso al sistema con datos.

## Costos y datos

Railway requiere cuenta y el plan Hobby cuesta actualmente US$5 al mes, con créditos de uso incluidos; exceder recursos incluidos puede añadir cargos. Revisar los límites y costos actuales antes de activar servicios. Nunca subir credenciales, dumps MySQL ni datos personales a GitHub.
