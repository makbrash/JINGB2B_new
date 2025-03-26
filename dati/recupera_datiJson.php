<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "../includes/db.php"; // Carica Medoo

// Percorso del file JSON
$jsonFile = 'catalogo.json'; 

// Legge il contenuto del JSON
$jsonData = file_get_contents($jsonFile);
$prodotti = json_decode($jsonData, true);


if (!$prodotti) {
    die("<h3>❌ Errore nella lettura del file JSON</h3>");
}

// 📌 Mostra il totale degli elementi contenuti nel JSON
$totaleProdotti = count($prodotti);
echo "<h3>🔹 Totale prodotti nel JSON: <strong>$totaleProdotti</strong></h3>";

// Inizializza i contatori
$processedCount = 0;
$insertedCount = 0;
$updatedCount = 0;
$skippedCount = 0;
$errorCount = 0;
$imagesSaved = 0;
$imagesMissing = 0;
$imagesError = 0;

// Percorso della cartella immagini
$imgDir = __DIR__ . "/../public/catalogo/";

// Crea la cartella immagini se non esiste
if (!is_dir($imgDir)) {
    mkdir($imgDir, 0777, true);
}

// Inizia l'elaborazione dei prodotti
foreach ($prodotti as $prodotto) {
    $processedCount++;

    // Controlla se l'EAN è presente e valido
    if (!isset($prodotto['ean']) || empty($prodotto['ean'])) {
        echo "<li class='error'>❌ Prodotto senza EAN: {$prodotto['titolo']}</li>";
        $skippedCount++;
        continue;
    }

    // Estrai dati dal JSON
    $titolo = $prodotto['titolo'];
    $ean = trim($prodotto['ean']);
    $prezzo = str_replace(" €", "", $prodotto['prezzo']);
    $prezzo = number_format((float)str_replace(",", ".", $prezzo), 2, ".", "");
    $disponibilita = intval($prodotto['disponibilita']);

    // **Fix per il valore "pezzi"**
    $pezzi = isset($prodotto['pezzi']) ? preg_replace('/\D/', '', $prodotto['pezzi']) : 1;
    $pezzi = intval($pezzi); // Assicura che sia solo un numero

    $immagineBase64 = $prodotto['immagine'] ?? null;

    // Controlla se il prodotto esiste già nel database
    $prodottoEsistente = $medooDB->get("prodotti", [
        "titolo", "prezzo", "disponibilita", "pezzi"
    ], ["ean" => $ean]);

    if ($prodottoEsistente) {
        // **Controllo delle modifiche**
        $modifiche = [];

        if ($prodottoEsistente["titolo"] !== $titolo) {
            $modifiche[] = "titolo (<strong>{$prodottoEsistente['titolo']}</strong> → <strong>$titolo</strong>)";
        }
        if ($prodottoEsistente["prezzo"] != $prezzo) {
            $modifiche[] = "prezzo (<strong>{$prodottoEsistente['prezzo']}</strong> → <strong>$prezzo</strong>)";
        }
        if ($prodottoEsistente["disponibilita"] != $disponibilita) {
            $modifiche[] = "disponibilità (<strong>{$prodottoEsistente['disponibilita']}</strong> → <strong>$disponibilita</strong>)";
        }
        if ($prodottoEsistente["pezzi"] != $pezzi) {
            $modifiche[] = "pezzi (<strong>{$prodottoEsistente['pezzi']}</strong> → <strong>$pezzi</strong>)";
        }

        if (!empty($modifiche)) {
            // Se ci sono modifiche, aggiorna il database
            $medooDB->update("prodotti", [
                "titolo" => $titolo,
                "prezzo" => $prezzo,
                "disponibilita" => $disponibilita,
                "pezzi" => $pezzi
            ], ["ean" => $ean]);

            $updatedCount++;
            echo "<li class='update'>🔄 Aggiornato: <strong>EAN:</strong> $ean | " . implode(" | ", $modifiche) . "</li>";
        } else {
            // Nessuna modifica, saltiamo l'update
            echo "<li class='info'>✅ Nessuna modifica per: <strong>EAN:</strong> $ean</li>";
        }
    } else {
        // **Gestione immagini**
        if (!empty($immagineBase64) && str_contains($immagineBase64, ',')) {
            list(, $data) = explode(',', $immagineBase64);
            $decodedImage = base64_decode($data);

            if ($decodedImage) {
                $imageFileName = $ean . ".jpg";
                $imagePath = $imgDir . $imageFileName;
                file_put_contents($imagePath, $decodedImage);
                $imagesSaved++;
            } else {

                echo "<li class='error'>❌ Errore nella decodifica immagine per il prodotto: <strong>$titolo</strong> (EAN: $ean)</li>";
                $imageFileName = "no-image.jpg"; // Immagine di default
                $imagesError++;
            }
        } else {
            echo "<li class='warning'>⚠️ Immagine non trovata per: <strong>$titolo</strong> (EAN: $ean)</li>";
            $imageFileName = "no-image.jpg"; // Immagine di default
            $imagesMissing++;
        }

        // **Inserisce il prodotto nel database**
        try {
            $medooDB->insert("prodotti", [
                "titolo" => $titolo,
                "ean" => $ean,
                "prezzo" => $prezzo,
                "disponibilita" => $disponibilita,
                "pezzi" => $pezzi,
                "immagine" => $imageFileName
            ]);
            $insertedCount++;
            echo "<li class='insert'>✅ Inserito: <strong>EAN:</strong> $ean | <strong>$titolo</strong> | Prezzo: $prezzo € | Disponibilità: $disponibilita | Confezione: $pezzi | Img: $imageFileName</li>";
        } catch (Exception $e) {
            echo "<li class='error'>❌ Errore inserimento prodotto: <strong>$titolo</strong> (EAN: $ean) - <em>{$e->getMessage()}</em></li>";
            $errorCount++;
        }
    }
}

// **Log finale con riepilogo**
echo "<h3>📊 Riepilogo importazione:</h3>";
echo "<ul>";
echo "<li>📄 <strong>Prodotti nel JSON:</strong> $totaleProdotti</li>";
echo "<li>✔️ <strong>Elaborati:</strong> $processedCount</li>";
echo "<li>✅ <strong>Inseriti:</strong> $insertedCount</li>";
echo "<li>🔄 <strong>Aggiornati:</strong> $updatedCount</li>";
echo "<li>⚠️ <strong>Scartati:</strong> $skippedCount</li>";
echo "<li>❌ <strong>Errori:</strong> $errorCount</li>";
echo "<li>🖼️ <strong>Immagini salvate:</strong> $imagesSaved</li>";
echo "<li>⚠️ <strong>Immagini mancanti:</strong> $imagesMissing</li>";
echo "<li>❌ <strong>Immagini con errore:</strong> $imagesError</li>";
echo "</ul>";

echo "<h3>✅ Importazione completata!</h3>";

?>
