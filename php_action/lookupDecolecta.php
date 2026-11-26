<?php
/**
 * php_action/lookupDecolecta.php
 * Queries Decolecta API (RENIEC / SUNAT) using DECOLECTA_API_TOKEN and DECOLECTA_API_URL.
 */
require_once 'core.php';
require_once 'security.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'messages' => 'No autorizado']);
    exit;
}

$type = strtoupper(cleanInput($_GET['type'] ?? $_POST['type'] ?? 'DNI'));
$number = trim(cleanInput($_GET['number'] ?? $_POST['number'] ?? ''));

if (empty($number)) {
    echo json_encode(['success' => false, 'messages' => 'Ingrese un número de documento válido.']);
    exit;
}

$apiToken = env('DECOLECTA_API_TOKEN');
$apiUrl   = rtrim(env('DECOLECTA_API_URL', 'https://api.decolecta.com/v1'), '/');

if (empty($apiToken)) {
    echo json_encode(['success' => false, 'messages' => 'El token de Decolecta (DECOLECTA_API_TOKEN) no está configurado.']);
    exit;
}

if ($type === 'DNI') {
    if (!preg_match('/^[0-9]{8}$/', $number)) {
        echo json_encode(['success' => false, 'messages' => 'El DNI debe tener 8 dígitos numéricos.']);
        exit;
    }
    $targetUrl = "{$apiUrl}/reniec/dni?numero=" . urlencode($number);
} elseif ($type === 'RUC') {
    if (!preg_match('/^[0-9]{11}$/', $number)) {
        echo json_encode(['success' => false, 'messages' => 'El RUC debe tener 11 dígitos numéricos.']);
        exit;
    }
    $targetUrl = "{$apiUrl}/sunat/ruc?numero=" . urlencode($number);
} else {
    echo json_encode(['success' => false, 'messages' => 'Tipo de documento no compatible para consulta automática (solo DNI y RUC).']);
    exit;
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $targetUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiToken}",
        "Accept: application/json"
    ],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'messages' => 'Error de conexión con la API de Decolecta: ' . $curlError]);
    exit;
}

$data = json_decode($response, true);

if ($httpCode === 200 && is_array($data)) {
    if ($type === 'DNI') {
        $fullName = trim($data['full_name'] ?? '');
        if (empty($fullName)) {
            $parts = array_filter([
                $data['first_name'] ?? '',
                $data['first_last_name'] ?? '',
                $data['second_last_name'] ?? ''
            ]);
            $fullName = implode(' ', $parts);
        }
        echo json_encode([
            'success' => true,
            'data' => [
                'document_type'   => 'DNI',
                'document_number' => $data['document_number'] ?? $number,
                'full_name'       => $fullName,
                'first_name'      => $data['first_name'] ?? '',
                'first_last_name' => $data['first_last_name'] ?? '',
                'second_last_name'=> $data['second_last_name'] ?? '',
                'address'         => ''
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // RUC
        $razonSocial = trim($data['razon_social'] ?? '');
        $addressParts = array_filter([
            $data['direccion'] ?? '',
            $data['distrito'] ?? '',
            $data['provincia'] ?? '',
            $data['departamento'] ?? ''
        ]);
        $address = implode(' - ', $addressParts);

        echo json_encode([
            'success' => true,
            'data' => [
                'document_type'   => 'RUC',
                'document_number' => $data['numero_documento'] ?? $number,
                'full_name'       => $razonSocial,
                'address'         => $address,
                'estado'          => $data['estado'] ?? '',
                'condicion'       => $data['condicion'] ?? ''
            ]
        ], JSON_UNESCAPED_UNICODE);
    }
} elseif ($httpCode === 404) {
    echo json_encode(['success' => false, 'messages' => "No se encontró información para el {$type} ingresado."]);
} else {
    $msg = $data['message'] ?? 'Respuesta inesperada del servicio de Decolecta.';
    echo json_encode(['success' => false, 'messages' => $msg]);
}
