<?php
// Previeni qualsiasi output prima degli header
ob_start();

// Disabilita temporaneamente la visualizzazione degli errori
error_reporting(0);
ini_set('display_errors', 0);

// Imposta error handler personalizzato
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    return true;
});

session_start();

// Includi le librerie necessarie

require "../config/config.php"; // Carica Medoo
use PhpOffice\PhpSpreadsheet\IOFactory;


// Includi le funzioni necessarie
require_once "includes/sincronizza/excel_functions.php";
require_once "includes/sincronizza/sync_functions.php";

// Imposta header per JSON e CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Pulisci qualsiasi output precedente
ob_clean();

// Inizializza directories con gestione errori
$tempDir = __DIR__ . "/temp";
$backupDir = __DIR__ . "/../public/aggiornamenti";

try {
    // Crea directories se non esistono
    foreach ([$tempDir, $backupDir] as $dir) {
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0777, true)) {
                throw new Exception("Impossibile creare la directory: $dir");
            }
            if (!@chmod($dir, 0777)) {
                throw new Exception("Impossibile impostare i permessi per: $dir");
            }
        }
    }

    // Verifica che sia una richiesta POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Metodo non consentito');
    }

    // Gestione richieste API
    $action = $_POST['action'] ?? '';
    
    error_log("DEBUG: Azione richiesta: " . $action);
    error_log("DEBUG: POST data: " . print_r($_POST, true));
    error_log("DEBUG: FILES data: " . print_r($_FILES, true));

    switch ($action) {
        case 'upload':
            // Upload file Excel
            $result = uploadExcelFile($tempDir);
            if (!$result['success']) {
                throw new Exception($result['message']);
            }

            // Leggi anteprima
            $preview = readExcelPreview($result['file_path']);
            if (!$preview['success']) {
                throw new Exception($preview['message']);
            }

            // Rileva colonne
            $mappings = detectColumnTypes($preview['data']);
            
            // Salva in sessione
            $_SESSION['excel_file_path'] = $result['file_path'];
            $_SESSION['excel_file_name'] = $result['file_name'];
            $_SESSION['excel_preview'] = $preview['data'];
            $_SESSION['suggested_mappings'] = $mappings;
            
            echo json_encode([
                'success' => true,
                'preview' => $preview['data'],
                'columns' => $preview['columns'],
                'mappings' => $mappings
            ]);
        break;

    case 'process':
        // Verifica dati necessari
        if (!isset($_SESSION['excel_file_path']) || !file_exists($_SESSION['excel_file_path'])) {
            throw new Exception('File Excel non trovato. Ricarica il file.');
        }

        if (!isset($_POST['mappings'])) {
            throw new Exception('Mappatura colonne mancante.');
        }

        // Decodifica mappatura
        $mappings = json_decode($_POST['mappings'], true);
        if (!$mappings) {
            throw new Exception('Mappatura colonne non valida.');
        }

        // Ottieni opzioni aggiuntive
        $negozioid = isset($_POST['negozio_id']) ? (int)$_POST['negozio_id'] : null;
        $isPromo = isset($_POST['is_promo']) ? (bool)$_POST['is_promo'] : false;
        
        // Verifica che sia specificato un negozio
        if (!$negozioid) {
            throw new Exception('Seleziona un negozio prima di procedere.');
        }

        // Processa file con le opzioni aggiuntive
        $skipRows = isset($_POST['skip_rows']) ? (int)$_POST['skip_rows'] : 1;
        $result = processExcelFile($_SESSION['excel_file_path'], $mappings, $skipRows, $negozioid, $isPromo);

        if (!$result['success']) {
            throw new Exception($result['message']);
        }

        $_SESSION['sync_results'] = $result['results'];
        echo json_encode([
            'success' => true,
            'results' => $result['results']
        ]);
        break;

    default:
        throw new Exception('Azione non valida');
    }
} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    // Assicurati che l'output sia inviato e pulito
    ob_end_flush();
}
?>