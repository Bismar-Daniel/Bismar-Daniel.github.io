USE hall_of_armor;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(120) NOT NULL,
  role ENUM('usuario','mecanico','admin','soporte') NOT NULL DEFAULT 'usuario',
  status ENUM('activo','pendiente','desactivado') NOT NULL DEFAULT 'pendiente',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role_status(role,status)
) ENGINE=InnoDB;

INSERT IGNORE INTO users(email,password_hash,display_name,role,status,created_at)
SELECT email,password_hash,display_name,'admin','activo',created_at
FROM administrators;
