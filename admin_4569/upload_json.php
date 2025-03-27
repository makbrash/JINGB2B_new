<?php
// Imposta gli header per la risposta JSON
header('Content-Type: application/json');

// Aumenta i limiti PHP per gestire file grandi
ini_set('max_execution_time', 300); // 5 minuti
ini_set('memory_limit', '512M');    // 512 MB
ini_set('post_max_size', '150M');   // Limite massimo POST a 150MB
ini_set('upload_max_filesize', '150M'); // Dimensione massima upload

// Cartella per il caricamento
$uploadDir = '../00_IN_REVISIONE/dati/uploads/';

// Controlla se la cartella esiste, altrimenti creala
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Verifica se è stato inviato un file
if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
    $error = 'Nessun file caricato';
    
    // Diagnostica l'errore specifico di upload
    if (isset($_FILES['json_file']['error'])) {
        $uploadErrors = array(
            UPLOAD_ERR_INI_SIZE => 'Il file supera il limite di dimensione impostato in php.ini (upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'Il file supera il limite di dimensione specificato nel form HTML',
            UPLOAD_ERR_PARTIAL => 'Il file è stato caricato solo parzialmente',
            UPLOAD_ERR_NO_FILE => 'Nessun file è stato caricato',
            UPLOAD_ERR_NO_TMP_DIR => 'Cartella temporanea mancante',
            UPLOAD_ERR_CANT_WRITE => 'Impossibile scrivere il file su disco',
            UPLOAD_ERR_EXTENSION => 'Una estensione PHP ha bloccato il caricamento del file'
        );
        
        $errorCode = $_FILES['json_file']['error'];
        if (isset($uploadErrors[$errorCode])) {
            $error = $uploadErrors[$errorCode];
        }
    }
    
    echo json_encode([
        'success' => false,
        'error' => $error
    ]);
    exit;
}

// Ottieni i dettagli del file
$file = $_FILES['json_file'];
$fileName = $file['name'];
$fileTmpPath = $file['tmp_name'];
$fileSize = $file['size'];
$fileType = $file['type'];

// Controlla estensione e tipo di file
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
if ($fileExtension !== 'json' || ($fileType !== 'application/json' && strpos($fileType, 'json') === false)) {
    echo json_encode([
        'success' => false,
        'error' => 'Il file deve essere in formato JSON.'
    ]);
    exit;
}

// Controlla dimensione file (max 150MB)
$maxFileSize = 150 * 1024 * 1024; // 150MB
if ($fileSize > $maxFileSize) {
    echo json_encode([
        'success' => false,
        'error' => 'Il file è troppo grande. Dimensione massima consentita: 150MB.'
    ]);
    exit;
}

// Genera un nome univoco per il file
$newFileName = uniqid('catalog_') . '.json';
$targetFilePath = $uploadDir . $newFileName;

// Sposta il file nella cartella di destinazione
if (move_uploaded_file($fileTmpPath, $targetFilePath)) {
    // Per file grandi, non verifichiamo subito la validità del JSON ma controlliamo solo la struttura di base
    // per evitare sovraccarico di memoria
    
    // Leggi solo l'inizio del file per verificare se inizia con una parentesi quadra o graffa
    $handle = fopen($targetFilePath, 'r');
    if ($handle) {
        // Ignora gli spazi e i caratteri di nuova riga
        $char = '';
        while (!feof($handle) && trim($char) === '') {
            $char = fread($handle, 1);
        }
        fclose($handle);
        
        // Controlla se il primo carattere valido è una parentesi quadra o graffa
        if ($char !== '{' && $char !== '[') {
            // Rimuovi il file se non sembra un JSON valido
            unlink($targetFilePath);
            
            echo json_encode([
                'success' => false,
                'error' => 'Il file caricato non sembra essere un JSON valido'
            ]);
            exit;
        }
    }
    
    // Ottieni il conteggio approssimativo degli elementi (solo per file di dimensioni ragionevoli)
    $itemsCount = 'Conteggio elementi non disponibile per file grandi';
    if ($fileSize < 10 * 1024 * 1024) { // Solo se meno di 10MB
        try {
            $jsonContent = file_get_contents($targetFilePath);
            $decoded = json_decode($jsonContent, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $itemsCount = count($decoded);
            }
        } catch (Exception $e) {
            // Ignora errori, usa il valore di default
        }
    }
    
    // Formatta la dimensione del file per la visualizzazione
    $formattedSize = formatFileSize($fileSize);
    
    echo json_encode([
        'success' => true,
        'filename' => $newFileName,
        'path' => $targetFilePath,
        'size' => $fileSize,
        'formatted_size' => $formattedSize,
        'items_count' => $itemsCount
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Errore nel caricamento del file. Verifica i permessi della cartella.'
    ]);
}

// Funzione per formattare le dimensioni del file
function formatFileSize($bytes) {
    if ($bytes < 1024) {
        return $bytes . ' bytes';
    } else if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return round($bytes / (1024 * 1024), 2) . ' MB';
    }
}
?>