<?php
$context = stream_context_create([
    "http" => [
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36",
        "timeout" => 30
    ]
]);

$ean = "8000036019535";
$description = "Amuchina Pavimenti Aloe 1500 Ml";

// Prova con una ricerca specifica per prodotto
$url = "https://www.google.com/search?q=" . urlencode($ean . " " . $description) . "&source=lnms&tbm=isch&sa=X";

$html = file_get_contents($url, false, $context);

// Le immagini in Google Images spesso sono incluse in strutture JSON nelle pagine
preg_match_all('/\["(https:\/\/[^"]+\.(?:jpg|jpeg|png|gif))",\d+,\d+\]/', $html, $matches);

$images = [];
foreach ($matches[1] as $image_url) {
    // Rimuovi le dimensioni thumbnail dall'URL se presenti
    $image_url = preg_replace('/=w\d+-h\d+(?:-p)?-k-no/', '=s0', $image_url);
    $images[] = $image_url;
    
    if (count($images) >= 10) break;
}

// Se non trovi immagini con il metodo sopra, prova un altro approccio
if (empty($images)) {
    preg_match_all('/"ou":"(https:\/\/[^"]+\.(?:jpg|jpeg|png|gif))"/', $html, $matches);
    foreach ($matches[1] as $image_url) {
        $images[] = $image_url;
        if (count($images) >= 10) break;
    }
}

// Stampa gli URL delle immagini
foreach ($images as $img) {
    echo $img . "\n";
}

// Opzionale: Salva la prima immagine trovata localmente
if (!empty($images)) {
    $img_content = file_get_contents($images[0], false, $context);
    if ($img_content) {
        file_put_contents("../public/catalogo_test/".$ean.".jpg", $img_content);
        echo "Immagine salvata come '".$ean.".jpg'\n";
    }
}
?>