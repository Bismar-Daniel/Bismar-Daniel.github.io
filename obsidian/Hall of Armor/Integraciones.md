# Integraciones

## Caja y Google Sheets

La aplicación usa MySQL como registro maestro de ingresos/egresos. La hoja destino debe tener las columnas: Fecha, Motivo, Ingresos, Egresos y Saldo. El Apps Script está en `integrations/google-sheets/Code.gs`; la URL, endpoint de Apps Script y token se configuran por administrador. No versionar el token. Consultar el README de esa carpeta.

## J.A.R.V.I.S.

El navegador llama al endpoint PHP; la clave de Gemini se configura en entorno de servidor o archivo privado local ignorado por Git. El historial de conversaciones se guarda en `app_state.jarvisHistory`. No incluir datos personales innecesarios en las consultas.

## Chat y notificaciones

Los módulos de estado se guardan en MySQL; sincronización en vivo entre navegadores requiere polling o un servicio WebSocket externo. No describirlo como tiempo real si el servidor no ofrece canal instantáneo.
