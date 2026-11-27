-- Title: Crear vista global de resumen del sistema (v_system_overview)
-- Description: Crea o reemplaza la vista SQL v_system_overview con métricas consolidadas para administración.

CREATE OR REPLACE SQL SECURITY INVOKER VIEW `v_system_overview` AS
SELECT
  (SELECT COUNT(*) FROM `users` WHERE `is_active` = 1) AS `active_users`,
  (SELECT COUNT(*) FROM `accounts` WHERE `is_deleted` = 0 AND `status` = 1) AS `active_accounts`,
  (SELECT COUNT(*) FROM `incomes` WHERE `is_deleted` = 0) AS `total_incomes_count`,
  (SELECT COUNT(*) FROM `expenses` WHERE `is_deleted` = 0) AS `total_expenses_count`,
  (SELECT COUNT(*) FROM `loans` WHERE `is_deleted` = 0) AS `total_loans_count`,
  (SELECT COUNT(*) FROM `borrowers` WHERE `is_deleted` = 0) AS `total_borrowers_count`,
  (SELECT COUNT(*) FROM `projects` WHERE `is_deleted` = 0) AS `total_projects_count`,
  (SELECT COUNT(*) FROM `assets` WHERE `is_deleted` = 0) AS `total_assets_count`;
