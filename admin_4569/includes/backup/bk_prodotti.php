<?php
/**
 * Funzioni per il backup e ripristino della tabella prodotti
 */

/**
 * Crea un backup della tabella prodotti
 * @return int ID del backup creato
 */
function createDatabaseBackup() {
    global $medooDB;
    
    try {
        // Crea tabella backup se non esiste
        createBackupTablesIfNotExist();
        
        // Genera nome del backup
        $backupName = 'Sincronizzazione Excel ' . date('d/m/Y H:i:s');
        
        // Inserisci record nella tabella di backup
        $medooDB->insert('prodotti_backups', [
            'backup_name' => $backupName,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['admin']['id'] ?? 0,
            'note' => 'Backup pre-sincronizzazione automatico'
        ]);
        
        $backupId = $medooDB->id();
        
        // Ottiene struttura tabella prodotti per poter copiare tutte le colonne automaticamente
        $columnsQuery = "SHOW COLUMNS FROM prodotti";
        $stmt = $medooDB->pdo->prepare($columnsQuery);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // Crea l'elenco delle colonne escludendo 'id' (verrà rinominato come product_id)
        $columnsList = array_diff($columns, ['id']);
        $columnsString = implode(', ', $columnsList);
        
        // Copia i dati nella tabella di backup, includendo tutte le colonne trovate
        $query = "INSERT INTO prodotti_backup_data 
                  (backup_id, product_id, {$columnsString}) 
                  SELECT 
                      {$backupId}, id, {$columnsString}
                  FROM 
                      prodotti";
        
        $stmt = $medooDB->pdo->prepare($query);
        $stmt->execute();
        
        // Elimina backup in eccesso (mantiene solo gli ultimi 5)
        cleanupOldBackups(5);
        
        error_log("Creato backup database ID: " . $backupId);
        return $backupId;
    } catch (Exception $e) {
        error_log("Errore nel backup database: " . $e->getMessage());
        return false;
    }
}

/**
 * Elimina i backup più vecchi
 * @param int $keepCount Numero di backup da mantenere
 */
function cleanupOldBackups($keepCount = 5) {
    global $medooDB;
    
    try {
        // Trova i backup da eliminare (tutti tranne i $keepCount più recenti)
        $oldBackups = $medooDB->select(
            'prodotti_backups', 
            ['id'],
            [
                'ORDER' => ['created_at' => 'DESC'],
                'LIMIT' => [$keepCount, 1000] // Salta i primi $keepCount e prendi il resto
            ]
        );
        
        if (empty($oldBackups)) {
            return; // Nessun backup da eliminare
        }
        
        $oldBackupIds = array_column($oldBackups, 'id');
        
        // Elimina i backup più vecchi
        // La tabella prodotti_backup_data ha vincoli foreign key CASCADE DELETE
        $medooDB->delete('prodotti_backups', ['id' => $oldBackupIds]);
        
        error_log("Eliminati " . count($oldBackupIds) . " backup vecchi");
    } catch (Exception $e) {
        error_log("Errore nell'eliminazione dei backup vecchi: " . $e->getMessage());
    }
}

/**
 * Crea le tabelle necessarie per il sistema di backup se non esistono
 */
function createBackupTablesIfNotExist() {
    global $medooDB;
    
    // Ottiene struttura tabella prodotti per ricreare la struttura nella tabella di backup
    $columnsQuery = "SHOW COLUMNS FROM prodotti";
    $stmt = $medooDB->pdo->prepare($columnsQuery);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tabella per i metadati dei backup
    $backupsTableQuery = "
        CREATE TABLE IF NOT EXISTS prodotti_backups (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            backup_name VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            created_by INT(11) NOT NULL,
            note TEXT,
            is_restored TINYINT(1) DEFAULT 0,
            restored_at DATETIME NULL,
            restored_by INT(11) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    // Costruisce la definizione delle colonne per la tabella di backup_data
    $columnDefinitions = [];
    $columnDefinitions[] = "id INT(11) AUTO_INCREMENT PRIMARY KEY";
    $columnDefinitions[] = "backup_id INT(11) NOT NULL";
    $columnDefinitions[] = "product_id INT(11) NOT NULL";
    
    foreach ($columns as $column) {
        if ($column['Field'] != 'id') { // Salta 'id' perché diventa product_id
            $type = $column['Type'];
            $null = $column['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
            $default = $column['Default'] !== null ? "DEFAULT '" . $column['Default'] . "'" : '';
            
            $columnDefinitions[] = "`{$column['Field']}` {$type} {$null} {$default}";
        }
    }
    
    // Tabella per i dati dei backup
    $backupDataTableQuery = "
        CREATE TABLE IF NOT EXISTS prodotti_backup_data (
            " . implode(",\n            ", $columnDefinitions) . ",
            FOREIGN KEY (backup_id) REFERENCES prodotti_backups(id) ON DELETE CASCADE,
            INDEX (backup_id),
            INDEX (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $medooDB->pdo->exec($backupsTableQuery);
    
    // Verifica se la tabella esiste già prima di ricrearla
    $tableExistsQuery = "SHOW TABLES LIKE 'prodotti_backup_data'";
    $tableExists = $medooDB->pdo->query($tableExistsQuery)->rowCount() > 0;
    
    if (!$tableExists) {
        $medooDB->pdo->exec($backupDataTableQuery);
    } else {
        // Se la tabella esiste, verifica se ha tutte le colonne necessarie
        syncBackupTableColumns();
    }
}

/**
 * Sincronizza le colonne della tabella di backup con le colonne della tabella prodotti
 */
function syncBackupTableColumns() {
    global $medooDB;
    
    try {
        // Ottiene le colonne correnti della tabella prodotti
        $prodottiColumnsQuery = "SHOW COLUMNS FROM prodotti";
        $stmt = $medooDB->pdo->prepare($prodottiColumnsQuery);
        $stmt->execute();
        $prodottiColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $prodottiColumnsNames = array_column($prodottiColumns, 'Field');
        
        // Ottiene le colonne correnti della tabella prodotti_backup_data
        $backupColumnsQuery = "SHOW COLUMNS FROM prodotti_backup_data";
        $stmt = $medooDB->pdo->prepare($backupColumnsQuery);
        $stmt->execute();
        $backupColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $backupColumnsNames = array_column($backupColumns, 'Field');
        
        // Verifica quali colonne mancano alla tabella di backup
        $missingColumns = [];
        foreach ($prodottiColumns as $column) {
            if ($column['Field'] != 'id' && !in_array($column['Field'], $backupColumnsNames)) {
                $missingColumns[] = $column;
            }
        }
        
        // Aggiunge le colonne mancanti
        foreach ($missingColumns as $column) {
            $type = $column['Type'];
            $null = $column['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
            $default = $column['Default'] !== null ? "DEFAULT '" . $column['Default'] . "'" : '';
            
            $addColumnQuery = "ALTER TABLE prodotti_backup_data ADD COLUMN `{$column['Field']}` {$type} {$null} {$default}";
            $medooDB->pdo->exec($addColumnQuery);
            
            error_log("Aggiunta colonna '{$column['Field']}' alla tabella prodotti_backup_data");
        }
    } catch (Exception $e) {
        error_log("Errore nella sincronizzazione delle colonne della tabella di backup: " . $e->getMessage());
    }
}

/**
 * Ripristina un backup della tabella prodotti
 * @param int $backupId ID del backup da ripristinare
 * @return bool True se il ripristino è avvenuto con successo
 */
function restoreBackup($backupId) {
    global $medooDB;
    
    try {
        // Debug log
        error_log("Inizio ripristino backup ID: " . $backupId);
        
        // Verifica che il backup esista
        $backup = $medooDB->get('prodotti_backups', '*', ['id' => $backupId]);
        if (!$backup) {
            error_log("Errore: Backup ID " . $backupId . " non trovato");
            throw new Exception("Backup non trovato");
        }
        
        error_log("Backup trovato: " . $backup['backup_name']);
        
        // Ottiene la struttura della tabella di backup
        $backupColumnsQuery = "SHOW COLUMNS FROM prodotti_backup_data";
        $stmt = $medooDB->pdo->prepare($backupColumnsQuery);
        $stmt->execute();
        $backupColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug log
        error_log("Numero colonne trovate: " . count($backupColumns));
        
        // Crea l'elenco delle colonne escludendo quelle non pertinenti
        $columnsList = [];
        foreach ($backupColumns as $column) {
            if (!in_array($column['Field'], ['id', 'backup_id', 'product_id'])) {
                $columnsList[] = $column['Field'];
            }
        }
        $columnsString = implode(', ', $columnsList);
        
        error_log("Colonne da ripristinare: " . $columnsString);
        
        // Verifica se esistono dati di backup
        $countQuery = "SELECT COUNT(*) FROM prodotti_backup_data WHERE backup_id = :backup_id";
        $stmt = $medooDB->pdo->prepare($countQuery);
        $stmt->execute([':backup_id' => $backupId]);
        $count = $stmt->fetchColumn();
        
        error_log("Numero di record nel backup: " . $count);
        
        if ($count == 0) {
            error_log("Errore: Nessun dato trovato per il backup ID " . $backupId);
            throw new Exception("Nessun dato trovato per questo backup");
        }
        
        // Transazione per garantire integrità
        $medooDB->pdo->beginTransaction();
        
        // Svuota la tabella prodotti
        $medooDB->pdo->exec("DELETE FROM prodotti");
        error_log("Tabella prodotti svuotata");
        
        // Ripristina i dati dal backup
        $query = "INSERT INTO prodotti (id, {$columnsString}) 
                  SELECT 
                      product_id, {$columnsString}
                  FROM 
                      prodotti_backup_data 
                  WHERE 
                      backup_id = :backup_id";
        
        $stmt = $medooDB->pdo->prepare($query);
        $result = $stmt->execute([':backup_id' => $backupId]);
        
        if (!$result) {
            error_log("Errore SQL nell'inserimento: " . print_r($stmt->errorInfo(), true));
            throw new Exception("Errore nell'inserimento dei dati: " . $stmt->errorInfo()[2]);
        }
        
        $rowCount = $stmt->rowCount();
        error_log("Prodotti ripristinati: " . $rowCount);
        
        // Aggiorna lo stato del backup
        $medooDB->update('prodotti_backups', [
            'is_restored' => 1,
            'restored_at' => date('Y-m-d H:i:s'),
            'restored_by' => $_SESSION['admin']['id'] ?? 0
        ], [
            'id' => $backupId
        ]);
        
        $medooDB->pdo->commit();
        
        error_log("Ripristino completato con successo. Backup ID: " . $backupId);
        return true;
    } catch (Exception $e) {
        // Rollback in caso di errore
        if ($medooDB->pdo->inTransaction()) {
            $medooDB->pdo->rollBack();
            error_log("Eseguito rollback della transazione");
        }
        error_log("Errore nel ripristino backup: " . $e->getMessage());
        return false;
    }
}

/**
 * Ottiene l'elenco dei backup disponibili
 * @return array Lista dei backup
 */
function getAvailableBackups() {
    global $medooDB;
    
    return $medooDB->select('prodotti_backups', [
        'id',
        'backup_name',
        'created_at',
        'note',
        'is_restored',
        'restored_at'
    ], [
        'ORDER' => ['created_at' => 'DESC']
    ]);
}
?>
