# Seguridad y mantenimiento

- Mantener `api/config.php`, credenciales, tokens y claves fuera de GitHub y de Obsidian compartido.
- Eliminar claves que se hayan publicado anteriormente y rotarlas. Configurarlas solo en XAMPP privado o en el gestor de secretos del host.
- Crear administradores desde el servidor; aprobar perfiles de mecánico/soporte desde el panel de administración.
- Realizar copias MySQL protegidas y probar su restauración en local. No versionar copias con datos de clientes.
- Antes de actualizar la base, revisar y aplicar migraciones idempotentes. No volver a importar el esquema inicial sobre datos de trabajo sin respaldo.
- En operación pública usar HTTPS, cookies seguras, validación de origen/CSRF y controles de acceso para cada acción.
- Separar secretos de ejecución del código público y limitar privilegios del usuario de base de datos.
- GitHub Pages no ofrece servidor PHP; ver [[Publicación y límites de GitHub Pages]].
