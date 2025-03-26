<?php
/**
 * Configurazione del database con Medoo
 * 
 * Questo file gestisce la connessione al database tramite la libreria Medoo
 */

// Controlla se è necessario includere l'autoloader di Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Se non c'è Composer, dà per scontato che Medoo sia già incluso manualmente
    if (!class_exists('Medoo\Medoo')) {
        die('Medoo non è disponibile. Installa utilizzando Composer o includi manualmente la libreria.');
    }
}

$hostname_std_conn = "localhost";
$database_std_conn = "esvieguz_JINGB2B"; ////database su dominio vitaletti.it
$username_std_conn = "esvieguz_JINGB2B";
$password_std_conn = "RiQNPz#H0B&j";


use Medoo\Medoo;
// Initialize

// Inizializzazione della connessione al database

    $medooDB = new Medoo([
        'database_type' => 'mysql',
        'database_name' => $database_std_conn,
        'server' => 'localhost',
        'username' => $username_std_conn,
        'password' => $password_std_conn,
        'charset' => 'utf8mb4',
	    'collation' => 'utf8mb4_general_ci',
	    'port' => 3306,
    ]);
    
 


?>