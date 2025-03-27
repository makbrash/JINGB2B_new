<?php
// mime_test.php
header('Content-Type: text/plain');

echo "Test del MIME type server\n\n";

// Ottieni informazioni sulla configurazione del server
echo "Server software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";

// Test per alcuni tipi MIME comuni
$extensions = [
    'jpg' => 'Dovrebbe essere image/jpeg',
    'jpeg' => 'Dovrebbe essere image/jpeg',
    'png' => 'Dovrebbe essere image/png',
    'css' => 'Dovrebbe essere text/css',
    'js' => 'Dovrebbe essere application/javascript'
];

foreach ($extensions as $ext => $comment) {
    $test_file = 'test.' . $ext;
    $mime = mime_content_type($test_file) ?: 'non determinato';
    echo "$ext => $mime ($comment)\n";
}

// Mostra le informazioni sul percorso catalogo
$catalog_path = $_SERVER['DOCUMENT_ROOT'] . '/public/catalogo/';
echo "\nInfo directory catalogo:\n";
echo "Percorso: $catalog_path\n";
echo "Esiste: " . (is_dir($catalog_path) ? 'Sì' : 'No') . "\n";
echo "Leggibile: " . (is_readable($catalog_path) ? 'Sì' : 'No') . "\n";

// Elenco alcuni file nella directory catalogo
if (is_dir($catalog_path) && is_readable($catalog_path)) {
    echo "\nAlcuni file nel catalogo:\n";
    $files = array_slice(scandir($catalog_path), 0, 10);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        $fullpath = $catalog_path . $file;
        $mime = mime_content_type($fullpath) ?: 'non determinato';
        $size = filesize($fullpath);
        echo "$file => $mime ($size bytes)\n";
    }
}
?>