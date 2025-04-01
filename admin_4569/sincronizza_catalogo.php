<?php ///sincronizza_catalogo.php
/**
 * Sincronizzazione Catalogo - Importazione prodotti da Excel
 */

// Imposta titolo pagina e descrizione
$pageTitle = "Sincronizzazione Catalogo";
$pageSubtitle = "Importazione prodotti da Excel";

// Includi file di configurazione e funzioni
require_once '../config/config.php';
require_once 'includes/functions.php';
require_once 'includes/sincronizza/sync_functions.php';

// Ottieni l'elenco dei negozi per la select
$negozi = $medooDB->select("negozi", [
    "id",
    "nome",
    "descrizione"
], [
    "ORDER" => ["id" => "ASC"]
]);

// Pulizia automatica dei file temporanei all'apertura della pagina
cleanupAllTempFiles();

// Include i componenti dell'interfaccia
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="container-xl">
        <!-- Header pagina -->
        <div class="page-header d-print-none">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <?php echo $pageTitle; ?>
                    </h2>
                    <div class="text-muted mt-1"><?php echo $pageSubtitle; ?></div>
                </div>
                <div class="col-auto ms-auto">
                    <a href="ripristina_catalogo.php" class="btn btn-warning">
                        <i class="ti ti-history me-1"></i>
                        Gestisci Backup
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="page-body">
        <div class="container-xl">
            <!-- Contenitori per messaggi e loading -->
            <div id="errorContainer" class="alert alert-danger mb-3" style="display: none;">
                <i class="ti ti-alert-circle me-2"></i>
                <span id="errorMessage"></span>
            </div>
            <div id="loadingContainer" class="text-center py-4" style="display: none;">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <h3 id="loadingMessage" class="mt-2">Caricamento in corso...</h3>
            </div>
            
            <!-- Area principale -->
            <div class="row">
                <div class="col-12">
                    <!-- Form upload -->
                    <div id="uploadForm" class="card">
                        <div class="card-header">
                            <h3 class="card-title">Seleziona file Excel</h3>
                        </div>
                        <div class="card-body">
                            <form id="excelUploadForm" enctype="multipart/form-data">
                                <div class="row mb-3">
                                    <div class="col-lg-6">
                                        <label class="form-label">File Excel (.xlsx, .xls, .csv)</label>
                                        <input type="file" class="form-control" name="excelFile" accept=".xlsx,.xls,.csv" required>
                                        <small class="form-hint">
                                            Il file deve contenere almeno le colonne: codice EAN, titolo, prezzo
                                        </small>
                                    </div>
                                    
                                    <div class="col-lg-6">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Negozio</label>
                                                <select class="form-select" name="negozio_id" required>
                                                    <option value="">Seleziona il negozio</option>
                                                    <?php foreach ($negozi as $negozio): ?>
                                                    <option value="<?php echo $negozio['id']; ?>">
                                                        <?php echo htmlspecialchars($negozio['nome']); ?> 
                                                        <?php if (!empty($negozio['descrizione'])): ?>
                                                        - <?php echo htmlspecialchars($negozio['descrizione']); ?>
                                                        <?php endif; ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="form-hint">
                                                    Seleziona il negozio a cui associare i prodotti
                                                </small>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label class="form-label w-100">&nbsp;</label>
                                                <label class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="is_promo">
                                                    <span class="form-check-label">È una lista promo?</span>
                                                </label>
                                                <small class="form-hint">
                                                    Seleziona se il file contiene prezzi in promozione
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-upload me-1"></i>
                                        Carica File
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Contenitori dinamici -->
                    <div id="previewContainer" style="display: none;"></div>
                    <div id="mappingContainer" style="display: none;"></div>
                    <div id="reportContainer" style="display: none;"></div>

                    <!-- Contenitore per i dettagli -->
                    <div id="duplicate-eans" class="collapse mb-3"></div>

                    <!-- Card Funzionalità -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Funzionalità della Sincronizzazione</h3>
                        </div>
                        <div class="card-body">
                            <p>Questa pagina permette di sincronizzare il catalogo prodotti caricando un file Excel.</p>
                            
                            <h4>Come funziona:</h4>
                            <ol>
                                <li>Seleziona il negozio di riferimento e indica se è una lista promozionale</li>
                                <li>Carica un file Excel (.xlsx, .xls, .csv)</li>
                                <li>Verifica l'anteprima e la mappatura delle colonne</li>
                                <li>Conferma per avviare la sincronizzazione</li>
                            </ol>
                            
                            <h4>Durante la sincronizzazione:</h4>
                            <ul>
                                <li>I prodotti esistenti verranno aggiornati</li>
                                <li>I nuovi prodotti verranno aggiunti</li>
                                <li>I prodotti non presenti nel file verranno disattivati</li>
                                <li>Prima di ogni sincronizzazione viene creato un backup automatico</li>
                            </ul>
                            
                            <div class="alert alert-info">
                                <i class="ti ti-info-circle me-2"></i>
                                In caso di problemi, puoi ripristinare una versione precedente dalla pagina "Gestisci Backup".
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php

$pageScripts = [
    'assets/js/sync.js'
];
require_once 'includes/footer.php';

?>