-- Title: Crear tablas de configuración general y auditoría del sistema
-- Description: Crea las tablas system_settings y system_audit_logs e inserta los parámetros iniciales de configuración general.

CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_system_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `system_audit_logs` (
  `audit_id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_type` VARCHAR(80) NOT NULL,
  `target_key` VARCHAR(150) DEFAULT NULL,
  `old_value` TEXT DEFAULT NULL,
  `new_value` TEXT DEFAULT NULL,
  `performed_by` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_audit_event_date` (`event_type`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `setting_group`, `description`) VALUES
  ('app_name', 'myFin', 'general', 'Nombre visible de la aplicación'),
  ('currency_symbol', 'S/', 'finance', 'Símbolo de moneda predeterminado'),
  ('default_monthly_limit', '4000.00', 'finance', 'Cuota de gasto mensual predeterminada para nuevos usuarios'),
  ('session_lifetime_minutes', '30', 'security', 'Tiempo máximo de inactividad de sesión en minutos'),
  ('allow_user_registration', '1', 'security', 'Permitir auto-registro de nuevos usuarios (1=Sí, 0=No)'),
  ('maintenance_mode', '0', 'system', 'Modo mantenimiento restringido para usuarios estándar (1=Sí, 0=No)');
