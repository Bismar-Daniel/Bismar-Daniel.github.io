# Modelo de datos

Base: `hall_of_armor`, InnoDB, UTF-8. El SQL versionado es solo estructura y catálogo de rutinas apagadas; no contiene registros reales.

## Entidades relacionales

- `users`: identidad, rol, estado y hash de contraseña.
- `administrators`: tabla histórica de administración.
- `customers` y `vehicles`: propietarios y vehículos asociados.
- `work_orders`: orden, descripción, estado y monto cotizado; referencia cliente y vehículo.
- `automations`: catálogo y estado encendido/apagado de rutinas.
- `cash_movements`: fecha, motivo, ingreso o egreso; el saldo se calcula desde movimientos.
- `chat_messages`: preparada para mensajería persistida.
- `notifications`: tabla de notificaciones disponible.
- `inventory_items`: esquema relacional disponible para inventario.
- `app_state`: estado JSON por módulo para colecciones operativas y preferencias existentes en la interfaz.

## Claves app_state usadas por el cliente

`vehicles`, `inventory`, `notices`, `tickets`, `messages`, `autoLog`, `autoSettings`, `workshop`, `sheetConfig`, `jarvisHistory`, `dailyFlags`, `orderDetails`. No almacenar credenciales de usuario en estos JSON: las cuentas viven en `users` con hashes. El acceso a `sheetConfig` se limita al perfil administrador.

## Roles

- `admin`: acceso global, caja, configuración y gestión de cuentas.
- `mecanico`: órdenes, rutinas y operación técnica.
- `usuario` y `soporte`: alcance limitado en estado compartido.

Revisar el allowlist de API antes de añadir claves nuevas. Una migración debe ser idempotente y no reiniciar datos existentes.
