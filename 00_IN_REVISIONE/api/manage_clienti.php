<?php
/**
 * API per la gestione dell'anagrafica clienti manage_clienti.php
 * 
 * Questo file gestisce tutte le operazioni CRUD per l'anagrafica clienti
 */

// Carica Medoo
require "../includes/db.php";

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
error_log("API Clienti - Azione: $action - Dati: " . json_encode($inputData));

switch ($action) {
    
    // Elenco clienti con paginazione e filtri
    case 'list_clienti':
        try {
            $page = intval($inputData['page'] ?? 1);
            $perPage = intval($inputData['per_page'] ?? 50);
            $search = sanitizeInput($inputData['search'] ?? '');
            $filterPagamento = sanitizeInput($inputData['filter_pagamento'] ?? '');
            
            // Calcola l'offset
            $offset = ($page - 1) * $perPage;
            
            // Costruisci i filtri WHERE
            $where = [];
            
            if (!empty($search)) {
                $where["OR"] = [
                    "nome_referente[~]" => $search,
                    "cognome_referente[~]" => $search,
                    "nome_negozio[~]" => $search,
                    "email[~]" => $search
                ];
            }
            
            if (!empty($filterPagamento)) {
                $where["tipo_pagamento"] = $filterPagamento;
            }
            
            // Conta il totale risultati
            $totalCount = $medooDB->count("clienti", $where);
            
            // Recupera i clienti con paginazione
            $clienti = $medooDB->select(
                "clienti", 
                [
                    "id",
                    "nome_referente",
                    "cognome_referente",
                    "nome_negozio",
                    "data_apertura",
                    "email",
                    "indirizzo",
                    "whatsapp",
                    "avatar",
                    "tipo_pagamento",
                    "attivo",
                    "note_interne"
                ],
                array_merge($where, [
                    "ORDER" => ["nome_negozio" => "ASC"],
                    "LIMIT" => [$offset, $perPage]
                ])
            );
            
            // Per ogni cliente, recupera i suoi cataloghi
            foreach ($clienti as &$cliente) {
                $cliente['cataloghi'] = $medooDB->select(
                    "cataloghi",
                    [
                        "[>]cliente_catalogo" => ["id" => "catalogo_id"]
                    ],
                    [
                        "cataloghi.id",
                        "cataloghi.nome",
                        "cataloghi.descrizione"
                    ],
                    [
                        "cliente_catalogo.cliente_id" => $cliente['id']
                    ]
                );
            }
            
            // Risposta di successo
            echo json_encode([
                "success" => true,
                "data" => $clienti,
                "total" => $totalCount,
                "page" => $page,
                "per_page" => $perPage
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Errore nel recupero clienti: " . $e->getMessage()
            ]);
        }
        break;
    
    // Recupera dettagli di un singolo cliente
    case 'get_cliente':
        try {
            $id = intval($inputData['id'] ?? 0);
            
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(["error" => "ID cliente non valido"]);
                exit;
            }
            
            // Recupera il cliente
            $cliente = $medooDB->get(
                "clienti", 
                [
                    "id",
                    "nome_referente",
                    "cognome_referente",
                    "nome_negozio",
                    "data_apertura",
                    "email",
                    "indirizzo",
                    "whatsapp",
                    "avatar",
                    "tipo_pagamento",
                    "attivo",
                    "note_interne"
                ],
                ["id" => $id]
            );
            
            if (!$cliente) {
                http_response_code(404);
                echo json_encode(["error" => "Cliente non trovato"]);
                exit;
            }
            
            // Recupera i cataloghi associati
            $cliente['cataloghi'] = $medooDB->select(
                "cataloghi",
                [
                    "[>]cliente_catalogo" => ["id" => "catalogo_id"]
                ],
                [
                    "cataloghi.id",
                    "cataloghi.nome",
                    "cataloghi.descrizione"
                ],
                [
                    "cliente_catalogo.cliente_id" => $id
                ]
            );
            
            // Risposta di successo
            echo json_encode([
                "success" => true,
                "data" => $cliente
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Errore nel recupero cliente: " . $e->getMessage()
            ]);
        }
        break;
    
    // Aggiungi nuovo cliente
    case 'add_cliente':
        try {
            // Recupera e sanitizza i dati
            $clienteData = sanitizeInput($inputData['data'] ?? []);
            
            // Valida i campi obbligatori
            $requiredFields = ['nome_referente', 'cognome_referente', 'nome_negozio', 'email', 'password', 'data_apertura', 'tipo_pagamento'];
            foreach ($requiredFields as $field) {
                if (empty($clienteData[$field])) {
                    http_response_code(400);
                    echo json_encode(["error" => "Campo obbligatorio mancante: $field"]);
                    exit;
                }
            }
            
            // Verifica se l'email è già in uso
            $esistente = $medooDB->has("clienti", ["email" => $clienteData['email']]);
            if ($esistente) {
                http_response_code(409);
                echo json_encode(["error" => "Email già registrata"]);
                exit;
            }
            
            // Processa avatar se presente
            $avatarPath = null;
            if (!empty($clienteData['avatar_data'])) {
                // Estrai i dati base64
                if (preg_match('/^data:image\/(\w+);base64,/', $clienteData['avatar_data'], $matches)) {
                    $imageType = $matches[1];
                    $base64Data = substr($clienteData['avatar_data'], strpos($clienteData['avatar_data'], ',') + 1);
                    $decodedData = base64_decode($base64Data);
                    
                    if ($decodedData === false) {
                        throw new Exception("Errore nella decodifica dell'immagine");
                    }
                    
                    // Crea directory per gli avatar se non esiste
                    $uploadDir = '../uploads/avatars/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Genera un nome file unico
                    $fileName = uniqid() . '.' . $imageType;
                    $filePath = $uploadDir . $fileName;
                    
                    // Salva il file
                    if (file_put_contents($filePath, $decodedData)) {
                        $avatarPath = 'uploads/avatars/' . $fileName;
                    } else {
                        throw new Exception("Errore nel salvataggio dell'immagine");
                    }
                } else {
                    throw new Exception("Formato immagine non valido");
                }
            }
            
            // Prepara i dati per l'inserimento
            $insertData = [
                "nome_referente" => $clienteData['nome_referente'],
                "cognome_referente" => $clienteData['cognome_referente'],
                "nome_negozio" => $clienteData['nome_negozio'],
                "data_apertura" => $clienteData['data_apertura'],
                "email" => $clienteData['email'],
                "indirizzo" => $clienteData['indirizzo'] ?? '',
                "whatsapp" => $clienteData['whatsapp'] ?? '',
                "password" => password_hash($clienteData['password'], PASSWORD_DEFAULT),
                "tipo_pagamento" => $clienteData['tipo_pagamento'],
                "attivo" => intval($clienteData['attivo'] ?? 1),
                "note_interne" => $clienteData['note_interne'] ?? '',
                "data_creazione" => date("Y-m-d H:i:s")
            ];
            
            // Aggiungi path avatar se presente
            if ($avatarPath) {
                $insertData["avatar"] = $avatarPath;
            }
            
            // Inserisci il cliente
            $medooDB->insert("clienti", $insertData);
            $clienteId = $medooDB->id();
            
            if (!$clienteId) {
                throw new Exception("Errore nell'inserimento del cliente");
            }
            
            // Associa i cataloghi selezionati
            if (!empty($clienteData['cataloghi']) && is_array($clienteData['cataloghi'])) {
                foreach ($clienteData['cataloghi'] as $catalogoId) {
                    $medooDB->insert("cliente_catalogo", [
                        "cliente_id" => $clienteId,
                        "catalogo_id" => intval($catalogoId)
                    ]);
                }
            }
            
            // Risposta di successo
            echo json_encode([
                "success" => true,
                "message" => "Cliente aggiunto con successo",
                "id" => $clienteId
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Errore nell'aggiunta del cliente: " . $e->getMessage()
            ]);
        }
        break;
    
    // Aggiorna cliente esistente
    case 'update_cliente':
        try {
            // Recupera e sanitizza i dati
            $clienteData = sanitizeInput($inputData['data'] ?? []);
            $clienteId = intval($clienteData['id'] ?? 0);
            
            if ($clienteId <= 0) {
                http_response_code(400);
                echo json_encode(["error" => "ID cliente non valido"]);
                exit;
            }
            
            // Verifica che il cliente esista
            $clienteEsistente = $medooDB->has("clienti", ["id" => $clienteId]);
            if (!$clienteEsistente) {
                http_response_code(404);
                echo json_encode(["error" => "Cliente non trovato"]);
                exit;
            }
            
            // Verifica se l'email è già in uso da un altro cliente
            $altroClienteConStessaEmail = $medooDB->has("clienti", [
                "AND" => [
                    "email" => $clienteData['email'],
                    "id[!]" => $clienteId
                ]
            ]);
            
            if ($altroClienteConStessaEmail) {
                http_response_code(409);
                echo json_encode(["error" => "Email già in uso da un altro cliente"]);
                exit;
            }
            
            // Processa avatar se presente
            $avatarPath = null;
            if (!empty($clienteData['avatar_data'])) {
                // Estrai i dati base64
                if (preg_match('/^data:image\/(\w+);base64,/', $clienteData['avatar_data'], $matches)) {
                    $imageType = $matches[1];
                    $base64Data = substr($clienteData['avatar_data'], strpos($clienteData['avatar_data'], ',') + 1);
                    $decodedData = base64_decode($base64Data);
                    
                    if ($decodedData === false) {
                        throw new Exception("Errore nella decodifica dell'immagine");
                    }
                    
                    // Crea directory per gli avatar se non esiste
                    $uploadDir = '../uploads/avatars/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Genera un nome file unico
                    $fileName = uniqid() . '.' . $imageType;
                    $filePath = $uploadDir . $fileName;
                    
                    // Salva il file
                    if (file_put_contents($filePath, $decodedData)) {
                        $avatarPath = 'uploads/avatars/' . $fileName;
                        
                        // Elimina avatar precedente se esiste
                        $oldAvatar = $medooDB->get("clienti", "avatar", ["id" => $clienteId]);
						if ($oldAvatar && file_exists("../" . $oldAvatar)) {
                            unlink("../" . $oldAvatar);
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
                "nome_referente" => $clienteData['nome_referente'],
                "cognome_referente" => $clienteData['cognome_referente'],
                "nome_negozio" => $clienteData['nome_negozio'],
                "data_apertura" => $clienteData['data_apertura'],
                "email" => $clienteData['email'],
                "indirizzo" => $clienteData['indirizzo'] ?? '',
                "whatsapp" => $clienteData['whatsapp'] ?? '',
                "tipo_pagamento" => $clienteData['tipo_pagamento'],
                "attivo" => intval($clienteData['attivo'] ?? 1),
                "note_interne" => $clienteData['note_interne'] ?? '',
                "data_modifica" => date("Y-m-d H:i:s")
            ];
            
            // Aggiorna password solo se fornita
            if (!empty($clienteData['password'])) {
                $updateData["password"] = password_hash($clienteData['password'], PASSWORD_DEFAULT);
            }
            
            // Aggiungi path avatar se presente
            if ($avatarPath) {
                $updateData["avatar"] = $avatarPath;
            }
            
            // Aggiorna il cliente
            $result = $medooDB->update("clienti", $updateData, ["id" => $clienteId]);
            
            if ($result->rowCount() === 0 && count($updateData) > 1) {
                // Non ci sono stati aggiornamenti, ma non è necessariamente un errore
                // potrebbe semplicemente non essere cambiato nulla
                error_log("Nessuna modifica apportata al cliente ID: $clienteId");
            }
            
            // Aggiorna associazioni cataloghi
            if (isset($clienteData['cataloghi'])) {
                // Rimuovi tutte le associazioni esistenti
                $medooDB->delete("cliente_catalogo", ["cliente_id" => $clienteId]);
                
                // Inserisci le nuove associazioni
                if (is_array($clienteData['cataloghi']) && !empty($clienteData['cataloghi'])) {
                    foreach ($clienteData['cataloghi'] as $catalogoId) {
                        $medooDB->insert("cliente_catalogo", [
                            "cliente_id" => $clienteId,
                            "catalogo_id" => intval($catalogoId)
                        ]);
                    }
                }
            }
            
            // Risposta di successo
            echo json_encode([
                "success" => true,
                "message" => "Cliente aggiornato con successo"
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Errore nell'aggiornamento del cliente: " . $e->getMessage()
            ]);
        }
        break;
    
    // Cambia stato cliente (attivo/sospeso)
    case 'toggle_cliente_status':
        try {
            $clienteId = intval($inputData['id'] ?? 0);
            $nuovoStato = intval($inputData['attivo'] ?? 0);
            
            if ($clienteId <= 0) {
                http_response_code(400);
                echo json_encode(["error" => "ID cliente non valido"]);
                exit;
            }
            
            // Verifica che il cliente esista
            $clienteEsistente = $medooDB->has("clienti", ["id" => $clienteId]);
            if (!$clienteEsistente) {
                http_response_code(404);
                echo json_encode(["error" => "Cliente non trovato"]);
                exit;
            }
            
            // Aggiorna stato
            $result = $medooDB->update("clienti", [
                "attivo" => $nuovoStato,
                "data_modifica" => date("Y-m-d H:i:s")
            ], ["id" => $clienteId]);
            
            if ($result->rowCount() === 0) {
                throw new Exception("Nessuna modifica apportata");
            }
            
            // Risposta di successo
            echo json_encode([
                "success" => true,
                "message" => "Stato cliente aggiornato con successo"
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Errore nel cambio stato: " . $e->getMessage()
            ]);
        }
        break;
    
    // Reset password cliente
    case 'reset_password':
        try {
            $clienteId = intval($inputData['id'] ?? 0);
            $nuovaPassword = sanitizeInput($inputData['password'] ?? '');
            
            if ($clienteId <= 0) {
                http_response_code(400);
                echo json_encode(["error" => "ID cliente non valido"]);
                exit;
            }
            
            if (empty($nuovaPassword)) {
                http_response_code(400);
                echo json_encode(["error" => "Password non valida"]);
                exit;
            }
            
            // Verifica che il cliente esista
            $clienteEsistente = $medooDB->has("clienti", ["id" => $clienteId]);
            if (!$clienteEsistente) {
                http_response_code(404);
                echo json_encode(["error" => "Cliente non trovato"]);
                exit;
            }
            
            // Aggiorna password
            $result = $medooDB->update("clienti", [
                "password" => password_hash($nuovaPassword, PASSWORD_DEFAULT),
                "data_modifica" => date("Y-m-d H:i:s")
            ], ["id" => $clienteId]);
            
            if ($result->rowCount() === 0) {
                throw new Exception("Nessuna modifica apportata");
            }
            
            // Risposta di successo
            echo json_encode([
                "success" => true,
                "message" => "Password resettata con successo"
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Errore nel reset password: " . $e->getMessage()
            ]);
        }
        break;
    
    // Elimina cliente
    case 'delete_cliente':
        try {
            $clienteId = intval($inputData['id'] ?? 0);
            
            if ($clienteId <= 0) {
                http_response_code(400);
                echo json_encode(["error" => "ID cliente non valido"]);
                exit;
            }
            
            // Verifica che il cliente esista
            $cliente = $medooDB->get("clienti", ["id", "avatar"], ["id" => $clienteId]);
            if (!$cliente) {
                http_response_code(404);
                echo json_encode(["error" => "Cliente non trovato"]);
                exit;
            }
            
            // Inizia una transazione
            $medooDB->pdo->beginTransaction();
            
            try {
                // Elimina l'avatar se esiste
                if (!empty($cliente['avatar']) && file_exists("../" . $cliente['avatar'])) {
                    unlink("../" . $cliente['avatar']);
                }
                
                // Elimina le associazioni con i cataloghi
                $medooDB->delete("cliente_catalogo", ["cliente_id" => $clienteId]);
                
                // Elimina il cliente
                $medooDB->delete("clienti", ["id" => $clienteId]);
                
                // Commit della transazione
                $medooDB->pdo->commit();
                
                // Risposta di successo
                echo json_encode([
                    "success" => true,
                    "message" => "Cliente eliminato con successo"
                ]);
                
            } catch (Exception $e) {
                // Rollback in caso di errore
                $medooDB->pdo->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Errore nell'eliminazione del cliente: " . $e->getMessage()
            ]);
        }
        break;
    
    // Elenco cataloghi disponibili
    case 'list_cataloghi':
        try {
            // Recupera tutti i cataloghi
            $cataloghi = $medooDB->select(
                "cataloghi",
                [
                    "id",
                    "nome",
                    "descrizione"
                ],
                ["ORDER" => ["nome" => "ASC"]]
            );
            
            // Risposta di successo
            echo json_encode([
                "success" => true,
                "data" => $cataloghi
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Errore nel recupero cataloghi: " . $e->getMessage()
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