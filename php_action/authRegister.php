<?php
/**
 * php_action/authRegister.php
 * Pure JSON user registration endpoint.
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

$input           = getRequestData();
$token           = $input['csrf_token'] ?? null;
$fullName        = trim((string)($input['full_name'] ?? ''));
$username        = trim((string)($input['username'] ?? ''));
$email           = trim((string)($input['email'] ?? ''));
$password        = (string)($input['password'] ?? '');
$confirmPassword = (string)($input['confirm_password'] ?? '');

if (!verifyCsrfToken($token)) {
    http_response_code(403);
    echo json_encode([
        'success'    => false,
        'messages'   => 'Error de validación de seguridad (CSRF inválido).',
        'csrf_token' => getCsrfToken(),
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (getSystemSetting($connect, 'allow_user_registration', '1') !== '1') {
    http_response_code(403);
    echo json_encode([
        'success'  => false,
        'messages' => 'El registro de nuevos usuarios se encuentra deshabilitado por el administrador.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$errors = [];
if ($fullName === '' || mb_strlen($fullName) < 2) {
    $errors[] = 'El nombre completo debe tener al menos 2 caracteres.';
}
if (!preg_match('/^[a-zA-Z0-9_.-]{3,40}$/', $username)) {
    $errors[] = "El usuario debe tener entre 3 y 40 caracteres alfanuméricos (puede incluir '.', '_' o '-').";
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Ingrese un correo electrónico válido.';
}
if (strlen($password) < 6) {
    $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
} elseif ($password !== $confirmPassword) {
    $errors[] = 'Las contraseñas no coinciden.';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'messages' => implode(' ', $errors), 'errors' => $errors], JSON_UNESCAPED_UNICODE);
    exit();
}

$emailParam = ($email !== '') ? $email : null;
$chkStmt = $connect->prepare("SELECT user_id, username, email FROM users WHERE username = ? OR (? IS NOT NULL AND email = ?) LIMIT 1");
if (!$chkStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'messages' => 'Error interno verificando disponibilidad de usuario.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$chkStmt->bind_param("sss", $username, $emailParam, $emailParam);
$chkStmt->execute();
$dup = $chkStmt->get_result()->fetch_assoc();
$chkStmt->close();

if ($dup) {
    $msg = strcasecmp($dup['username'], $username) === 0
        ? 'El nombre de usuario ya está registrado. Elija otro.'
        : 'El correo electrónico ya está asociado a otra cuenta.';
    echo json_encode(['success' => false, 'messages' => $msg], JSON_UNESCAPED_UNICODE);
    exit();
}

$hashed = hashPassword($password);
$insStmt = $connect->prepare("INSERT INTO users (username, password, email, full_name, role, is_active) VALUES (?, ?, ?, ?, 'USER', 1)");
if (!$insStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'messages' => 'Error preparando el registro de usuario.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$insStmt->bind_param("ssss", $username, $hashed, $emailParam, $fullName);
$ok = $insStmt->execute();
$insStmt->close();

if ($ok) {
    echo json_encode([
        'success'  => true,
        'messages' => 'Cuenta creada exitosamente. Ya puede iniciar sesión con su usuario y contraseña (sus cuentas y categorías iniciales se generaron automáticamente).'
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'messages' => 'No se pudo completar el registro. Intente nuevamente.'], JSON_UNESCAPED_UNICODE);
}
