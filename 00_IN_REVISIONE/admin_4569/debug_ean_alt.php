<?php
/**
 * debug_ean_alt.php - Script di debug per testare fetch_images.php con più dettagli
 * 
 * Questo script modifica fetch_images.php per mostrare più informazioni durante il processo
 * di scraping, ma usa lo stesso codice di base.
 */

// Configura l'output
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Imposta header
header('Content-Type: text/html; charset=utf-8');

$context = stream_context_create(
    array(
        "http" => array(
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
        )
    )
);




// EAN di test valido da provare
$test_ean = "8000036019535"; // Un EAN reale di un prodotto (esempio: Nutella)
$url = "https://www.barcodelookup.com/" . $test_ean;
//echo file_get_contents("www.google.com");

$html = file_get_contents($url, false, $context);
var_dump($html);

exit;



echo "<h1>Test fetch_images.php con visualizzazione dettagli</h1>";
echo "<p>EAN: <strong>$test_ean</strong></p>";

// Cartella di destinazione
$target_dir = __DIR__ . "/../public/catalogo/";
echo "<p>Cartella di destinazione: <code>$target_dir</code></p>";

// Verifica se la cartella esiste
if (!file_exists($target_dir)) {
    echo "<p style='color: orange;'>La cartella non esiste, verrà creata</p>";
    mkdir($target_dir, 0755, true);
} else {
    echo "<p style='color: green;'>La cartella esiste</p>";
}

// Nome file di destinazione
$filename = $test_ean . ".jpg";
$target_file = $target_dir . $filename;
echo "<p>File di destinazione: <code>$target_file</code></p>";

// URL barcodelookup
$url = "https://www.barcodelookup.com/" . $test_ean;
echo "<p>URL: <a href='$url' target='_blank'>$url</a></p>";

// Output buffer per catturare eventuali echo durante il processo
ob_start();

try {
    echo "<h2>Inizializzazione cURL...</h2>";
    
    // Inizializza cURL
    $ch = curl_init();
    
    if (!$ch) {
        throw new Exception("Impossibile inizializzare cURL");
    }
    
    // Impostazioni cURL
    echo "<p>Configurazione opzioni cURL...</p>";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    
    // Esegui la richiesta
    echo "<h2>Esecuzione richiesta cURL...</h2>";
    $html = curl_exec($ch);
    
    // Verifica errori cURL
    if (curl_errno($ch)) {
        throw new Exception('Errore cURL: ' . curl_error($ch));
    }
    
    // Info sulla richiesta
    $info = curl_getinfo($ch);
    echo "<p>Dimensione risposta: " . $info['size_download'] . " bytes</p>";
    echo "<p>Codice HTTP: " . $info['http_code'] . "</p>";
    echo "<p>Content Type: " . $info['content_type'] . "</p>";
    
    // Chiudi cURL
    curl_close($ch);
    
    if (empty($html)) {
        throw new Exception("La pagina è vuota o non accessibile");
    }
    
    echo "<h2>Parsing HTML con DOMDocument...</h2>";
    
    // Usa DOMDocument per parsare l'HTML
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    
    // Cerca l'immagine nel productImageThumbs
    echo "<p>Ricerca immagini in #productImageThumbs...</p>";
    $imgNodes = $xpath->query('//div[@id="productImageThumbs"]//img');
    
    if ($imgNodes->length === 0) {
        echo "<p>Nessuna immagine trovata in productImageThumbs, ricerca in #largeProductImage...</p>";
        // Prova a cercare nell'immagine principale se non trova nel thumb
        $imgNodes = $xpath->query('//div[@id="largeProductImage"]//img');
    }
    
    if ($imgNodes->length > 0) {
        echo "<p style='color: green;'>Trovate " . $imgNodes->length . " immagini</p>";
        
        // Prendi la prima immagine trovata
        $imgElement = $imgNodes->item(0);
        $imgUrl = '';
        
        // Verifica che sia un elemento DOM e non un semplice nodo
        if ($imgElement instanceof DOMElement) {
            $imgUrl = $imgElement->getAttribute('src');
            echo "<p>URL immagine: <a href='$imgUrl' target='_blank'>$imgUrl</a></p>";
            echo "<p><img src='$imgUrl' style='max-width: 300px; border: 1px solid #ccc; padding: 5px;'></p>";
        } else {
            throw new Exception('Elemento immagine non valido');
        }
        
        if (empty($imgUrl)) {
            throw new Exception('URL immagine non trovato');
        }
        
        // Scarica l'immagine
        echo "<h2>Download immagine...</h2>";
        $imgData = @file_get_contents($imgUrl);
        
        if ($imgData === false) {
            throw new Exception('Impossibile scaricare l\'immagine');
        }
        
        echo "<p>Immagine scaricata: " . strlen($imgData) . " bytes</p>";
        
        // Salva l'immagine originale
        echo "<h2>Salvataggio immagine...</h2>";
        if (file_put_contents($target_file, $imgData)) {
            echo "<p style='color: green;'>Immagine originale salvata con successo in: $target_file</p>";
        } else {
            throw new Exception('Impossibile salvare l\'immagine');
        }
        
        // Ridimensiona l'immagine a 200x200px
        echo "<h2>Ridimensionamento immagine a 200x200px...</h2>";
        $img = @imagecreatefromstring($imgData);
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
            echo "<p>Posizione nell'immagine: x=$x, y=$y</p>";
            
            // Ridimensiona
            imagecopyresampled($thumb, $img, $x, $y, 0, 0, $new_width, $new_height, $width, $height);
            
            // Salva l'immagine ridimensionata
            if (imagejpeg($thumb, $target_file, 90)) {
                echo "<p style='color: green;'>Immagine ridimensionata salvata con successo</p>";
            } else {
                throw new Exception('Impossibile salvare l\'immagine ridimensionata');
            }
            
            // Libera memoria
            imagedestroy($img);
            imagedestroy($thumb);
            
            echo "<h2>Risultato finale</h2>";
            echo "<p><img src='../public/catalogo/$filename?t=" . time() . "' style='border: 1px solid #ccc; padding: 5px;'></p>";
            
            echo "<p style='color: green; font-weight: bold;'>Operazione completata con successo</p>";
        } else {
            throw new Exception('Formato immagine non supportato');
        }
    } else {
        throw new Exception('Nessuna immagine trovata per questo EAN');
    }
} catch (Exception $e) {
    echo "<div style='color: red; background-color: #ffebee; padding: 15px; border-radius: 5px;'>";
    echo "<h3>Errore</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

// Mostra il codice HTML grezzo in un'area nascondibile
if (isset($html)) {
    echo "<h2>Codice HTML grezzo della pagina</h2>";
    echo "<details>";
    echo "<summary>Clicca per mostrare/nascondere il codice HTML</summary>";
    echo "<pre style='background-color: #f0f0f0; padding: 10px; max-height: 500px; overflow: auto;'>";
    echo htmlspecialchars($html);
    echo "</pre>";
    echo "</details>";
} 