<?php
// File: api/getModifiedData.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';

try {
    // Ottieni il timestamp dell'ultimo aggiornamento del client
    $data = json_decode(file_get_contents("php://input"), true);
    $lastUpdate = isset($data['last_update']) ? $data['last_update'] : '1970-01-01 00:00:00';
    
    // 1. Ottieni tutti i prodotti modificati dopo l'ultimo aggiornamento
    $prodottiModificati = $medooDB->select(
        "prodotti", 
        "*",
        [
            "date_update[>]" => $lastUpdate,
            "eliminato" => 0
        ]
    );
    
    // 2. Ottieni tutti i prodotti con immagini modificate dopo l'ultimo aggiornamento
    $immaginiModificate = $medooDB->select(
        "prodotti",
        ["ean", "immagine", "data_modifica_immagine"],
        [
            "data_modifica_immagine[>]" => $lastUpdate,
            "eliminato" => 0
        ]
    );
    
    // 3. Ottieni la data dell'ultimo aggiornamento del database
    $lastDbUpdate = $medooDB->max("prodotti", "date_update", ["eliminato" => 0]);
    $lastImgUpdate = $medooDB->max("prodotti", "data_modifica_immagine", ["eliminato" => 0]);
    
    // Usa il più recente tra i due timestamp
    $latestUpdate = ($lastDbUpdate > $lastImgUpdate) ? $lastDbUpdate : $lastImgUpdate;
    
    // 4. Prepara la risposta
    echo json_encode([
        "success" => true,
        "prodotti_modificati" => $prodottiModificati,
        "immagini_modificate" => array_column($immaginiModificate, 'ean'),
        "last_update" => $latestUpdate
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?> 