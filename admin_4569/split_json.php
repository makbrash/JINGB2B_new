// CREA UN NUOVO FILE: split_json.php
<?php
// Imposta i limiti PHP
ini_set('max_execution_time', 600);
ini_set('memory_limit', '1024M');

// Funzione per dividere un grande file JSON in più parti
function splitJsonFile($sourceFile, $outputDir, $maxItemsPerFile = 500) {
    if (!file_exists($sourceFile)) {
        return ['success' => false, 'error' => 'File sorgente non trovato'];
    }
    
    // Crea la directory di output se non esiste
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }
    
    // Leggi il file JSON un pezzo alla volta
    $handle = fopen($sourceFile, 'r');
    if (!$handle) {
        return ['success' => false, 'error' => 'Impossibile aprire il file JSON'];
    }
    
    // Leggi il primo carattere per determinare se è un array
    $char = '';
    while (!feof($handle) && trim($char) === '') {
        $char = fread($handle, 1);
    }
    
    if ($char !== '[') {
        fclose($handle);
        return ['success' => false, 'error' => 'Il file JSON deve iniziare con un array ([)'];
    }
    
    // Torna all'inizio del file
    fseek($handle, 0);
    
    // Leggi tutto il file in memoria (per file grandi questo è rischioso ma necessario)
    $content = fread($handle, filesize($sourceFile));
    fclose($handle);
    
    // Decodifica il JSON
    $items = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Errore nella decodifica JSON: ' . json_last_error_msg()];
    }
    
    // Libera memoria
    $content = null;
    
    // Dividi in parti
    $chunks = array_chunk($items, $maxItemsPerFile);
    $items = null; // Libera memoria
    
    $files = [];
    $chunkNumber = 1;
    
    foreach ($chunks as $chunk) {
        $outputFile = $outputDir . '/part_' . $chunkNumber . '.json';
        file_put_contents($outputFile, json_encode($chunk));
        $files[] = $outputFile;
        $chunkNumber++;
    }
    
    return [
        'success' => true,
        'files' => $files,
        'count' => count($files)
    ];
}

// Se il file viene chiamato direttamente, esegui la divisione
if (basename($_SERVER['PHP_SELF']) === 'split_json.php' && isset($_GET['file'])) {
    header('Content-Type: application/json');
    
    $sourceFile = '../00_IN_REVISIONE/dati/uploads/' . basename($_GET['file']);
    $outputDir = '../00_IN_REVISIONE/dati/splits';
    $maxItems = isset($_GET['items']) ? intval($_GET['items']) : 500;
    
    $result = splitJsonFile($sourceFile, $outputDir, $maxItems);
    echo json_encode($result);
}
?>