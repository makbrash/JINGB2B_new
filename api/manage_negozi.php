<?php
/**
 * API per la gestione dell'anagrafica negozi manage_negozi.php
 * 
 * Questo file gestisce tutte le operazioni CRUD per l'anagrafica negozi
 */

// Carica Medoo
require "../config/config.php";

// Imposta gli header per JSON e sicurezza
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

// Protezione API con Token
$token_valid = TUO_TOKEN_API; // il tuo token definito in config.php

// Recupera il token dalla richiesta
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!$authHeader || $authHeader !== "Bearer $token_valid") {
    http_response_code(403);
    echo json_encode(["error" => "Accesso non autorizzato"]);
    exit;
}

// Funzione per pulire e validare i dati in input
function sanitizeInput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
        return $data;
    }
    
    // Converti null o undefined in stringa vuota
    if ($data === null || $data === "null" || $data === "undefined") {
        return "";
    }
    
    if (is_string($data)) {
        // Rimuovi spazi extra e caratteri potenzialmente pericolosi
        return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
    }
    
    return $data;
}

// Verifica il metodo di richiesta
$method = $_SERVER['REQUEST_METHOD'];

// Legge il body JSON
$inputData = json_decode(file_get_contents("php://input"), true);

if (!$inputData) {
    http_response_code(400);
    echo json_encode(["error" => "Formato JSON non valido"]);
    exit;
}

// Azione richiesta
$action = $inputData['action'] ?? '';

// Log delle richieste
error_log("API Negozi - Azione: $action - Dati: " . json_encode($inputData));

switch ($action) {
    
    // Elenco negozi con paginazione e filtri
    case 'list_negozi':
        try {
            $page = intval($inputData['page'] ?? 1);
            $perPage = intval($inputData['per_page'] ?? 50);
            $search = sanitizeInput($inputData['search'] ?? '');
            
            // Calcola l'offset
            $offset = ($page - 1) * $perPage;
            
            // Costruisci i filtri WHERE
            $where = [];
            
            if (!empty($search)) {
                $where["OR"] = [
                    "nome[~]" => $search,
                    "descrizione[~]" => $search,
                    "email[~]" => $search
                ];
            }
            
            // Conta il totale risultati
            $totalCount = $medooDB->count("negozi", $where);
            
            // Recupera i negozi con paginazione
            $negozi = $medooDB->select(
                "negozi", 
                [
                    "id",
                    "nome",
                    "descrizione",
                    "data_creazione",
                    "ordinamento",
                    "stanby",
                    "email",
                    "immagine",
                    "whatsapp",
                    "note",
                    "json_layout_catalogo"
                ],
                array_merge($where, [
                    "ORDER" => ["ordinamento" => "ASC", "nome" => "ASC"],
                    "LIMIT" => [$offset, $perPage]
                ])
            );
            
            // Risposta di successo
            echo json_encode([
                "success" => true,
                "data" => $negozi,
                "total" => $totalCount,
                "page" => $page,
                "per_page" => $perPage
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Errore nel recupero negozi: " . $e->getMessage()
            ]);
        }
        break;
    
    // Recupera dettagli di un singolo negozio
    case 'get_negozio':
        try {
            $id = intval($inputData['id'] ?? 0);
            
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(["error" => "ID negozio non valido"]);
                exit;
            }
            
            // Recupera il negozio
            $negozio = $medooDB->get(
                "negozi", 
                [
                    "id",
                    "nome",
                    "descrizione",
                    "data_creazione",
                    "ordinamento",
                    "stanby",
                    "email",
                    "immagine",
                    "whatsapp",
                    "note",
                    "json_layout_catalogo"
                ],
                ["id" => $id]
            );
            
            if (!$negozio) {
                http_response_code(404);
                echo json_encode(["error" => "Negozio non trovato"]);
                exit;
            }
            
            // Risposta di successo
            echo json_encode([
                "success" => true,
                "data" => $negozio
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Errore nel recupero negozio: " . $e->getMessage()
            ]);
        }
        break;
    
    // Aggiungi nuovo negozio
    case 'add_negozio':
        try {
            // Recupera e sanitizza i dati
            $negozioData = sanitizeInput($inputData['data'] ?? []);
            
            // Valida i campi obbligatori
            $requiredFields = ['nome'];
            foreach ($requiredFields as $field) {
                if (empty($negozioData[$field])) {
                    http_response_code(400);
                    echo json_encode(["error" => "Campo obbligatorio mancante: $field"]);
                    exit;
                }
            }
            
            // Processa immagine se presente
            $immaginePath = null;
            if (!empty($negozioData['immagine_data'])) {
                // Estrai i dati base64
                if (preg_match('/^data:image\/(\w+);base64,/', $negozioData['immagine_data'], $matches)) {
                    $imageType = $matches[1];
                    $base64Data = substr($negozioData['immagine_data'], strpos($negozioData['immagine_data'], ',') + 1);
                    $decodedData = base64_decode($base64Data);
                    
                    if ($decodedData === false) {
                        throw new Exception("Errore nella decodifica dell'immagine");
                    }
                    
                    // Crea directory per le immagini se non esiste
                    $uploadDir = '../public/negozi/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Genera un nome file unico
                    $fileName = uniqid() . '.' . $imageType;
                    $filePath = $uploadDir . $fileName;
                    
                    // Salva il file
                    if (file_put_contents($filePath, $decodedData)) {
                        $immaginePath = 'public/negozi/' . $fileName;
                    } else {
                        throw new Exception("Errore nel salvataggio dell'immagine");
                    }
                } else {
                    throw new Exception("Formato immagine non valido");
                }
            }
            
            // Prepara i dati per l'inserimento
            $insertData = [
                "nome" => $negozioData['nome'],
                "descrizione" => $negozioData['descrizione'] ?? '',
                "ordinamento" => intval($negozioData['ordinamento'] ?? 0),
                "stanby" => intval($negozioData['stanby'] ?? 0),
                "email" => $negozioData['email'] ?? '',
                "whatsapp" => $negozioData['whatsapp'] ?? '',
                "note" => $negozioData['note'] ?? '',
                "json_layout_catalogo" => $negozioData['json_layout_catalogo'] ?? '',
                "data_creazione" => date("Y-m-d H:i:s")
            ];
            
            // Aggiungi path immagine se presente
            if ($immaginePath) {
                $insertData["immagine"] = $immaginePath;
            }
            
            // Inserisci il negozio
            $medooDB->insert("negozi", $insertData);
            $negozioId = $medooDB->id();
            
            if (!$negozioId) {
                throw new Exception("Errore nell'inserimento del negozio");
            }
            
            // Risposta di successo
            echo json_encode([
                "success" => true,
                "message" => "Negozio aggiunto con successo",
                "id" => $negozioId
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Errore nell'aggiunta del negozio: " . $e->getMessage()
            ]);
        }
        break;
    
    // Aggiorna negozio esistente
    case 'update_negozio':
        try {
            // Recupera e sanitizza i dati
            $negozioData = sanitizeInput($inputData['data'] ?? []);
            $negozioId = intval($negozioData['id'] ?? 0);
            
            if ($negozioId <= 0) {
                http_response_code(400);
                echo json_encode(["error" => "ID negozio non valido"]);
                exit;
            }
            
            // Verifica che il negozio esista
            $negozioEsistente = $medooDB->has("negozi", ["id" => $negozioId]);
            if (!$negozioEsistente) {
                http_response_code(404);
                echo json_encode(["error" => "Negozio non trovato"]);
                exit;
            }
            
            // Processa immagine se presente
            $immaginePath = null;
            if (!empty($negozioData['immagine_data'])) {
                // Estrai i dati base64
                if (preg_match('/^data:image\/(\w+);base64,/', $negozioData['immagine_data'], $matches)) {
                    $imageType = $matches[1];
                    $base64Data = substr($negozioData['immagine_data'], strpos($negozioData['immagine_data'], ',') + 1);
                    $decodedData = base64_decode($base64Data);
                    
                    if ($decodedData === false) {
                        throw new Exception("Errore nella decodifica dell'immagine");
                    }
                    
                    // Crea directory per le immagini se non esiste
                    $uploadDir = '../public/negozi/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Genera un nome file unico
                    $fileName = uniqid() . '.' . $imageType;
                    $filePath = $uploadDir . $fileName;
                    
                    // Salva il file
                    if (file_put_contents($filePath, $decodedData)) {
                        $immaginePath = 'public/negozi/' . $fileName;
                        
                        // Elimina immagine precedente se esiste
                        $oldImmagine = $medooDB->get("negozi", "immagine", ["id" => $negozioId]);
                        if ($oldImmagine && file_exists("../" . $oldImmagine)) {
                            unlink("../" . $oldImmagine);
                        }
                    } else {
                        throw new Exception("Errore nel salvataggio dell'immagine");
                    }
                } else {
                    throw new Exception("Formato immagine non valido");
                }
            }
            
            // Prepara i dati per l'aggiornamento
            $updateData = [
                "nome" => $negozioData['nome'],
                "descrizione" => $negozioData['descrizione'] ?? '',
                "ordinamento" => intval($negozioData['ordinamento'] ?? 0),
                "stanby" => intval($negozioData['stanby'] ?? 0),
                "email" => $negozioData['email'] ?? '',
                "whatsapp" => $negozioData['whatsapp'] ?? '',
                "note" => $negozioData['note'] ?? '',
                "json_layout_catalogo" => $negozioData['json_layout_catalogo'] ?? '',
                "data_modifica" => date("Y-m-d H:i:s")
            ];
            
            // Aggiungi path immagine se presente
            if ($immaginePath) {
                $updateData["immagine"] = $immaginePath;
            }
            
            // Aggiorna il negozio
            $result = $medooDB->update("negozi", $updateData, ["id" => $negozioId]);
            
            if ($result->rowCount() === 0 && count($updateData) > 1) {
                // Non ci sono stati aggiornamenti, ma non è necessariamente un errore
                // potrebbe semplicemente non essere cambiato nulla
                error_log("Nessuna modifica apportata al negozio ID: $negozioId");
            }
            
            // Risposta di successo
            echo json_encode([
                "success" => true,
                "message" => "Negozio aggiornato con successo"
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Errore nell'aggiornamento del negozio: " . $e->getMessage()
            ]);
        }
        break;
    
    // Cambia stato negozio (attivo/sospeso)
    case 'toggle_negozio_status':
        try {
            $negozioId = intval($inputData['id'] ?? 0);
            $nuovoStanby = intval($inputData['stanby'] ?? 0);
            
            if ($negozioId <= 0) {
                http_response_code(400);
                echo json_encode(["error" => "ID negozio non valido"]);
                exit;
            }
            
            // Verifica che il negozio esista
            $negozioEsistente = $medooDB->has("negozi", ["id" => $negozioId]);
            if (!$negozioEsistente) {
                http_response_code(404);
                echo json_encode(["error" => "Negozio non trovato"]);
                exit;
            }
            
            // Aggiorna stato
            $result = $medooDB->update("negozi", [
                "stanby" => $nuovoStanby,
                "data_modifica" => date("Y-m-d H:i:s")
            ], ["id" => $negozioId]);
            
            if ($result->rowCount() === 0) {
                throw new Exception("Nessuna modifica apportata");
            }
            
            // Risposta di successo
            echo json_encode([
                "success" => true,
                "message" => "Stato negozio aggiornato con successo"
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Errore nel cambio stato: " . $e->getMessage()
            ]);
        }
        break;
    
    // Elimina negozio
    case 'delete_negozio':
        try {
            $negozioId = intval($inputData['id'] ?? 0);
            
            if ($negozioId <= 0) {
                http_response_code(400);
                echo json_encode(["error" => "ID negozio non valido"]);
                exit;
            }
            
            // Verifica che il negozio esista
            $negozio = $medooDB->get("negozi", ["id", "immagine"], ["id" => $negozioId]);
            if (!$negozio) {
                http_response_code(404);
                echo json_encode(["error" => "Negozio non trovato"]);
                exit;
            }
            
            // Inizia una transazione
            $medooDB->pdo->beginTransaction();
            
            try {
                // Elimina l'immagine se esiste
                if (!empty($negozio['immagine']) && file_exists("../" . $negozio['immagine'])) {
                    unlink("../" . $negozio['immagine']);
                }
                
                // Elimina il negozio
                $medooDB->delete("negozi", ["id" => $negozioId]);
                
                // Commit della transazione
                $medooDB->pdo->commit();
                
                // Risposta di successo
                echo json_encode([
                    "success" => true,
                    "message" => "Negozio eliminato con successo"
                ]);
                
            } catch (Exception $e) {
                // Rollback in caso di errore
                $medooDB->pdo->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Errore nell'eliminazione del negozio: " . $e->getMessage()
            ]);
        }
        break;
        
    // Nessuna azione riconosciuta
    default:
        http_response_code(400);
        echo json_encode(["error" => "Azione non riconosciuta: $action"]);
        break;
}
?> 