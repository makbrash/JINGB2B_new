<?php
 //Abilita i messaggi di errore solo per debug

require "../includes/db.php";

// Impedisce accessi diretti da browser
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(["error" => "Metodo non consentito"]));
}

// Recupera il body della richiesta
$data = json_decode(file_get_contents("php://input"), true);

// Verifica il formato JSON
if (!$data) {
    http_response_code(400);
    die(json_encode(["error" => "Formato JSON non valido", "raw_body" => file_get_contents("php://input")]));
}

// Controlla se i parametri sono presenti
if (!isset($data['action'])) {
    http_response_code(400);
    die(json_encode(["error" => "Dati mancanti", "received" => $data]));
}

// Determina quale endpoint API usare in base al tipo di richiesta
$apiEndpoint = "manage_request.php"; // Default

// Se l'azione è relativa ai clienti, usa l'endpoint clienti
if (strpos($data['action'], 'client') !== false || 
    strpos($data['action'], 'list_cataloghi') !== false) {
    $apiEndpoint = "manage_clienti.php";
}

// Log per debugging
error_log("?? Proxy: richiesta per action '" . $data['action'] . "' diretta a " . $apiEndpoint);

// Prova prima l'esecuzione locale senza cURL
try {
    // Imposta variabili per l'esecuzione locale
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    // Salva il contenuto originale di input
    $originalInput = file_get_contents("php://input");
    
    // Nascondi gli errori di output prima di includerlo
    ob_start();
    
    // Esegui il file direttamente con include
    include $apiEndpoint;
    
    // Cattura l'output e pulisci il buffer
    $response = ob_get_clean();
    
    // Se arriviamo qui, l'esecuzione locale è andata a buon fine
    // e $response contiene la risposta JSON dell'API
    exit($response);
    
} catch (Exception $e) {
    // Se c'è un errore nell'esecuzione locale, log e continua con cURL
    error_log("?? Esecuzione locale fallita: " . $e->getMessage() . ". Provo con cURL.");
    
    // Se è stato impostato un codice HTTP, ripristinalo
    if (http_response_code() !== 200) {
        http_response_code(200);
    }
}

// Se l'esecuzione locale fallisce, proviamo con cURL
// Costruisci il percorso assoluto per l'API
$apiUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://";
$apiUrl .= $_SERVER['HTTP_HOST'];
$baseDir = dirname($_SERVER['SCRIPT_NAME']);
// Assicurati che il baseDir termini con uno slash
$baseDir = rtrim($baseDir, '/') . '/';
// Rimuovi 'api/' se presente
$baseDir = str_replace('api/', '', $baseDir);
$apiUrl .= $baseDir . "api/" . $apiEndpoint;

error_log("?? Fallback cURL: chiamata a " . $apiUrl);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . TUO_TOKEN_API,
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Opzioni aggiuntive per aiutare il debug
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

// Log verboso in caso di errore
if ($curlError || $httpCode >= 400) {
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    error_log("?? Dettagli cURL: " . $verboseLog);
}

curl_close($ch);

// Controlla eventuali errori cURL
if ($curlError) {
    error_log("? Errore cURL: " . $curlError);
    http_response_code(500);
    die(json_encode([
        "error" => "Errore di comunicazione con l'API", 
        "details" => $curlError,
        "url" => $apiUrl
    ]));
}

// Risposta
if ($httpCode != 200) {
    error_log("?? API ha risposto con codice HTTP " . $httpCode . ": " . $response);
}

http_response_code($httpCode);
echo $response;
?>