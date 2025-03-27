<?php
/**
 * classificatore.php - Interfaccia principale sistema classificazione prodotti
 * 
 * Questo file è il punto di entrata principale per il sistema di classificazione
 * dei prodotti. Gestisce il rendering dell'interfaccia e il caricamento delle risorse.
 * 
 * @author Marco Vitaletti
 * @version 1.0
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
    
    <!-- Stili CSS -->
    <link rel="stylesheet" href="classificatore.css">
    
    <!-- jQuery -->
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
                        
                        <!-- Tag cloud -->
                        <h3>Tag più utilizzati</h3>
                        <div id="tag-cloud" class="tag-cloud">
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
                        
                        <!-- Statistiche categorie -->
                        <h3>Distribuzione categorie</h3>
                        <div id="category-stats">
                            <?php
                            if (!empty($stats['prodotti_per_categoria'])) {
                                foreach ($stats['prodotti_per_categoria'] as $cat) {
                                    $percentage = round($cat['count'] / $stats['totale_prodotti'] * 100, 1);
                                    echo '
                                    <div class="stat-bar-item">
                                        <div class="stat-bar-label">' . $cat['nome'] . '</div>
                                        <div class="stat-bar-container">
                                            <div class="stat-bar-fill" style="width: ' . $percentage . '%"></div>
                                        </div>
                                        <div class="stat-bar-value">' . $cat['count'] . ' (' . $percentage . '%)</div>
                                    </div>';
                                }
                            } else {
                                echo '<em>Nessuna categoria disponibile</em>';
                            }
                            ?>
                        </div>
                        
                        <!-- Statistiche marche principali -->
                        <h3>Marche principali</h3>
                        <div id="brand-stats">
                            <?php
                            if (!empty($stats['prodotti_per_marca'])) {
                                // Prendiamo solo le prime 10 marche
                                $top_brands = array_slice($stats['prodotti_per_marca'], 0, 10);
                                foreach ($top_brands as $marca) {
                                    if ($marca['count'] > 0) {
                                        $percentage = round($marca['count'] / $stats['totale_prodotti'] * 100, 1);
                                        echo '
                                        <div class="stat-bar-item">
                                            <div class="stat-bar-label">' . $marca['nome'] . '</div>
                                            <div class="stat-bar-container">
                                                <div class="stat-bar-fill" style="width: ' . $percentage . '%"></div>
                                            </div>
                                            <div class="stat-bar-value">' . $marca['count'] . ' (' . $percentage . '%)</div>
                                        </div>';
                                    }
                                }
                            } else {
                                echo '<em>Nessuna marca disponibile</em>';
                            }
                            ?>
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
    
    <!-- Modal dettaglio prodotto -->
    <div id="product-detail-modal" class="modal">
        <div class="modal-content">
            <!-- Contenuto caricato dinamicamente -->
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="classificatore.js"></script>
    <script src="selectCheck.js"></script>
</body>
</html>