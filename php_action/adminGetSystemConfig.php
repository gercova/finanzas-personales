<?php
/**
 * php_action/adminGetSystemConfig.php
 * Returns environment info, general system settings, migrations, applied changes history,
 * and live database object inventory for the admin user.
 */
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__ . '/MigrationRunner.php';

header('Content-Type: application/json; charset=utf-8');

$runner = new MigrationRunner($connect);

// Check whether system_settings, system_audit_logs, and v_system_overview exist
$hasSettingsTable = false;
$resChk = $connect->query("SHOW TABLES LIKE 'system_settings'");
if ($resChk && $resChk->num_rows > 0) $hasSettingsTable = true;
if ($resChk) $resChk->free();

$hasAuditTable = false;
$resAud = $connect->query("SHOW TABLES LIKE 'system_audit_logs'");
if ($resAud && $resAud->num_rows > 0) $hasAuditTable = true;
if ($resAud) $resAud->free();

$hasOverviewView = false;
$resOv = $connect->query("SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'v_system_overview'");
if ($resOv && $resOv->num_rows > 0) $hasOverviewView = true;
if ($resOv) $resOv->free();

$settings = [
    'app_name'                 => env('APP_NAME', 'myFin'),
    'currency_symbol'          => 'S/',
    'default_monthly_limit'    => '4000.00',
    'session_lifetime_minutes' => (string)max(5, (int)round(((int)env('SESSION_LIFETIME', 1800)) / 60)),
    'allow_user_registration'  => '1',
    'maintenance_mode'         => '0',
];
$settingsMeta = [];

if ($hasSettingsTable) {
    $resSet = $connect->query("SELECT setting_key, setting_value, setting_group, description, updated_at FROM `system_settings` ORDER BY setting_id ASC");
    if ($resSet) {
        while ($r = $resSet->fetch_assoc()) {
            $settings[$r['setting_key']] = (string)($r['setting_value'] ?? '');
            $settingsMeta[] = $r;
        }
        $resSet->free();
    }
}

$overview = null;
if ($hasOverviewView) {
    $resView = $connect->query("SELECT * FROM `v_system_overview` LIMIT 1");
    if ($resView) {
        $overview = $resView->fetch_assoc();
        $resView->free();
    }
}

$auditLogs = [];
if ($hasAuditTable) {
    $resLogs = $connect->query("
        SELECT a.audit_id, a.event_type, a.target_key, a.old_value, a.new_value, a.created_at,
               u.username AS performed_by_username
        FROM `system_audit_logs` a
        LEFT JOIN `users` u ON a.performed_by = u.user_id
        ORDER BY a.audit_id DESC
        LIMIT 25
    ");
    if ($resLogs) {
        while ($r = $resLogs->fetch_assoc()) {
            $auditLogs[] = $r;
        }
        $resLogs->free();
    }
}

$dbName = '';
if ($resDb = $connect->query("SELECT DATABASE() AS db_name")) {
    $dbRow = $resDb->fetch_assoc();
    $dbName = $dbRow['db_name'] ?? '';
    $resDb->free();
}

$inventory  = $runner->getDatabaseInventory();
$migrations = $runner->listAvailableMigrations();
$history    = $runner->getAppliedHistory();

echo json_encode([
    'success'               => true,
    'settings_table_exists' => $hasSettingsTable,
    'settings'              => $settings,
    'settings_meta'         => $settingsMeta,
    'overview'              => $overview,
    'audit_logs'            => $auditLogs,
    'environment'           => [
        'php_version'          => PHP_VERSION,
        'db_version'           => $connect->server_info,
        'db_name'              => $dbName,
        'app_debug'            => (bool)env('APP_DEBUG', false),
        'decolecta_configured' => env('DECOLECTA_API_TOKEN', '') !== '',
        'tables_count'         => count($inventory['tables']),
        'views_count'          => count($inventory['views']),
        'procedures_count'     => count($inventory['procedures']),
        'triggers_count'       => count($inventory['triggers']),
    ],
    'migrations'            => $migrations,
    'history'               => $history,
    'inventory'             => $inventory,
], JSON_UNESCAPED_UNICODE);
