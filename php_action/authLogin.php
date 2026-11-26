<?php
/**
 * php_action/authLogin.php
 * Pure JSON authentication endpoint with CSRF check, rate limiting, and SHA-256 verification.
 */
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/security.php';

initSecureSession();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'messages' => 'Método no permitido.']);
    exit();
}

$input    = getRequestData();
$token    = $input['csrf_token'] ?? null;
$username = trim((string)($input['username'] ?? ''));
$password = trim((string)($input['password'] ?? ''));

if (!verifyCsrfToken($token)) {
    http_response_code(403);
    echo json_encode([
        'success'    => false,
        'messages'   => 'Error de validación de seguridad (CSRF inválido). Recargue e intente nuevamente.',
        'csrf_token' => getCsrfToken(),
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'messages' => 'Ingrese su usuario y contraseña.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$rateLimit = checkRateLimit($connect, $username);
if (!$rateLimit['allowed']) {
    $minutes = ceil($rateLimit['remaining_seconds'] / 60);
    echo json_encode([
        'success'  => false,
        'messages' => "Demasiados intentos fallidos. Su acceso está bloqueado temporalmente. Intente nuevamente en {$minutes} minuto(s)."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmt = $connect->prepare("SELECT user_id, username, password, full_name, role, is_active FROM users WHERE username = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'messages' => 'Error interno del servidor. Intente más tarde.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows !== 1) {
    $stmt->close();
    recordLoginAttempt($connect, $username, false);
    echo json_encode(['success' => false, 'messages' => 'Combinación incorrecta de nombre de usuario y/o contraseña.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

if ((int)$user['is_active'] !== 1) {
    echo json_encode(['success' => false, 'messages' => 'Esta cuenta de usuario se encuentra desactivada.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$passwordCheck = verifyPassword($password, $user['password']);
if ($passwordCheck !== true && $passwordCheck !== 'rehash_needed') {
    recordLoginAttempt($connect, $username, false);
    echo json_encode(['success' => false, 'messages' => 'Combinación incorrecta de nombre de usuario y/o contraseña.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$isUserAdmin = (strtoupper((string)($user['role'] ?? 'USER')) === 'ADMIN') || (strtolower((string)$user['username']) === 'admin');
if (!$isUserAdmin && getSystemSetting($connect, 'maintenance_mode', '0') === '1') {
    echo json_encode([
        'success'  => false,
        'messages' => 'El sistema se encuentra temporalmente en modo mantenimiento. Solo el administrador puede iniciar sesión.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($passwordCheck === 'rehash_needed') {
    $newSha256 = hashPassword($password);
    $upgradeStmt = $connect->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    if ($upgradeStmt) {
        $upgradeStmt->bind_param("si", $newSha256, $user['user_id']);
        $upgradeStmt->execute();
        $upgradeStmt->close();
    }
}

recordLoginAttempt($connect, $username, true);
session_regenerate_id(true);

$userId = (int)$user['user_id'];
$_SESSION['userId']        = $userId;
$_SESSION['username']      = $user['username'];
$_SESSION['full_name']     = !empty($user['full_name']) ? $user['full_name'] : $user['username'];
$_SESSION['role']          = !empty($user['role']) ? $user['role'] : 'USER';
$_SESSION['last_activity'] = time();
$_SESSION['user_agent']    = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
$_SESSION['csrf_token']    = bin2hex(random_bytes(32));

$headerMetrics = fetchHeaderMetrics($connect, $userId);

echo json_encode([
    'success'        => true,
    'authenticated'  => true,
    'messages'       => 'Sesión iniciada correctamente.',
    'csrf_token'     => $_SESSION['csrf_token'],
    'user'           => [
        'id'        => $userId,
        'username'  => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role'      => $_SESSION['role'],
    ],
    'header_metrics' => $headerMetrics,
], JSON_UNESCAPED_UNICODE);
