<?php
/**
 * debug_ean.php - Script di debug per testare lo scraping di barcodelookup.com
 */

// Configura l'output
ini_set('display_errors', 1);
error_reporting(E_ALL);

// EAN di test valido da provare
$test_ean = "8004120919770"; // Un EAN reale di un prodotto (esempio: Nutella)

echo "<h1>Test di scraping per barcodelookup.com</h1>";
echo "<p>EAN: <strong>$test_ean</strong></p>";

// URL del sito
$url = "https://www.barcodelookup.com/" . $test_ean;

echo "<p>URL: <a href='$url' target='_blank'>$url</a></p>";

// Impostazioni per il contesto di file_get_contents
$opts = [
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: it-IT,it;q=0.8,en-US;q=0.5,en;q=0.3'
        ]
    ]
];

$context = stream_context_create($opts);

try {
    // Recupera il contenuto della pagina
    echo "<h2>Tentativo di recupero pagina...</h2>";
    $html = @file_get_contents($url, false, $context);
    
    if ($html === false) {
        throw new Exception("Impossibile recuperare la pagina. Verificare che l'URL sia accessibile e che il firewall non blocchi la richiesta.");
    }
    
    // Estrai immagini con DOM
    echo "<h2>Estrazione informazioni...</h2>";
    
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    
    // Cerca le immagini
    $imgNodes = $xpath->query('//div[@id="productImageThumbs"]//img');
    
    if ($imgNodes->length === 0) {
        // Prova a cercare nell'immagine principale
        $imgNodes = $xpath->query('//div[@id="largeProductImage"]//img');
    }
    
    // Mostra le immagini trovate
    if ($imgNodes->length > 0) {
        echo "<h3>Immagini trovate: " . $imgNodes->length . "</h3>";
        echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";
        
        foreach ($imgNodes as $index => $imgNode) {
            // Verifico che sia un elemento DOM prima di usare getAttribute
            if ($imgNode instanceof \DOMElement) {
                $imgSrc = $imgNode->getAttribute('src');
                echo "<div style='border: 1px solid #ccc; padding: 10px; text-align: center;'>";
                echo "<img src='$imgSrc' style='max-width: 200px; max-height: 200px;'><br>";
                echo "<small>Immagine #" . ($index + 1) . "</small>";
                echo "</div>";
            }
        }
        
        echo "</div>";
    } else {
        echo "<p>Nessuna immagine trovata</p>";
    }
    
    // Cerchiamo anche il titolo del prodotto
    $titleNodes = $xpath->query('//h1[@class="product-details-title"]');
    if ($titleNodes->length > 0) {
        echo "<h3>Titolo prodotto:</h3>";
        echo "<p>" . $titleNodes->item(0)->textContent . "</p>";
    }
    
    // Cerchiamo informazioni aggiuntive
    $infoNodes = $xpath->query('//div[@class="product-details-essentials"]');
    if ($infoNodes->length > 0) {
        echo "<h3>Informazioni prodotto:</h3>";
        echo "<div style='background-color: #f5f5f5; padding: 15px; border-radius: 5px;'>";
        echo $infoNodes->item(0)->ownerDocument->saveHTML($infoNodes->item(0));
        echo "</div>";
    }
    
    // Mostra il codice HTML grezzo in un area nascondibile
    echo "<h2>Codice HTML grezzo della pagina</h2>";
    echo "<details>";
    echo "<summary>Clicca per mostrare/nascondere il codice HTML</summary>";
    echo "<pre style='background-color: #f0f0f0; padding: 10px; max-height: 500px; overflow: auto;'>";
    echo htmlspecialchars($html);
    echo "</pre>";
    echo "</details>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background-color: #ffebee; padding: 15px; border-radius: 5px;'>";
    echo "<h3>Errore</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
    
    echo "<h3>Possibili soluzioni:</h3>";
    echo "<ul>";
    echo "<li>Verificare che il codice EAN sia corretto</li>";
    echo "<li>Controllare che il sito barcodelookup.com sia accessibile</li>";
    echo "<li>Il server potrebbe bloccare le richieste (necessario un proxy)</li>";
    echo "<li>Potrebbe essere necessario utilizzare cURL invece di file_get_contents</li>";
    echo "</ul>";
} 