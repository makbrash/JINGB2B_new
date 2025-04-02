<?php
/**
 * fetch_images.php - Recupero immagini prodotti da barcodelookup.com
 * 
 * Questo file gestisce il recupero delle immagini dei prodotti tramite scraping 
 * dal sito barcodelookup.com utilizzando il codice EAN.
 * 
 * @author Sistema classificazione prodotti
 * @version 1.0
 */

// Configurazione iniziale
ini_set('display_errors', 0); // Nascondi gli errori in output
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

// Imposta il content type a JSON
header('Content-Type: application/json');

// Controllo accesso CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Se è una richiesta OPTIONS, termina qui
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Carica le dipendenze
require_once '../config/config.php';

// Assicurati che l'estensione GD sia attiva
if (!extension_loaded('gd')) {
    echo json_encode(['success' => false, 'message' => 'PHP GD extension not loaded']);
    exit;
}

// Funzione per recuperare l'immagine
function recupera_immagine_prodotto($ean, $description = '', $mode = 'single') {
    global $medooDB;
    $result = ['success' => false, 'message' => 'Nessuna immagine trovata'];
    
    try {
        $context = stream_context_create([
            "http" => [
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36",
                "timeout" => 30
            ]
        ]);
        
        // URL di ricerca
        $url = "https://www.google.com/search?q=" . urlencode($ean . " " . $description) . "&source=lnms&tbm=isch&sa=X";
        
        $html = file_get_contents($url, false, $context);
        if ($html === false) {
            throw new Exception("Impossibile recuperare i risultati di ricerca");
        }
        
        // Pattern per trovare URL di immagini nelle risposte JSON
        preg_match_all('/\["(https:\/\/[^"]+\.(?:jpg|jpeg|png|gif))",\d+,\d+\]/', $html, $matches);
        
        $images = [];
        foreach ($matches[1] as $image_url) {
            // Rimuovi le dimensioni thumbnail per ottenere immagini più grandi
            $image_url = preg_replace('/=w\d+-h\d+(?:-p)?-k-no/', '=s0', $image_url);
            $images[] = $image_url;
            
            if (count($images) >= 12) break;
        }
        
        // Se il primo metodo non ha trovato risultati, prova con un altro pattern
        if (empty($images)) {
            preg_match_all('/"ou":"(https:\/\/[^"]+\.(?:jpg|jpeg|png|gif))"/', $html, $matches);
            foreach ($matches[1] as $image_url) {
                $images[] = $image_url;
                if (count($images) >= 12) break;
            }
        }
        
        // Se ancora non ci sono risultati
        if (empty($images)) {
            throw new Exception("Nessuna immagine trovata per il prodotto");
        }
        
        // Se siamo in modalità multi, restituisci solo gli URL
        if ($mode == 'multi') {
            return [
                'success' => true,
                'message' => 'Immagini trovate con successo',
                'data' => [
                    'ean' => $ean,
                    'images' => $images
                ]
            ];
        }
        
        // Percorso di destinazione
        $target_dir = __DIR__ . "/../public/catalogo/";
        
        // Genera nome file con timestamp per evitare cache
        
        $filename = $ean .  ".jpg";
        $filepath = $target_dir . $filename;
        
        // Crea la directory se non esiste
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) {
                throw new Exception("Impossibile creare la directory di destinazione");
            }
        }
        
        // Scarica l'immagine
        $img_content = file_get_contents($images[0], false, $context);
        if ($img_content === false) {
            throw new Exception("Impossibile scaricare l'immagine");
        }
        
        // Salva temporaneamente l'immagine originale
        $temp_file = tempnam(sys_get_temp_dir(), 'img_');
        file_put_contents($temp_file, $img_content);
        
        // Ottieni dimensioni e tipo dell'immagine
        list($width, $height, $type) = getimagesize($temp_file);
        
        // Crea una nuova immagine quadrata 200x200
        $new_img = imagecreatetruecolor(200, 200);
        
        // Sfondo bianco (in caso l'immagine abbia trasparenza)
        $white = imagecolorallocate($new_img, 255, 255, 255);
        imagefill($new_img, 0, 0, $white);
        
        // Carica l'immagine di origine
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source_img = imagecreatefromjpeg($temp_file);
                break;
            case IMAGETYPE_PNG:
                $source_img = imagecreatefrompng($temp_file);
                break;
            case IMAGETYPE_GIF:
                $source_img = imagecreatefromgif($temp_file);
                break;
            default:
                throw new Exception("Formato immagine non supportato");
        }
        
        if (!$source_img) {
            throw new Exception("Impossibile elaborare l'immagine scaricata");
        }
        
        // Calcola le dimensioni per il ridimensionamento mantenendo l'aspect ratio
        // Usa "contain" invece di "crop" (ridimensiona per adattare al quadrato)
        $scale = min(200 / $width, 200 / $height);
        $new_width = (int)($width * $scale);
        $new_height = (int)($height * $scale);
        
        // Posiziona l'immagine al centro del quadrato
        $x_offset = (int)((200 - $new_width) / 2);
        $y_offset = (int)((200 - $new_height) / 2);
        
        // Ridimensiona l'immagine mantenendo l'aspect ratio e posizionandola al centro
        // Questo metodo "contain" evita il cropping
        imagecopyresampled(
            $new_img, $source_img,
            $x_offset, $y_offset,
            0, 0,
            $new_width, $new_height,
            $width, $height
        );
        
        // Salva l'immagine finale come JPEG
        if (imagejpeg($new_img, $filepath, 90)) {
            // Rimuovi eventuali vecchie immagini dello stesso prodotto
            rimuoviVecchieImmagini($target_dir, $ean);
            
            // Aggiorna il database se necessario
            try {
                $medooDB->update(
                    "prodotti",
                    ["immagine" => $filename."?".time()],
                    ["ean" => $ean]
                );
            } catch (Exception $dbEx) {
                // Ignorare errori DB, immagine salvata comunque
            }
            
            $result = [
                'success' => true,
                'message' => "Immagine salvata con successo",
                'data' => [
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'ean' => $ean
                ]
            ];
        } else {
            throw new Exception("Impossibile salvare l'immagine finale");
        }
        
        // Libera la memoria
        imagedestroy($new_img);
        imagedestroy($source_img);
        unlink($temp_file);
        
    } catch (Exception $e) {
        $result = [
            'success' => false,
            'message' => $e->getMessage(),
            'data' => ['ean' => $ean]
        ];
    }
    
    return $result;
}

// Funzione per rimuovere le vecchie immagini di un prodotto
function rimuoviVecchieImmagini($directory, $ean) {
    // Pattern per trovare vecchie immagini dello stesso prodotto
    $pattern = $ean . "_*.jpg";
    
    // Trova tutti i file che corrispondono al pattern
    $files = glob($directory . $pattern);
    
    // Se ci sono file, rimuovili (eccetto il più recente che verrà sostituito)
    if (is_array($files)) {
        // Ordina per data di creazione (il più vecchio prima)
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Rimuovi tutti tranne l'ultimo (il più recente)
        $count = count($files);
        for ($i = 0; $i < $count - 1; $i++) {
            @unlink($files[$i]);
        }
        
        // L'ultimo file non viene rimosso perché sarà sostituito dal nuovo
    }
}

// Funzione per salvare un'immagine da URL
function salva_immagine_da_url($ean, $image_url) {
    global $medooDB;
    $result = ['success' => false, 'message' => 'Errore nel salvataggio dell\'immagine'];
    
    try {
        // Verifico se l'URL contiene domini problematici noti
        $domini_problematici = ['idealo.com', 'amazon.com', 'amazon.it', 'ebay.com', 'ebay.it'];
        $is_problematico = false;
        
        foreach ($domini_problematici as $dominio) {
            if (strpos($image_url, $dominio) !== false) {
                $is_problematico = true;
                break;
            }
        }
        
        // Creo un contesto con user agent più convincente e referer ambiguo
        $context = stream_context_create([
            "http" => [
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n" .
                           "Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8\r\n" .
                           "Accept-Language: it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7\r\n" .
                           "Referer: https://www.google.com/\r\n",
                "timeout" => 30,
                "follow_location" => 1,
                "max_redirects" => 5
            ]
        ]);
        
        // Percorso di destinazione
        $target_dir = __DIR__ . "/../public/catalogo/";
        
        // Genera nome file con timestamp per evitare cache
      
        $filename = $ean . ".jpg" ;
        $filepath = $target_dir . $filename;
        
        // Crea la directory se non esiste
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) {
                throw new Exception("Impossibile creare la directory di destinazione");
            }
        }
        
        // Scarica l'immagine
        $img_content = false;
        
        // Primo tentativo - metodo standard
        $img_content = @file_get_contents($image_url, false, $context);
        
        // Se fallisce e l'URL è problematico, prova con cURL che ha più opzioni
        if ($img_content === false && $is_problematico && function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $image_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                'Accept-Language: it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer: https://www.google.com/'
            ]);
            $img_content = curl_exec($ch);
            curl_close($ch);
        }
        
        if ($img_content === false) {
            throw new Exception("Impossibile scaricare l'immagine. Il sito di origine potrebbe bloccare l'accesso diretto.");
        }
        
        // Verifica che il contenuto sia effettivamente un'immagine
        if (substr($img_content, 0, 2) === 'PK' || stripos($img_content, '<!DOCTYPE html>') !== false || stripos($img_content, '<html') !== false) {
            throw new Exception("Il contenuto scaricato non è un'immagine valida, ma probabilmente una pagina HTML o un altro tipo di file.");
        }
        
        // Salva temporaneamente l'immagine originale
        $temp_file = tempnam(sys_get_temp_dir(), 'img_');
        file_put_contents($temp_file, $img_content);
        
        // Ottieni dimensioni e tipo dell'immagine
        $image_info = @getimagesize($temp_file);
        
        if ($image_info === false) {
            throw new Exception("Il file scaricato non è un'immagine valida");
        }
        
        list($width, $height, $type) = $image_info;
        
        // Crea una nuova immagine quadrata 200x200
        $new_img = imagecreatetruecolor(200, 200);
        
        // Sfondo bianco (in caso l'immagine abbia trasparenza)
        $white = imagecolorallocate($new_img, 255, 255, 255);
        imagefill($new_img, 0, 0, $white);
        
        // Carica l'immagine di origine
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source_img = imagecreatefromjpeg($temp_file);
                break;
            case IMAGETYPE_PNG:
                $source_img = imagecreatefrompng($temp_file);
                break;
            case IMAGETYPE_GIF:
                $source_img = imagecreatefromgif($temp_file);
                break;
            default:
                throw new Exception("Formato immagine non supportato");
        }
        
        if (!$source_img) {
            throw new Exception("Impossibile elaborare l'immagine scaricata");
        }
        
        // Calcola le dimensioni per il ridimensionamento mantenendo l'aspect ratio
        // Usa "contain" invece di "crop" (ridimensiona per adattare al quadrato)
        $scale = min(200 / $width, 200 / $height);
        $new_width = (int)($width * $scale);
        $new_height = (int)($height * $scale);
        
        // Posiziona l'immagine al centro del quadrato
        $x_offset = (int)((200 - $new_width) / 2);
        $y_offset = (int)((200 - $new_height) / 2);
        
        // Ridimensiona l'immagine mantenendo l'aspect ratio e posizionandola al centro
        // Questo metodo "contain" evita il cropping
        imagecopyresampled(
            $new_img, $source_img,
            $x_offset, $y_offset,
            0, 0,
            $new_width, $new_height,
            $width, $height
        );
        
        // Salva l'immagine finale come JPEG
        if (imagejpeg($new_img, $filepath, 90)) {
            // Rimuovi eventuali vecchie immagini dello stesso prodotto
            rimuoviVecchieImmagini($target_dir, $ean);
            
            // Aggiorna il database se necessario
            try {
                $medooDB->update(
                    "prodotti",
                    ["immagine" => $filename."?".time()],
                    ["ean" => $ean]
                );
            } catch (Exception $dbEx) {
                // Ignorare errori DB, immagine salvata comunque
            }
            
            $result = [
                'success' => true,
                'message' => "Immagine salvata con successo",
                'data' => [
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'ean' => $ean
                ]
            ];
        } else {
            throw new Exception("Impossibile salvare l'immagine finale");
        }
        
        // Libera la memoria
        imagedestroy($new_img);
        imagedestroy($source_img);
        unlink($temp_file);
        
    } catch (Exception $e) {
        $result = [
            'success' => false,
            'message' => $e->getMessage(),
            'data' => ['ean' => $ean]
        ];
    }
    
    return $result;
}

// Gestione della richiesta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determina quale operazione eseguire
    $action = isset($_POST['action']) ? trim($_POST['action']) : 'fetch_single';
    $ean = isset($_POST['ean']) ? trim($_POST['ean']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    if (empty($ean)) {
        echo json_encode(['success' => false, 'message' => 'EAN non specificato']);
        exit;
    }
    
    switch ($action) {
        case 'fetch_multiple':
            // Restituisci fino a 8 immagini trovate
            $result = recupera_immagine_prodotto($ean, $description, 'multi');
            echo json_encode($result);
            break;
            
        case 'save_url':
            // Salva un'immagine da URL specifico
            $image_url = isset($_POST['image_url']) ? trim($_POST['image_url']) : '';
            if (empty($image_url)) {
                echo json_encode(['success' => false, 'message' => 'URL immagine non specificato']);
                exit;
            }
            $result = salva_immagine_da_url($ean, $image_url);
            echo json_encode($result);
            break;
            
        case 'upload_file':
            // Carica un'immagine da file
            if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
                $error_message = 'Errore nel caricamento del file';
                $error_details = [];
                
                if (isset($_FILES['image_file']['error'])) {
                    $error_code = $_FILES['image_file']['error'];
                    $error_details['code'] = $error_code;
                    
                    switch ($error_code) {
                        case UPLOAD_ERR_INI_SIZE:
                            $error_message = 'File troppo grande (supera upload_max_filesize in php.ini)';
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            $error_message = 'File troppo grande (supera MAX_FILE_SIZE nel form)';
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $error_message = 'Caricamento incompleto';
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $error_message = 'Nessun file caricato';
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $error_message = 'Directory temporanea mancante';
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $error_message = 'Impossibile scrivere il file su disco';
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $error_message = 'Caricamento interrotto da un\'estensione PHP';
                            break;
                    }
                }
                
                // Aggiungi dettagli sul file se disponibili
                if (isset($_FILES['image_file'])) {
                    $error_details['file_info'] = [
                        'name' => $_FILES['image_file']['name'] ?? 'N/A',
                        'size' => $_FILES['image_file']['size'] ?? 'N/A',
                        'type' => $_FILES['image_file']['type'] ?? 'N/A'
                    ];
                }
                
                // Controllo configurazione PHP
                $error_details['php_config'] = [
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size'),
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time')
                ];
                
                echo json_encode([
                    'success' => false, 
                    'message' => $error_message,
                    'details' => $error_details
                ]);
                exit;
            }
            
            // Controlla il tipo di file
            $file_type = $_FILES['image_file']['type'];
            $file_name = $_FILES['image_file']['name'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            // Ottieni l'estensione del file
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Log diagnostico
            error_log("File upload: " . $file_name . ", Type: " . $file_type . ", Extension: " . $file_extension);
            
            // Se l'estensione è valida ma il MIME no, fidiamoci dell'estensione
            if (in_array($file_extension, $allowed_extensions) && !in_array($file_type, $allowed_types)) {
                if ($file_extension == 'jpg' || $file_extension == 'jpeg') {
                    $file_type = 'image/jpeg';
                } else if ($file_extension == 'png') {
                    $file_type = 'image/png';
                } else if ($file_extension == 'gif') {
                    $file_type = 'image/gif';
                }
                
                error_log("File type corrected based on extension to: " . $file_type);
            }
            
            // Se il tipo MIME non è tra quelli consentiti, proviamo a determinarlo dal contenuto
            if (!in_array($file_type, $allowed_types)) {
                $temp_file = $_FILES['image_file']['tmp_name'];
                
                error_log("Trying to detect image type from content for: " . $file_name);
                
                // Prova a determinare il tipo dal contenuto
                if (function_exists('exif_imagetype')) {
                    $image_type = @exif_imagetype($temp_file);
                    error_log("EXIF image type detected: " . $image_type);
                    
                    if ($image_type === IMAGETYPE_JPEG) {
                        $file_type = 'image/jpeg';
                    } elseif ($image_type === IMAGETYPE_PNG) {
                        $file_type = 'image/png';
                    } elseif ($image_type === IMAGETYPE_GIF) {
                        $file_type = 'image/gif';
                    }
                }
                
                // Metodo alternativo se exif_imagetype fallisce
                if (!in_array($file_type, $allowed_types)) {
                    error_log("Trying getimagesize for: " . $file_name);
                    $image_info = @getimagesize($temp_file);
                    if ($image_info && isset($image_info['mime'])) {
                        $file_type = $image_info['mime'];
                        error_log("Getimagesize detected MIME: " . $file_type);
                    }
                }
                
                // Tentiamo un ultimo approccio basato su firme di file
                if (!in_array($file_type, $allowed_types)) {
                    error_log("Trying file signature detection for: " . $file_name);
                    $file_signature = file_get_contents($temp_file, false, null, 0, 8);
                    $hex_signature = bin2hex($file_signature);
                    error_log("File hex signature: " . $hex_signature);
                    
                    // JPEG inizia con FF D8
                    if (strpos($hex_signature, 'ffd8') === 0) {
                        $file_type = 'image/jpeg';
                        error_log("Signature detected as JPEG");
                    } 
                    // PNG inizia con 89 50 4E 47 (‰PNG)
                    elseif (strpos($hex_signature, '89504e47') === 0) {
                        $file_type = 'image/png';
                        error_log("Signature detected as PNG");
                    }
                    // GIF inizia con GIF87a o GIF89a
                    elseif (strpos($hex_signature, '47494638') === 0) {
                        $file_type = 'image/gif';
                        error_log("Signature detected as GIF");
                    }
                }
                
                // Se ancora non è un tipo valido, rifiuta il file
                if (!in_array($file_type, $allowed_types)) {
                    error_log("File type detection failed for: " . $file_name);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Formato immagine non supportato. Sono accettati solo JPG, PNG e GIF.',
                        'details' => [
                            'detected_type' => $file_type,
                            'file_name' => $file_name,
                            'extension' => $file_extension,
                            'info' => 'Il formato dell\'immagine non è stato riconosciuto automaticamente. Prova a convertire l\'immagine in JPG usando un programma di grafica.'
                        ]
                    ]);
                    exit;
                }
            }
            
            try {
                // Percorso di destinazione temporaneo
                $temp_file = $_FILES['image_file']['tmp_name'];
                
                // Log dell'operazione
                error_log("Processing image from: " . $temp_file . ", Type: " . $file_type);
                
                // Ottieni dimensioni e tipo dell'immagine
                $image_info = @getimagesize($temp_file);
                if ($image_info === false) {
                    throw new Exception("Impossibile leggere le dimensioni dell'immagine. Il file potrebbe essere danneggiato.");
                }
                
                list($width, $height, $type) = $image_info;
                error_log("Image dimensions: " . $width . "x" . $height . ", Type: " . $type);
                
                // Percorso di destinazione finale
                $target_dir = __DIR__ . "/../public/catalogo/";
                
                // Genera nome file con timestamp per evitare cache
              
                $filename = $ean .  ".jpg";
                $filepath = $target_dir . $filename;
                
                // Crea la directory se non esiste
                if (!is_dir($target_dir)) {
                    if (!mkdir($target_dir, 0755, true)) {
                        throw new Exception("Impossibile creare la directory di destinazione");
                    }
                }
                
                // Crea una nuova immagine quadrata 200x200 con sfondo bianco
                $new_img = imagecreatetruecolor(200, 200);
                if (!$new_img) {
                    throw new Exception("Errore nella creazione dell'immagine di destinazione. Verifica che la libreria GD sia attiva e configurata correttamente.");
                }
                
                // Sfondo bianco (in caso l'immagine abbia trasparenza)
                $white = imagecolorallocate($new_img, 255, 255, 255);
                imagefill($new_img, 0, 0, $white);
                
                // Carica l'immagine di origine in base al tipo rilevato
                $source_img = false;
                
                // Usa il tipo corretto in base alla variabile $file_type
                if ($file_type == 'image/jpeg') {
                    $source_img = @imagecreatefromjpeg($temp_file);
                } else if ($file_type == 'image/png') {
                    $source_img = @imagecreatefrompng($temp_file);
                } else if ($file_type == 'image/gif') {
                    $source_img = @imagecreatefromgif($temp_file);
                }
                
                if (!$source_img) {
                    throw new Exception("Impossibile elaborare l'immagine di origine. Formato: " . $file_type);
                }
                
                error_log("Source image created successfully");
                
                // Usa "contain" invece di "crop" (ridimensiona per adattare al quadrato)
                $scale = min(200 / $width, 200 / $height);
                $new_width = (int)($width * $scale);
                $new_height = (int)($height * $scale);
                
                // Posiziona l'immagine al centro del quadrato
                $x_offset = (int)((200 - $new_width) / 2);
                $y_offset = (int)((200 - $new_height) / 2);
                
                error_log("Resizing image: Scale=" . $scale . ", New dimensions: " . $new_width . "x" . $new_height . ", Offset: " . $x_offset . "," . $y_offset);
                
                // Imposta la gestione della memoria per immagini grandi
                ini_set('memory_limit', '256M');
                
                // Ridimensiona l'immagine mantenendo l'aspect ratio e posizionandola al centro
                $result = imagecopyresampled(
                    $new_img, $source_img,
                    $x_offset, $y_offset,
                    0, 0,
                    $new_width, $new_height,
                    $width, $height
                );
                
                if (!$result) {
                    throw new Exception("Errore nel ridimensionamento dell'immagine. Verifica che l'immagine non sia danneggiata.");
                }
                
                error_log("Image resized successfully");
                
                // Salva l'immagine finale come JPEG
                if (imagejpeg($new_img, $filepath, 90)) {
                    error_log("Image saved to: " . $filepath);
                    
                    // Rimuovi eventuali vecchie immagini dello stesso prodotto
                    rimuoviVecchieImmagini($target_dir, $ean);
                    
                    // Aggiorna il database se necessario
                    try {
                        global $medooDB;
                        $medooDB->update(
                            "prodotti",
                            ["immagine" => $filename."?".time()],
                            ["ean" => $ean]
                        );
                        error_log("Database updated for EAN: " . $ean);
                    } catch (Exception $dbEx) {
                        error_log("Database error: " . $dbEx->getMessage());
                        // Ignorare errori DB, immagine salvata comunque
                    }
                    
                    // Libera la memoria
                    imagedestroy($new_img);
                    imagedestroy($source_img);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Immagine caricata con successo',
                        'data' => [
                            'filename' => $filename,
                            'filepath' => $filepath,
                            'ean' => $ean
                        ]
                    ]);
                } else {
                    throw new Exception("Impossibile salvare l'immagine finale");
                }
            } catch (Exception $e) {
                error_log("Error processing image: " . $e->getMessage());
                
                // Libera la memoria se le risorse sono state create
                if (isset($new_img) && $new_img) imagedestroy($new_img);
                if (isset($source_img) && $source_img) imagedestroy($source_img);
                
                echo json_encode([
                    'success' => false, 
                    'message' => 'Errore nell\'elaborazione dell\'immagine: ' . $e->getMessage(),
                    'details' => [
                        'file' => $file_name,
                        'type' => $file_type
                    ]
                ]);
            }
            break;
            
        case 'fetch_single':
        default:
            // Comportamento predefinito: recupera e salva una singola immagine
            $result = recupera_immagine_prodotto($ean, $description, 'single');
            echo json_encode($result);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metodo di richiesta non valido']);
}