<?php
/**
 * Funzioni per il backup e ripristino della tabella prodotti
 */

/**
 * Crea un backup della tabella prodotti
 * @return int ID del backup creato o false in caso di errore
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
        
        // Prima di procedere, verifica che la struttura delle tabelle sia compatibile
        $checkStructureResult = checkTableStructures();
        if ($checkStructureResult !== true) {
            // C'è un problema con le strutture delle tabelle
            error_log("Errore nella verifica delle strutture: " . $checkStructureResult);
            return false;
        }
        
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
 * Controlla che la struttura delle tabelle sia compatibile
 * @return bool|string true se tutto ok, altrimenti messaggio di errore
 */
function checkTableStructures() {
    global $medooDB;
    
    try {
        // Ottiene colonne della tabella prodotti
        $prodottiColumnsQuery = "SHOW COLUMNS FROM prodotti";
        $stmt = $medooDB->pdo->prepare($prodottiColumnsQuery);
        $stmt->execute();
        $prodottiColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $prodottiColumnsNames = array_column($prodottiColumns, 'Field');
        
        // Ottiene colonne della tabella prodotti_backup_data
        $backupColumnsQuery = "SHOW COLUMNS FROM prodotti_backup_data";
        $stmt = $medooDB->pdo->prepare($backupColumnsQuery);
        $stmt->execute();
        $backupColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $backupColumnsNames = array_column($backupColumns, 'Field');
        
        // Rimuove le colonne speciali (id, backup_id, product_id) dall'elenco dei backup
        $backupColumnsNames = array_diff($backupColumnsNames, ['id', 'backup_id', 'product_id']);
        
        // Rimuove 'id' dai nomi delle colonne prodotti, poiché diventa product_id nel backup
        $prodottiColumnsForCompare = array_diff($prodottiColumnsNames, ['id']);
        
        // Controlla se ci sono colonne in prodotti che non esistono in backup_data
        $missingColumns = array_diff($prodottiColumnsForCompare, $backupColumnsNames);
        
        if (!empty($missingColumns)) {
            return "Le seguenti colonne sono presenti in 'prodotti' ma mancano in 'prodotti_backup_data': " . implode(", ", $missingColumns);
        }
        
        return true;
    } catch (Exception $e) {
        return "Errore nel controllo struttura tabelle: " . $e->getMessage();
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
 * Ripristina i prodotti da un backup specifico, seguendo esattamente la logica dell'immagine
 * @param int $backupId ID del backup da ripristinare
 * @return array Array con risultato dell'operazione
 */
function restoreProductsFromBackup($backupId) {
    global $medooDB;
    
    try {
        // Verifica che il backup esista
        $backup = $medooDB->get('prodotti_backups', '*', ['id' => $backupId]);
        if (!$backup) {
            return [
                'success' => false,
                'message' => "Backup ID {$backupId} non trovato",
                'error' => true
            ];
        }
        
        // Verifica compatibilità delle strutture
        $checkStructureResult = checkTableStructures();
        if ($checkStructureResult !== true) {
            return [
                'success' => false,
                'message' => $checkStructureResult,
                'error' => true,
                'error_message' => "Le colonne di prodotti sono diverse da prodotti_backup_data. " . $checkStructureResult
            ];
        }
        
        // Verifica se esistono dati di backup
        $countQuery = "SELECT COUNT(*) FROM prodotti_backup_data WHERE backup_id = :backup_id";
        $stmt = $medooDB->pdo->prepare($countQuery);
        $stmt->execute([':backup_id' => $backupId]);
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            return [
                'success' => false,
                'message' => "Nessun dato trovato per il backup ID {$backupId}",
                'error' => true
            ];
        }
        
        // Verifica e correggi EAN duplicati se necessario (molto raro)
        $duplicateQuery = "SELECT ean, COUNT(*) AS num 
                           FROM prodotti_backup_data 
                           WHERE backup_id = :backup_id AND ean IS NOT NULL AND ean != ''
                           GROUP BY ean 
                           HAVING num > 1";
        $stmt = $medooDB->pdo->prepare($duplicateQuery);
        $stmt->execute([':backup_id' => $backupId]);
        $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($duplicates)) {
            return [
                'success' => false,
                'message' => "Impossibile ripristinare: il backup contiene EAN duplicati",
                'error' => true,
                'error_message' => "Il backup contiene " . count($duplicates) . " EAN duplicati"
            ];
        }
        
        // Ottiene la struttura delle colonne per il ripristino
        $backupColumnsQuery = "SHOW COLUMNS FROM prodotti_backup_data";
        $stmt = $medooDB->pdo->prepare($backupColumnsQuery);
        $stmt->execute();
        $backupColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Crea l'elenco delle colonne escludendo quelle non pertinenti
        $columnsList = [];
        foreach ($backupColumns as $column) {
            if (!in_array($column['Field'], ['id', 'backup_id', 'product_id'])) {
                $columnsList[] = $column['Field'];
            }
        }
        $columnsString = implode(', ', $columnsList);
        
        // Log per debug
        error_log("Inizio ripristino backup ID: " . $backupId . ", righe da ripristinare: " . $count);
        
        try {
            // Usiamo una tabella temporanea per evitare problemi con i vincoli
            // 1. Creiamo una tabella temporanea senza vincoli
            $dropTempTableQuery = "DROP TABLE IF EXISTS temp_prodotti";
            $medooDB->pdo->exec($dropTempTableQuery);
            
            $createTempTableQuery = "CREATE TABLE temp_prodotti LIKE prodotti";
            $medooDB->pdo->exec($createTempTableQuery);
            
            // Rimuoviamo tutti i vincoli dalla tabella temporanea
            $dropIndexesQuery = "
                SELECT CONCAT('ALTER TABLE temp_prodotti DROP INDEX ', index_name, ';')
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = 'temp_prodotti'
                AND index_name <> 'PRIMARY'
            ";
            $indexesToDrop = $medooDB->pdo->query($dropIndexesQuery)->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($indexesToDrop as $dropIndexQuery) {
                $medooDB->pdo->exec($dropIndexQuery);
            }
            
            error_log("Tabella temporanea creata senza vincoli");
            
            // 2. Inseriamo i dati del backup nella tabella temporanea
            $insertTempQuery = "INSERT INTO temp_prodotti (id, {$columnsString}) 
                                SELECT product_id, {$columnsString}
                                FROM prodotti_backup_data 
                                WHERE backup_id = :backup_id";
            
            $stmt = $medooDB->pdo->prepare($insertTempQuery);
            $result = $stmt->execute([':backup_id' => $backupId]);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Errore nell'inserimento dati nella tabella temporanea: " . implode(" ", $errorInfo));
            }
            
            $rowsInserted = $stmt->rowCount();
            error_log("Inseriti {$rowsInserted} record nella tabella temporanea");
            
            // 3. Scambiamo le tabelle (non disponibile in tutte le versioni di MySQL)
            $medooDB->pdo->beginTransaction();
            
            // Prima svuotiamo la tabella originale
            $truncateQuery = "TRUNCATE TABLE prodotti";
            $medooDB->pdo->exec($truncateQuery);
            
            // Poi copiamo i dati dalla tabella temporanea
            $copyFromTempQuery = "INSERT INTO prodotti SELECT * FROM temp_prodotti";
            $medooDB->pdo->exec($copyFromTempQuery);
            
            $rowsRestored = $medooDB->pdo->query("SELECT COUNT(*) FROM prodotti")->fetchColumn();
            error_log("Copiati {$rowsRestored} record nella tabella prodotti");
            
            // Eliminiamo la tabella temporanea
            $dropTempQuery = "DROP TABLE temp_prodotti";
            $medooDB->pdo->exec($dropTempQuery);
            
            // Aggiorna lo stato del backup
            $medooDB->update('prodotti_backups', [
                'is_restored' => 1,
                'restored_at' => date('Y-m-d H:i:s'),
                'restored_by' => $_SESSION['admin']['id'] ?? 0
            ], [
                'id' => $backupId
            ]);
            
            $medooDB->pdo->commit();
            
            return [
                'success' => true,
                'message' => "Ripristino completato con successo. Ripristinati {$rowsRestored} prodotti dal backup.",
                'backup_id' => $backupId,
                'rows_restored' => $rowsRestored
            ];
        } catch (Exception $e) {
            // Verifica in modo più sicuro se una transazione è attiva
            try {
                if ($medooDB->pdo->inTransaction()) {
                    $medooDB->pdo->rollBack();
                }
            } catch (Exception $rollbackEx) {
                // Ignora eventuali errori nel rollback
                error_log("Errore nel rollback della transazione: " . $rollbackEx->getMessage());
            }
            
            // Pulisci eventuali tabelle temporanee
            try {
                $medooDB->pdo->exec("DROP TABLE IF EXISTS temp_prodotti");
            } catch (Exception $cleanupEx) {
                // Ignora errori nella pulizia
            }
            
            throw $e;
        }
    } catch (Exception $e) {
        error_log("Errore nel ripristino backup: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Errore durante il ripristino',
            'error' => true,
            'error_message' => $e->getMessage()
        ];
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

/**
 * Crea un backup della tabella prodotti esattamente come mostrato nell'immagine
 * @return array Array con risultato dell'operazione
 */
function createProductsBackup() {
    global $medooDB;
    
    try {
        // Crea tabella backup se non esiste
        createBackupTablesIfNotExist();
        
        // Verifica compatibilità delle strutture
        $checkStructureResult = checkTableStructures();
        if ($checkStructureResult !== true) {
            return [
                'success' => false,
                'message' => $checkStructureResult,
                'error' => true,
                'error_message' => "Le colonne di prodotti sono diverse da prodotti_backup_data. " . $checkStructureResult
            ];
        }
        
        // Genera nome del backup
        $backupName = 'Backup Manuale ' . date('d/m/Y H:i:s');
        
        // Inserisci record nella tabella di backup
        $medooDB->insert('prodotti_backups', [
            'backup_name' => $backupName,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['admin']['id'] ?? 0,
            'note' => 'Backup manuale'
        ]);
        
        $backupId = $medooDB->id();
        
        // Ottiene colonne dalla tabella prodotti per la copia
        $columnsQuery = "SHOW COLUMNS FROM prodotti";
        $stmt = $medooDB->pdo->prepare($columnsQuery);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // Crea l'elenco delle colonne escludendo 'id' (verrà mappato come product_id)
        $columnsList = array_diff($columns, ['id']);
        $columnsString = implode(', ', $columnsList);
        
        // Esegue l'operazione di copia come mostrato nell'immagine
        // FROM 'prodotti' -> FROM 'prodotti_backup_data'
        $query = "INSERT INTO prodotti_backup_data 
                  (backup_id, product_id, {$columnsString}) 
                  SELECT 
                      {$backupId}, id, {$columnsString}
                  FROM 
                      prodotti";
        
        $stmt = $medooDB->pdo->prepare($query);
        $result = $stmt->execute();
        
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Errore durante la creazione del backup',
                'error' => true,
                'error_message' => implode(" ", $stmt->errorInfo())
            ];
        }
        
        $rowCount = $stmt->rowCount();
        
        return [
            'success' => true,
            'message' => "Backup completato con successo. Copiati {$rowCount} prodotti nel backup ID: {$backupId}",
            'backup_id' => $backupId,
            'rows_copied' => $rowCount
        ];
    } catch (Exception $e) {
        error_log("Errore nella creazione del backup: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Errore durante la creazione del backup',
            'error' => true,
            'error_message' => $e->getMessage()
        ];
    }
}

/**
 * Corregge eventuali EAN vuoti o NULL nel backup
 * @param int $backupId ID del backup da correggere
 * @return array Risultato dell'operazione
 */
function fixEmptyEansInBackup($backupId) {
    global $medooDB;
    
    try {
        // Trova i record con EAN vuoti o NULL
        $emptyEansQuery = "SELECT id, product_id FROM prodotti_backup_data 
                          WHERE backup_id = :backup_id AND (ean = '' OR ean IS NULL)";
        $stmt = $medooDB->pdo->prepare($emptyEansQuery);
        $stmt->execute([':backup_id' => $backupId]);
        $emptyEans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $fixed = 0;
        
        foreach ($emptyEans as $record) {
            // Genera un EAN unico basato sull'ID del prodotto
            $uniqueEan = 'FIXED-' . $backupId . '-' . $record['product_id'] . '-' . uniqid();
            
            // Aggiorna il record
            $medooDB->update('prodotti_backup_data', 
                ['ean' => $uniqueEan], 
                ['id' => $record['id']]
            );
            
            $fixed++;
        }
        
        return [
            'success' => true,
            'fixed_count' => $fixed,
            'message' => "Corretti $fixed record con EAN vuoti nel backup"
        ];
    } catch (Exception $e) {
        error_log("Errore nella correzione degli EAN vuoti: " . $e->getMessage());
        return [
            'success' => false,
            'error' => true,
            'error_message' => $e->getMessage()
        ];
    }
}

/**
 * Elimina un backup e i relativi dati
 * @param int $backupId ID del backup da eliminare
 * @return array Risultato dell'operazione
 */
function deleteBackup($backupId) {
    global $medooDB;
    
    try {
        // Verifica che il backup esista
        $backup = $medooDB->get('prodotti_backups', '*', ['id' => $backupId]);
        if (!$backup) {
            return [
                'success' => false,
                'message' => "Backup ID {$backupId} non trovato",
                'error' => true
            ];
        }
        
        // Elimina il backup (cascade eliminerà anche i dati correlati)
        $medooDB->delete('prodotti_backups', ['id' => $backupId]);
        
        return [
            'success' => true,
            'message' => "Backup '{$backup['backup_name']}' eliminato con successo",
            'backup_id' => $backupId
        ];
    } catch (Exception $e) {
        error_log("Errore nell'eliminazione del backup: " . $e->getMessage());
        return [
            'success' => false,
            'message' => "Errore durante l'eliminazione del backup",
            'error' => true,
            'error_message' => $e->getMessage()
        ];
    }
}
?>