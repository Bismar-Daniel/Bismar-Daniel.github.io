---
title: Hall of Armor · Mapa del sistema
tags: [hall-of-armor, taller, software]
---

# Hall of Armor

Portal de operación para un solo taller de mecánica especializado en frenos convencionales, híbridos y eléctricos. El diseño evoca un centro tecnológico asistido por J.A.R.V.I.S.

## Mapa del conocimiento

- [[Arquitectura]] · navegador, PHP, MySQL y alojamiento.
- [[Mapa de páginas]] · menú y responsabilidad de cada vista.
- [[Modelo de datos]] · tablas, JSON de estado y relaciones.
- [[API y sesiones]] · rutas, autenticación y permisos.
- [[Automatizaciones]] · rutinas disponibles y límites.
- [[Puesta en marcha con XAMPP]] · instalación y migraciones.
- [[Publicación y límites de GitHub Pages]] · frontend estático y backend.
- [[Integraciones]] · caja, Google Sheets y J.A.R.V.I.S.
- [[Seguridad y mantenimiento]] · secretos, copias y operación segura.

## Fuente del proyecto

La aplicación está en `app-web/`. Escribe en MySQL a través de `app-web/api/`; el navegador solo mantiene datos en memoria mientras está abierta la página y una cookie de sesión HTTP.

> GitHub guarda el esquema y el código, no una base MySQL activa. Nunca pongas aquí contraseñas, claves Gemini, tokens de Apps Script ni dumps con información real.
