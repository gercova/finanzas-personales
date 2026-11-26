<?php
/**
 * Security and Session Management Module
 * Implements:
 * - Cookie-based secure session management (HttpOnly, SameSite, Secure, Strict Mode)
 * - Anti-XSS measures and HTTP security headers
 * - CSRF token generation and verification
 * - Rate limiting for login attempts
 * - Password hashing using SHA-256
 * - Input sanitization and output encoding helpers
 */

require_once __DIR__ . '/env_loader.php';

/**
 * Configure and start secure cookie-based session
 */
function initSecureSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Harden PHP session configuration
    if (!headers_sent()) {
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                   || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
                   || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        // Set cookie parameters
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => 0, // Session cookie (expires when browser closes)
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        } else {
            session_set_cookie_params(0, '/; samesite=Lax', '', $isHttps, true);
        }
    }

    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    // Verify session validity if user is logged in
    $sessionLifetime = (int)env('SESSION_LIFETIME', 1800);

    if (isset($_SESSION['userId'])) {
        // Inactivity timeout check
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $sessionLifetime)) {
            session_unset();
            session_destroy();
            session_start();
            $GLOBALS['__sessionInvalidReason'] = 'timeout';
            return;
        }
        $_SESSION['last_activity'] = time();

        // Session hijacking prevention via User-Agent fingerprinting
        $currentAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== hash('sha256', $currentAgent)) {
            session_unset();
            session_destroy();
            session_start();
            $GLOBALS['__sessionInvalidReason'] = 'hijack';
            return;
        }
    }
}

/**
 * Helper to parse JSON or form-encoded request input uniformly
 */
function getRequestData(): array {
    if (!empty($_POST)) {
        return $_POST;
    }
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return [];
}

/**
 * Fetch a system setting value from system_settings if the table exists, else fallback.
 */
function getSystemSetting(mysqli $connect, string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $chk = @$connect->query("SHOW TABLES LIKE 'system_settings'");
        if ($chk && $chk->num_rows > 0) {
            $res = @$connect->query("SELECT setting_key, setting_value FROM `system_settings`");
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $cache[$r['setting_key']] = (string)($r['setting_value'] ?? '');
                }
                $res->free();
            }
        }
        if ($chk) $chk->free();
    }
    return array_key_exists($key, $cache) && $cache[$key] !== '' ? $cache[$key] : $default;
}

/**
 * Fetch current user's header KPI metrics (month income & today's expenses)
 */
function fetchHeaderMetrics(mysqli $connect, int $userId): array {
    $currentMonth = date('Y-m');
    list($monthStart, $monthEnd) = getMonthDateBounds($currentMonth);
    $todayDate = date('Y-m-d');

    $monthIncome   = 0.0;
    $todayExpenses = 0.0;
    $hmSets = callProcedureMulti($connect, "CALL sp_get_header_metrics(?, ?, ?, ?)", "isss", [$userId, $monthStart, $monthEnd, $todayDate]);
    if (!empty($hmSets[0][0])) {
        $monthIncome   = (float)$hmSets[0][0]['month_income'];
        $todayExpenses = (float)$hmSets[0][0]['today_expenses'];
    }

    return [
        'month_income'   => $monthIncome,
        'today_expenses' => $todayExpenses,
    ];
}

/**
 * Send HTTP Security Headers to prevent XSS, clickjacking, and MIME sniffing
 */
function setSecurityHeaders() {
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Content Security Policy allowing necessary scripts and styles for Bootstrap/DataTables/jQuery
    header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' data:; img-src 'self' data: blob:; font-src 'self' data:;");
}

/**
 * Generate or retrieve CSRF token for the current session
 */
function getCsrfToken() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        initSecureSession();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output HTML hidden input with CSRF token
 */
function csrfField() {
    $token = htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '" />';
}

/**
 * Verify CSRF token from POST or headers
 */
function verifyCsrfToken($token = null) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        initSecureSession();
    }

    if (empty($_SESSION['csrf_token'])) {
        return false;
    }

    if ($token === null) {
        if (isset($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        } elseif (isset($_GET['csrf_token'])) {
            $token = $_GET['csrf_token'];
        }
    }

    if (empty($token) || !is_string($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Encrypt password using SHA-256
 */
function hashPassword($password) {
    return hash('sha256', $password);
}

/**
 * Verify password against stored hash (supports SHA-256 with fallback to legacy md5)
 * Returns true if valid, 'rehash_needed' if valid but stored as legacy md5, or false if invalid.
 */
function verifyPassword($password, $storedHash) {
    $sha256 = hash('sha256', $password);
    if (hash_equals($sha256, $storedHash)) {
        return true;
    }

    // Check legacy MD5 hash for seamless migration
    if (strlen($storedHash) === 32 && hash_equals(md5($password), $storedHash)) {
        return 'rehash_needed';
    }

    return false;
}

/**
 * Get client IP address safely
 */
function getClientIp() {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    // Validate IP format
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    return '127.0.0.1';
}

/**
 * Ensure login_attempts table exists
 */
function ensureLoginAttemptsTable($connect) {
    static $ensured = false;
    if ($ensured) return;

    $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        username VARCHAR(255) NOT NULL,
        attempted_at DATETIME NOT NULL,
        success TINYINT(1) DEFAULT 0,
        INDEX idx_ip_time (ip_address, attempted_at),
        INDEX idx_user_time (username, attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $connect->query($sql);
    $ensured = true;
}

/**
 * Check if login attempt is allowed under rate limiter
 * Returns ['allowed' => bool, 'remaining_seconds' => int, 'attempts' => int]
 */
function checkRateLimit($connect, $username, $explicitIp = null) {
    ensureLoginAttemptsTable($connect);

    $ipAddress = ($explicitIp !== null) ? $explicitIp : getClientIp();
    $maxAttempts = (int)env('MAX_LOGIN_ATTEMPTS', 5);
    $lockoutTime = (int)env('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

    $cutoff = date('Y-m-d H:i:s', time() - $lockoutTime);

    // Count failed attempts per account (isolated so one user doesn't lock out another on shared IPs)
    $sql = "SELECT COUNT(*) as failed_count, MAX(attempted_at) as last_attempt
            FROM login_attempts
            WHERE username = ?
              AND success = 0
              AND attempted_at >= ?";
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        return ['allowed' => true, 'remaining_seconds' => 0, 'attempts' => 0];
    }

    $stmt->bind_param("ss", $username, $cutoff);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    $failedCount = (int)$data['failed_count'];
    if ($failedCount >= $maxAttempts) {
        $lastAttemptTime = strtotime($data['last_attempt']);
        $elapsed = time() - $lastAttemptTime;
        $remaining = max(0, $lockoutTime - $elapsed);
        return [
            'allowed' => false,
            'remaining_seconds' => $remaining,
            'attempts' => $failedCount
        ];
    }

    // Secondary broad IP flood guard (4x threshold so shared-IP / multi-user logins aren't disrupted)
    $ipLimit = $maxAttempts * 4;
    $sqlIp = "SELECT COUNT(*) as ip_failed, MAX(attempted_at) as last_ip_attempt
              FROM login_attempts
              WHERE ip_address = ?
                AND success = 0
                AND attempted_at >= ?";
    $stmtIp = $connect->prepare($sqlIp);
    if ($stmtIp) {
        $stmtIp->bind_param("ss", $ipAddress, $cutoff);
        $stmtIp->execute();
        $ipData = $stmtIp->get_result()->fetch_assoc();
        $stmtIp->close();
        if ((int)$ipData['ip_failed'] >= $ipLimit) {
            $elapsed = time() - strtotime($ipData['last_ip_attempt']);
            return [
                'allowed' => false,
                'remaining_seconds' => max(0, $lockoutTime - $elapsed),
                'attempts' => (int)$ipData['ip_failed']
            ];
        }
    }

    return [
        'allowed' => true,
        'remaining_seconds' => 0,
        'attempts' => $failedCount
    ];
}

/**
 * Record a login attempt in the database
 */
function recordLoginAttempt($connect, $username, $success, $explicitIp = null) {
    ensureLoginAttemptsTable($connect);

    $ipAddress = ($explicitIp !== null) ? $explicitIp : getClientIp();
    $now = date('Y-m-d H:i:s');
    $successVal = $success ? 1 : 0;

    $sql = "INSERT INTO login_attempts (ip_address, username, attempted_at, success) VALUES (?, ?, ?, ?)";
    $stmt = $connect->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sssi", $ipAddress, $username, $now, $successVal);
        $stmt->execute();
        $stmt->close();
    }

    // If login succeeded, clear previous failed attempts for this IP and username
    if ($success) {
        $clearSql = "DELETE FROM login_attempts WHERE (ip_address = ? OR username = ?) AND success = 0";
        $clearStmt = $connect->prepare($clearSql);
        if ($clearStmt) {
            $clearStmt->bind_param("ss", $ipAddress, $username);
            $clearStmt->execute();
            $clearStmt->close();
        }
    }

    // Clean up attempts older than 24 hours (1 in 50 requests)
    if (mt_rand(1, 50) === 1) {
        $oldCutoff = date('Y-m-d H:i:s', time() - 86400);
        $connect->query("DELETE FROM login_attempts WHERE attempted_at < '{$oldCutoff}'");
    }
}

/**
 * Sanitize string input to prevent XSS
 */
function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    $data = trim($data);
    $data = str_replace(chr(0), '', $data); // Remove null bytes
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Output encoding helper
 */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Automatically apply security headers
setSecurityHeaders();
