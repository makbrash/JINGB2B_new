<?php
/**
 * classificatore.php - Interfaccia principale sistema classificazione prodotti
 * 
 * Questo file è il punto di entrata principale per il sistema di classificazione
 * dei prodotti. Gestisce il rendering dell'interfaccia e il caricamento delle risorse.
 * 
 * @author Marco Vitaletti
 * @version 2.0
 */

// Configurazione iniziale
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);

// Carica dipendenze
require_once "../includes/db.php"; // Carica Medoo
require_once "classificatore_backend.php"; // Carica backend

// Controlliamo prima di tutto se è necessario aggiornare la struttura del database
require_once "struttura_db.php";
$db_results = aggiorna_struttura_db();

// Carica statistiche iniziali
$stats = ottieni_statistiche();

// Titolo pagina
$page_title = "Classificatore Avanzato Prodotti con AI";
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Librerie esterne CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
    
    <!-- Stili CSS applicazione -->
    <link rel="stylesheet" href="classificatore.css">
    
    <!-- jQuery (base) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Contenitore notifiche -->
    <div id="notifications"></div>
    
    <!-- Contenitore principale -->
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div>
                <h1>🔍 <?php echo $page_title; ?></h1>
                <p class="header-subtitle">Analisi e categorizzazione automatica con GPT-4o mini</p>
            </div>
            <div>
                <button id="btn-show-settings" class="button button-sm">⚙️ Impostazioni</button>
            </div>
        </header>
        
        <!-- Toolbar principale -->
        <div class="toolbar">
            <div class="toolbar-group">
                <button id="btn-process-selected" class="button button-success" disabled>🔄 Elabora selezionati (<span id="selected-count">0</span>)</button>
                <button id="btn-reset-selected" class="button button-warning" disabled>↩️ Reset selezionati</button>
                <button id="btn-stop-processing" class="button button-danger">⛔ Ferma elaborazione</button>
            </div>
            
            <div class="toolbar-divider"></div>
            
            <div class="toolbar-group">
                <button id="btn-select-all" class="button button-sm">✅ Seleziona tutti</button>
                <button id="btn-deselect-all" class="button button-sm">❌ Deseleziona tutti</button>
            </div>
            
            <div class="toolbar-divider"></div>
            
            <div class="toolbar-group">
                <label for="stato-filter">Stato:</label>
                <select id="stato-filter">
                    <option value="all">Tutti</option>
                    <option value="pending">Da elaborare</option>
                    <option value="done">Elaborati</option>
                    <option value="error">Con errori</option>
                </select>
            </div>
            
            <div class="toolbar-group">
                <label for="categoria-filter">Categoria:</label>
                <select id="categoria-filter">
                    <option value="">Tutte le categorie</option>
                </select>
            </div>
            
            <div class="toolbar-group">
                <label for="sottocategoria-filter">Sottocategoria:</label>
                <select id="sottocategoria-filter">
                    <option value="">Tutte le sottocategorie</option>
                </select>
            </div>
            
            <div class="toolbar-group">
                <label for="marca-filter">Marca:</label>
                <select id="marca-filter">
                    <option value="">Tutte le marche</option>
                </select>
            </div>
            
            <div class="toolbar-group">
                <label for="limit-selector">Prodotti per pagina:</label>
                <select id="limit-selector">
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="500">500</option>
                    <option value="1000">1000</option>
                    <option value="3000">3000</option>
                </select>
            </div>
        </div>
        
        <!-- UI elaborazione batch -->
        <div id="processing-container" class="processing-container">
            <div class="progress-bar-container">
                <div id="processing-progress" class="progress-bar-fill" style="width: 0%"></div>
            </div>
            <div class="processing-info">
                <div id="processing-status">Elaborazione in corso...</div>
                <div class="processing-counts">
                    <div class="processing-count-item">
                        Successi: <span id="processing-success" class="count-success">0</span>
                    </div>
                    <div class="processing-count-item">
                        Errori: <span id="processing-error" class="count-error">0</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Layout principale a due colonne -->
        <div class="two-columns">
            <!-- Colonna sinistra (pannelli) -->
            <div class="column-narrow">
                <!-- Pannello di ricerca -->
                <div class="card">
                    <div class="card-header">
                        <h2>🔎 Cerca prodotti</h2>
                    </div>
                    <div class="card-body">
                        <form id="search-form" class="search-form">
                            <input type="text" id="search-query" placeholder="Cerca per titolo, categoria, marca o tag..." class="search-input">
                            <button type="submit" class="button">Cerca</button>
                        </form>
                    </div>
                </div>
                
                <!-- Pannello range prodotti -->
                <div class="card">
                    <div class="card-header">
                        <h2>🔢 Range prodotti</h2>
                    </div>
                    <div class="card-body">
                        <form id="range-form">
                            <div class="form-group">
                                <label for="range_start">ID iniziale:</label>
                                <input type="number" id="range_start" min="1" step="1">
                            </div>
                            <div class="form-group">
                                <label for="range_end">ID finale:</label>
                                <input type="number" id="range_end" min="1" step="1">
                            </div>
                            <button type="submit" class="button">Carica range</button>
                        </form>
                    </div>
                </div>
                
                <!-- Pannello statistiche -->
                <div class="card">
                    <div class="card-header">
                        <h2>📊 Statistiche</h2>
                    </div>
                    <div class="card-body">
                        <div class="stats-container">
                            <div class="stat-card">
                                <div class="stat-label">Totale prodotti</div>
                                <div id="stat-total" class="stat-value"><?php echo $stats['totale_prodotti']; ?></div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-label">Elaborati</div>
                                <div id="stat-processed" class="stat-value"><?php echo $stats['prodotti_elaborati']; ?></div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-label">Da elaborare</div>
                                <div id="stat-pending" class="stat-value"><?php echo $stats['prodotti_da_elaborare']; ?></div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-label">Con errori</div>
                                <div id="stat-error" class="stat-value"><?php echo $stats['prodotti_in_errore']; ?></div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-label">Tag unici</div>
                                <div id="stat-unique-tags" class="stat-value"><?php echo $stats['tag_unici']; ?></div>
                            </div>
                        </div>
                        
                        <!-- Tabs per le statistiche -->
                        <div id="stat-tabs" class="stat-tabs">
                            <button class="stat-tab active" data-stat="category">Distribuzione categorie</button>
                            <button class="stat-tab" data-stat="marca">Marche principali</button>
                            <button class="stat-tab" data-stat="tag">Tag più utilizzati</button>
                            <button class="stat-tab" data-stat="subcategory">Sottocategorie</button>
                        </div>
                        
                        <!-- Contenitori per i grafici -->
                        <div id="category-stats">
                            <canvas id="categories-chart"></canvas>
                            <div id="categories-legend" class="chart-legend"></div>
                        </div>
                        
                        <div id="brand-stats" style="display: none;">
                            <canvas id="brands-chart"></canvas>
                            <div id="brands-legend" class="chart-legend"></div>
                        </div>
                        
                        <!-- Tag cloud rimane come era -->
                        <div id="tag-cloud" class="tag-cloud" style="display: none;">
                            <?php
                            if (!empty($stats['tag_popolari'])) {
                                foreach ($stats['tag_popolari'] as $tag) {
                                    $fontSize = min(10 + log($tag['count'], 2) * 2, 24);
                                    echo '<span class="tag" style="font-size: ' . $fontSize . 'px" title="Usato ' . $tag['count'] . ' volte">' . $tag['tag'] . ' (' . $tag['count'] . ')</span> ';
                                }
                            } else {
                                echo '<em>Nessun tag disponibile</em>';
                            }
                            ?>
                        </div>
                        
                        <div id="subcategory-stats" style="display: none;">
                            <canvas id="subcategories-chart"></canvas>
                            <div id="subcategories-legend" class="chart-legend"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Colonna destra (prodotti) -->
            <div class="column-wide">

                <!-- Pannello prodotti -->
                <div class="card">
                    <div class="card-header">
                        <h2>📦 Prodotti</h2>
                    </div>
                    <div class="card-body">
                        <!-- Contenitore prodotti (caricato via AJAX) -->
                        <div id="products-container"></div>
                        
                        <!-- Indicatore caricamento -->
                        <div id="products-loader" class="products-loader">
                            <div class="loader"></div>
                            <p>Caricamento prodotti...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> Sistema di Classificazione Prodotti con AI</p>
        </footer>
    </div>
    
    <!-- Modal dettaglio prodotto (usato come fallback se SweetAlert2 non è disponibile) -->
    <div id="product-detail-modal" class="modal">
        <div class="modal-content">
            <!-- Contenuto caricato dinamicamente -->
        </div>
    </div>
    
    <!-- Modal classificazione manuale -->
    <div id="manual-classify-modal" class="modal">
        <div class="modal-content" style="max-width: 600px">
            <div class="card-header">
                <h2>Classificazione Manuale</h2>
            </div>
            <div class="card-body">
                <p>Assegna valori a <strong><span id="manual-count">0</span> prodotti</strong> selezionati:</p>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="manual-update-categoria"> 
                        Aggiorna Categoria
                    </label>
                    <select id="manual-categoria" class="form-control" disabled>
                        <option value="">-- Seleziona Categoria --</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="manual-update-sottocategoria"> 
                        Aggiorna Sottocategoria
                    </label>
                    <select id="manual-sottocategoria" class="form-control" disabled>
                        <option value="">-- Seleziona Sottocategoria --</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="manual-update-marca"> 
                        Aggiorna Marca
                    </label>
                    <select id="manual-marca" class="form-control" disabled>
                        <option value="">-- Seleziona Marca --</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="manual-update-tags"> 
                        Aggiorna Tags
                    </label>
                    <div id="manual-tags-container" style="display: none;">
                        <input type="text" id="manual-tags" class="form-control" placeholder="Tag1, Tag2, Tag3..." disabled>
                        <div class="form-check">
                            <label>
                                <input type="radio" name="manual-tags-mode" value="replace" checked> 
                                Sostituisci tags esistenti
                            </label>
                        </div>
                        <div class="form-check">
                            <label>
                                <input type="radio" name="manual-tags-mode" value="append"> 
                                Aggiungi ai tags esistenti
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <button id="btn-apply-manual" class="button button-success">Applica Modifiche</button>
                    <button id="btn-cancel-manual" class="button">Annulla</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Librerie JavaScript esterne -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.7.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.66/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.66/vfs_fonts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Moduli applicazione -->
    <script src="moduli/config.js"></script> 
    <script src="moduli/state.js"></script>
	<script src="moduli/products.js"></script>
    <script src="moduli/ui.js"></script>
    <script src="moduli/api.js"></script>
    <script src="moduli/inline-edit.js"></script>    
    <script src="moduli/filters.js"></script>
    <script src="moduli/statistics.js"></script>
    <script src="moduli/multiselect.js"></script>

    
    <!-- JavaScript principale -->
    <script src="classificatore.js"></script>
</body>
</html>