<?php
/**
 * struttura_db.php - Gestione struttura database per sistema classificazione prodotti
 * 
 * Questo file contiene tutte le query necessarie per creare o aggiornare 
 * la struttura del database per il sistema di classificazione dei prodotti.
 * 
 * @author Marco Vitaletti
 * @version 1.0
 */

// Configurazione iniziale
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);

// Carica la connessione al database
require_once "../includes/db.php";

/**
 * Esegue tutte le query necessarie per aggiornare la struttura del database
 * @return array Array con risultati delle operazioni
 */
function aggiorna_struttura_db() {
    global $medooDB;
    $risultati = [];
    
    // Verifiche e creazioni tabelle/colonne
    $risultati['prodotti_tags'] = verifica_tabella_prodotti_tags();
    $risultati['tags_colonna'] = verifica_colonna_tags();
    $risultati['stato_tag_colonna'] = verifica_colonna_stato_tag();
    
    return $risultati;
}

/**
 * Verifica e crea la tabella prodotti_tags se non esiste
 * @return string Risultato dell'operazione
 */
function verifica_tabella_prodotti_tags() {
    global $medooDB;
    
    // Controlla se la tabella esiste
    $tables = $medooDB->query("SHOW TABLES LIKE 'prodotti_tags'")->fetchAll();
    
    if (empty($tables)) {
        // Crea la tabella
        try {
            $medooDB->query("
                CREATE TABLE IF NOT EXISTS prodotti_tags (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    prodotto_id INT(11) NOT NULL,
                    tag VARCHAR(100) NOT NULL,
                    PRIMARY KEY (id),
                    INDEX (prodotto_id),
                    FULLTEXT INDEX (tag)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
            return "Tabella prodotti_tags creata con successo.";
        } catch (Exception $e) {
            return "Errore nella creazione della tabella prodotti_tags: " . $e->getMessage();
        }
    }
    
    return "Tabella prodotti_tags già esistente.";
}

/**
 * Verifica e aggiunge la colonna 'tags' alla tabella prodotti se non esiste
 * @return string Risultato dell'operazione
 */
function verifica_colonna_tags() {
    global $medooDB;
    
    // Controlla se la colonna esiste
    $columns = $medooDB->query("SHOW COLUMNS FROM prodotti LIKE 'tags'")->fetchAll();
    
    if (empty($columns)) {
        // Aggiungi la colonna
        try {
            $medooDB->query("ALTER TABLE prodotti ADD COLUMN tags TEXT NULL AFTER marca_id");
            return "Colonna 'tags' aggiunta alla tabella prodotti.";
        } catch (Exception $e) {
            return "Errore nell'aggiunta della colonna 'tags': " . $e->getMessage();
        }
    }
    
    return "Colonna 'tags' già esistente.";
}

/**
 * Verifica e aggiunge la colonna 'stato_tag' alla tabella prodotti se non esiste
 * Usata per tracciare lo stato di elaborazione dei tag (0=non elaborato, 1=elaborato, 2=errore)
 * @return string Risultato dell'operazione
 */
function verifica_colonna_stato_tag() {
    global $medooDB;
    
    // Controlla se la colonna esiste
    $columns = $medooDB->query("SHOW COLUMNS FROM prodotti LIKE 'stato_tag'")->fetchAll();
    
    if (empty($columns)) {
        // Aggiungi la colonna
        try {
            $medooDB->query("ALTER TABLE prodotti ADD COLUMN stato_tag TINYINT(1) DEFAULT 0 AFTER tags");
            return "Colonna 'stato_tag' aggiunta alla tabella prodotti.";
        } catch (Exception $e) {
            return "Errore nell'aggiunta della colonna 'stato_tag': " . $e->getMessage();
        }
    }
    
    return "Colonna 'stato_tag' già esistente.";
}

// Esegui solo se richiesto direttamente
if (isset($_GET['esegui']) && $_GET['esegui'] == 1) {
    $risultati = aggiorna_struttura_db();
    
    header('Content-Type: application/json');
    echo json_encode($risultati);
    exit;
}