<?php
/// manage_request.php
// Abilita la visualizzazione degli errori (solo in sviluppo)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Carica Medoo
require "../config/config.php";

// Imposta gli header per JSON e sicurezza
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

// Protezione API con Token
$token_valid = TUO_TOKEN_API; // il tuo token

// Recupera il token dalla richiesta
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!$authHeader || $authHeader !== "Bearer $token_valid") {
    http_response_code(403);
    echo json_encode(["error" => "Accesso non autorizzato"]);
    exit;
}

// Verifica il metodo di richiesta
$method = $_SERVER['REQUEST_METHOD'];

// Legge il body JSON una sola volta
$inputData = json_decode(file_get_contents("php://input"), true);

// --- Rimuovi o commenta questi debug che bloccano l’esecuzione ---
// var_dump($_GET, $_PUT);
// exit;

switch ($method) {

    case 'GET':
        // Se gestisci ancora GET (es. per test veloce da browser), 
        // qui puoi pescare i parametri da $_GET
        // Esempio: ?action=list
        $action = $_GET['action'] ?? '';
        switch ($action) {
            case 'list':
                $records = $medooDB->select("prodotti", "*", ['ORDER' => 'titolo']);
                http_response_code(200);
                echo json_encode($records);
                break;

            case 'count':
                $total = $medooDB->count("prodotti");
                http_response_code(200);
                echo json_encode(["total" => $total]);
                break;

            // ... eventuali altre azioni GET ...

            default:
                http_response_code(400);
                echo json_encode(["error" => "Azione GET non valida"]);
                break;
        }
        break;

    case 'POST':
        // Qui gestisci tutte le azioni inviate in POST
        // Il tuo JavaScript fa data: { action: 'list' }, 
        // quindi la trovi in $inputData
        if (!$inputData) {
            http_response_code(400);
            echo json_encode(["error" => "Formato JSON non valido"]);
            exit;
        }

        $action = $inputData['action'] ?? '';
        switch ($action) {

            case 'list':
                // Esempio: restituisci l’elenco di prodotti
                $records = $medooDB->select("prodotti", "*");
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "data" => $records
                ]);
                break;

            case 'count':
                $total = $medooDB->count("prodotti");
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "total" => $total
                ]);
                break;
            case 'getCategoriesHierarchy':
                try {
                    // Ottieni tutte le categorie attive
                    $categories = $medooDB->select(
                        "categoria",
                        ["id", "nome", "titolo", "ordinamento"],
                        [
                            "standby" => 0,
                            "ORDER" => ["ordinamento" => "ASC"]
                        ]
                    );

                    // Ottieni tutte le sottocategorie attive
                    $subcategories = $medooDB->select(
                        "sottocategoria",
                        ["id", "nome", "titolo", "categoria_id", "ordinamento"],
                        [
                            "standby" => 0,
                            "ORDER" => ["ordinamento" => "ASC"]
                        ]
                    );

                    // Organizza le sottocategorie per categoria
                    $hierarchy = [];
                    foreach ($categories as $category) {
                        $category['subcategories'] = [];
                        $hierarchy[$category['id']] = $category;
                    }

                    // Aggiungi sottocategorie alle rispettive categorie
                    foreach ($subcategories as $subcategory) {
                        $catId = $subcategory['categoria_id'];
                        if (isset($hierarchy[$catId])) {
                            $hierarchy[$catId]['subcategories'][] = $subcategory;
                        }
                    }

                    http_response_code(200);
                    echo json_encode([
                        "success" => true,
                        "data" => array_values($hierarchy) // Converti in array numerico
                    ]);
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode([
                        "success" => false,
                        "error" => "Errore nel recupero della gerarchia: " . $e->getMessage()
                    ]);
                }
                break;
            // Qui puoi aggiungere tutte le altre azioni che vuoi gestire via POST

            default:
                http_response_code(400);
                echo json_encode(["error" => "Azione POST non valida"]);
                break;
        }
        break;

    case 'PUT':
        // Se vuoi gestire l’aggiornamento di un record
        // Esempio (commentato nella tua bozza):
        /*
        if (!$inputData) {
            http_response_code(400);
            echo json_encode(["error" => "Formato JSON non valido"]);
            exit;
        }

        $request_id = intval($inputData['request_id'] ?? 0);
        $stato = trim($inputData['stato'] ?? '');

        if ($request_id <= 0 || empty($stato)) {
            http_response_code(400);
            echo json_encode(["error" => "Dati mancanti o non validi"]);
            exit;
        }

        try {
            $update = $medooDB->update("requests", [

                "stato" => $stato
            ], ["id" => $request_id]);

            if ($update->rowCount() > 0) {
                http_response_code(200);
                echo json_encode(["success" => "Richiesta aggiornata"]);
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Nessuna richiesta trovata con ID $request_id"]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Errore interno: " . $e->getMessage()]);
        }
        */
        break;

    default:
        // Se arriva un metodo che non è GET, POST o PUT
        http_response_code(405);
        echo json_encode(["error" => "Metodo non supportato"]);
        break;
}
