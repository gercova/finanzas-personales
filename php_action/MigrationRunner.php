<?php
/**
 * php_action/MigrationRunner.php
 * Safe, DELIMITER-aware SQL migration parser, executor, object inspector, and change logger
 * for MySQL / MariaDB.
 */
class MigrationRunner {
    
    private mysqli $db;
    private string $migrationsDir;

    public function __construct(mysqli $db, ?string $migrationsDir = null) {
        $this->db            = $db;
        $this->migrationsDir = $migrationsDir ?? (__DIR__ . '/../database/migrations');
        if (!is_dir($this->migrationsDir)) {
            @mkdir($this->migrationsDir, 0775, true);
        }
        $this->ensureTrackingTable();
    }

    /**
     * Ensure schema_migrations tracking table exists.
     */
    private function ensureTrackingTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS `schema_migrations` (
            `migration_id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration_key` VARCHAR(190) NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `filename` VARCHAR(255) NOT NULL,
            `category` VARCHAR(50) NOT NULL DEFAULT 'MIXED',
            `checksum` VARCHAR(64) NOT NULL,
            `status` ENUM('APPLIED','FAILED','PENDING') NOT NULL DEFAULT 'APPLIED',
            `statements_executed` INT NOT NULL DEFAULT 0,
            `execution_ms` INT NOT NULL DEFAULT 0,
            `changes_applied` LONGTEXT DEFAULT NULL,
            `sql_content` LONGTEXT DEFAULT NULL,
            `error_message` TEXT DEFAULT NULL,
            `executed_by` INT DEFAULT NULL,
            `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_schema_migration_key` (`migration_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql);
        @$this->db->query("ALTER TABLE `schema_migrations` MODIFY COLUMN `status` ENUM('APPLIED','FAILED','PENDING') NOT NULL DEFAULT 'APPLIED'");
        @$this->db->query("ALTER TABLE `schema_migrations` ADD COLUMN IF NOT EXISTS `sql_content` LONGTEXT DEFAULT NULL AFTER `changes_applied`");
    }

    /**
     * Discover SQL migration files under database/migrations/.
     */
    public function listAvailableMigrations(): array {
        $files = glob($this->migrationsDir . '/*.sql') ?: [];
        sort($files, SORT_NATURAL);

        $appliedMap = $this->getAppliedMap();
        $list       = [];

        foreach ($files as $path) {
            $base     = basename($path);
            $key      = pathinfo($base, PATHINFO_FILENAME);
            $sql      = (string)file_get_contents($path);
            $checksum = hash('sha256', $sql);
            $meta     = $this->parseHeaderMetadata($sql, $key);
            $stmts    = $this->splitSqlStatements($sql);
            $objects  = [];
            foreach ($stmts as $s) {
                $desc = $this->describeStatement($s);
                if (!in_array($desc['object_type'], $objects, true) && $desc['object_type'] !== 'SESSION') {
                    $objects[] = $desc['object_type'];
                }
            }

            $applied = $appliedMap[$key] ?? null;
            $status  = 'PENDING';
            if ($applied) {
                if ($applied['status'] === 'FAILED') {
                    $status = 'FAILED';
                } elseif ($applied['status'] === 'PENDING') {
                    $status = 'PENDING';
                } elseif ($applied['checksum'] !== $checksum) {
                    $status = 'MODIFIED';
                } else {
                    $status = 'APPLIED';
                }
            }

            $list[] = [
                'migration_key'   => $key,
                'filename'        => 'database/migrations/' . $base,
                'title'           => $meta['title'],
                'description'     => $meta['description'],
                'object_types'    => $objects,
                'statement_count' => count($stmts),
                'checksum'        => $checksum,
                'status'          => $status,
                'applied_at'      => $status === 'PENDING' ? null : ($applied['executed_at'] ?? null),
                'execution_ms'    => $status === 'PENDING' ? null : ($applied ? (int)$applied['execution_ms'] : null),
                'changes_applied' => $applied && !empty($applied['changes_applied'])
                    ? (json_decode($applied['changes_applied'], true) ?: [])
                    : [],
                'error_message'   => $applied['error_message'] ?? null,
                'sql'             => $sql,
            ];
            unset($appliedMap[$key]);
        }

        // Include DB-integrated migrations if directory was read-only to web server user
        foreach ($appliedMap as $key => $applied) {
            if (empty($applied['sql_content'])) continue;
            $sql      = (string)$applied['sql_content'];
            $meta     = $this->parseHeaderMetadata($sql, $key);
            $stmts    = $this->splitSqlStatements($sql);
            $objects  = [];
            foreach ($stmts as $s) {
                $desc = $this->describeStatement($s);
                if (!in_array($desc['object_type'], $objects, true) && $desc['object_type'] !== 'SESSION') {
                    $objects[] = $desc['object_type'];
                }
            }
            $list[] = [
                'migration_key'   => $key,
                'filename'        => $applied['filename'] ?: 'db://' . $key,
                'title'           => $applied['title'] ?: $meta['title'],
                'description'     => $meta['description'],
                'object_types'    => $objects,
                'statement_count' => count($stmts),
                'checksum'        => $applied['checksum'],
                'status'          => $applied['status'],
                'applied_at'      => $applied['status'] === 'PENDING' ? null : $applied['executed_at'],
                'execution_ms'    => $applied['status'] === 'PENDING' ? null : (int)$applied['execution_ms'],
                'changes_applied' => !empty($applied['changes_applied']) ? (json_decode($applied['changes_applied'], true) ?: []) : [],
                'error_message'   => $applied['error_message'] ?? null,
                'sql'             => $sql,
            ];
        }

        return $list;
    }

    /**
     * Fetch full chronological history of applied/attempted migrations and changes.
     */
    public function getAppliedHistory(): array
    {
        $sql = "SELECT m.*, u.username AS executed_by_username
                FROM `schema_migrations` m
                LEFT JOIN `users` u ON m.executed_by = u.user_id
                WHERE m.status IN ('APPLIED', 'FAILED')
                ORDER BY m.executed_at DESC, m.migration_id DESC";
        $res = $this->db->query($sql);
        if (!$res) return [];

        $history = [];
        while ($row = $res->fetch_assoc()) {
            $history[] = [
                'migration_id'         => (int)$row['migration_id'],
                'migration_key'        => $row['migration_key'],
                'title'                => $row['title'],
                'filename'             => $row['filename'],
                'category'             => $row['category'],
                'status'               => $row['status'],
                'statements_executed'  => (int)$row['statements_executed'],
                'execution_ms'         => (int)$row['execution_ms'],
                'changes_applied'      => json_decode((string)$row['changes_applied'], true) ?: [],
                'error_message'        => $row['error_message'],
                'executed_by_username' => $row['executed_by_username'] ?: 'admin',
                'executed_at'          => $row['executed_at'],
            ];
        }
        $res->free();
        return $history;
    }

    /**
     * Execute a single migration by key.
     */
    public function runMigrationByKey(string $key, int $userId, bool $force = false): array
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-.]/', '', $key);
        $path    = $this->migrationsDir . '/' . $safeKey . '.sql';
        $applied = $this->getAppliedMap()[$safeKey] ?? null;

        if (file_exists($path)) {
            $sql = (string)file_get_contents($path);
        } elseif ($applied && !empty($applied['sql_content'])) {
            $sql = (string)$applied['sql_content'];
        } else {
            return ['success' => false, 'messages' => "Migración '{$safeKey}' no encontrada."];
        }

        $checksum = hash('sha256', $sql);

        if ($applied && $applied['status'] === 'APPLIED' && $applied['checksum'] === $checksum && !$force) {
            return [
                'success'  => true,
                'skipped'  => true,
                'messages' => "La migración '{$safeKey}' ya se encuentra aplicada y sin cambios.",
                'changes'  => json_decode((string)$applied['changes_applied'], true) ?: [],
            ];
        }

        $meta   = $this->parseHeaderMetadata($sql, $safeKey);
        $stmts  = $this->splitSqlStatements($sql);
        $tStart = microtime(true);

        $changes  = [];
        $execCnt  = 0;
        $errMsg   = null;
        $cats     = [];

        foreach ($stmts as $stmtSql) {
            $desc = $this->describeStatement($stmtSql);
            if ($desc['object_type'] === 'USE_DB') {
                continue; // Always execute against active connected database
            }
            if (!in_array($desc['object_type'], $cats, true) && $desc['object_type'] !== 'SESSION') {
                $cats[] = $desc['object_type'];
            }

            $ok = $this->db->query($stmtSql);
            // Drain any extra result sets if a CALL statement was executed
            while ($this->db->more_results() && $this->db->next_result()) {
                $extra = $this->db->store_result();
                if ($extra instanceof mysqli_result) $extra->free();
            }

            if (!$ok) {
                $errMsg = "Error en sentencia ({$desc['summary']}): " . $this->db->error;
                $changes[] = [
                    'action'      => $desc['action'],
                    'object_type' => $desc['object_type'],
                    'object_name' => $desc['object_name'],
                    'summary'     => $desc['summary'],
                    'status'      => 'FAILED',
                    'error'       => $this->db->error,
                ];
                break;
            }

            $execCnt++;
            $changes[] = [
                'action'        => $desc['action'],
                'object_type'   => $desc['object_type'],
                'object_name'   => $desc['object_name'],
                'summary'       => $desc['summary'],
                'affected_rows' => max(0, (int)$this->db->affected_rows),
                'status'        => 'APPLIED',
            ];
        }

        $elapsedMs   = (int)round((microtime(true) - $tStart) * 1000);
        $status      = $errMsg === null ? 'APPLIED' : 'FAILED';
        $category    = count($cats) === 1 ? $cats[0] : (empty($cats) ? 'SQL' : 'MIXED');
        $filenameRel = file_exists($path) ? ('database/migrations/' . $safeKey . '.sql') : ($applied['filename'] ?? ('db://' . $safeKey));
        $changesJson = json_encode($changes, JSON_UNESCAPED_UNICODE);

        $upsert = $this->db->prepare("
            INSERT INTO `schema_migrations`
                (`migration_key`, `title`, `filename`, `category`, `checksum`, `status`,
                 `statements_executed`, `execution_ms`, `changes_applied`, `sql_content`, `error_message`, `executed_by`, `executed_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                `title`               = VALUES(`title`),
                `filename`            = VALUES(`filename`),
                `category`            = VALUES(`category`),
                `checksum`            = VALUES(`checksum`),
                `status`              = VALUES(`status`),
                `statements_executed` = VALUES(`statements_executed`),
                `execution_ms`        = VALUES(`execution_ms`),
                `changes_applied`     = VALUES(`changes_applied`),
                `sql_content`         = COALESCE(VALUES(`sql_content`), `sql_content`),
                `error_message`       = VALUES(`error_message`),
                `executed_by`         = VALUES(`executed_by`),
                `executed_at`         = NOW()
        ");
        if ($upsert) {
            $upsert->bind_param(
                "ssssssiisssi",
                $safeKey, $meta['title'], $filenameRel, $category, $checksum, $status,
                $execCnt, $elapsedMs, $changesJson, $sql, $errMsg, $userId
            );
            $upsert->execute();
            $upsert->close();
        }

        if ($errMsg !== null) {
            return [
                'success'  => false,
                'messages' => $errMsg,
                'changes'  => $changes,
            ];
        }

        return [
            'success'      => true,
            'messages'     => "Migración '{$meta['title']}' ejecutada correctamente ({$execCnt} sentencias en {$elapsedMs} ms).",
            'changes'      => $changes,
            'execution_ms' => $elapsedMs,
        ];
    }

    /**
     * Execute all pending or modified migrations in natural order.
     */
    public function runAllPending(int $userId): array
    {
        $available = $this->listAvailableMigrations();
        $executed  = [];
        $allChanges = [];

        foreach ($available as $m) {
            if (in_array($m['status'], ['PENDING', 'MODIFIED', 'FAILED'], true)) {
                $res = $this->runMigrationByKey($m['migration_key'], $userId, true);
                $executed[] = [
                    'migration_key' => $m['migration_key'],
                    'title'         => $m['title'],
                    'success'       => $res['success'],
                    'messages'      => $res['messages'],
                    'changes'       => $res['changes'] ?? [],
                ];
                foreach (($res['changes'] ?? []) as $ch) {
                    $allChanges[] = array_merge($ch, ['migration' => $m['migration_key']]);
                }
                if (!$res['success']) {
                    return [
                        'success'  => false,
                        'messages' => "Se detuvo en '{$m['migration_key']}': " . $res['messages'],
                        'executed' => $executed,
                        'changes'  => $allChanges,
                    ];
                }
            }
        }

        if (empty($executed)) {
            return [
                'success'  => true,
                'messages' => 'No hay migraciones pendientes por ejecutar. El esquema está actualizado.',
                'executed' => [],
                'changes'  => [],
            ];
        }

        return [
            'success'  => true,
            'messages' => count($executed) . ' migración(es) ejecutada(s) correctamente.',
            'executed' => $executed,
            'changes'  => $allChanges,
        ];
    }

    /**
     * Integrate a new migration SQL script into database/migrations/, optionally executing immediately.
     */
    public function integrateMigration(string $title, string $description, string $sql, int $userId, bool $executeNow = true): array {
        $title = trim($title);
        $sql   = trim($sql);
        if ($title === '' || $sql === '') {
            return ['success' => false, 'messages' => 'El título y el contenido SQL de la migración son obligatorios.'];
        }

        $stmts = $this->splitSqlStatements($sql);
        if (empty($stmts)) {
            return ['success' => false, 'messages' => 'El script SQL no contiene sentencias ejecutables válidas.'];
        }

        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title) ?: $title), '_'));
        if ($slug === '') $slug = 'custom_migration';
        $slug = substr($slug, 0, 60);

        $prefix = date('Y_m_d_His');
        $key    = "{$prefix}_{$slug}";
        $path   = $this->migrationsDir . '/' . $key . '.sql';

        $header = "-- Title: {$title}\n";
        if (trim($description) !== '') {
            $cleanDesc = str_replace(["\r", "\n"], ' ', trim($description));
            $header .= "-- Description: {$cleanDesc}\n";
        }
        $header .= "-- Integrated-At: " . date('Y-m-d H:i:s') . "\n\n";

        $fullContent = preg_match('/^\s*--\s*Title:/im', $sql) ? $sql . "\n" : $header . $sql . "\n";

        $savedOnDisk = @file_put_contents($path, $fullContent) !== false;
        $storageRef  = $savedOnDisk ? ('database/migrations/' . $key . '.sql') : ('db://' . $key);
        $checksum    = hash('sha256', $fullContent);

        // Always persist into schema_migrations so integration works even when filesystem mount is read-only to Apache
        $ins = $this->db->prepare("
            INSERT INTO `schema_migrations`
                (`migration_key`, `title`, `filename`, `category`, `checksum`, `status`, `sql_content`, `executed_by`, `executed_at`)
            VALUES (?, ?, ?, 'CUSTOM', ?, 'PENDING', ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                `title`       = VALUES(`title`),
                `filename`    = VALUES(`filename`),
                `checksum`    = VALUES(`checksum`),
                `sql_content` = VALUES(`sql_content`)
        ");
        if ($ins) {
            $ins->bind_param("sssssi", $key, $title, $storageRef, $checksum, $fullContent, $userId);
            $ins->execute();
            $ins->close();
        }

        if ($executeNow) {
            $execRes = $this->runMigrationByKey($key, $userId, true);
            $execRes['migration_key'] = $key;
            $execRes['filename']      = $storageRef;
            return $execRes;
        }

        return [
            'success'       => true,
            'migration_key' => $key,
            'filename'      => $storageRef,
            'messages'      => "Migración integrada ({$storageRef}) como pendiente de ejecución.",
            'changes'       => [],
        ];
    }

    /**
     * Inspect live database objects (tables, views, procedures, triggers) from information_schema.
     */
    public function getDatabaseInventory(): array
    {
        $tables     = [];
        $views      = [];
        $procedures = [];
        $triggers   = [];

        $resT = $this->db->query("
            SELECT TABLE_NAME, TABLE_TYPE, ENGINE, TABLE_ROWS, CREATE_TIME, UPDATE_TIME
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            ORDER BY TABLE_TYPE ASC, TABLE_NAME ASC
        ");
        if ($resT) {
            while ($r = $resT->fetch_assoc()) {
                $item = [
                    'name'        => $r['TABLE_NAME'],
                    'type'        => $r['TABLE_TYPE'],
                    'engine'      => $r['ENGINE'] ?: '—',
                    'rows'        => (int)($r['TABLE_ROWS'] ?? 0),
                    'created_at'  => $r['CREATE_TIME'] ?: '—',
                    'updated_at'  => $r['UPDATE_TIME'] ?: '—',
                ];
                if ($r['TABLE_TYPE'] === 'VIEW') {
                    $views[] = $item;
                } else {
                    $tables[] = $item;
                }
            }
            $resT->free();
        }

        $resP = $this->db->query("
            SELECT ROUTINE_NAME, ROUTINE_TYPE, CREATED, LAST_ALTERED
            FROM information_schema.ROUTINES
            WHERE ROUTINE_SCHEMA = DATABASE()
            ORDER BY ROUTINE_NAME ASC
        ");
        if ($resP) {
            while ($r = $resP->fetch_assoc()) {
                $procedures[] = [
                    'name'       => $r['ROUTINE_NAME'],
                    'type'       => $r['ROUTINE_TYPE'],
                    'created_at' => $r['CREATED'] ?: '—',
                    'updated_at' => $r['LAST_ALTERED'] ?: '—',
                ];
            }
            $resP->free();
        }

        $resTr = $this->db->query("
            SELECT TRIGGER_NAME, EVENT_OBJECT_TABLE, ACTION_TIMING, EVENT_MANIPULATION, CREATED
            FROM information_schema.TRIGGERS
            WHERE TRIGGER_SCHEMA = DATABASE()
            ORDER BY EVENT_OBJECT_TABLE ASC, TRIGGER_NAME ASC
        ");
        if ($resTr) {
            while ($r = $resTr->fetch_assoc()) {
                $triggers[] = [
                    'name'       => $r['TRIGGER_NAME'],
                    'table'      => $r['EVENT_OBJECT_TABLE'],
                    'timing'     => $r['ACTION_TIMING'] . ' ' . $r['EVENT_MANIPULATION'],
                    'created_at' => $r['CREATED'] ?: '—',
                ];
            }
            $resTr->free();
        }

        return [
            'tables'     => $tables,
            'views'      => $views,
            'procedures' => $procedures,
            'triggers'   => $triggers,
        ];
    }

    private function getAppliedMap(): array {
        $map = [];
        $res = $this->db->query("SELECT * FROM `schema_migrations`");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $map[$row['migration_key']] = $row;
            }
            $res->free();
        }
        return $map;
    }

    private function parseHeaderMetadata(string $sql, string $fallbackKey): array {
        $title = $fallbackKey;
        $desc  = '';
        if (preg_match('/^\s*--\s*Title:\s*(.+)$/im', $sql, $m)) {
            $title = trim($m[1]);
        }
        if (preg_match('/^\s*--\s*Description:\s*(.+)$/im', $sql, $m)) {
            $desc = trim($m[1]);
        }
        return ['title' => $title, 'description' => $desc];
    }

    /**
     * DELIMITER-aware SQL statement splitter supporting triggers, procedures, views, tables,
     * single/double/backtick quotes, and comments.
     */
    public function splitSqlStatements(string $sql): array {
        $lines      = preg_split("/\r\n|\n|\r/", $sql);
        $delimiter  = ';';
        $buffer     = '';
        $statements = [];

        $inSingle   = false;
        $inDouble   = false;
        $inBacktick = false;
        $inBlockCmt = false;

        foreach ($lines as $line) {
            $trimmed = ltrim($line);
            if (!$inSingle && !$inDouble && !$inBacktick && !$inBlockCmt) {
                if (preg_match('/^DELIMITER\s+(\S+)\s*$/i', $trimmed, $dm)) {
                    $newDelim = $dm[1];
                    if (trim($buffer) !== '') {
                        $statements[] = trim($buffer);
                        $buffer = '';
                    }
                    $delimiter = $newDelim;
                    continue;
                }
                if ($trimmed === '' || strpos($trimmed, '--') === 0 || strpos($trimmed, '#') === 0) {
                    continue;
                }
            }

            $len = strlen($line);
            $dLen = strlen($delimiter);

            for ($i = 0; $i < $len; $i++) {
                $ch   = $line[$i];
                $next = $i + 1 < $len ? $line[$i + 1] : '';

                if ($inBlockCmt) {
                    $buffer .= $ch;
                    if ($ch === '*' && $next === '/') {
                        $buffer .= '/';
                        $i++;
                        $inBlockCmt = false;
                    }
                    continue;
                }

                if (!$inSingle && !$inDouble && !$inBacktick) {
                    if ($ch === '/' && $next === '*') {
                        // Preserve /*!50003 ... */ conditional comments cleanly as SQL or skip standard /* ... */
                        if ($i + 2 < $len && $line[$i + 2] === '!') {
                            $buffer .= $ch;
                            continue;
                        }
                        $inBlockCmt = true;
                        $buffer .= '/*';
                        $i++;
                        continue;
                    }
                    if ($ch === '-' && $next === '-' && ($i + 2 >= $len || ctype_space($line[$i + 2]))) {
                        break; // ignore trailing -- comment
                    }
                    if ($ch === '#') {
                        break; // ignore trailing # comment
                    }
                }

                if ($ch === '\\' && ($inSingle || $inDouble)) {
                    $buffer .= $ch;
                    if ($i + 1 < $len) {
                        $buffer .= $line[$i + 1];
                        $i++;
                    }
                    continue;
                }

                if ($ch === "'" && !$inDouble && !$inBacktick) {
                    $inSingle = !$inSingle;
                    $buffer .= $ch;
                    continue;
                }
                if ($ch === '"' && !$inSingle && !$inBacktick) {
                    $inDouble = !$inDouble;
                    $buffer .= $ch;
                    continue;
                }
                if ($ch === '`' && !$inSingle && !$inDouble) {
                    $inBacktick = !$inBacktick;
                    $buffer .= $ch;
                    continue;
                }

                if (!$inSingle && !$inDouble && !$inBacktick && substr($line, $i, $dLen) === $delimiter) {
                    $stmt = trim($buffer);
                    if ($stmt !== '') {
                        $statements[] = $stmt;
                    }
                    $buffer = '';
                    $i += $dLen - 1;
                    continue;
                }

                $buffer .= $ch;
            }
            $buffer .= "\n";
        }

        $tail = trim($buffer);
        if ($tail !== '') {
            $statements[] = $tail;
        }

        return array_values(array_filter($statements, fn($s) => trim($s) !== ''));
    }

    /**
     * Classify a SQL statement to produce human-readable object change metadata.
     */
    private function describeStatement(string $sql): array {
        $clean = preg_replace('/\/\*![0-9]+\s*(.*?)\*\//s', '$1', trim($sql));
        $clean = preg_replace('/\s+/', ' ', trim($clean));

        if (preg_match('/^USE\s+`?([a-zA-Z0-9_]+)`?/i', $clean, $m)) {
            return ['action' => 'USE', 'object_type' => 'USE_DB', 'object_name' => $m[1], 'summary' => "USE {$m[1]}"];
        }
        if (preg_match('/^CREATE\s+(?:TEMPORARY\s+)?TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s+`?([a-zA-Z0-9_]+)`?/i', $clean, $m)) {
            return ['action' => 'CREATE', 'object_type' => 'TABLE', 'object_name' => $m[1], 'summary' => "CREATE TABLE {$m[1]}"];
        }
        if (preg_match('/^ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?/i', $clean, $m)) {
            return ['action' => 'ALTER', 'object_type' => 'TABLE', 'object_name' => $m[1], 'summary' => "ALTER TABLE {$m[1]}"];
        }
        if (preg_match('/^DROP\s+TABLE(?:\s+IF\s+EXISTS)?\s+`?([a-zA-Z0-9_]+)`?/i', $clean, $m)) {
            return ['action' => 'DROP', 'object_type' => 'TABLE', 'object_name' => $m[1], 'summary' => "DROP TABLE {$m[1]}"];
        }
        if (preg_match('/^CREATE(?:\s+OR\s+REPLACE)?(?:\s+ALGORITHM=\S+)?(?:\s+DEFINER=\S+)?(?:\s+SQL\s+SECURITY\s+\S+)?\s+VIEW\s+`?([a-zA-Z0-9_]+)`?/i', $clean, $m)) {
            return ['action' => 'CREATE/REPLACE', 'object_type' => 'VIEW', 'object_name' => $m[1], 'summary' => "CREATE OR REPLACE VIEW {$m[1]}"];
        }
        if (preg_match('/^DROP\s+VIEW(?:\s+IF\s+EXISTS)?\s+`?([a-zA-Z0-9_]+)`?/i', $clean, $m)) {
            return ['action' => 'DROP', 'object_type' => 'VIEW', 'object_name' => $m[1], 'summary' => "DROP VIEW {$m[1]}"];
        }
        if (preg_match('/^CREATE(?:\s+DEFINER=\S+)?\s+(PROCEDURE|FUNCTION)\s+`?([a-zA-Z0-9_]+)`?/i', $clean, $m)) {
            $type = strtoupper($m[1]);
            return ['action' => 'CREATE', 'object_type' => $type, 'object_name' => $m[2], 'summary' => "CREATE {$type} {$m[2]}"];
        }
        if (preg_match('/^DROP\s+(PROCEDURE|FUNCTION)(?:\s+IF\s+EXISTS)?\s+`?([a-zA-Z0-9_]+)`?/i', $clean, $m)) {
            $type = strtoupper($m[1]);
            return ['action' => 'DROP', 'object_type' => $type, 'object_name' => $m[2], 'summary' => "DROP {$type} {$m[2]}"];
        }
        if (preg_match('/^CREATE(?:\s+DEFINER=\S+)?\s+TRIGGER\s+`?([a-zA-Z0-9_]+)`?/i', $clean, $m)) {
            return ['action' => 'CREATE', 'object_type' => 'TRIGGER', 'object_name' => $m[1], 'summary' => "CREATE TRIGGER {$m[1]}"];
        }
        if (preg_match('/^DROP\s+TRIGGER(?:\s+IF\s+EXISTS)?\s+`?([a-zA-Z0-9_]+)`?/i', $clean, $m)) {
            return ['action' => 'DROP', 'object_type' => 'TRIGGER', 'object_name' => $m[1], 'summary' => "DROP TRIGGER {$m[1]}"];
        }
        if (preg_match('/^CREATE(?:\s+UNIQUE)?\s+INDEX\s+`?([a-zA-Z0-9_]+)`?/i', $clean, $m)) {
            return ['action' => 'CREATE', 'object_type' => 'INDEX', 'object_name' => $m[1], 'summary' => "CREATE INDEX {$m[1]}"];
        }
        if (preg_match('/^(INSERT(?:\s+IGNORE)?|UPDATE|DELETE)\s+(?:INTO\s+|FROM\s+)?`?([a-zA-Z0-9_]+)`?/i', $clean, $m)) {
            $act = strtoupper(strtok($m[1], ' '));
            return ['action' => $act, 'object_type' => 'DATA', 'object_name' => $m[2], 'summary' => "{$act} ON {$m[2]}"];
        }
        if (preg_match('/^SET\s+/i', $clean)) {
            return ['action' => 'SET', 'object_type' => 'SESSION', 'object_name' => 'SQL_VAR', 'summary' => substr($clean, 0, 50)];
        }

        return ['action' => 'SQL', 'object_type' => 'SQL', 'object_name' => 'STATEMENT', 'summary' => substr($clean, 0, 65)];
    }
}
