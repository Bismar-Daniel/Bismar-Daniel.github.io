USE hall_of_armor;

-- Persistencia de módulos secundarios del portal. No borra ni reinicia datos.
CREATE TABLE IF NOT EXISTS app_state (
  state_key VARCHAR(80) PRIMARY KEY,
  state_json LONGTEXT NOT NULL,
  updated_by INT UNSIGNED NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_app_state_user FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
