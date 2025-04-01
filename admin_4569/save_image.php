<?php
/**
 * save_image.php - Salva l'immagine selezionata dal debug_ean_alternative.php
 * 
 * Questo script riceve un URL di un'immagine e un EAN, scarica l'immagine,
 * la ridimensiona e la salva nella cartella catalogo.
 */

// Configura l'output
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Carica le dipendenze
require_once '../config/config.php';

// Imposta header
header('Content-Type: text/html; charset=utf-8');

// Verifica parametri


$imageUrl = 'https://www.barcodelookup.com/';
$ean = '8000036019535';

// Visualizza intestazione
echo "<h1>Salvataggio immagine per EAN: $ean</h1>";
echo "<p>URL immagine: <a href='$imageUrl' target='_blank'>$imageUrl</a></p>";

// Cartella di destinazione
$target_dir = __DIR__ . "/../public/catalogo/";

// Verifica se la cartella esiste
if (!file_exists($target_dir)) {
    echo "<p>Creazione cartella di destinazione...</p>";
    mkdir($target_dir, 0755, true);
}

// Nome file di destinazione
$filename = $ean . ".jpg";
$target_file = $target_dir . $filename;

try {
    // Scarica l'immagine
    echo "<h2>Download immagine...</h2>";
    
    // Inizializza cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $imageUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Esegui la richiesta
    $imgData = curl_exec($ch);
    
    // Verifica errori cURL
    if (curl_errno($ch)) {
        throw new Exception('Errore cURL: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if (empty($imgData)) {
        throw new Exception("Impossibile recuperare l'immagine dall'URL specificato");
    }
    
    echo "<p>Immagine scaricata: " . strlen($imgData) . " bytes</p>";
    
    // Salva l'immagine originale
    echo "<h2>Salvataggio immagine temporanea...</h2>";
    $temp_file = $target_dir . "temp_" . $filename;
    if (!file_put_contents($temp_file, $imgData)) {
        throw new Exception("Impossibile salvare l'immagine temporanea");
    }
    
    // Ridimensiona l'immagine a 200x200px
    echo "<h2>Ridimensionamento immagine a 200x200px...</h2>";
    $img = @imagecreatefromstring($imgData);
    
    if ($img === false) {
        // Prova a determinare il formato e convertirlo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($imgData);
        
        echo "<p>Formato immagine rilevato: $mime</p>";
        
        if ($mime == 'image/webp') {
            // Se è disponibile, converte da WebP
            if (function_exists('imagecreatefromwebp')) {
                file_put_contents($temp_file, $imgData);
                $img = imagecreatefromwebp($temp_file);
            } else {
                throw new Exception("Formato WebP non supportato da questa installazione PHP");
            }
        } else {
            throw new Exception("Formato immagine non supportato: $mime");
        }
    }
    
    if ($img !== false) {
        $width = imagesx($img);
        $height = imagesy($img);
        
        echo "<p>Dimensioni originali: $width x $height px</p>";
        
        // Crea immagine 200x200
        $thumb = imagecreatetruecolor(200, 200);
        
        // Preserva trasparenza
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, 200, 200, $transparent);
        
        // Calcola proporzioni
        $ratio = min(200 / $width, 200 / $height);
        $new_width = $width * $ratio;
        $new_height = $height * $ratio;
        $x = (200 - $new_width) / 2;
        $y = (200 - $new_height) / 2;
        
        echo "<p>Nuove dimensioni: $new_width x $new_height px (ratio: $ratio)</p>";
        
        // Ridimensiona
        imagecopyresampled($thumb, $img, $x, $y, 0, 0, $new_width, $new_height, $width, $height);
        
        // Salva l'immagine ridimensionata
        if (imagejpeg($thumb, $target_file, 90)) {
            echo "<p style='color: green;'>Immagine ridimensionata salvata con successo: $target_file</p>";
            
            // Rimuove il file temporaneo
            if (file_exists($temp_file)) {
                unlink($temp_file);
            }
            
            // Aggiorna il database
            global $medooDB;
            
            $result = $medooDB->update(
                "prodotti",
                ["immagine" => $filename],
                ["ean" => $ean]
            );
            
            if ($result->rowCount() > 0) {
                echo "<p style='color: green;'>Database aggiornato con successo</p>";
            } else {
                echo "<p style='color: orange;'>Nessun prodotto aggiornato nel database. EAN non trovato: $ean</p>";
            }
            
            echo "<h2>Risultato finale</h2>";
            echo "<p><img src='../public/catalogo/$filename?t=" . time() . "' style='border: 1px solid #ccc; padding: 5px;'></p>";
            
            echo "<p style='color: green; font-weight: bold;'>Operazione completata con successo</p>";
            echo "<p><a href='debug_ean_alternative.php' class='button'>Torna alla pagina precedente</a></p>";
        } else {
            throw new Exception("Impossibile salvare l'immagine ridimensionata");
        }
        
        // Libera memoria
        imagedestroy($img);
        imagedestroy($thumb);
    } else {
        throw new Exception("Formato immagine non supportato o immagine danneggiata");
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; background-color: #ffebee; padding: 15px; border-radius: 5px;'>";
    echo "<h3>Errore</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
    
    echo "<p><a href='javascript:history.back()'>Torna indietro</a></p>";
}

// Aggiungi un po' di stile
echo "
<style>
body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; max-width: 1000px; margin: 0 auto; }
h1, h2 { color: #2c3e50; }
a { color: #3498db; text-decoration: none; }
a:hover { text-decoration: underline; }
a.button { 
    display: inline-block; 
    background: #3498db; 
    color: white; 
    padding: 8px 16px; 
    border-radius: 4px; 
    text-decoration: none; 
}
a.button:hover { background: #2980b9; }
pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
code { background: #f8f9fa; padding: 2px 4px; border-radius: 4px; }
</style>
"; 