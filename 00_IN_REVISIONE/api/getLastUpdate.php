<?php
// File: getLastUpdate.php



header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php'; // qui dentro hai $database = new Medoo([...]);

try {
    // 1) Contiamo i record
    $count = $medooDB->count("aggiornamenti", [
        "eliminato" => 0
    ]);

    // 2) Se 0, inseriamo un record fittizio
    if ($count === 0) {
        $fakeTime = date('Y-m-d H:i:s', strtotime('-1 week'));
        $medooDB->insert("aggiornamenti", [
            "time_stamp" => $fakeTime,
            "negozio"    => "defaultShop",
            "log"        => "Inserito record fittizio",
            "eliminato"  => 0
        ]);
    }

    // 3) Recuperiamo il record con time_stamp più recente
    $record = $medooDB->get("aggiornamenti", "time_stamp", [
        "eliminato" => 0,
        "ORDER" => ["time_stamp" => "DESC"]
    ]);

    if ($record) {
        echo json_encode([
            "success"    => true,
            "lastUpdate" => $record
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "error"   => "Nessun aggiornamento trovato o creazione fallita."
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error"   => $e->getMessage()
    ]);
}
