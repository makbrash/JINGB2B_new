<?php
/**
 * debug_ean_alternative.php - Test scraping da Google Shopping
 * 
 * Questo script prova a recuperare immagini di prodotti tramite Google Shopping
 * usando il codice EAN come query di ricerca.
 */

// Configura l'output
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Imposta header
header('Content-Type: text/html; charset=utf-8');

// EAN di test valido da provare
$test_ean = "8000036019535"; // Un EAN reale di un prodotto (esempio: Nutella)
$test_nome = "Amuchina Pavimenti Aloe 1500 Ml"; // Nome del prodotto per migliorare la ricerca

echo "<h1>Test scraping immagini da Google Shopping</h1>";
echo "<p>EAN: <strong>$test_ean</strong>, Nome: <strong>$test_nome</strong></p>";

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

// Funzione per pulire l'input per la ricerca
function cleanSearchTerm($term) {
    return rawurlencode(trim($term));
}

// Funzione per normalizzare un URL di immagine
function normalizeImageUrl($url) {
    // Rimuovi l'encoding unicode
    $url = preg_replace('/\\\\u([0-9a-fA-F]{4})/', '&#x\\1;', $url);
    $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
    
    // Rimuovi l'encoding degli spazi
    $url = str_replace('\ ', ' ', $url);
    
    // Gestisci URL relative
    if (strpos($url, '//') === 0) {
        $url = 'https:' . $url;
    }
    
    return $url;
}

// Funzione per verificare se un'immagine è accessibile
function isImageAccessible($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($httpCode >= 200 && $httpCode < 300);
}

try {
    // Prepara la query di ricerca
    $searchQuery = cleanSearchTerm("$test_ean $test_nome");
    
    // URL Google Shopping
    $url = "https://www.google.com/search?q={$searchQuery}&tbm=shop&tbs=vw:l";
    echo "<p>URL di ricerca: <a href='$url' target='_blank'>$url</a></p>";
    
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    // Aggiungi header per sembrare un browser normale
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: it-IT,it;q=0.8,en-US;q=0.5,en;q=0.3',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Cache-Control: max-age=0'
    ));
    
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
    
    // Cerchiamo URL immagini direttamente nel codice sorgente
    echo "<h2>Ricerca URLs immagini nel codice sorgente...</h2>";
    $directImageUrls = [];

    // Pattern migliorato per estrarre URL da JavaScript
    $patterns = [
        // Immagine in JSON con "https:" come prefisso
        '/(?:"https:\\\\?\/\\\\?\/[^"]+\.(?:jpg|jpeg|png|webp|gif))(?=")/',
        // Immagine in variabili JavaScript
        '/(?:src|image|img|url|link)(?:\s*=\s*|\s*:\s*)(?:\'|")(https?:\/\/[^\'"\s]+\.(?:jpg|jpeg|png|webp|gif))(?:[\'"])/',
        // Immagini in array JS o oggetti
        '/\[(?:\'|")(https?:\/\/[^\'"\s]+\.(?:jpg|jpeg|png|webp|gif))(?:[\'"])/',
        // Percorsi immagini con protocollo relativo
        '/(?:"\\\\?\/\\\\?\/[^"]+\.(?:jpg|jpeg|png|webp|gif))(?=")/',
        // URL standard
        '/(https?:\/\/[^\'"\s]+\.(?:jpg|jpeg|png|webp|gif))(?=[\'"\s])/'
    ];

    $totalMatches = 0;
    foreach ($patterns as $index => $pattern) {
        if (preg_match_all($pattern, $html, $matches)) {
            $count = count($matches[1] ?? $matches[0]);
            $totalMatches += $count;
            echo "<p>Pattern #" . ($index + 1) . " ha trovato $count URL di immagini</p>";
            
            $matchSet = isset($matches[1]) ? $matches[1] : $matches[0];
            foreach ($matchSet as $matchedUrl) {
                $url = $matchedUrl;
                
                // Rimuovi escape sequences
                $url = str_replace('\/', '/', $url);
                $url = preg_replace('/\\\\u([0-9a-fA-F]{4})/', '&#x\\1;', $url);
                $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
                
                // Per URL che iniziano con doppio slash
                if (strpos($url, '//') === 0) {
                    $url = 'https:' . $url;
                }

                // Rimuovi qualsiasi quota all'inizio o alla fine
                $url = trim($url, '"\'');
                
                if (strpos($url, 'icon') === false && 
                    strpos($url, 'logo') === false &&
                    strpos($url, 'pixel') === false &&
                    strpos($url, 'x-small') === false &&
                    strpos($url, 'profile') === false &&
                    strpos($url, 'avatar') === false &&
                    strlen($url) > 40) {  // Skip piccole URLs e icone
                    $directImageUrls[] = $url;
                }
            }
        }
    }

    // Cerca specificamente strutture JavaScript di Google Shopping
    $jsPattern = '/s\d+x\d+":"([^"]+\.(?:jpg|jpeg|png|webp|gif))"/i';
    if (preg_match_all($jsPattern, $html, $matches)) {
        echo "<p>Trovate " . count($matches[1]) . " URL di immagini nelle strutture JS di Google Shopping</p>";
        foreach ($matches[1] as $imgUrl) {
            $url = $imgUrl;
            if (strpos($url, '//') === 0) {
                $url = 'https:' . $url;
            }
            $directImageUrls[] = $url;
        }
    }

    // Elimina duplicati e limita il numero
    $directImageUrls = array_slice(array_unique($directImageUrls), 0, 30);
    echo "<p>Filtrate a " . count($directImageUrls) . " immagini potenzialmente rilevanti</p>";

    // Aggiungi anche tentativi di URL comuni per prodotti noti
    $commonImagePatterns = [
        "https://www.nutella.com/it/it/sites/nutella20_it/files/styles/product_detail/public/2021-08/nutella-jar-200g-it.png",
        "https://static.ferrero.com/globalcms/immagini/2917.jpg",
        "https://m.media-amazon.com/images/I/61TKLzE+J+L._AC_UF894,1000_QL80_.jpg",
        "https://buonitalia.zizzu.it/wp-content/uploads/2020/10/Nutella-450-g.jpg"
    ];

    if (strpos(strtolower($test_nome), 'nutella') !== false) {
        $directImageUrls = array_merge($directImageUrls, $commonImagePatterns);
        echo "<p>Aggiunte " . count($commonImagePatterns) . " URL di immagini comuni per prodotti Nutella</p>";
    }
    
    // Combina i risultati trovati
    $allImages = array_unique(array_merge($directImageUrls));
    
    if (empty($allImages)) {
        throw new Exception("Nessuna immagine trovata nei risultati di ricerca");
    }
    
    // Mostra le immagini trovate
    echo "<h2>Immagini trovate: " . count($allImages) . "</h2>";
    echo "<div id='images-container' style='display: flex; flex-wrap: wrap; gap: 10px;'>";
    
    $validImages = 0;
    
    foreach ($allImages as $index => $imgSrc) {
        $safeImgSrc = htmlspecialchars($imgSrc);
        echo "<div class='image-container' style='border: 1px solid #ccc; padding: 10px; text-align: center; width: 220px;'>";
        echo "<div style='height: 200px; width: 200px; display: flex; align-items: center; justify-content: center; background-color: #f9f9f9;'>";
        echo "<img src='$safeImgSrc' data-index='$index' style='max-width: 180px; max-height: 180px;' onerror=\"this.onerror=null; this.parentNode.parentNode.classList.add('error-image'); this.style.display='none'; this.nextElementSibling.style.display='block';\" onload=\"this.parentNode.parentNode.classList.add('valid-image'); imageLoaded($index);\"/>";
        echo "<div class='error-placeholder' style='display:none; width: 100%; height: 100%; font-size: 12px; color: #999; display: flex; flex-direction: column; align-items: center; justify-content: center;'><svg width='48' height='48' viewBox='0 0 24 24'><path fill='#ddd' d='M21.9 21.9l-8.49-8.49-9.82-9.82L2.1 2.1.69 3.51 3 5.83V19c0 1.1.9 2 2 2h13.17l2.31 2.31 1.42-1.41zM5 19V7.83l7.17 7.17H5zm1-16c-.55 0-1 .45-1 1v.39l2 2V5c0-.55.45-1 1-1h10c.55 0 1 .45 1 1v10c0 .55-.45 1-1 1h-3.17l2 2H19c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2H6z'/></svg><p>Immagine non caricata</p></div>";
        echo "</div>";
        echo "<small>Immagine #" . ($index + 1) . "</small><br>";
        echo "<div style='margin: 5px 0; max-height: 40px; overflow: hidden;'>";
        echo "<a href='$safeImgSrc' target='_blank' style='font-size: 10px; word-break: break-all; display: block;'>Visualizza URL</a>";
        echo "</div>";
        echo "<button class='use-button' onclick='selectImage(\"$safeImgSrc\", \"$test_ean\")' style='margin-top: 5px; padding: 5px 10px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;'>Usa questa</button>";
        echo "</div>";
        $validImages++;
        
        // Limita a massimo 30 immagini mostrate
        if ($validImages >= 30) {
            break;
        }
    }
    
    echo "</div>";
    
    // Messaggio di assistenza se non ci sono immagini
    if ($validImages == 0) {
        echo "<div style='margin: 20px; padding: 20px; background-color: #f8f8f8; border-radius: 5px;'>";
        echo "<h3>Nessuna immagine trovata</h3>";
        echo "<p>Suggerimenti:</p>";
        echo "<ul>";
        echo "<li>Google potrebbe bloccare le richieste automatiche. Prova ad accedere direttamente all'URL in un browser: <a href='$url' target='_blank'>$url</a></li>";
        echo "<li>Prova un altro codice EAN o un nome prodotto diverso</li>";
        echo "<li>Prova a usare uno dei seguenti URL per Nutella:</li>";
        echo "<ul>";
        foreach ($commonImagePatterns as $url) {
            echo "<li><a href='$url' target='_blank'>$url</a></li>";
        }
        echo "</ul>";
        echo "</ul>";
        echo "</div>";
    }
    
    // JavaScript per selezionare un'immagine e migliorare UI
    echo "<script>
    var loadedImages = 0;
    var totalImages = " . $validImages . ";

    function imageLoaded(index) {
        loadedImages++;
        document.getElementById('loading-status').innerText = loadedImages + ' di ' + totalImages + ' immagini caricate';
        var progressPercent = (loadedImages / totalImages) * 100;
        document.getElementById('progress-bar').style.width = progressPercent + '%';
        
        if (loadedImages >= totalImages) {
            document.getElementById('loading-container').style.backgroundColor = '#e6ffe6';
            document.getElementById('loading-status').innerText = 'Tutte le immagini caricate!';
        }
    }

    function selectImage(imgSrc, ean) {
        if (confirm('Vuoi usare questa immagine per il prodotto con EAN ' + ean + '?')) {
            window.location.href = 'save_image.php?url=' + encodeURIComponent(imgSrc) + '&ean=' + ean;
        }
    }

    // Scorciatoie da tastiera per selezionare le immagini
    document.addEventListener('keydown', function(e) {
        if (e.key >= '1' && e.key <= '9') {
            var index = parseInt(e.key) - 1;
            var buttons = document.querySelectorAll('.use-button');
            if (index < buttons.length) {
                buttons[index].click();
            }
        }
    });
    </script>";

    // Loading status
    echo "<div id='loading-container' style='position: fixed; bottom: 0; left: 0; right: 0; background-color: #f5f5f5; padding: 10px; border-top: 1px solid #ddd; display: flex; flex-direction: column;'>";
    echo "<div style='display: flex; justify-content: space-between; margin-bottom: 5px;'>";
    echo "<span id='loading-status'>0 di " . $validImages . " immagini caricate</span>";
    echo "<button onclick='this.parentNode.parentNode.style.display=\"none\"' style='border: none; background: none; cursor: pointer;'>×</button>";
    echo "</div>";
    echo "<div style='width: 100%; background-color: #eee; height: 10px; border-radius: 5px;'>";
    echo "<div id='progress-bar' style='width: 0%; height: 100%; background-color: #4CAF50; border-radius: 5px;'></div>";
    echo "</div>";
    echo "</div>";

    // Aggiungi stili CSS
    echo "<style>
    .error-image { border-color: #ffcccb; background-color: #ffecec; }
    .valid-image { border-color: #c3e6cb; background-color: #e6ffe6; }
    .image-container:hover { box-shadow: 0 0 5px rgba(0,0,0,0.2); }
    .use-button:hover { background-color: #45a049; }
    </style>";
    
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