<?php
/**
 * classificatore_ajax.php - Gestore delle richieste AJAX
 * 
 * Questo file gestisce tutte le richieste AJAX per il sistema di classificazione,
 * processando le azioni richieste e restituendo i risultati in formato JSON.
 * 
 * @author Marco Vitaletti
 * @version 1.0
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Se è una richiesta OPTIONS, termina qui
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}
// Configurazione iniziale
ini_set('display_errors', 0); // Nascondi gli errori in output
error_reporting(E_ALL & ~E_DEPRECATED);

/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/


// Imposta il content type a JSON
header('Content-Type: application/json');

// Carica le dipendenze
require_once "../includes/db.php"; // Carica Medoo
require_once "classificatore_backend.php"; // Carica logica backend

// Ottieni l'azione richiesta
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Inizializza la risposta
$response = [
    'success' => false,
    'message' => 'Nessuna azione specificata',
    'data' => null
];

// Gestisci l'azione richiesta
switch ($action) {
case 'get_products':
    // Ottieni parametri 
    $range_start = isset($_POST['range_start']) ? intval($_POST['range_start']) : 0;
    $range_end = isset($_POST['range_end']) ? intval($_POST['range_end']) : 0;
    $stato_tag = isset($_POST['stato_tag']) && $_POST['stato_tag'] !== '' ? intval($_POST['stato_tag']) : null;
    $categoria_id = isset($_POST['categoria_id']) && $_POST['categoria_id'] !== '' ? intval($_POST['categoria_id']) : 0;
    $sottocategoria_id = isset($_POST['sottocategoria_id']) && $_POST['sottocategoria_id'] !== '' ? intval($_POST['sottocategoria_id']) : 0;
    $marca_id = isset($_POST['marca_id']) && $_POST['marca_id'] !== '' ? intval($_POST['marca_id']) : 0;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
    
    // Opzioni di selezione
    $options = ['limit' => $limit];
    
    if ($range_start > 0 && $range_end > 0) {
        $options['range_start'] = $range_start;
        $options['range_end'] = $range_end;
    }
    
    if ($stato_tag !== null) {
        $options['stato_tag'] = $stato_tag;
    }
    
    if ($categoria_id > 0) {
        $options['categoria_id'] = $categoria_id;
    }
    
    if ($sottocategoria_id > 0) {
        $options['sottocategoria_id'] = $sottocategoria_id;
    }
    
    if ($marca_id > 0) {
        $options['marca_id'] = $marca_id;
    }
    
    // Ottieni i prodotti
    $products = ottieni_prodotti($options);
    
    // Log per debug
    file_put_contents('debug_ajax.log', 'Prodotti trovati: ' . count($products) . "\n", FILE_APPEND);
    
    // Resto del codice...
        
        // Prepara i dati per frontend
        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product['id'],
                'titolo' => $product['titolo'],
                'immagine' => $product['immagine'],
                'categoria_id' => $product['categoria_id'],
                'sottocategoria_id' => $product['sottocategoria_id'],
                'marca_id' => $product['marca_id'],
                'categoria_nome' => get_name_by_id($product['categoria_id'], 'categoria'),
                'sottocategoria_nome' => get_name_by_id($product['sottocategoria_id'], 'sottocategoria'),
                'marca_nome' => get_name_by_id($product['marca_id'], 'marche'),
                'tags' => json_decode($product['tags'] ?: '[]', true),
                'stato_tag' => $product['stato_tag']
            ];
        }
        
        $response = [
            'success' => true,
            'message' => count($data) . ' prodotti trovati',
            'data' => $data
        ];
        break;
        
    case 'classify_product':
        // Ottieni ID prodotto
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if ($product_id > 0) {
            // Ottieni prodotto
            $products = ottieni_prodotti(['ids' => [$product_id]]);
            
            if (!empty($products)) {
                // Classifica il prodotto
                $result = classifica_prodotto($products[0]);
                
                $response = [
                    'success' => ($result['stato'] == 'successo'),
                    'message' => $result['messaggio'],
                    'data' => $result
                ];
            } else {
                $response['message'] = 'Prodotto non trovato';
            }
        } else {
            $response['message'] = 'ID prodotto non valido';
        }
        break;

	case 'manual_classify':
		// Ottieni parametri
		$product_ids = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];
		$update_fields = isset($_POST['update_fields']) ? $_POST['update_fields'] : [];
		
		if (empty($product_ids)) {
			$response['message'] = 'Nessun prodotto selezionato';
		} else {
			// Aggiorna prodotti
			$result = aggiorna_classificazione_manuale($product_ids, $update_fields);
			
			$response = [
				'success' => $result['success'],
				'message' => $result['message'],
				'data' => [
					'count' => isset($result['count']) ? $result['count'] : 0
				]
			];
		}
		break;
	
    case 'batch_classify':
        // Ottieni parametri
        $product_ids = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];
        $range_start = isset($_POST['range_start']) ? intval($_POST['range_start']) : 0;
        $range_end = isset($_POST['range_end']) ? intval($_POST['range_end']) : 0;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        
        // Opzioni di selezione
        $options = [
            'limit' => $limit,
            'stato_tag' => 0 // Solo prodotti non elaborati
        ];
        
        // Filtro per IDs specifici o range
        if (!empty($product_ids)) {
            $options['ids'] = array_map('intval', $product_ids);
        } elseif ($range_start > 0 && $range_end > 0) {
            $options['range_start'] = $range_start;
            $options['range_end'] = $range_end;
        }
        
        // Elabora batch
        $result = elabora_batch_prodotti($options);
        
        $response = [
            'success' => true,
            'message' => 'Elaborazione batch completata: ' . $result['successo'] . ' successi, ' . $result['errore'] . ' errori',
            'data' => $result
        ];
        break;
        
    case 'reset_tags':
        // Ottieni parametri
        $product_ids = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];
        $range_start = isset($_POST['range_start']) ? intval($_POST['range_start']) : 0;
        $range_end = isset($_POST['range_end']) ? intval($_POST['range_end']) : 0;
        
        // Opzioni di reset
        $options = [];
        
        // Filtro per IDs specifici o range
        if (!empty($product_ids)) {
            $options['ids'] = array_map('intval', $product_ids);
        } elseif ($range_start > 0 && $range_end > 0) {
            $options['range_start'] = $range_start;
            $options['range_end'] = $range_end;
        }
        
        // Esegui reset
        $count = resetta_stato_tag($options);
        
        $response = [
            'success' => true,
            'message' => $count . ' prodotti resettati',
            'data' => [
                'count' => $count
            ]
        ];
        break;
        
    case 'get_stats':
        // Ottieni statistiche
        $stats = ottieni_statistiche();
        
        $response = [
            'success' => true,
            'message' => 'Statistiche recuperate',
            'data' => $stats
        ];
        break;
        
    case 'search_products':
        // Ottieni parametri
        $query = isset($_POST['query']) ? $_POST['query'] : '';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        
        if (!empty($query)) {
            // Esegui ricerca
            $products = ricerca_prodotti($query, $limit);
            
            // Prepara i dati per frontend
            $data = [];
            foreach ($products as $product) {
                $data[] = [
                    'id' => $product['id'],
                    'titolo' => $product['titolo'],
                    'immagine' => $product['immagine'],
                    'categoria_id' => $product['categoria_id'],
                    'sottocategoria_id' => $product['sottocategoria_id'],
                    'marca_id' => $product['marca_id'],
                    'categoria_nome' => get_name_by_id($product['categoria_id'], 'categoria'),
                    'sottocategoria_nome' => get_name_by_id($product['sottocategoria_id'], 'sottocategoria'),
                    'marca_nome' => get_name_by_id($product['marca_id'], 'marche'),
                    'tags' => json_decode($product['tags'] ?: '[]', true),
                    'stato_tag' => $product['stato_tag']
                ];
            }
            
            $response = [
                'success' => true,
                'message' => count($data) . ' prodotti trovati',
                'data' => $data
            ];
        } else {
            $response['message'] = 'Nessuna query di ricerca specificata';
        }
        break;
        
    case 'get_selectors':
        // Carica i dati delle categorie, sottocategorie e marche
        $data = carica_dati_classificazione();
        
        // Prepara i dati per i selettori
        $selectors = [
            'categorie' => [],
            'sottocategorie' => [],
            'marche' => []
        ];
        
        // Prepara le categorie
        foreach ($data['cat_id_by_name'] as $nome => $id) {
            $selectors['categorie'][] = [
                'id' => $id,
                'nome' => $nome
            ];
        }
        
        // Prepara le sottocategorie
        foreach ($data['subcat_id_by_name'] as $nome => $info) {
            $selectors['sottocategorie'][] = [
                'id' => $info['id'],
                'categoria_id' => $info['categoria_id'],
                'nome' => $nome
            ];
        }
        
        // Prepara le marche
        foreach ($data['marca_id_by_name'] as $nome => $id) {
            $selectors['marche'][] = [
                'id' => $id,
                'nome' => $nome
            ];
        }
        
        // Ordina per nome
        usort($selectors['categorie'], function($a, $b) {
            return strcmp($a['nome'], $b['nome']);
        });
        
        usort($selectors['sottocategorie'], function($a, $b) {
            return strcmp($a['nome'], $b['nome']);
        });
        
        usort($selectors['marche'], function($a, $b) {
            return strcmp($a['nome'], $b['nome']);
        });
        
        $response = [
            'success' => true,
            'message' => 'Selettori recuperati',
            'data' => $selectors
        ];
        break;
    case 'get_subcategory_stats':
        // Ottieni statistiche sottocategorie
        $stats = ottieni_statistiche_sottocategorie();
        
        $response = [
            'success' => true,
            'message' => 'Statistiche sottocategorie recuperate',
            'data' => $stats
        ];
        break;
    default:
        $response['message'] = 'Azione non supportata: ' . $action;
}

// Restituisci la risposta
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;