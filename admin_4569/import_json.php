<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 300); // Aumenta il tempo massimo di esecuzione a 5 minuti
ini_set('memory_limit', '512M');    // Aumenta il limite di memoria

require "../config/config.php";

// Imposta l'header per lo streaming
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
ob_start(); // Assicurati che il buffer sia attivo
ob_implicit_flush(true);

// MODIFICA in import_json.php
function emitLog($message) {
    echo $message . "\n";
    // Importante: usare ob_flush() e flush() per garantire l'invio immediato
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

// MODIFICA agli script js nei log
function emitProgressUpdate($progressBar, $value, $count, $total) {
    static $lastUpdates = [];
    $key = $progressBar . $count;
    
    // Aggiorna solo ogni 5% di avanzamento per ridurre il sovraccarico
    $percent = round(($count / $total) * 100);
    $lastPercent = isset($lastUpdates[$key]) ? $lastUpdates[$key] : -1;
    
    if ($percent != $lastPercent && ($percent % 5 == 0 || $count == $total || $count == 1)) {
        // Correzione: usa <script> tag con attributo type corretto
        emitLog('<div class="d-none" id="js-progress-update"><script type="text/javascript">
            document.getElementById("'.$progressBar.'").style.width = "'.$percent.'%";
            document.getElementById("'.$progressBar.'").textContent = "'.$percent.'%";
        </script></div>');
        $lastUpdates[$key] = $percent;
    }
}


// Gestione parametri
$useDefault = isset($_GET['use_default']) && $_GET['use_default'] === '1';
$overwriteImages = isset($_GET['overwrite_images']) && $_GET['overwrite_images'] === '1';
$skipExisting = isset($_GET['skip_existing']) && $_GET['skip_existing'] === '1';
$customFile = isset($_GET['file']) ? $_GET['file'] : null;

// Determina il percorso del file JSON
if ($useDefault || !$customFile) {
    $jsonFile = '../00_IN_REVISIONE/dati/catalogo.json';
    emitLog("<div class='text-info'><i class='ti ti-info-circle'></i> Utilizzo del file predefinito: catalogo.json</div>");
} else {
    $jsonFile = '../00_IN_REVISIONE/dati/uploads/' . basename($customFile);
    emitLog("<div class='text-info'><i class='ti ti-info-circle'></i> Utilizzo del file caricato: " . basename($customFile) . "</div>");
}

// Verifica esistenza file
if (!file_exists($jsonFile)) {
    emitLog("<div class='text-danger'><i class='ti ti-alert-circle'></i> Il file JSON non esiste: $jsonFile</div>");
    die();
}

// Leggi il contenuto del JSON
$jsonData = "";
$handle = fopen($jsonFile, "r");
if ($handle) {
    while (!feof($handle)) {
        $jsonData .= fread($handle, 8192); // Leggi a blocchi di 8KB
    }
    fclose($handle);
}

// Debug: verifica il contenuto del JSON
emitLog("<div class='text-info'>Contenuto JSON caricato: " . substr($jsonData, 0, 100) . "...</div>");

$prodotti = json_decode($jsonData, true);
$jsonData = null; // Libera memoria

if (!$prodotti) {
    emitLog("<div class='text-danger'><i class='ti ti-alert-circle'></i> Errore nella lettura del file JSON: " . json_last_error_msg() . "</div>");
    die();
}

// Mostra il totale degli elementi
$totaleProdotti = count($prodotti);
emitLog("<div class='text-primary'><i class='ti ti-file'></i> Totale prodotti nel JSON: <strong>$totaleProdotti</strong></div>");

// Aggiorna contatore totale prodotti
emitLog("<script>document.getElementById('total-products').textContent = '$totaleProdotti';</script>");

// Inizializza i contatori
$processedCount = $insertedCount = $updatedCount = $skippedCount = $errorCount = $imagesSaved = $imagesMissing = $imagesError = 0;

// Percorso della cartella immagini
$imgDir = __DIR__ . "/../public/catalogo/";

// Crea la cartella immagini se non esiste
if (!is_dir($imgDir)) {
    mkdir($imgDir, 0777, true);
    emitLog("<div class='text-info'><i class='ti ti-folder'></i> Creata cartella immagini: " . basename($imgDir) . "</div>");
}

// Ottimizzazione: Dimensione batch aumentata per file grandi
$batchSize = 50;  // Aumentato da 10 a 50
$totalBatches = ceil($totaleProdotti / $batchSize);
$currentBatch = 0;

// Suddividi i prodotti in batch
$productBatches = array_chunk($prodotti, $batchSize);
$prodotti = null; // Libera memoria

// Informazioni sul batch
emitLog("<div class='text-info'><i class='ti ti-packages'></i> Elaborazione in $totalBatches pacchetti di $batchSize elementi ciascuno</div>");

// Impostazione per limitare aggiornamenti immagini
$imageUpdateFrequency = max(1, round($totaleProdotti / 100));

// Elabora i prodotti in batch
foreach ($productBatches as $batch) {
    $currentBatch++;
    
    // Aggiorna meno frequentemente
    if ($currentBatch == 1 || $currentBatch == $totalBatches || $currentBatch % 5 == 0) {
        $batchProgress = round(($currentBatch / $totalBatches) * 100);
        emitLog("<div class='text-info'><i class='ti ti-package'></i> Elaborazione pacchetto $currentBatch di $totalBatches</div>");
        
        // Aggiorna la progress bar principale
        emitLog("<script>
            document.getElementById('import-progress').style.width = '$batchProgress%';
            document.getElementById('import-progress').textContent = '$batchProgress%';
        </script>");
    }
    
    // Inizializza contatori per questo batch
    $batchInserted = $batchUpdated = $batchSkipped = $batchErrors = 0;
    $batchImagesSaved = $batchImagesMissing = $batchImagesError = 0;
    
    // Processa ogni prodotto nel batch
    foreach ($batch as $prodotto) {
        $processedCount++;
        
        // Aggiornamenti meno frequenti dell'UI per ridurre sovraccarico
        emitProgressUpdate('insert-progress', $processedCount, $processedCount, $totaleProdotti);
        
        // Controlla se l'EAN è presente e valido
        if (!isset($prodotto['ean']) || empty($prodotto['ean'])) {
            if ($processedCount % 20 == 0 || $processedCount == 1) {
                emitLog("<div class='text-warning'><i class='ti ti-alert-triangle'></i> Prodotto senza EAN: {$prodotto['titolo']}</div>");
            }
            $skippedCount++;
            $batchSkipped++;
            continue;
        }
        
        // Estrai dati dal JSON
        $titolo = $prodotto['titolo'];
        $ean = trim($prodotto['ean']);
        $prezzo = str_replace(" €", "", $prodotto['prezzo']);
        $prezzo = number_format((float)str_replace(",", ".", $prezzo), 2, ".", "");
        $disponibilita = intval($prodotto['disponibilita']);
        $pezzi = isset($prodotto['pezzi']) ? preg_replace('/\D/', '', $prodotto['pezzi']) : 1;
        $pezzi = intval($pezzi);
        
        $immagineBase64 = $prodotto['immagine'] ?? null;
        
        try {
            // Controlla se il prodotto esiste già
            $prodottoEsistente = $medooDB->get("prodotti", 
                ["titolo", "prezzo", "disponibilita", "pezzi"],
                ["ean" => $ean]
            );
            
            if ($prodottoEsistente) {
                // Se è attiva l'opzione "salta esistenti", vai al prossimo prodotto
                if ($skipExisting) {
                    $skippedCount++;
                    $batchSkipped++;
                    
                    // Riduci i log per prodotti saltati
                    if ($processedCount % 50 == 0 || $batchSkipped == 1) {
                        emitLog("<div class='text-info'><i class='ti ti-skip-forward'></i> Saltati prodotti esistenti: $batchSkipped finora in questo batch</div>");
                    }
                    continue;
                }
                
                // Verifica se ci sono modifiche
                $modifiche = [];
                if ($prodottoEsistente["titolo"] !== $titolo) {
                    $modifiche[] = "titolo";
                }
                if ($prodottoEsistente["prezzo"] != $prezzo) {
                    $modifiche[] = "prezzo";
                }
                if ($prodottoEsistente["disponibilita"] != $disponibilita) {
                    $modifiche[] = "disponibilità";
                }
                if ($prodottoEsistente["pezzi"] != $pezzi) {
                    $modifiche[] = "pezzi";
                }
                
                if (!empty($modifiche)) {
                    $medooDB->update("prodotti", [
                        "titolo" => $titolo,
                        "prezzo" => $prezzo,
                        "disponibilita" => $disponibilita,
                        "pezzi" => $pezzi
                    ], ["ean" => $ean]);
                    
                    $updatedCount++;
                    $batchUpdated++;
                    
                    // Riduci il numero di log per prodotti aggiornati
                    if ($processedCount % 20 == 0 || $batchUpdated == 1) {
                        emitLog("<div class='text-warning'><i class='ti ti-refresh'></i> Aggiornato: <strong>$titolo</strong> (EAN: $ean) - Modifiche: " . implode(", ", $modifiche) . "</div>");
                    }
                }
                
                // Gestione immagine per prodotto esistente
                if ($overwriteImages && !empty($immagineBase64) && str_contains($immagineBase64, ',')) {
                    // Aggiorna l'UI meno frequentemente
                    if ($processedCount % $imageUpdateFrequency == 0) {
                        $imagesProgress = round((($imagesSaved + $imagesMissing + $imagesError + 1) / $totaleProdotti) * 100);
                        emitLog("<script>
                            document.getElementById('images-progress').style.width = '$imagesProgress%';
                            document.getElementById('images-progress').textContent = '$imagesProgress%';
                        </script>");
                    }
                    
                    list(, $data) = explode(',', $immagineBase64);
                    $decodedImage = base64_decode($data);
                    
                    if ($decodedImage) {
                        $imageFileName = $ean . ".jpg";
                        file_put_contents($imgDir . $imageFileName, $decodedImage);
                        $imagesSaved++;
                        $batchImagesSaved++;
                        
                        // Log ridotto
                        if ($processedCount % 50 == 0) {
                            emitLog("<div class='text-info'><i class='ti ti-photo'></i> Salvate $batchImagesSaved immagini in questo batch</div>");
                        }
                    } else {
                        $imagesError++;
                        $batchImagesError++;
                    }
                }
            } else {
                // Gestione immagine per nuovo prodotto
                $imageFileName = "no-image.jpg";
                
                // Aggiorna l'UI meno frequentemente
                if ($processedCount % $imageUpdateFrequency == 0) {
                    $imagesProgress = round((($imagesSaved + $imagesMissing + $imagesError + 1) / $totaleProdotti) * 100);
                    emitLog("<script>
                        document.getElementById('images-progress').style.width = '$imagesProgress%';
                        document.getElementById('images-progress').textContent = '$imagesProgress%';
                    </script>");
                }
                
// Gestione immagine ottimizzata
if (!empty($immagineBase64) && str_contains($immagineBase64, ',')) {
    // Pulizia della memoria per evitare memory leak
    if (isset($decodedImage)) {
        unset($decodedImage);
    }
    
    list(, $data) = explode(',', $immagineBase64);
    $decodedImage = base64_decode($data);
    
    if ($decodedImage) {
        $imageFileName = $ean . ".jpg";
        file_put_contents($imgDir . $imageFileName, $decodedImage);
        $imagesSaved++;
        $batchImagesSaved++;
        
        // Libera memoria
        unset($data, $decodedImage);
    }
}
                
                // Inserisce il nuovo prodotto
                $medooDB->insert("prodotti", [
                    "titolo" => $titolo,
                    "ean" => $ean,
                    "prezzo" => $prezzo,
                    "disponibilita" => $disponibilita,
                    "pezzi" => $pezzi,
                    "immagine" => $imageFileName
                ]);
                
                $insertedCount++;
                $batchInserted++;
                
                // Riduci il numero di log per inserimenti
                if ($processedCount % 20 == 0 || $batchInserted % 20 == 0) {
                    emitLog("<div class='text-success'><i class='ti ti-plus'></i> Inseriti $batchInserted prodotti in questo batch</div>");
                }
            }
        } catch (Exception $e) {
            emitLog("<div class='text-danger'><i class='ti ti-alert-circle'></i> Errore: <strong>$titolo</strong> (EAN: $ean) - {$e->getMessage()}</div>");
            $errorCount++;
            $batchErrors++;
        }
        
        // Aggiorna i contatori nell'interfaccia solo periodicamente
        if ($processedCount % 50 == 0 || $processedCount == $totaleProdotti) {
            emitLog("<script>
                document.getElementById('inserted-count').textContent = '$insertedCount';
                document.getElementById('updated-count').textContent = '$updatedCount';
                document.getElementById('error-count').textContent = '$errorCount';
            </script>");
        }
    }
    
    // Resoconto di fine batch
    emitLog("<div class='text-primary'><i class='ti ti-checkup-list'></i> <strong>Batch $currentBatch/$totalBatches completato</strong>: $batchInserted inseriti, $batchUpdated aggiornati, $batchSkipped saltati, $batchErrors errori</div>");
    
    // Non necessario sleep tra batch per file grandi - rallenta troppo
    // Invece, facciamo un microflush per garantire che i dati vengano inviati
    flush();
}

// Riepilogo finale
emitLog("<div class='alert alert-success mt-3'>
    <h4 class='alert-title'><i class='ti ti-check me-2'></i> Importazione completata!</h4>
    <p>📊 Riepilogo finale:</p>
    <div class='row text-muted'>
        <div class='col-md-6'>
            <ul class='list-unstyled'>
                <li><i class='ti ti-file me-2'></i> Totale prodotti: $totaleProdotti</li>
                <li><i class='ti ti-plus me-2'></i> Inseriti: $insertedCount</li>
                <li><i class='ti ti-refresh me-2'></i> Aggiornati: $updatedCount</li>
                <li><i class='ti ti-skip-forward me-2'></i> Scartati: $skippedCount</li>
            </ul>
        </div>
        <div class='col-md-6'>
            <ul class='list-unstyled'>
                <li><i class='ti ti-alert-circle me-2'></i> Errori: $errorCount</li>
                <li><i class='ti ti-photo me-2'></i> Immagini salvate: $imagesSaved</li>
                <li><i class='ti ti-photo-off me-2'></i> Immagini mancanti: $imagesMissing</li>
                <li><i class='ti ti-bug me-2'></i> Errori immagini: $imagesError</li>
            </ul>
        </div>
    </div>
</div>");

// Imposta tutte le barre di progresso al 100% alla fine
emitLog("<script>
    document.getElementById('import-progress').style.width = '100%';
    document.getElementById('import-progress').textContent = '100%';
    document.getElementById('insert-progress').style.width = '100%';
    document.getElementById('insert-progress').textContent = '100%';
    document.getElementById('images-progress').style.width = '100%';
    document.getElementById('images-progress').textContent = '100%';
</script>");
?> 