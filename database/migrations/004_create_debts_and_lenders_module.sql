-- Title: Crear módulo de préstamos recibidos / deudas (lenders, debts, debt_payments)
-- Description: Crea las tablas lenders, debts y debt_payments, la vista v_active_debts, procedimientos almacenados y triggers contables para préstamos recibidos de personas, empresas y entidades financieras.

CREATE TABLE IF NOT EXISTS `lenders` (
  `lender_id` INT(11) NOT NULL AUTO_INCREMENT,
  `lender_type` ENUM('PERSONA','EMPRESA','ENTIDAD_FINANCIERA') NOT NULL DEFAULT 'PERSONA',
  `document_type` ENUM('DNI','RUC','CE','PASAPORTE','OTRO') NOT NULL DEFAULT 'DNI',
  `document_number` VARCHAR(20) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: Disponible, 2: No disponible',
  `created_by` INT(11) NOT NULL,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`lender_id`),
  KEY `idx_lenders_user_del` (`created_by`, `is_deleted`),
  KEY `idx_lenders_doc` (`created_by`, `document_number`),
  KEY `idx_lenders_type` (`created_by`, `lender_type`),
  CONSTRAINT `fk_lenders_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `debts` (
  `debt_id` INT(11) NOT NULL AUTO_INCREMENT,
  `lender_id` INT(11) DEFAULT NULL,
  `lender_name` VARCHAR(150) NOT NULL,
  `lender_type` ENUM('PERSONA','EMPRESA','ENTIDAD_FINANCIERA') NOT NULL DEFAULT 'PERSONA',
  `concept` VARCHAR(255) NOT NULL,
  `account_id` INT(11) NOT NULL,
  `debt_date` DATE NOT NULL,
  `due_date` DATE DEFAULT NULL,
  `principal_amount` DECIMAL(12,2) NOT NULL,
  `interest_type` ENUM('NONE','PERCENTAGE','FIXED') NOT NULL DEFAULT 'NONE',
  `interest_value` DECIMAL(10,2) DEFAULT 0.00,
  `interest_amount` DECIMAL(12,2) DEFAULT 0.00,
  `total_payable` DECIMAL(12,2) NOT NULL,
  `amount_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `balance_pending` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('PENDIENTE','PARCIAL','PAGADO') NOT NULL DEFAULT 'PENDIENTE',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT(11) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`debt_id`),
  KEY `idx_debt_lender` (`lender_name`),
  KEY `idx_debt_status` (`status`),
  KEY `idx_debt_date` (`debt_date`),
  KEY `fk_debts_account` (`account_id`),
  KEY `idx_debts_user_status` (`created_by`, `is_deleted`, `status`),
  KEY `idx_debts_user_date` (`created_by`, `is_deleted`, `debt_date`),
  KEY `fk_debts_lender` (`lender_id`),
  CONSTRAINT `fk_debts_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_debts_lender` FOREIGN KEY (`lender_id`) REFERENCES `lenders` (`lender_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_debts_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `debt_payments` (
  `payment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `debt_id` INT(11) NOT NULL,
  `source_account_id` INT(11) NOT NULL,
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `receipt_image` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT(11) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `idx_debt_pay_debt` (`debt_id`),
  KEY `fk_debt_pay_account` (`source_account_id`),
  KEY `idx_debtpay_user_debt` (`created_by`, `is_deleted`, `debt_id`),
  CONSTRAINT `fk_debt_pay_account` FOREIGN KEY (`source_account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_debt_pay_debt` FOREIGN KEY (`debt_id`) REFERENCES `debts` (`debt_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_debtpay_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE OR REPLACE SQL SECURITY INVOKER VIEW `v_active_debts` AS
SELECT
  d.`debt_id`,
  d.`lender_id`,
  d.`lender_name`,
  d.`lender_type`,
  d.`concept`,
  d.`account_id`,
  a.`account_name`,
  d.`debt_date`,
  d.`due_date`,
  d.`principal_amount`,
  d.`interest_type`,
  d.`interest_value`,
  d.`interest_amount`,
  d.`total_payable`,
  d.`amount_paid`,
  d.`balance_pending`,
  d.`status`,
  d.`notes`,
  d.`created_by`,
  l.`document_type`,
  l.`document_number`
FROM `debts` d
LEFT JOIN `accounts` a ON d.`account_id` = a.`account_id`
LEFT JOIN `lenders` l ON d.`lender_id` = l.`lender_id`
WHERE d.`is_deleted` = 0;

DROP TRIGGER IF EXISTS `trg_debts_before_insert`;
DELIMITER ;;
CREATE TRIGGER `trg_debts_before_insert`
BEFORE INSERT ON `debts`
FOR EACH ROW
BEGIN
  SET NEW.`interest_amount` = GREATEST(0.00, NEW.`total_payable` - NEW.`principal_amount`);
END ;;
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_debts_after_insert`;
DELIMITER ;;
CREATE TRIGGER `trg_debts_after_insert`
AFTER INSERT ON `debts`
FOR EACH ROW
BEGIN
  IF NEW.`is_deleted` = 0 AND NEW.`status` <> 'PAGADO' THEN
    UPDATE `accounts`
    SET `current_balance` = `current_balance` + NEW.`principal_amount`
    WHERE `account_id` = NEW.`account_id` AND `created_by` = NEW.`created_by`;
  END IF;
END ;;
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_debts_before_update`;
DELIMITER ;;
CREATE TRIGGER `trg_debts_before_update`
BEFORE UPDATE ON `debts`
FOR EACH ROW
BEGIN
  SET NEW.`interest_amount` = GREATEST(0.00, NEW.`total_payable` - NEW.`principal_amount`);
END ;;
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_debts_after_update`;
DELIMITER ;;
CREATE TRIGGER `trg_debts_after_update`
AFTER UPDATE ON `debts`
FOR EACH ROW
BEGIN
  IF OLD.`is_deleted` = 0 AND NEW.`is_deleted` = 1 THEN
    IF OLD.`status` <> 'PAGADO' THEN
      UPDATE `accounts`
      SET `current_balance` = `current_balance` - OLD.`principal_amount`
      WHERE `account_id` = OLD.`account_id` AND `created_by` = OLD.`created_by`;
    END IF;
  ELSEIF OLD.`is_deleted` = 1 AND NEW.`is_deleted` = 0 THEN
    IF NEW.`status` <> 'PAGADO' THEN
      UPDATE `accounts`
      SET `current_balance` = `current_balance` + NEW.`principal_amount`
      WHERE `account_id` = NEW.`account_id` AND `created_by` = NEW.`created_by`;
    END IF;
  ELSEIF OLD.`is_deleted` = 0 AND NEW.`is_deleted` = 0 THEN
    IF OLD.`account_id` <> NEW.`account_id` OR OLD.`principal_amount` <> NEW.`principal_amount` THEN
      IF OLD.`status` <> 'PAGADO' THEN
        UPDATE `accounts`
        SET `current_balance` = `current_balance` - OLD.`principal_amount`
        WHERE `account_id` = OLD.`account_id` AND `created_by` = OLD.`created_by`;
      END IF;
      IF NEW.`status` <> 'PAGADO' THEN
        UPDATE `accounts`
        SET `current_balance` = `current_balance` + NEW.`principal_amount`
        WHERE `account_id` = NEW.`account_id` AND `created_by` = NEW.`created_by`;
      END IF;
    END IF;
  END IF;
END ;;
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_debt_payments_after_insert`;
DELIMITER ;;
CREATE TRIGGER `trg_debt_payments_after_insert`
AFTER INSERT ON `debt_payments`
FOR EACH ROW
BEGIN
  DECLARE v_total DECIMAL(12,2) DEFAULT 0.00;
  DECLARE v_paid DECIMAL(12,2) DEFAULT 0.00;
  DECLARE v_pending DECIMAL(12,2) DEFAULT 0.00;
  DECLARE v_status VARCHAR(20) DEFAULT 'PENDIENTE';

  IF NEW.`is_deleted` = 0 THEN
    UPDATE `accounts`
    SET `current_balance` = `current_balance` - NEW.`amount`
    WHERE `account_id` = NEW.`source_account_id` AND `created_by` = NEW.`created_by`;

    SELECT `total_payable`, `amount_paid`
    INTO v_total, v_paid
    FROM `debts`
    WHERE `debt_id` = NEW.`debt_id`;

    SET v_paid    = v_paid + NEW.`amount`;
    SET v_pending = GREATEST(0.00, v_total - v_paid);
    SET v_status  = CASE
                      WHEN v_pending <= 0 THEN 'PAGADO'
                      WHEN v_paid > 0 THEN 'PARCIAL'
                      ELSE 'PENDIENTE'
                    END;

    UPDATE `debts`
    SET `amount_paid` = v_paid,
        `balance_pending` = v_pending,
        `status` = v_status
    WHERE `debt_id` = NEW.`debt_id`;
  END IF;
END ;;
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_debt_payments_after_update`;
DELIMITER ;;
CREATE TRIGGER `trg_debt_payments_after_update`
AFTER UPDATE ON `debt_payments`
FOR EACH ROW
BEGIN
  DECLARE v_total DECIMAL(12,2) DEFAULT 0.00;
  DECLARE v_paid DECIMAL(12,2) DEFAULT 0.00;
  DECLARE v_pending DECIMAL(12,2) DEFAULT 0.00;
  DECLARE v_status VARCHAR(20) DEFAULT 'PENDIENTE';
  DECLARE v_debt_deleted TINYINT(1) DEFAULT 0;

  IF OLD.`is_deleted` = 0 AND NEW.`is_deleted` = 1 THEN
    UPDATE `accounts`
    SET `current_balance` = `current_balance` + OLD.`amount`
    WHERE `account_id` = OLD.`source_account_id` AND `created_by` = OLD.`created_by`;

    SELECT `total_payable`, `amount_paid`, `is_deleted`
    INTO v_total, v_paid, v_debt_deleted
    FROM `debts`
    WHERE `debt_id` = OLD.`debt_id`;

    IF v_debt_deleted = 0 THEN
      SET v_paid    = GREATEST(0.00, v_paid - OLD.`amount`);
      SET v_pending = GREATEST(0.00, v_total - v_paid);
      SET v_status  = CASE
                        WHEN v_pending <= 0 THEN 'PAGADO'
                        WHEN v_paid > 0 THEN 'PARCIAL'
                        ELSE 'PENDIENTE'
                      END;

      UPDATE `debts`
      SET `amount_paid` = v_paid,
          `balance_pending` = v_pending,
          `status` = v_status
      WHERE `debt_id` = OLD.`debt_id`;
    END IF;
  END IF;
END ;;
DELIMITER ;

DROP PROCEDURE IF EXISTS `sp_record_debt_payment`;
DELIMITER ;;
CREATE PROCEDURE `sp_record_debt_payment`(
  IN `p_user_id` INT,
  IN `p_debt_id` INT,
  IN `p_source_account_id` INT,
  IN `p_payment_date` DATE,
  IN `p_amount` DECIMAL(12,2),
  IN `p_receipt_image` VARCHAR(255),
  IN `p_notes` TEXT
)
BEGIN
  DECLARE v_debt_exists INT DEFAULT 0;
  DECLARE v_acc_exists INT DEFAULT 0;
  DECLARE v_new_status VARCHAR(20) DEFAULT '';

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT 0 AS `success`, 'Error interno al registrar el pago en la base de datos.' AS `messages`, '' AS `new_status`;
  END;

  SELECT COUNT(*) INTO v_debt_exists
  FROM `debts`
  WHERE `debt_id` = p_debt_id AND `created_by` = p_user_id AND `is_deleted` = 0;

  SELECT COUNT(*) INTO v_acc_exists
  FROM `accounts`
  WHERE `account_id` = p_source_account_id AND `created_by` = p_user_id AND `is_deleted` = 0;

  IF v_debt_exists = 0 THEN
    SELECT 0 AS `success`, 'Préstamo recibido no encontrado o no pertenece a su usuario.' AS `messages`, '' AS `new_status`;
  ELSEIF v_acc_exists = 0 THEN
    SELECT 0 AS `success`, 'Cuenta origen no válida o no pertenece a su usuario.' AS `messages`, '' AS `new_status`;
  ELSEIF p_amount <= 0 THEN
    SELECT 0 AS `success`, 'El monto del pago debe ser mayor a cero.' AS `messages`, '' AS `new_status`;
  ELSE
    START TRANSACTION;
    INSERT INTO `debt_payments` (`debt_id`, `source_account_id`, `payment_date`, `amount`, `receipt_image`, `notes`, `created_by`)
    VALUES (p_debt_id, p_source_account_id, p_payment_date, p_amount, p_receipt_image, p_notes, p_user_id);

    SELECT `status` INTO v_new_status FROM `debts` WHERE `debt_id` = p_debt_id;
    COMMIT;

    SELECT 1 AS `success`, CONCAT('Pago registrado exitosamente. Estado: ', v_new_status) AS `messages`, v_new_status AS `new_status`;
  END IF;
END ;;
DELIMITER ;

DROP PROCEDURE IF EXISTS `sp_update_debt_status`;
DELIMITER ;;
CREATE PROCEDURE `sp_update_debt_status`(
  IN `p_user_id` INT,
  IN `p_debt_id` INT,
  IN `p_new_status` VARCHAR(20)
)
BEGIN
  DECLARE v_total DECIMAL(12,2);
  DECLARE v_paid DECIMAL(12,2);
  DECLARE v_pending DECIMAL(12,2);
  DECLARE v_cur_status VARCHAR(20);
  DECLARE v_found INT DEFAULT 0;

  SELECT COUNT(*), COALESCE(MAX(`total_payable`), 0), COALESCE(MAX(`amount_paid`), 0),
         COALESCE(MAX(`balance_pending`), 0), COALESCE(MAX(`status`), '')
  INTO v_found, v_total, v_paid, v_pending, v_cur_status
  FROM `debts`
  WHERE `debt_id` = p_debt_id AND `created_by` = p_user_id AND `is_deleted` = 0;

  IF v_found = 0 THEN
    SELECT 0 AS `success`, 'Préstamo recibido no encontrado o ha sido eliminado.' AS `messages`;
  ELSEIF p_new_status NOT IN ('PENDIENTE', 'PARCIAL', 'PAGADO') THEN
    SELECT 0 AS `success`, 'Estado no válido.' AS `messages`;
  ELSE
    IF p_new_status = 'PAGADO' THEN
      SET v_paid    = v_total;
      SET v_pending = 0.00;
    ELSEIF p_new_status = 'PENDIENTE' AND v_cur_status = 'PAGADO' THEN
      SET v_paid    = 0.00;
      SET v_pending = v_total;
    END IF;

    UPDATE `debts`
    SET `status` = p_new_status,
        `amount_paid` = v_paid,
        `balance_pending` = v_pending
    WHERE `debt_id` = p_debt_id AND `created_by` = p_user_id;

    SELECT 1 AS `success`, CONCAT('Estado actualizado exitosamente a ', p_new_status, '.') AS `messages`;
  END IF;
END ;;
DELIMITER ;
