<?php
/**
 * classificatore_api.php - Gestione chiamate API a OpenAI
 * 
 * Questo file gestisce tutte le interazioni con l'API di OpenAI,
 * inclusi meccanismi di retry, gestione rate limit e ottimizzazione token.
 * 
 * @author Marco Vitaletti
 * @version 1.0
 */

// Configurazione predefinita delle API
define('OPENAI_API_KEY', YOUR_OPENAI_API_KEY); // Sostituire con la tua API key
define('DEFAULT_MODEL', 'gpt-4o-mini');
define('MAX_RETRIES', 3);
define('RETRY_DELAY_MS', 1000); // 1 secondo di base tra i retry

// Log degli errori API
$api_log_file = __DIR__ . '/log/openai_api.log';

// Assicurati che la directory di log esista
if (!file_exists(dirname($api_log_file))) {
    mkdir(dirname($api_log_file), 0755, true);
}

/**
 * Log degli errori API
 * @param string $message Messaggio di errore
 * @return void
 */
function api_log($message) {
    global $api_log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(
        $api_log_file, 
        "[{$timestamp}] {$message}" . PHP_EOL, 
        FILE_APPEND
    );
}

/**
 * Classifica un prodotto tramite OpenAI API
 * 
 * @param int $id ID del prodotto
 * @param string $titolo Titolo del prodotto
 * @param string $image_path Percorso completo all'immagine
 * @param array $categorie_list Lista delle categorie possibili
 * @param array $sottocategorie_list Lista delle sottocategorie possibili
 * @param array $marche_list Lista delle marche possibili
 * @param string $model Modello OpenAI da utilizzare
 * @return array|string Array con dati classificati o stringa di errore
 */
function classifica_prodotto_api($id, $titolo, $image_path, $categorie_list, $sottocategorie_list, $marche_list, $model = DEFAULT_MODEL) {
    // Creo le liste formattate come stringa per il prompt
    $categorie_str = implode(", ", $categorie_list);
    $sottocategorie_str = implode(", ", $sottocategorie_list);
    $marche_str = implode(", ", $marche_list);
    
    // Preparazione del system prompt ottimizzato
    $system_prompt = "Sei un classificatore prodotti per e-commerce. Analizza il titolo e l'immagine del prodotto per:
1. Assegnare categoria, sottocategoria e marca dalle liste fornite
2. Generare tag strutturati per migliorare la ricerca

ANALIZZA ATTENTAMENTE IL NOME MARCA/PRODOTTO:
- Estrai LA MARCA o il nome prodotto dalle PRIME 1-4 PAROLE del titolo
- Es: in 'Dash Pods Detersivo Lavastoviglie', la marca è 'Dash'
- Es: in 'Fairy Platinum Plus Pastiglie', la marca è 'Fairy'
- Scegli sempre la marca più simile dalla lista marche fornita
- Se nessuna marca corrisponde, scegli 'Altro'

Genera i seguenti tag (minimo 3, massimo 8 tag in totale):

1. TAG MARCA/PRODOTTO: 
   - Solo il nome marca/prodotto senza altre parole (es: 'ace', 'glade', 'dash')

2. TAG TIPO PRODOTTO: 
   - Tipo esatto di prodotto (es: 'detersivo', 'spray', 'ricarica')

3. TAG CARATTERISTICA: 
   - Un dettaglio distintivo (es: 'lavaggi', '700ml', 'lavanda', 'bucato')

4. TAG AGGIUNTIVI (opzionali):
   - Solo se necessari per la ricerca

REGOLE PER I TAG:
- Tutti minuscoli, senza caratteri speciali
- Brevi (1-4 parole per tag)
- In italiano
- Senza duplicati

CATEGORIE POSSIBILI: {$categorie_str}

SOTTOCATEGORIE POSSIBILI: {$sottocategorie_str}

MARCHE POSSIBILI: {$marche_str}

Rispondi SOLO in JSON con questa struttura:
{
  \"categoria\": \"[categoria dalla lista]\",
  \"sottocategoria\": \"[sottocategoria dalla lista]\",
  \"marca\": \"[marca dalla lista]\",
  \"tags\": [\"tag1\", \"tag2\", \"tag3\", ...],
  \"tag_marca_prodotto\": \"nome marca/prodotto\",
  \"tag_tipo_prodotto\": \"tipo di prodotto\",
  \"tag_caratteristica\": \"caratteristica distintiva\"
}";

    // Preparazione dei messaggi
    if (!file_exists($image_path) || basename($image_path) == "no-image.jpg") {
        // Se non c'è immagine, usa solo il titolo
        $messages = [
            [
                "role" => "system", 
                "content" => $system_prompt
            ],
            [
                "role" => "user",
                "content" => "Titolo prodotto: " . $titolo
            ]
        ];
    } else {
        // Se c'è un'immagine, codifica in base64 e usa entrambi
        try {
            $image_data = file_get_contents($image_path);
            $base64_image = base64_encode($image_data);
            
            $messages = [
                [
                    "role" => "system", 
                    "content" => $system_prompt
                ],
                [
                    "role" => "user",
                    "content" => [
                        [
                            "type" => "text",
                            "text" => "Titolo prodotto: " . $titolo
                        ],
                        [
                            "type" => "image_url",
                            "image_url" => [
                                "url" => "data:image/jpeg;base64," . $base64_image
                            ]
                        ]
                    ]
                ]
            ];
        } catch (Exception $e) {
            // Se c'è un errore con l'immagine, fallback solo al titolo
            api_log("Errore caricamento immagine per prodotto ID {$id}: " . $e->getMessage());
            
            $messages = [
                [
                    "role" => "system", 
                    "content" => $system_prompt
                ],
                [
                    "role" => "user",
                    "content" => "Titolo prodotto: " . $titolo . " (immagine non disponibile)"
                ]
            ];
        }
    }
    
    // Preparazione della richiesta ottimizzata
    $data = [
        "model" => $model,
        "messages" => $messages,
        "max_tokens" => 500,
        "temperature" => 0.2, // Ridotta per maggiore precisione
        "response_format" => ["type" => "json_object"] // Force JSON output
    ];
    
    // Richiesta API con retry
    return call_openai_api_with_retry($data);
}

/**
 * Chiama OpenAI API con sistema di retry
 * @param array $data Dati della richiesta
 * @param int $retry_count Numero attuale di tentativi
 * @return array|string Risposta o messaggio d'errore
 */
function call_openai_api_with_retry($data, $retry_count = 0) {
    $api_key = OPENAI_API_KEY;
    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer " . $api_key
    ];
    
    // Inizializza cURL
    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Timeout di 60 secondi
    
    // Esegui la richiesta
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $http_code = $info['http_code'];
    
    // Gestione errori
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        
        // Log dell'errore
        api_log("Errore cURL: " . $error);
        
        // Retry in caso di errori di connessione
        if ($retry_count < MAX_RETRIES) {
            // Backoff esponenziale
            $delay = RETRY_DELAY_MS * pow(2, $retry_count);
            usleep($delay * 1000); // Converti in microsecondi
            return call_openai_api_with_retry($data, $retry_count + 1);
        }
        
        return "Errore di connessione API dopo " . MAX_RETRIES . " tentativi: " . $error;
    }
    
    curl_close($ch);
    
    // Decodifica risposta
    $response_data = json_decode($response, true);
    
    // Verifica rate limit o altri errori
    if ($http_code == 429 || ($http_code >= 500 && $http_code < 600)) {
        api_log("Errore API HTTP {$http_code}: " . ($response_data['error']['message'] ?? 'Rate limit o errore server'));
        
        // Retry con backoff
        if ($retry_count < MAX_RETRIES) {
            $delay = RETRY_DELAY_MS * pow(2, $retry_count);
            // Se c'è un header Retry-After, usalo come delay
            if (isset($info['headers']) && isset($info['headers']['Retry-After'])) {
                $delay = intval($info['headers']['Retry-After']) * 1000;
            }
            usleep($delay * 1000);
            return call_openai_api_with_retry($data, $retry_count + 1);
        }
        
        return "Errore rate limit API dopo " . MAX_RETRIES . " tentativi";
    }
    
    // Altri errori API
    if (isset($response_data['error'])) {
        api_log("Errore API: " . $response_data['error']['message']);
        return "Errore API: " . $response_data['error']['message'];
    }
    
    try {
        // Estrai il contenuto JSON
        $content = $response_data['choices'][0]['message']['content'];
        $classification = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            api_log("Errore parsing JSON: " . json_last_error_msg() . " - Content: " . substr($content, 0, 200));
            return "Errore parsing JSON: " . json_last_error_msg();
        }
        
        // Aggiungi ID del prodotto alla risposta
        $classification['id'] = is_array($data['messages'][1]['content']) 
            ? intval(preg_replace('/[^0-9]/', '', $data['messages'][1]['content'][0]['text']))
            : intval(preg_replace('/[^0-9]/', '', $data['messages'][1]['content']));
        
        return $classification;
    } catch (Exception $e) {
        api_log("Errore nell'elaborazione della risposta: " . $e->getMessage());
        return "Errore nell'elaborazione della risposta: " . $e->getMessage();
    }
}