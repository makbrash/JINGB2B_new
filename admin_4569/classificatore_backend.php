<?php
/**
 * classificatore_backend.php - Logica di backend per classificazione prodotti
 * 
 * Questo file contiene le funzioni per gestire la categorizzazione dei prodotti,
 * il salvataggio dei tag e le interazioni con il database.
 * 
 * @author Marco Vitaletti
 * @version 1.0
 */

// Includi l'API OpenAI
require_once "classificatore_api.php";

// Percorso base per le immagini
$base_img_path = __DIR__ . "/../public/catalogo/";

// Mappa globale di cache per ridurre le query al DB
$cache_categorie = [];
$cache_sottocategorie = [];
$cache_marche = [];
$unique_tags = [];
$tag_counts = [];

/**
 * Carica i dati delle categorie, sottocategorie e marche
 * @return array Array associativo con le liste e le mappe 
 */
 
 
 
 /**
 * Aggiorna manualmente i campi di classificazione per prodotti selezionati
 * @param array $product_ids Array di ID prodotti da aggiornare
 * @param array $update_fields Campi da aggiornare (categoria_id, sottocategoria_id, marca_id, tags)
 * @return array Risultato dell'operazione
 */
function aggiorna_classificazione_manuale($product_ids, $update_fields) {
    global $medooDB;
    
    if (empty($product_ids) || empty($update_fields)) {
        return [
            'success' => false,
            'message' => 'Parametri mancanti: nessun prodotto o campo da aggiornare specificato'
        ];
    }
    
    // Converte gli ID in interi per sicurezza
    $product_ids = array_map('intval', $product_ids);
    
    // Prepara array per aggiornamento
    $data = [];
    
    // Aggiungi categoria
    if (isset($update_fields['categoria_id']) && !empty($update_fields['categoria_id'])) {
        $data['categoria_id'] = intval($update_fields['categoria_id']);
    }
    
    // Aggiungi sottocategoria
    if (isset($update_fields['sottocategoria_id']) && !empty($update_fields['sottocategoria_id'])) {
        $data['sottocategoria_id'] = intval($update_fields['sottocategoria_id']);
    }
    
    // Aggiungi marca
    if (isset($update_fields['marca_id']) && !empty($update_fields['marca_id'])) {
        $data['marca_id'] = intval($update_fields['marca_id']);
    }
    
    // Gestione tag
    if (isset($update_fields['tags']) && isset($update_fields['tags_mode'])) {
        // Ottieni e prepara i nuovi tag
        $nuovi_tags = explode(',', $update_fields['tags']);
        $nuovi_tags = array_map(function($tag) {
            return trim(strtolower($tag));
        }, $nuovi_tags);
        $nuovi_tags = array_filter($nuovi_tags); // Rimuovi tag vuoti
        
        // Se modalità append, aggiungi ai tag esistenti per ogni prodotto
        if ($update_fields['tags_mode'] === 'append') {
            // Recupera prodotti per lavorare sui tag esistenti
            $prodotti = $medooDB->select("prodotti", [
                "id", 
                "tags"
            ], [
                "id" => $product_ids
            ]);
            
            // Aggiorna ogni prodotto individualmente per i tag
            foreach ($prodotti as $prodotto) {
                $tags_esistenti = json_decode($prodotto['tags'] ?: '[]', true);
                $combined_tags = array_unique(array_merge($tags_esistenti, $nuovi_tags));
                
                // Aggiorna il singolo prodotto
                $medooDB->update("prodotti", [
                    "tags" => json_encode($combined_tags, JSON_UNESCAPED_UNICODE),
                    "stato_tag" => 1 // Imposta come elaborato
                ], [
                    "id" => $prodotto['id']
                ]);
                
                // Aggiorna anche la tabella prodotti_tags
                salva_tags($prodotto['id'], $combined_tags);
            }
        } 
        // Se modalità replace, sostituisci i tag per tutti i prodotti
        else {
            $data['tags'] = json_encode($nuovi_tags, JSON_UNESCAPED_UNICODE);
            $data['stato_tag'] = 1; // Imposta come elaborato
            
            // Aggiorna anche la tabella prodotti_tags per tutti i prodotti
            foreach ($product_ids as $id) {
                salva_tags($id, $nuovi_tags);
            }
        }
    }
    
    // Se ci sono campi da aggiornare oltre ai tag in modalità append
    if (!empty($data)) {
        // Aggiorna i prodotti
        $medooDB->update("prodotti", $data, [
            "id" => $product_ids
        ]);
    }
    
    $count = count($product_ids);
    
    return [
        'success' => true,
        'message' => "Aggiornati $count prodotti con successo",
        'count' => $count
    ];
}

function carica_dati_classificazione() {
    global $medooDB, $cache_categorie, $cache_sottocategorie, $cache_marche;
    
    // Log per debug
    file_put_contents('debug_clasificazione.log', 'Inizio carica_dati_classificazione: ' . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    
    // Inizializza risultato
    $result = [
        'categorie_list' => [],
        'sottocategorie_list' => [],
        'marche_list' => [],
        'cat_id_by_name' => [],
        'subcat_id_by_name' => [],
        'marca_id_by_name' => []
    ];
    
    // Recupera categorie dal database - senza ORDER BY per ora
    try {
        // Recupera categorie dal database
        $categorie = $medooDB->select("categoria", ["id", "nome"]);
        $cache_categorie = $categorie;
        
        file_put_contents('debug_clasificazione.log', 'Dopo categorie: ' . (is_array($categorie) ? count($categorie) : 'non è un array') . "\n", FILE_APPEND);
        
        $result['categorie_list'] = is_array($categorie) ? array_column($categorie, 'nome') : [];
        $result['cat_id_by_name'] = is_array($categorie) ? array_column($categorie, 'id', 'nome') : [];
        
        // Qui potresti ordinare manualmente l'array se necessario
        asort($result['categorie_list']);
    } catch (Exception $e) {
        file_put_contents('debug_clasificazione.log', 'Errore query categorie: ' . $e->getMessage() . "\n", FILE_APPEND);
        // Valori di default in caso di errore
        $result['categorie_list'] = [];
        $result['cat_id_by_name'] = [];
    }
    
    // Recupera sottocategorie dal database
    try {
        $sottocategorie = $medooDB->select("sottocategoria", ["id", "nome", "categoria_id"]);
        $cache_sottocategorie = $sottocategorie;
        
        file_put_contents('debug_clasificazione.log', 'Dopo sottocategorie: ' . (is_array($sottocategorie) ? count($sottocategorie) : 'non è un array') . "\n", FILE_APPEND);
        
        $result['sottocategorie_list'] = is_array($sottocategorie) ? array_column($sottocategorie, 'nome') : [];
        
        if (is_array($sottocategorie)) {
            $result['subcat_id_by_name'] = array_combine(
                array_column($sottocategorie, 'nome'),
                array_map(function($item) {
                    return ['id' => $item['id'], 'categoria_id' => $item['categoria_id']];
                }, $sottocategorie)
            );
        } else {
            $result['subcat_id_by_name'] = [];
        }
        
        // Ordinamento manuale
        asort($result['sottocategorie_list']);
    } catch (Exception $e) {
        file_put_contents('debug_clasificazione.log', 'Errore query sottocategorie: ' . $e->getMessage() . "\n", FILE_APPEND);
        $result['sottocategorie_list'] = [];
        $result['subcat_id_by_name'] = [];
    }
    
    // Recupera marche dal database
    try {
        $marche = $medooDB->select("marche", ["id", "nome"]);
        $cache_marche = $marche;
        
        file_put_contents('debug_clasificazione.log', 'Dopo marche: ' . (is_array($marche) ? count($marche) : 'non è un array') . "\n", FILE_APPEND);
        
        $result['marche_list'] = is_array($marche) ? array_column($marche, 'nome') : [];
        $result['marca_id_by_name'] = is_array($marche) ? array_column($marche, 'id', 'nome') : [];
        
        // Ordinamento manuale
        asort($result['marche_list']);
    } catch (Exception $e) {
        file_put_contents('debug_clasificazione.log', 'Errore query marche: ' . $e->getMessage() . "\n", FILE_APPEND);
        $result['marche_list'] = [];
        $result['marca_id_by_name'] = [];
    }
    
    return $result;
}

/**
 * Ottiene prodotti nel range specificato o secondo criteri di selezione
 * @param array $options Opzioni di selezione (ids, range_start, range_end, stato_tag)
 * @return array Array di prodotti
 */
function ottieni_prodotti($options = []) {
    global $medooDB;
    
    $where = [];
    
    // Filtro per IDs specifici
    if (isset($options['ids']) && is_array($options['ids']) && !empty($options['ids'])) {
        $where["id"] = $options['ids'];
    }
    // Filtro per range
    else if (isset($options['range_start']) && isset($options['range_end'])) {
        $range_start = intval($options['range_start']);
        $range_end = intval($options['range_end']);
        
        if ($range_start > 0 && $range_end > 0) {
            $where["id[>=]"] = $range_start;
            $where["id[<=]"] = $range_end;
        }
    }
    
    // Filtro per stato tag
    if (isset($options['stato_tag'])) {
        $where["stato_tag"] = $options['stato_tag'];
    }
    
    // Filtro per categoria
    if (isset($options['categoria_id']) && $options['categoria_id'] > 0) {
        $where["categoria_id"] = intval($options['categoria_id']);
    }
    
    // Filtro per sottocategoria
    if (isset($options['sottocategoria_id']) && $options['sottocategoria_id'] > 0) {
        $where["sottocategoria_id"] = intval($options['sottocategoria_id']);
    }
    
    // Filtro per marca
    if (isset($options['marca_id']) && $options['marca_id'] > 0) {
        $where["marca_id"] = intval($options['marca_id']);
    }
    
    // Ordina sempre per ID ascendente
    $where["ORDER"] = ["id" => "ASC"];
    
    // Limita i risultati se richiesto, altrimenti usa un limite più grande
    if (isset($options['limit']) && $options['limit'] > 0) {
        $where["LIMIT"] = intval($options['limit']);
    } else {
        // Imposta un limite alto ma ragionevole se non specificato
        $where["LIMIT"] = 3000;
    }
    
    // Log per debug
    file_put_contents('debug_prodotti.log', 'Opzioni ricerca: ' . print_r($options, true) . "\n", FILE_APPEND);
    file_put_contents('debug_prodotti.log', 'Where: ' . print_r($where, true) . "\n", FILE_APPEND);
    
    // Seleziona i campi necessari
    return $medooDB->select("prodotti", [
        "id",
        "titolo",
        "immagine",
        "categoria_id",
        "sottocategoria_id",
        "marca_id",
        "tags",
        "stato_tag",
        "ean"
    ], $where);
}

/**
 * Classifica un singolo prodotto e salva i risultati nel database
 * @param array $prodotto Dati del prodotto da classificare
 * @return array Risultato della classificazione e stato dell'operazione
 */
function classifica_prodotto($prodotto) {
    global $medooDB, $base_img_path, $unique_tags;
    
    // Carica dati necessari per la classificazione
    $classificazione_data = carica_dati_classificazione();
    $categorie_list = $classificazione_data['categorie_list'];
    $sottocategorie_list = $classificazione_data['sottocategorie_list'];
    $marche_list = $classificazione_data['marche_list'];
    $cat_id_by_name = $classificazione_data['cat_id_by_name'];
    $subcat_id_by_name = $classificazione_data['subcat_id_by_name'];
    $marca_id_by_name = $classificazione_data['marca_id_by_name'];
    
    // Estrai dati del prodotto
    $id = $prodotto['id'];
    $titolo = $prodotto['titolo'];
    $immagine = $prodotto['immagine'];
    $image_path = $base_img_path . $immagine;
    
    // Valori precedenti
    $categoria_id_precedente = $prodotto['categoria_id'] ?: 0;
    $sottocategoria_id_precedente = $prodotto['sottocategoria_id'] ?: 0;
    $marca_id_precedente = $prodotto['marca_id'] ?: 0;
    $tags_precedenti = $prodotto['tags'] ?: "[]";
    
    // Ottieni i nomi per la visualizzazione
    $categoria_nome_precedente = get_name_by_id($categoria_id_precedente, 'categoria');
    $sottocategoria_nome_precedente = get_name_by_id($sottocategoria_id_precedente, 'sottocategoria');
    $marca_nome_precedente = get_name_by_id($marca_id_precedente, 'marche');
    
    // Array per la risposta
    $response = [
        'id' => $id,
        'titolo' => $titolo,
        'immagine' => $immagine,
        'precedente' => [
            'categoria' => [
                'id' => $categoria_id_precedente,
                'nome' => $categoria_nome_precedente
            ],
            'sottocategoria' => [
                'id' => $sottocategoria_id_precedente,
                'nome' => $sottocategoria_nome_precedente
            ],
            'marca' => [
                'id' => $marca_id_precedente,
                'nome' => $marca_nome_precedente
            ],
            'tags' => json_decode($tags_precedenti, true) ?: []
        ],
        'nuovo' => [],
        'stato' => 'errore',
        'messaggio' => ''
    ];
    
    try {
        // Classifica il prodotto con l'API OpenAI
        $classificazione = classifica_prodotto_api(
            $id, 
            $titolo, 
            $image_path, 
            $categorie_list, 
            $sottocategorie_list, 
            $marche_list
        );
        
        if (is_array($classificazione)) {
            // Estrai dati dalla classificazione
            $categoria_nome = $classificazione['categoria'];
            $sottocategoria_nome = $classificazione['sottocategoria'];
            $marca_nome = $classificazione['marca'];
            $tags = $classificazione['tags'] ?: [];
            
            // Ottieni tag strutturati se disponibili
            $tag_marca_prodotto = isset($classificazione['tag_marca_prodotto']) ? $classificazione['tag_marca_prodotto'] : "";
            $tag_tipo_prodotto = isset($classificazione['tag_tipo_prodotto']) ? $classificazione['tag_tipo_prodotto'] : "";
            $tag_caratteristica = isset($classificazione['tag_caratteristica']) ? $classificazione['tag_caratteristica'] : "";
            
            // Assicurati che i tag strutturati siano inclusi
            if (!empty($tag_marca_prodotto) && !in_array($tag_marca_prodotto, $tags)) {
                $tags[] = $tag_marca_prodotto;
            }
            if (!empty($tag_tipo_prodotto) && !in_array($tag_tipo_prodotto, $tags)) {
                $tags[] = $tag_tipo_prodotto;
            }
            if (!empty($tag_caratteristica) && !in_array($tag_caratteristica, $tags)) {
                $tags[] = $tag_caratteristica;
            }
            
            // Normalizza i tag (lowercase, trim)
            $tags = array_map(function($tag) {
                return trim(strtolower($tag));
            }, $tags);
            
            // Rimuovi eventuali duplicati
            $tags = array_unique($tags);
            
            // Ottieni gli ID dalle mappe
            $categoria_id = isset($cat_id_by_name[$categoria_nome]) ? $cat_id_by_name[$categoria_nome] : 0;
            $sottocategoria_id = isset($subcat_id_by_name[$sottocategoria_nome]) ? $subcat_id_by_name[$sottocategoria_nome]['id'] : 0;
            $marca_id = isset($marca_id_by_name[$marca_nome]) ? $marca_id_by_name[$marca_nome] : 0;
            
            // Converti l'array di tag in JSON
            $tags_json = json_encode($tags, JSON_UNESCAPED_UNICODE);
            
            // Aggiorna il database
            $updated_data = [
                "categoria_id" => $categoria_id,
                "sottocategoria_id" => $sottocategoria_id,
                "marca_id" => $marca_id,
                "tags" => $tags_json,
                "stato_tag" => 1 // 1 = elaborato con successo
            ];
            
            $medooDB->update("prodotti", $updated_data, ["id" => $id]);
            
            // Salva i tag nella tabella prodotti_tags
            salva_tags($id, $tags);
            
            // Aggiorna statistiche tag unici
            foreach ($tags as $tag) {
                if (!isset($unique_tags[$tag])) {
                    $unique_tags[$tag] = 1;
                } else {
                    $unique_tags[$tag]++;
                }
            }
            
            // Prepara i dati per la risposta
            $response['nuovo'] = [
                'categoria' => [
                    'id' => $categoria_id,
                    'nome' => $categoria_nome
                ],
                'sottocategoria' => [
                    'id' => $sottocategoria_id,
                    'nome' => $sottocategoria_nome
                ],
                'marca' => [
                    'id' => $marca_id,
                    'nome' => $marca_nome
                ],
                'tags' => $tags,
                'tag_strutturati' => [
                    'marca_prodotto' => $tag_marca_prodotto,
                    'tipo_prodotto' => $tag_tipo_prodotto,
                    'caratteristica' => $tag_caratteristica
                ]
            ];
            
            $response['stato'] = 'successo';
            $response['messaggio'] = 'Prodotto classificato con successo';
        } else {
            // In caso di errore dall'API
            $medooDB->update("prodotti", ["stato_tag" => 2], ["id" => $id]); // 2 = errore
            $response['stato'] = 'errore';
            $response['messaggio'] = $classificazione; // Contiene il messaggio di errore
        }
    } catch (Exception $e) {
        // In caso di eccezione
        $medooDB->update("prodotti", ["stato_tag" => 2], ["id" => $id]); // 2 = errore
        $response['stato'] = 'errore';
        $response['messaggio'] = 'Errore: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Salva i tag nella tabella prodotti_tags
 * @param int $prodotto_id ID del prodotto
 * @param array $tags Array di tag
 * @return bool Successo dell'operazione
 */
function salva_tags($prodotto_id, $tags) {
    global $medooDB;
    
    try {
        // Elimina tutti i tag esistenti per questo prodotto
        $medooDB->delete("prodotti_tags", [
            "prodotto_id" => $prodotto_id
        ]);
        
        // Se non ci sono tag, termina
        if (empty($tags)) {
            return true;
        }
        
        // Prepara l'array di dati per inserimento multiplo
        $data = [];
        foreach ($tags as $tag) {
            $tag = trim(strtolower($tag));
            if (empty($tag)) continue;
            
            $data[] = [
                "prodotto_id" => $prodotto_id,
                "tag" => $tag
            ];
        }
        
        // Inserimento multiplo per performance
        if (!empty($data)) {
            $medooDB->insert("prodotti_tags", $data);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Errore nel salvare i tag: " . $e->getMessage());
        return false;
    }
}

/**
 * Ottiene il nome data una categoria, sottocategoria o marca dato un ID
 * @param int $id ID dell'entità
 * @param string $tipo Tipo di entità ('categoria', 'sottocategoria', 'marche')
 * @return string Nome dell'entità o messaggio di default
 */
function get_name_by_id($id, $tipo = 'categoria') {
    global $medooDB, $cache_categorie, $cache_sottocategorie, $cache_marche;
    
    if (empty($id)) {
        return "Non assegnata";
    }
    
    // Usa cache se disponibile
    if ($tipo == 'categoria' && !empty($cache_categorie)) {
        foreach ($cache_categorie as $cat) {
            if ($cat['id'] == $id) {
                return $cat['nome'];
            }
        }
    } elseif ($tipo == 'sottocategoria' && !empty($cache_sottocategorie)) {
        foreach ($cache_sottocategorie as $subcat) {
            if ($subcat['id'] == $id) {
                return $subcat['nome'];
            }
        }
    } elseif ($tipo == 'marche' && !empty($cache_marche)) {
        foreach ($cache_marche as $marca) {
            if ($marca['id'] == $id) {
                return $marca['nome'];
            }
        }
    }
    
    // Se non in cache, query al DB
    $result = $medooDB->get($tipo, "nome", ["id" => $id]);
    return $result ?: "Non trovata (ID: $id)";
}

/**
 * Ottiene statistiche sui prodotti e sui tag
 * @return array Array con statistiche
 */
function ottieni_statistiche() {
    global $medooDB, $unique_tags;
    
    // Statistiche sui prodotti
    $stats = [
        'totale_prodotti' => $medooDB->count("prodotti"),
        'prodotti_elaborati' => $medooDB->count("prodotti", ["stato_tag" => 1]),
        'prodotti_da_elaborare' => $medooDB->count("prodotti", ["stato_tag" => 0]),
        'prodotti_in_errore' => $medooDB->count("prodotti", ["stato_tag" => 2]),
        'prodotti_per_categoria' => [],
        'prodotti_per_sottocategoria' => [], // Aggiunto
        'prodotti_per_marca' => [],
        'tag_unici' => count($unique_tags),
        'tag_popolari' => []
    ];
    
    // Ottieni statistiche per categoria
    $categorie = $medooDB->select("categoria", ["id", "nome"]);
    foreach ($categorie as $cat) {
        $count = $medooDB->count("prodotti", ["categoria_id" => $cat['id']]);
        if ($count > 0) {
            $stats['prodotti_per_categoria'][] = [
                'id' => $cat['id'],
                'nome' => $cat['nome'],
                'count' => $count
            ];
        }
    }
    
    // Ottieni statistiche per sottocategoria (NUOVA SEZIONE)
    $sottocategorie = $medooDB->select("sottocategoria", ["id", "nome", "categoria_id"]);
    foreach ($sottocategorie as $subcat) {
        $count = $medooDB->count("prodotti", ["sottocategoria_id" => $subcat['id']]);
        if ($count > 0) {
            $stats['prodotti_per_sottocategoria'][] = [
                'id' => $subcat['id'],
                'nome' => $subcat['nome'],
                'categoria_id' => $subcat['categoria_id'],
                'count' => $count
            ];
        }
    }
    
    // Ottieni statistiche per marca
    $marche = $medooDB->select("marche", ["id", "nome"]);
    foreach ($marche as $marca) {
        $count = $medooDB->count("prodotti", ["marca_id" => $marca['id']]);
        if ($count > 0) {
            $stats['prodotti_per_marca'][] = [
                'id' => $marca['id'],
                'nome' => $marca['nome'],
                'count' => $count
            ];
        }
    }
    
    // Ottieni tag popolari (direttamente dalla tabella)
    $tag_counts = $medooDB->query("
        SELECT tag, COUNT(*) as count 
        FROM prodotti_tags 
        GROUP BY tag 
        ORDER BY count DESC 
        LIMIT 50
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $stats['tag_popolari'] = $tag_counts;
    
    return $stats;
}

/**
 * Elabora un batch di prodotti
 * @param array $options Opzioni per la selezione (limit, ids, range_start, range_end)
 * @return array Risultati dell'elaborazione
 */
function elabora_batch_prodotti($options = []) {
    $prodotti = ottieni_prodotti($options);
    
    $risultati = [
        'totale' => count($prodotti),
        'successo' => 0,
        'errore' => 0,
        'dettagli' => []
    ];
    
    foreach ($prodotti as $prodotto) {
        $result = classifica_prodotto($prodotto);
        
        if ($result['stato'] == 'successo') {
            $risultati['successo']++;
        } else {
            $risultati['errore']++;
        }
        
        $risultati['dettagli'][] = $result;
        
        // Pausa breve tra le richieste per evitare rate limiting
        usleep(200000); // 0.2 secondi
    }
    
    return $risultati;
}

/**
 * Resetta lo stato dei tag per prodotti selezionati
 * @param array $options Opzioni per la selezione (ids, range_start, range_end)
 * @return int Numero di prodotti resettati
 */
function resetta_stato_tag($options = []) {
    global $medooDB;
    
    $where = [];
    
    // Filtro per IDs specifici
    if (isset($options['ids']) && is_array($options['ids']) && !empty($options['ids'])) {
        $where["id"] = $options['ids'];
    }
    // Filtro per range
    else if (isset($options['range_start']) && isset($options['range_end'])) {
        $range_start = intval($options['range_start']);
        $range_end = intval($options['range_end']);
        
        if ($range_start > 0 && $range_end > 0) {
            $where["id[>=]"] = $range_start;
            $where["id[<=]"] = $range_end;
        }
    }
    
    // Esegui update
    $data_count = $medooDB->update("prodotti", [
        "stato_tag" => 0
    ], $where);
    
    return $data_count->rowCount();
}

/**
 * Ricerca prodotti nel database
 * @param string $query Query di ricerca
 * @param int $limit Limite risultati
 * @return array Prodotti trovati
 */
function ricerca_prodotti($query, $limit = 50) {
    global $medooDB;
    
    $query = trim($query);
    
    if (empty($query)) {
        return [];
    }
    
    // Cerca nel titolo, nelle categorie e nei tag
    $prodotti = $medooDB->query("
        SELECT DISTINCT p.id, p.titolo, p.immagine, p.categoria_id, p.sottocategoria_id, p.marca_id, p.tags, p.stato_tag, p.ean
        FROM prodotti p
        LEFT JOIN prodotti_tags pt ON p.id = pt.prodotto_id
        LEFT JOIN categoria c ON p.categoria_id = c.id
        LEFT JOIN sottocategoria sc ON p.sottocategoria_id = sc.id
        LEFT JOIN marche m ON p.marca_id = m.id
        WHERE 
            p.titolo LIKE :query OR
            p.ean LIKE :query OR
            c.nome LIKE :query OR
            sc.nome LIKE :query OR
            m.nome LIKE :query OR
            pt.tag LIKE :query
        ORDER BY p.id ASC
        LIMIT :limit
    ", [
        ':query' => '%' . $query . '%',
        ':limit' => $limit
    ])->fetchAll(PDO::FETCH_ASSOC);
    
    return $prodotti;
}


/**
 * Aggiungi questa funzione al file classificatore_backend.php
 * per ottenere le statistiche per sottocategoria
 */
function ottieni_statistiche_sottocategorie() {
    global $medooDB;
    
    // Prepara array risultato
    $result = [
        'prodotti_per_sottocategoria' => []
    ];
    
    // Ottieni sottocategorie
    $sottocategorie = $medooDB->select("sottocategoria", ["id", "nome", "categoria_id"]);
    
    // Per ogni sottocategoria, conta i prodotti
    foreach ($sottocategorie as $subcat) {
        $count = $medooDB->count("prodotti", ["sottocategoria_id" => $subcat['id']]);
        
        if ($count > 0) {
            $result['prodotti_per_sottocategoria'][] = [
                'id' => $subcat['id'],
                'nome' => $subcat['nome'],
                'categoria_id' => $subcat['categoria_id'],
                'count' => $count
            ];
        }
    }
    
    return $result;
}



/**
 * Inoltre, modifica la funzione ottieni_statistiche() nel file
 * classificatore_backend.php per includere anche le sottocategorie:
 */
/*

*/