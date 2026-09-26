CREATE DATABASE IF NOT EXISTS hall_of_armor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hall_of_armor;

CREATE TABLE IF NOT EXISTS administrators (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(120) NOT NULL DEFAULT 'Jefe de taller',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  phone VARCHAR(40) NULL,
  email VARCHAR(190) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehicles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NOT NULL,
  make_model VARCHAR(160) NOT NULL,
  year SMALLINT UNSIGNED NULL,
  powertrain ENUM('Convencional','Híbrido','Eléctrico') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_vehicle_customer FOREIGN KEY(customer_id) REFERENCES customers(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS work_orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_code VARCHAR(24) NOT NULL UNIQUE,
  customer_id INT UNSIGNED NOT NULL,
  vehicle_id INT UNSIGNED NOT NULL,
  service_description VARCHAR(500) NOT NULL,
  status ENUM('En diagnóstico','En reparación','Esperando repuesto','Listo para entrega','Cerrada') NOT NULL DEFAULT 'En diagnóstico',
  quoted_amount DECIMAL(10,2) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_order_customer FOREIGN KEY(customer_id) REFERENCES customers(id),
  CONSTRAINT fk_order_vehicle FOREIGN KEY(vehicle_id) REFERENCES vehicles(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS automations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  automation_key VARCHAR(80) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  body VARCHAR(500) NOT NULL,
  category VARCHAR(40) NOT NULL DEFAULT 'system',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chat_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sender_name VARCHAR(120) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(80) NOT NULL UNIQUE,
  name VARCHAR(180) NOT NULL,
  compatibility VARCHAR(180) NULL,
  quantity INT NOT NULL DEFAULT 0,
  minimum_quantity INT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cash_movements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  movement_date DATE NOT NULL,
  reason VARCHAR(500) NOT NULL,
  income DECIMAL(12,2) NOT NULL DEFAULT 0,
  expense DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_cash_movement_one_side CHECK ((income > 0 AND expense = 0) OR (expense > 0 AND income = 0))
) ENGINE=InnoDB;

INSERT IGNORE INTO automations (automation_key,name,enabled) VALUES
 ('intake','Recepción inteligente',0),('brake_diagnosis','Diagnóstico de frenos',0),
 ('maintenance_reminders','Recordatorio de mantenimiento',0),('inventory_alerts','Alerta de inventario',0),
 ('daily_report','Informe diario J.A.R.V.I.S.',0),('sheets_sync','Sincronización con hoja',0);

UPDATE automations SET enabled=0;
