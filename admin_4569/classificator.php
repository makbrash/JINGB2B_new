<?php
/**
 * Classificator - Gestione catalogo prodotti con AI
 */

// Imposta titolo pagina e descrizione
$pageTitle = "Classificatore prodotti";
$pageSubtitle = "Gestione immagini e categorie con intelligenza artificiale";

// Includi file di configurazione e funzioni necessarie
require_once '../config/config.php';
require_once 'includes/functions.php';
require_once "classificatore_backend.php"; // Carica backend

// Controlliamo se è necessario aggiornare la struttura del database
require_once "../dati/struttura_db.php";
$db_results = aggiorna_struttura_db();

// Carica statistiche iniziali
$stats = ottieni_statistiche();

// Configura pulsanti aggiuntivi per la toolbar
$additionalButtons = [
    [
        'text' => 'Elabora selezionati',
        'icon' => 'ti ti-refresh',
        'class' => 'btn-success',
        'link' => '#',
        'id' => 'btn-process-selected'
    ],
    [
        'text' => 'Recupera immagini',
        'icon' => 'ti ti-photo',
        'class' => 'btn-primary',
        'link' => '#',
        'id' => 'btn-fetch-images'
    ],
    [
        'text' => 'Classificazione manuale',
        'icon' => 'ti ti-tag',
        'class' => 'btn-warning',
        'link' => '#',
        'id' => 'btn-manual-classification'
    ]
];

// Disattiva il pulsante predefinito "Crea nuovo"
$showCreateButton = false;

// Include header e altri componenti dell'interfaccia
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/navbar.php';
require_once 'includes/page-header.php';
?>

<!-- Toolbar fissa -->
<div class="sticky-top bg-body border-bottom mb-3 py-2">
    <div class="container-xl d-flex flex-wrap align-items-center gap-2">
        <div class="d-flex flex-wrap gap-2 me-auto">
            <!-- Filtri di stato e categoria -->
            <div class="btn-group">
                <select id="stato-filter" class="form-select form-select-sm">
                    <option value="all">Tutti gli stati</option>
                    <option value="pending">Da elaborare</option>
                    <option value="done">Elaborati</option>
                    <option value="error">Con errori</option>
                </select>
            </div>
            
            <div class="btn-group">
                <select id="categoria-filter" class="form-select form-select-sm">
                    <option value="">Tutte le categorie</option>
                </select>
            </div>
            
            <div class="btn-group">
                <select id="sottocategoria-filter" class="form-select form-select-sm">
                    <option value="">Tutte le sottocategorie</option>
                </select>
            </div>
            
            <div class="btn-group">
                <select id="marca-filter" class="form-select form-select-sm">
                    <option value="">Tutte le marche</option>
                </select>
            </div>
        </div>
        
        <!-- Right side buttons -->
        <div class="d-flex gap-2 align-items-center">
            <select id="limit-selector" class="form-select form-select-sm">
                <option value="20">20 per pagina</option>
                <option value="50">50 per pagina</option>
                <option value="100">100 per pagina</option>
                <option value="500">500 per pagina</option>
                <option value="1000">1000 per pagina</option>
                <option value="3000">3000 per pagina</option>
            </select>
            
            <button id="btn-toggle-stats" class="btn btn-icon btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Mostra/nascondi statistiche">
                <i class="ti ti-chart-pie"></i>
            </button>
            
            <button id="btn-stop-processing" class="btn btn-sm btn-icon btn-danger d-none" data-bs-toggle="tooltip" title="Ferma elaborazione">
                <i class="ti ti-ban"></i>
            </button>
        </div>
    </div>
</div>

<!-- Container nascosto per l'elaborazione, sempre presente nel DOM -->
<div id="processing-container" class="container-xl mb-3 d-none">
    <div class="card">
        <div class="card-status-top bg-primary"></div>
        <div class="card-body p-3">
            <div class="d-flex align-items-center mb-2">
                <h4 class="card-title m-0 me-auto">Elaborazione in corso</h4>
                <span class="badge bg-primary ms-2" id="processing-percent">0%</span>
            </div>
            <div class="progress mb-2">
                <div id="processing-progress" class="progress-bar" style="width: 0%"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div id="processing-status" class="text-muted">Inizializzazione...</div>
                <div class="d-flex gap-3">
                    <span class="badge bg-success-lt">Successi: <span id="processing-success">0</span></span>
                    <span class="badge bg-danger-lt">Errori: <span id="processing-error">0</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row g-3">
            <!-- Colonna sinistra (ricerca e filtri) -->
            <div class="col-12 col-md-4 col-lg-3">
                <!-- Pannello di ricerca -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-search me-1"></i> Cerca prodotti
                        </h3>
                    </div>
                    <div class="card-body">
                        <form id="search-form" class="mb-2">
                            <div class="input-icon mb-3">
                                <input type="text" id="search-query" class="form-control" placeholder="Titolo, categoria, marca o tag...">
                                <span class="input-icon-addon">
                                    <i class="ti ti-search"></i>
                                </span>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                Cerca
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Pannello range prodotti -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-number me-1"></i> Range prodotti
                        </h3>
                    </div>
                    <div class="card-body">
                        <form id="range-form">
                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label" for="range_start">ID iniziale:</label>
                                    <input type="number" id="range_start" class="form-control" min="1" step="1">
                                </div>
                                <div class="col">
                                    <label class="form-label" for="range_end">ID finale:</label>
                                    <input type="number" id="range_end" class="form-control" min="1" step="1">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Carica range</button>
                        </form>
                    </div>
                </div>
                
                <!-- Pannello statistiche (inizialmente nascosto) -->
                <div id="stats-panel" class="card d-none">
                    <div class="card-header">
                        <div class="card-actions">
                            <a href="#" id="close-stats" class="btn-close"></a>
                        </div>
                        <h3 class="card-title">
                            <i class="ti ti-chart-bar me-1"></i> Statistiche
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <div class="card card-sm">
                                    <div class="card-body p-2 text-center">
                                        <div class="h1 m-0" id="stat-total"><?php echo $stats['totale_prodotti']; ?></div>
                                        <div class="text-muted">Totale prodotti</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card card-sm">
                                    <div class="card-body p-2 text-center">
                                        <div class="h1 m-0" id="stat-processed"><?php echo $stats['prodotti_elaborati']; ?></div>
                                        <div class="text-muted">Elaborati</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card card-sm">
                                    <div class="card-body p-2 text-center">
                                        <div class="h1 m-0" id="stat-pending"><?php echo $stats['prodotti_da_elaborare']; ?></div>
                                        <div class="text-muted">Da completare</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card card-sm">
                                    <div class="card-body p-2 text-center">
                                        <div class="h1 m-0" id="stat-error"><?php echo $stats['prodotti_in_errore']; ?></div>
                                        <div class="text-muted">Con errori</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <ul class="nav nav-tabs card-header-tabs" id="stat-tabs">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-stat="category" href="#">Categorie</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-stat="marca" href="#">Marche</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-stat="tag" href="#">Tag</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-stat="subcategory" href="#">Sottocategorie</a>
                                    </li>
                                </ul>
                                
                                <div class="card-body p-0 pt-3">
                                    <div id="category-stats"></div>
                                    <div id="brand-stats" class="d-none"></div>
                                    <div id="tag-cloud" class="d-none"></div>
                                    <div id="subcategory-stats" class="d-none"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Colonna destra (tabella prodotti) -->
            <div class="col-12 col-md-8 col-lg-9">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-package me-1"></i> Catalogo prodotti
                        </h3>
                        <div class="card-actions">
                            <a href="#" id="btn-refresh-products" class="btn btn-icon btn-outline-secondary" data-bs-toggle="tooltip" title="Aggiorna">
                                <i class="ti ti-refresh"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- Contenitore prodotti (caricato via AJAX) -->
                        <div id="products-container"></div>
                        
                        <!-- Indicatore caricamento -->
                        <div id="products-loader" class="d-flex justify-content-center align-items-center p-5">
                            <div class="spinner-border text-primary me-2" role="status"></div>
                            <span>Caricamento prodotti...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal dettaglio prodotto -->
<div class="modal modal-blur fade" id="product-detail-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dettagli prodotto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Contenuto caricato dinamicamente -->
            </div>
        </div>
    </div>
</div>

<!-- Modal selezione immagini -->
<div class="modal modal-blur fade" id="image-selection-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seleziona un'immagine per il prodotto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="image-grid" class="d-flex flex-wrap gap-3 mb-4 justify-content-center">
                    <!-- Le immagini saranno caricate qui dinamicamente -->
                    <div class="d-flex justify-content-center align-items-center w-100 py-4">
                        <div class="spinner-border text-primary me-2" role="status"></div>
                        <span>Caricamento immagini...</span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">URL diretto</h3>
                        </div>
                        <div class="card-body">
                            <div class="input-group">
                                <input type="text" id="direct-image-url" class="form-control" placeholder="https://esempio.com/immagine.jpg">
                                <button id="save-direct-url" class="btn btn-primary">
                                    <i class="ti ti-device-floppy me-1"></i> Salva URL
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Carica dal computer</h3>
                        </div>
                        <div class="card-body">
                            <form id="upload-image-form" enctype="multipart/form-data">
                                <input type="hidden" id="upload-ean" name="ean" value="">
                                <input type="hidden" name="action" value="upload_file">
                                <div class="input-group">
                                    <input type="file" id="image-file" name="image_file" accept="image/*" class="form-control">
                                    <button type="submit" id="upload-image-btn" class="btn btn-primary">
                                        <i class="ti ti-upload me-1"></i> Carica
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div id="image-selection-status" class="alert mt-3 d-none"></div>
            </div>
        </div>
    </div>
</div>

<!-- CSS personalizzato -->
<style>
/* Stili essenziali non presenti in Tabler */
.image-option {
    position: relative;
    width: 150px;
    height: 150px;
    border: 2px solid #e6e7e9;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.2s;
    overflow: hidden;
}

.image-option img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.image-option:hover {
    border-color: #206bc4;
}

.image-option.selected {
    border-color: #206bc4;
    box-shadow: 0 0 0 2px rgba(32, 107, 196, 0.25);
}

.image-option.selected::after {
    content: '\2713';
    position: absolute;
    top: 5px;
    right: 5px;
    width: 20px;
    height: 20px;
    background-color: #206bc4;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}
</style>

<?php
// Script specifici per la pagina
$pageScripts = [
    'assets/js/classificatore.js',
    'assets/js/classificatore-tabler.js',
    'assets/js/selectCheck.js'
];

// Stili specifici per la pagina
$pageStyles = [
    'assets/css/classificatore-tabler.css',
    'assets/css/classificatore.css'
];

// Includi il footer
require_once 'includes/footer.php';
?> 

<!-- Assicuriamoci che lo stile sia caricato (nel caso il tema non lo gestisca) -->
<link rel="stylesheet" href="assets/css/classificatore-tabler.css">

<!-- Script di inizializzazione -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestione pulsante chiusura pannello statistiche
    document.getElementById('close-stats').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('stats-panel').classList.add('d-none');
    });
    
    // Aggiorna percentuale di elaborazione
    const originalUpdateProgress = window.updateProgress;
    if (typeof originalUpdateProgress === 'function') {
        window.updateProgress = function(percent, successCount, errorCount) {
            // Aggiorna anche l'elemento percentuale
            const percentElement = document.getElementById('processing-percent');
            if (percentElement) {
                percentElement.textContent = Math.round(percent) + '%';
            }
            
            // Chiama la funzione originale
            originalUpdateProgress(percent, successCount, errorCount);
        };
    }
    
    // Refresh pulsante
    document.getElementById('btn-refresh-products').addEventListener('click', function(e) {
        e.preventDefault();
        caricaProdotti();
    });
});
</script> 