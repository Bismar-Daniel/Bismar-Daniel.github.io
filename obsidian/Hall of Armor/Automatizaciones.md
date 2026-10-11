# Automatizaciones

Todas las rutinas comienzan apagadas en una instalación limpia. Sus interruptores se guardan en la tabla `automations`; sus preferencias/eventos del portal se guardan en MySQL.

- **Recepción inteligente:** prepara los datos recibidos al registrar una orden.
- **Diagnóstico de frenos:** genera una lista guiada según motorización y síntoma.
- **Recordatorio de mantenimiento:** revisa fechas de inspección guardadas.
- **Alerta de inventario:** compara stock frente al mínimo.
- **Informe diario J.A.R.V.I.S.:** genera un resumen al cierre configurado mientras el portal está abierto.
- **Sincronización con hoja:** replica movimientos de caja hacia Google Sheets.

Las rutinas del navegador solo corren mientras el portal está abierto. Para ejecución programada sin navegador se necesita un worker o tarea cron en el servidor. Ninguna rutina detecta sensores reales ni controla actuadores sin hardware/servicio de integración.
