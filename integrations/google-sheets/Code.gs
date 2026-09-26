// Hall of Armor Google Sheets connector. Keep the target spreadsheet fixed here.
const SPREADSHEET_ID = 'PEGA_AQUI_EL_ID_DE_TU_HOJA';
const SHARED_TOKEN = 'CAMBIA_POR_UN_TOKEN_ALEATORIO_LARGO';
const HEADERS = ['Fecha', 'Motivo', 'Ingresos', 'Egresos', 'Saldo'];

function doPost(e) {
  let requestId = '';
  try {
    const payload = JSON.parse((e && e.parameter && e.parameter.payload) || '{}');
    requestId = String(payload.requestId || '');
    if (SPREADSHEET_ID.indexOf('PEGA_AQUI_') === 0 || SHARED_TOKEN.indexOf('CAMBIA_') === 0) {
      throw new Error('Completa SPREADSHEET_ID y SHARED_TOKEN en Code.gs antes de desplegar.');
    }
    if (!payload.token || payload.token !== SHARED_TOKEN) throw new Error('Token incorrecto.');
    if (!Array.isArray(payload.movements)) throw new Error('No se recibió la lista de movimientos.');

    const spreadsheet = SpreadsheetApp.openById(SPREADSHEET_ID);
    const sheet = spreadsheet.getSheets()[0];
    if (!sheet) throw new Error('La hoja no tiene una pestaña disponible.');
    const lastRow = sheet.getLastRow();
    if (lastRow === 0) {
      sheet.getRange(1, 1, 1, HEADERS.length).setValues([HEADERS]);
    } else {
      const actualHeaders = sheet.getRange(1, 1, 1, HEADERS.length).getDisplayValues()[0];
      if (HEADERS.some((header, i) => actualHeaders[i] !== header)) {
        throw new Error('La primera fila debe contener, en orden: Fecha, Motivo, Ingresos, Egresos, Saldo. No se modificó la hoja.');
      }
    }

    const endRow = sheet.getLastRow();
    const existing = new Map();
    if (endRow > 1) {
      const notes = sheet.getRange(2, 5, endRow - 1, 1).getNotes();
      notes.forEach((row, i) => {
        const match = /^hoa-id:(.+)$/.exec(row[0] || '');
        if (match) existing.set(match[1], i + 2);
      });
    }

    const movements = payload.movements;
    let synced = 0;
    movements.forEach((movement) => {
      const id = String(movement.id || '');
      const date = String(movement.date || '');
      const reason = String(movement.reason || '').trim();
      const income = Number(movement.income || 0);
      const expense = Number(movement.expense || 0);
      const balance = Number(movement.balance);
      if (!id || !/^\d{4}-\d{2}-\d{2}$/.test(date) || !reason || reason.length > 500) throw new Error('Movimiento inválido: requiere ID, fecha y motivo.');
      if (!Number.isFinite(income) || !Number.isFinite(expense) || !Number.isFinite(balance) || income < 0 || expense < 0 || ((income > 0) === (expense > 0))) throw new Error('Importe inválido en el movimiento ' + id + '.');
      const row = [date, reason, income, expense, balance];
      let rowNumber = existing.get(id);
      if (!rowNumber) {
        rowNumber = sheet.getLastRow() + 1;
        existing.set(id, rowNumber);
      }
      sheet.getRange(rowNumber, 1, 1, 5).setValues([row]);
      sheet.getRange(rowNumber, 5).setNote('hoa-id:' + id);
      synced++;
    });

    return resultPage({ requestId: requestId, ok: true, synced: synced });
  } catch (error) {
    return resultPage({ requestId: requestId, ok: false, error: String(error && error.message || error) });
  }
}

function resultPage(result) {
  const message = JSON.stringify(result).replace(/</g, '\\u003c');
  return HtmlService.createHtmlOutput('<!doctype html><meta charset="utf-8"><script>parent.postMessage(' + message + ', "*");</script>')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
}
