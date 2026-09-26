# Conectar la caja de Hall of Armor a Google Sheets

GitHub Pages no ejecuta PHP ni puede escribir en una hoja de Google solo con su enlace. Este conector usa un Apps Script desplegado desde la cuenta propietaria de la hoja. La hoja queda fijada en el script y el conector exige un token compartido antes de aceptar escrituras.

## 1. Preparar el Apps Script

1. Abre la hoja correcta en Google Sheets.
2. Ve a **Extensiones → Apps Script**.
3. Reemplaza el contenido de `Code.gs` con el archivo [`Code.gs`](Code.gs) de esta carpeta.
4. Copia el ID de la hoja desde su URL: el texto entre `/spreadsheets/d/` y `/edit`.
5. En las primeras dos constantes de `Code.gs`, pega ese ID en `SPREADSHEET_ID` y reemplaza `SHARED_TOKEN` por un texto aleatorio largo. Conserva ese token privado; no lo publiques en GitHub.
6. Guarda el proyecto. En **Implementar → Nueva implementación**, elige **Aplicación web**, ejecuta como tu cuenta y habilita acceso **Cualquier persona**. Google solicitará autorizar el acceso a la hoja.
7. Implementa y copia la URL de aplicación web que termina en `/exec`.

El acceso público de la aplicación web está limitado por el token y el ID fijo de la hoja dentro del script. No cambies el ID por un valor recibido en una petición. Si tu cuenta no permite el acceso **Cualquier persona**, este conector no podrá recibir escrituras desde GitHub Pages.

## 2. Configurar el sitio

En **Hoja de cálculo**, pega y guarda:

- La URL normal de Google Sheets.
- La URL de aplicación web de Apps Script terminada en `/exec`.
- El mismo token configurado en `SHARED_TOKEN`.

Pulsa **Guardar y sincronizar**. El conector verifica que la fila 1 esté vacía o ya tenga exactamente `Fecha`, `Motivo`, `Ingresos`, `Egresos`, `Saldo`. Si la hoja tiene otros encabezados, se detiene sin sobrescribirlos. Los movimientos se agregan o actualizan usando una nota interna en la celda de saldo, sin agregar una sexta columna. Si la implementación se actualiza en Apps Script, usa **Implementar → Administrar implementaciones → Editar** para publicar la versión nueva.

Después de conectarlo, cada movimiento de caja vuelve a sincronizarse automáticamente. **Sincronizar ahora** permite reintentar y ponerse al día con los movimientos guardados en el navegador o MySQL.

El enlace y la URL se guardan en el navegador; el token queda en el almacenamiento local de ese navegador. Para usar otro dispositivo, configura allí la URL y el mismo token. Los datos almacenados únicamente en un navegador no se envían hasta que sincronices o registres un movimiento después de conectar.
