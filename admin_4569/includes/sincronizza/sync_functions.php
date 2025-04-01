<?php
/**
 * Funzioni per la sincronizzazione del catalogo
 */

// Includi le funzioni di backup
require_once __DIR__ . '/../backup/bk_prodotti.php';

/**
 * Rileva automaticamente il tipo di colonne nel file Excel
 */
function detectColumnTypes($data) {
    error_log("Inizio rilevamento colonne");
    
    if (empty($data) || count($data) < 2) {
        error_log("Dati insufficienti per il rilevamento colonne");
        return []; 
    }

    $headerRow = array_map('trim', array_map('strtolower', $data[0]));
    $dataRow = isset($data[1]) ? $data[1] : [];
    
    $columnCount = count($headerRow);
    $mappings = [
        'ean' => null,
        'titolo' => null,
        'prezzo' => null,
    ];

    $patterns = [
        'ean' => [
            'headers' => ['ean', 'codice', 'barcode', 'gtin', 'upc', 'codice prodotto', 'product code', 'cod', 'codice ean'],
            'test' => function ($value) {
                $value = trim((string)$value);
                return is_numeric($value) && strlen($value) >= 8 && strlen($value) <= 14;
            }
        ],
        'titolo' => [
            'headers' => ['titolo', 'nome', 'descrizione', 'prodotto', 'product', 'name', 'title', 'descrizione', 'desc'],
            'test' => function ($value) {
                return is_string($value) && strlen(trim($value)) > 3;
            }
        ],
        'prezzo' => [
            'headers' => ['prezzo', 'price', 'costo', 'cost', 'vendita', 'retail', 'prezzo vendita', 'prezzo al pubblico'],
            'test' => function ($value) {
                $value = str_replace(['€', '$', '£', ','], ['',' ', '', '.'], (string)$value);
                return is_numeric(trim($value)) && floatval($value) > 0 && floatval($value) < 10000;
            }
        ]
    ];

    // Cerca nei titoli delle colonne
    for ($i = 0; $i < $columnCount; $i++) {
        $header = $headerRow[$i];
        foreach ($patterns as $field => $pattern) {
            if (in_array($header, $pattern['headers']) || 
                array_filter($pattern['headers'], function($h) use ($header) { 
                    return strpos($header, $h) !== false; 
                })) {
                $mappings[$field] = $i + 1;
                break;
            }
        }
    }

    // Se non trovato nei titoli, analizza i dati
    if (!empty($dataRow)) {
        for ($i = 0; $i < $columnCount; $i++) {
            if (!empty($dataRow[$i])) {
                foreach ($patterns as $field => $pattern) {
                    if (is_null($mappings[$field]) && $pattern['test']($dataRow[$i])) {
                        $mappings[$field] = $i + 1;
                        break;
                    }
                }
            }
        }
    }

    return $mappings;
}

/**
 * Processa il file Excel e sincronizza il database
 */
function processExcelFile($filePath, $mappings, $skipRows, $negozioid = null, $isPromo = false) {
    global $medooDB;

    try {
        error_log("Inizio elaborazione file Excel");
        
        // Crea backup della tabella prodotti prima della sincronizzazione
        $backupId = createDatabaseBackup();
        if (!$backupId) {
            throw new Exception("Impossibile creare il backup del database");
        }
        
        $spreadsheet = loadSpreadsheet($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        $results = initializeResults($skipRows, $highestRow);
        
        // Contatori per le statistiche
        $totalExcelRows = $highestRow;
        $totalSkippedRows = $skipRows;
        $totalEmptyEanRows = 0;
        
        // Ottieni prodotti esistenti - filtro per negozio se specificato
        $where = [];
        if ($negozioid) {
            $where['negozio_id'] = $negozioid;
            error_log("Filtro prodotti per negozio_id: " . $negozioid);
        }
        $allProducts = $medooDB->select("prodotti", ["id", "ean", "titolo", "prezzo", "prezzo_promo", "immagine", "stato", "negozio_id"], $where);
        $productsData = indexProductsByEan($allProducts);
        $allEans = array_keys($productsData);
        $excelEans = [];

        // Se è una sincronizzazione promozionale, azzera tutti i prezzi promo per il negozio selezionato
        if ($isPromo && $negozioid) {
            resetAllPromoForStore($negozioid, $results);
        }

        // Indici delle colonne mappate
        $eanColumnIndex = $mappings['ean'];
        $titleColumnIndex = $mappings['titolo'];
        $priceColumnIndex = $mappings['prezzo'];

        // Elabora le righe
        for ($row = $skipRows + 1; $row <= $highestRow; $row++) {
            $eanColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($eanColumnIndex);
            $ean = trim((string)$worksheet->getCell($eanColLetter . $row)->getValue());
            
            if (empty($ean)) {
                $totalEmptyEanRows++;
                continue;
            }
            
            $titleColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($titleColumnIndex);
            $priceColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($priceColumnIndex);
            
            $title = trim((string)$worksheet->getCell($titleColLetter . $row)->getValue());
            $price = normalizePrice($worksheet->getCell($priceColLetter . $row)->getValue());

            $excelEans[] = $ean;
            $results['totals']['processed_rows']++;

            if (in_array($ean, $allEans)) {
                updateExistingProduct($ean, $title, $price, $productsData, $results, $negozioid, $isPromo);
            } else {
                addNewProduct($ean, $title, $price, $results, $negozioid, $isPromo);
            }
        }

        // Disattiva prodotti non presenti - solo se non è una lista promo
        if (!$isPromo) {
            disableUnusedProducts($allEans, $excelEans, $productsData, $results, $negozioid);
        }

        // Trova EAN duplicati
        $eanCounts = array_count_values($excelEans);
        $duplicateEans = [];
        foreach ($eanCounts as $ean => $count) {
            if ($count > 1) {
                $duplicateEans[] = [
                    'ean' => $ean,
                    'count' => $count
                ];
            }
        }

        // Aggiungi i totali per Excel e Database con dettaglio
        $results['totals']['excel_raw_rows'] = $totalExcelRows;
        $results['totals']['excel_skipped_rows'] = $totalSkippedRows;
        $results['totals']['excel_empty_rows'] = $totalEmptyEanRows;
        $results['totals']['excel_total'] = count(array_unique($excelEans));
        $results['totals']['excel_duplicate_eans'] = count($excelEans) - count(array_unique($excelEans));
        $results['duplicate_eans'] = $duplicateEans;
        $results['totals']['db_total'] = count($allProducts);
        $results['backup_id'] = $backupId;
        $results['is_promo'] = $isPromo;
        
        // Aggiungi informazioni sul negozio selezionato
        if ($negozioid) {
            // Ottieni il nome del negozio
            $negozio = $medooDB->get("negozi", "nome", ["id" => $negozioid]);
            $results['negozio'] = [
                'id' => $negozioid,
                'nome' => $negozio
            ];
        }

        // Salva backup e log
        $backupFileName = saveBackupAndLog($filePath, $results);
        $results['backup_file'] = $backupFileName;

        // Pulisci il file Excel temporaneo
        cleanupTempFile($filePath);

        return [
            'success' => true,
            'results' => $results
        ];
    } catch (Exception $e) {
        // Pulisci il file Excel temporaneo anche in caso di errore
        cleanupTempFile($filePath);
        
        return [
            'success' => false,
            'message' => 'Errore nell\'elaborazione del file Excel: ' . $e->getMessage()
        ];
    }
}

/**
 * Funzioni di supporto per il processo di sincronizzazione
 */
function initializeResults($skipRows, $totalRows) {
    return [
        'disabled' => [],
        'price_updated' => [],
        'title_updated' => [],
        'new_added' => [],
        'unchanged' => [],
        'reactivated' => [],
        'skip_count' => $skipRows,
        'totals' => [
            'disabled' => 0,
            'price_updated' => 0,
            'title_updated' => 0,
            'new_added' => 0,
            'unchanged' => 0,
            'reactivated' => 0,
            'total_rows' => $totalRows,
            'processed_rows' => 0
        ]
    ];
}

function indexProductsByEan($products) {
    $indexed = [];
    foreach ($products as $product) {
        $indexed[$product['ean']] = $product;
    }
    return $indexed;
}

function processRow($worksheet, $row, $eanColumnIndex, $titleColumnIndex, $priceColumnIndex, 
                   &$excelEans, $allEans, $productsData, &$results) {
    global $medooDB;

    $eanColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($eanColumnIndex);
    $titleColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($titleColumnIndex);
    $priceColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($priceColumnIndex);

    $ean = trim((string)$worksheet->getCell($eanColLetter . $row)->getValue());
    if (empty($ean)) {
        return;
    }

    $title = trim((string)$worksheet->getCell($titleColLetter . $row)->getValue());
    $price = normalizePrice($worksheet->getCell($priceColLetter . $row)->getValue());

    $excelEans[] = $ean;
    $results['totals']['processed_rows']++;

    if (in_array($ean, $allEans)) {
        updateExistingProduct($ean, $title, $price, $productsData, $results);
    } else {
        addNewProduct($ean, $title, $price, $results);
    }
}

function normalizePrice($price) {
    $price = str_replace(['€', '$', '£', ','], ['',' ', '', '.'], (string)$price);
    return number_format((float)$price, 2, '.', '');
}

function updateExistingProduct($ean, $title, $price, $productsData, &$results, $negozioid = null, $isPromo = false) {
    global $medooDB;
    
    $existingProduct = $productsData[$ean];
    $changes = [];
    
    // Se è stato specificato un negozio, controlla se il prodotto appartiene a quel negozio
    if ($negozioid && (string)$existingProduct['negozio_id'] !== (string)$negozioid) {
        // Se il prodotto non appartiene al negozio selezionato, non aggiornarlo
        // ma piuttosto aggiungi una nota nei risultati
        $results['skipped'][] = [
            'id' => $existingProduct['id'],
            'ean' => $ean,
            'titolo' => $existingProduct['titolo'],
            'negozio_attuale' => $existingProduct['negozio_id'],
            'negozio_richiesto' => $negozioid
        ];
        if (!isset($results['totals']['skipped'])) {
            $results['totals']['skipped'] = 0;
        }
        $results['totals']['skipped']++;
        return;
    }

    // Riattiva se disattivato o se è una sincronizzazione promo
    if ($existingProduct['stato'] === "0" || $isPromo) {
        $medooDB->update("prodotti", ["stato" => "attivo"], ["ean" => $ean]);
        if ($existingProduct['stato'] === "0") {
            $results['reactivated'][] = [
                'id' => $existingProduct['id'],
                'ean' => $ean,
                'titolo' => $existingProduct['titolo']
            ];
            $results['totals']['reactivated']++;
        }
    }

    // Se è una sincronizzazione promo, aggiorna solo il prezzo promo
    if ($isPromo) {
        if (!isset($existingProduct['prezzo_promo']) || (float)$existingProduct['prezzo_promo'] !== (float)$price) {
            $medooDB->update("prodotti", ["prezzo_promo" => $price], ["ean" => $ean]);
            $results['promo_updated'][] = [
                'id' => $existingProduct['id'],
                'ean' => $ean,
                'titolo' => $existingProduct['titolo'],
                'prezzo_promo_vecchio' => $existingProduct['prezzo_promo'] ?? 'Non impostato',
                'prezzo_promo_nuovo' => $price
            ];
            if (!isset($results['totals']['promo_updated'])) {
                $results['totals']['promo_updated'] = 0;
            }
            $results['totals']['promo_updated']++;
        }
        return; // In modalità promo non aggiorniamo il prezzo normale o il titolo
    }

    // Modalità normale (non promo) - continua con aggiornamenti standard
    
    // Aggiorna prezzo se cambiato
    if (abs((float)$existingProduct['prezzo'] - (float)$price) > 0.01) {
        $medooDB->update("prodotti", ["prezzo" => $price], ["ean" => $ean]);
        $results['price_updated'][] = [
            'id' => $existingProduct['id'],
            'ean' => $ean,
            'titolo' => $existingProduct['titolo'],
            'prezzo_vecchio' => $existingProduct['prezzo'],
            'prezzo_nuovo' => $price,
            'immagine' => $existingProduct['immagine']
        ];
        $results['totals']['price_updated']++;
    }

    // Aggiorna titolo se cambiato
    if ($existingProduct['titolo'] !== $title) {
        $medooDB->update("prodotti", ["titolo" => $title], ["ean" => $ean]);
        $results['title_updated'][] = [
            'id' => $existingProduct['id'],
            'ean' => $ean,
            'titolo_vecchio' => $existingProduct['titolo'],
            'titolo_nuovo' => $title,
            'immagine' => $existingProduct['immagine']
        ];
        $results['totals']['title_updated']++;
    }

    // Se nessun cambiamento
    if (empty($changes)) {
        $results['unchanged'][] = [
            'id' => $existingProduct['id'],
            'ean' => $ean,
            'titolo' => $existingProduct['titolo'],
            'immagine' => $existingProduct['immagine']
        ];
        $results['totals']['unchanged']++;
    }
}

function addNewProduct($ean, $title, $price, &$results, $negozioid = null, $isPromo = false) {
    global $medooDB;
    
    $data = [
        'ean' => $ean,
        'titolo' => $title,
        'stato' => 'attivo',
        'negozio_id' => $negozioid,
        'immagine' => 'no-image.jpg'
    ];
    
    // In modalità promo, imposta il prezzo standard solo se non esiste già un prodotto
    if ($isPromo) {
        // Controlla se il prodotto esiste già in altri negozi
        $existingProduct = $medooDB->get("prodotti", ["id", "prezzo"], ["ean" => $ean]);
        
        if ($existingProduct) {
            // Se esiste, usa il suo prezzo standard
            $data['prezzo'] = $existingProduct['prezzo'];
        } else {
            // Se non esiste, imposta lo stesso prezzo come standard
            $data['prezzo'] = $price;
        }
        
        // In ogni caso, imposta il prezzo promo
        $data['prezzo_promo'] = $price;
    } else {
        // In modalità normale, imposta solo il prezzo standard
        $data['prezzo'] = $price;
    }
    
    $medooDB->insert("prodotti", $data);
    
    $results['new_added'][] = [
        'id' => $medooDB->id(),
        'ean' => $ean,
        'titolo' => $title,
        'prezzo' => $data['prezzo'],
        'prezzo_promo' => $data['prezzo_promo'] ?? null,
        'is_promo' => $isPromo ? 'Sì' : 'No',
        'negozio_id' => $negozioid
    ];
    $results['totals']['new_added']++;
}

function disableUnusedProducts($allEans, $excelEans, $productsData, &$results, $negozioid = null) {
    global $medooDB;
    
    $productsToDisable = array_diff($allEans, $excelEans);
    foreach ($productsToDisable as $eanToDisable) {
        $product = $productsData[$eanToDisable];
        if ($product['stato'] !== "0") {
            // Salta prodotti di negozi diversi da quello selezionato
            if ($negozioid && (string)$product['negozio_id'] !== (string)$negozioid) {
                continue;
            }
            $medooDB->update("prodotti", ["stato" => "0"], ["ean" => $eanToDisable]);
            $results['disabled'][] = [
                'id' => $product['id'],
                'ean' => $eanToDisable,
                'titolo' => $product['titolo'],
                'immagine' => $product['immagine']
            ];
            $results['totals']['disabled']++;
        }
    }
}

function saveVersionCatalog($filePath, $results) {
    global $medooDB;
    
    $dataVersione = date('Y-m-d H:i:s');

    $medooDB->insert("aggiornamenti", [
        'time_stamp' => $dataVersione,
        'url_data' => '',
        'download_url_img_zip' => '',
        'tipo_aggiornamento' => 'prezzi_excel',
        'log' => json_encode([
            'file' => $_SESSION['excel_file_name'],
            'totals' => $results['totals']
        ]),
        'eliminato' => 0
    ]);

    return 'versione_catalogo '.$dataVersione;
}

/**
 * Pulisce un file temporaneo
 * @param string $filePath Percorso del file da eliminare
 */
function cleanupTempFile($filePath) {
    if (file_exists($filePath)) {
        unlink($filePath);
        error_log("File eliminato: " . $filePath);
    }
}

/**
 * Pulisce tutti i file temporanei nella directory temp
 */
function cleanupAllTempFiles() {
    $tempDir = __DIR__ . "/../../temp";
    
    if (is_dir($tempDir)) {
        $files = glob($tempDir . '/*.xlsx');
        $files = array_merge($files, glob($tempDir . '/*.xls'));
        $files = array_merge($files, glob($tempDir . '/*.csv'));
        
        $now = time();
        $deletedCount = 0;
        
        foreach ($files as $file) {
            // Elimina i file più vecchi di 24 ore
            if ($now - filemtime($file) > 86400) {
                unlink($file);
                $deletedCount++;
            }
        }
        
        error_log("Pulizia directory temp: eliminati " . $deletedCount . " file");
    }
}

/**
 * Salva backup e log
 * @param string $filePath Percorso del file Excel
 * @param array $results Risultati della sincronizzazione
 * @return string Nome del file di backup salvato
 */
function saveBackupAndLog($filePath, $results) {
    global $medooDB;
    
    $dataVersione = date('Y-m-d H:i:s');

    $medooDB->insert("aggiornamenti", [
        'time_stamp' => $dataVersione,
        'url_data' => '',
        'download_url_img_zip' => '',
        'tipo_aggiornamento' => 'prezzi_excel',
        'log' => json_encode([
            'file' => $_SESSION['excel_file_name'],
            'totals' => $results['totals']
        ]),
        'eliminato' => 0
    ]);

    return 'versione_catalogo '.$dataVersione;
}

/**
 * Azzera tutti i prezzi promo per un negozio specifico
 */
function resetAllPromoForStore($negozioid, &$results) {
    global $medooDB;
    
    try {
        // Conta quanti prodotti hanno prezzi promo
        $count = $medooDB->count("prodotti", [
            "AND" => [
                "negozio_id" => $negozioid,
                "prezzo_promo[!]" => null
            ]
        ]);
        
        // Azzera tutti i prezzi promo
        $medooDB->update("prodotti", 
            ["prezzo_promo" => null], 
            ["negozio_id" => $negozioid]
        );
        
        // Aggiungi statistiche al report
        if (!isset($results['promo_reset'])) {
            $results['promo_reset'] = [];
        }
        
        $results['promo_reset'][] = [
            'message' => "Azzerati tutti i prezzi promo",
            'count' => $count,
            'negozio_id' => $negozioid
        ];
        
        if (!isset($results['totals']['promo_reset'])) {
            $results['totals']['promo_reset'] = 0;
        }
        $results['totals']['promo_reset'] += $count;
        
        error_log("Azzerati " . $count . " prezzi promo per il negozio " . $negozioid);
        return true;
    } catch (Exception $e) {
        error_log("Errore nell'azzeramento dei prezzi promo: " . $e->getMessage());
        return false;
    }
}

// La funzione loadSpreadsheet è già definita in excel_functions.php
// Rimuovo la duplicazione che causa l'errore "Cannot redeclare loadSpreadsheet()"
?> 