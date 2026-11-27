-- Title: Crear procedimiento almacenado y trigger de auditoría del sistema
-- Description: Agrega sp_log_system_audit y trg_system_settings_after_update para auditar cambios de configuración.

DROP PROCEDURE IF EXISTS `sp_log_system_audit`;
DELIMITER ;;
CREATE PROCEDURE `sp_log_system_audit`(
  IN `p_event_type` VARCHAR(80),
  IN `p_target_key` VARCHAR(150),
  IN `p_old_value` TEXT,
  IN `p_new_value` TEXT,
  IN `p_user_id` INT
)
BEGIN
  INSERT INTO `system_audit_logs` (`event_type`, `target_key`, `old_value`, `new_value`, `performed_by`)
  VALUES (`p_event_type`, `p_target_key`, `p_old_value`, `p_new_value`, `p_user_id`);
END ;;
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_system_settings_after_update`;
DELIMITER ;;
CREATE TRIGGER `trg_system_settings_after_update`
AFTER UPDATE ON `system_settings`
FOR EACH ROW
BEGIN
  IF NOT (OLD.`setting_value` <=> NEW.`setting_value`) THEN
    INSERT INTO `system_audit_logs` (`event_type`, `target_key`, `old_value`, `new_value`, `performed_by`)
    VALUES ('SYSTEM_SETTING_UPDATED', NEW.`setting_key`, OLD.`setting_value`, NEW.`setting_value`, NEW.`updated_by`);
  END IF;
END ;;
DELIMITER ;
