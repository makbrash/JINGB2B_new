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
error_reporting(E_ALL & ~E_DEPRECATED);

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
function recupera_immagine_prodotto($ean, $description = '') {
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
            
            if (count($images) >= 10) break;
        }
        
        // Se il primo metodo non ha trovato risultati, prova con un altro pattern
        if (empty($images)) {
            preg_match_all('/"ou":"(https:\/\/[^"]+\.(?:jpg|jpeg|png|gif))"/', $html, $matches);
            foreach ($matches[1] as $image_url) {
                $images[] = $image_url;
                if (count($images) >= 10) break;
            }
        }
        
        // Se ancora non ci sono risultati
        if (empty($images)) {
            throw new Exception("Nessuna immagine trovata per il prodotto");
        }
        
        // Percorso di destinazione
        $target_dir = __DIR__ . "/../public/catalogo/";
        $filename = $ean . ".jpg";
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
        if ($width > $height) {
            $new_width = $width * (200 / $height);
            $new_height = 200;
            $x_offset = ($new_width - 200) / 2 * -1;
            $y_offset = 0;
        } else {
            $new_width = 200;
            $new_height = $height * (200 / $width);
            $x_offset = 0;
            $y_offset = ($new_height - 200) / 2 * -1;
        }
        
        // Ridimensiona e ritaglia l'immagine per farla diventare quadrata
        imagecopyresampled(
            $new_img, $source_img,
            $x_offset, $y_offset,
            0, 0,
            $new_width, $new_height,
            $width, $height
        );
        
        // Salva l'immagine finale come JPEG
        if (imagejpeg($new_img, $filepath, 90)) {
            // Aggiorna il database se necessario
            try {

                        $medooDB->update(
                            "prodotti",
                            ["immagine" => $filename],
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
    $ean = isset($_POST['ean']) ? trim($_POST['ean']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    if (empty($ean)) {
        echo json_encode(['success' => false, 'message' => 'EAN non specificato']);
        exit;
    }
    
    $result = recupera_immagine_prodotto($ean, $description);
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Metodo di richiesta non valido']);
}