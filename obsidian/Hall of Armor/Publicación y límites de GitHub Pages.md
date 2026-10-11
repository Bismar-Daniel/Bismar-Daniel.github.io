# Publicación y límites de GitHub Pages

El repositorio público puede conservar el frontend, la estructura SQL, migraciones vacías y documentación. No subir `api/config.php`, claves Gemini, token de Sheets, contraseñas ni dumps con clientes, movimientos u órdenes reales.

GitHub Pages publica HTML/CSS/JS estático. No ejecuta el API PHP ni administra MySQL. La página pública no puede persistir en el XAMPP de la computadora del propietario. Para que múltiples visitantes compartan datos:

1. Desplegar PHP API en un host compatible con PHP con HTTPS.
2. Crear una base MySQL privada en ese host e importar el esquema y migraciones.
3. Configurar secretos y credenciales en entorno privado del host.
4. Establecer URL del API y política de origen/cookies; usar sesión segura y protección CSRF adecuada.
5. Revisar usuarios, permisos, backups y exposición de datos antes de conectar la publicación.

Una base local `.sql` versionada sirve como plano de instalación, no como base de datos activa alojada en GitHub.
