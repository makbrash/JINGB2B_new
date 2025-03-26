<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "../config/config.php";

// Impedisce accessi diretti da browser
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(["error" => "Metodo non consentito"]));
}


// Recupera il body della richiesta
$data = json_decode(file_get_contents("php://input"), true);


// Debug: verifica se `$data` è vuoto o errato
if (!$data) {
    http_response_code(400);
    die(json_encode(["error" => "Formato JSON non valido", "raw_body" => file_get_contents("php://input")]));
}

// Controlla se i parametri sono presenti
if (!isset($data['action'])) {
    http_response_code(400);
    die(json_encode(["error" => "Dati mancanti", "received" => $data]));
}

if(isset($data['apicall']) &&  $data['apicall'] == 'cliente'){
	$apiUrl = 'manage_clienti.php';
}else{
	$apiUrl = 'manage_request.php';
}
	

// **Costruisce il percorso relativo**
$apiUrl = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . "/api/" . $apiUrl;

// Debug: Verifica URL API
error_log("🔍 Chiamata API a: " . $apiUrl);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . TUO_TOKEN_API, // Token nascosto
    "Content-Type: application/json"
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);


// Debug: Controlla eventuali errori cURL
if ($curlError) {
    http_response_code(500);
    die(json_encode(["error" => "Errore cURL", "details" => $curlError]));
}
/*
var_dump(http_response_code($httpCode));
exit;*/

// Restituisce la stessa risposta dell'API
http_response_code($httpCode);
echo $response;
?>
