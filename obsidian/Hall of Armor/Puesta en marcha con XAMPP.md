# Puesta en marcha con XAMPP

1. Coloca la carpeta en `C:\xampp\htdocs\hall-of-armor`.
2. En el panel XAMPP inicia Apache y MySQL.
3. Base nueva: importa `database/schema.sql` en phpMyAdmin. Base existente: ejecuta una sola vez la migración `database/migrations/2026-10-11-app-state.sql` y cualquier migración pendiente anterior.
4. Copia `api/config.example.php` a `api/config.php`; completa host, base, usuario y contraseña. El archivo privado está ignorado por Git.
5. Crea el usuario administrador con `php api\create-admin.php admin@hallofarmor.local "CONTRASENA-LARGA" "Jefe de taller"`.
6. Abre `http://localhost/hall-of-armor/`, inicia sesión y confirma que Apache y MySQL permanezcan activos.

No abrir por doble clic ni esperar persistencia de cuenta/caja en GitHub Pages. Consulta también [[Modelo de datos]] y [[Seguridad y mantenimiento]].
