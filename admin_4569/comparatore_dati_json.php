<?php
// Imposta titolo pagina e descrizione
$pageTitle = "Importazione Catalogo";
$pageSubtitle = "Gestione e sincronizzazione prodotti da file JSON";

// Includi file di configurazione e funzioni
require_once '../config/config.php';
require_once 'includes/functions.php';

// Include header e altri componenti dell'interfaccia
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/navbar.php';
require_once 'includes/page-header.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Percorso del file JSON di default
$jsonFile = '../00_IN_REVISIONE/dati/catalogo.json';
$jsonFileExists = file_exists($jsonFile);

// Cartella per i file caricati
$uploadDir = '../00_IN_REVISIONE/dati/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
?>

<!-- Card di Controllo Upload -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Caricamento File JSON</h3>
    </div>
    <div class="card-body">
        <form id="json-upload-form" method="post" enctype="multipart/form-data">
            <div class="row g-3 align-items-top">
                <div class="col-md-6">
                    <div class="form-label">File JSON da importare</div>
                    <input type="file" id="json-file" name="json_file" class="form-control" accept=".json" required>
                    <div class="form-hint">Seleziona un file JSON contenente i dati del catalogo</div>
                </div>
                <div class="col-md-6">
                    <div class="form-label">Opzioni di importazione</div>
                    <div class="form-selectgroup">
                        <label class="form-selectgroup-item">
                            <input type="checkbox" name="overwrite_images" value="1" class="form-selectgroup-input" checked>
                            <span class="form-selectgroup-label">Sovrascrivi immagini esistenti</span>
                        </label>
                        <label class="form-selectgroup-item">
                            <input type="checkbox" name="skip_existing" value="1" class="form-selectgroup-input">
                            <span class="form-selectgroup-label">Salta prodotti esistenti</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary" id="btn-upload-json">
                    <i class="ti ti-upload me-2"></i>
                    Carica e processa
                </button>
                <?php if ($jsonFileExists): ?>
                <button type="button" class="btn btn-outline-primary" id="btn-use-default">
                    <i class="ti ti-file-import me-2"></i>
                    Usa file predefinito
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Card Stato File -->
<div class="card mb-3" id="file-status-card" style="display: none;">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-auto">
                <span class="bg-primary text-white avatar">
                    <i class="ti ti-file-json"></i>
                </span>
            </div>
            <div class="col">
                <h3 class="card-title mb-1" id="file-name-display">
                    File selezionato
                </h3>
                <div class="text-muted" id="file-size-display"></div>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" id="btn-start-import">
                    <i class="ti ti-download me-2"></i>
                    Avvia Importazione
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Card Risultati -->
<div class="card" id="results-card" style="display: none;">
    <div class="card-body">
        <h3 class="card-title">📊 Riepilogo Importazione</h3>
        
        <!-- Progress Bar Generale -->
        <div class="mb-3">
            <label class="form-label">Avanzamento Generale</label>
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" id="import-progress" 
                     role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                    0%
                </div>
            </div>
        </div>

        <!-- Progress Bars Dettagliate -->
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Inserimento Prodotti</label>
                <div class="progress">
                    <div class="progress-bar bg-green" id="insert-progress" 
                         role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                        0%
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Elaborazione Immagini</label>
                <div class="progress">
                    <div class="progress-bar bg-blue" id="images-progress" 
                         role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                        0%
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiche in Grid -->
        <div class="row row-cards">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-blue text-white avatar">
                                    <i class="ti ti-file"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    Prodotti nel JSON
                                </div>
                                <div class="text-muted" id="total-products">
                                    0
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-green text-white avatar">
                                    <i class="ti ti-check"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    Inseriti
                                </div>
                                <div class="text-muted" id="inserted-count">
                                    0
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-yellow text-white avatar">
                                    <i class="ti ti-refresh"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    Aggiornati
                                </div>
                                <div class="text-muted" id="updated-count">
                                    0
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-red text-white avatar">
                                    <i class="ti ti-alert-triangle"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    Errori
                                </div>
                                <div class="text-muted" id="error-count">
                                    0
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Log delle operazioni con tabs per tipologie di log -->
        <div class="card mt-3">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                    <li class="nav-item">
                        <a href="#log-all" class="nav-link active" data-bs-toggle="tab">
                            <i class="ti ti-list me-2"></i>
                            Tutti i log
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#log-successful" class="nav-link" data-bs-toggle="tab">
                            <i class="ti ti-check me-2"></i>
                            Successi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#log-warnings" class="nav-link" data-bs-toggle="tab">
                            <i class="ti ti-alert-triangle me-2"></i>
                            Avvisi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#log-errors" class="nav-link" data-bs-toggle="tab">
                            <i class="ti ti-alert-circle me-2"></i>
                            Errori
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane active show" id="log-all">
                        <div id="import-log" style="max-height: 400px; overflow-y: auto;">
                            <!-- I log verranno inseriti qui dinamicamente -->
                        </div>
                    </div>
                    <div class="tab-pane" id="log-successful">
                        <div id="success-log" style="max-height: 400px; overflow-y: auto;">
                            <!-- I log di successo verranno inseriti qui -->
                        </div>
                    </div>
                    <div class="tab-pane" id="log-warnings">
                        <div id="warning-log" style="max-height: 400px; overflow-y: auto;">
                            <!-- Gli avvisi verranno inseriti qui -->
                        </div>
                    </div>
                    <div class="tab-pane" id="log-errors">
                        <div id="error-log" style="max-height: 400px; overflow-y: auto;">
                            <!-- Gli errori verranno inseriti qui -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script per la gestione dell'importazione -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const jsonUploadForm = document.getElementById('json-upload-form');
    const fileStatusCard = document.getElementById('file-status-card');
    const fileNameDisplay = document.getElementById('file-name-display');
    const fileSizeDisplay = document.getElementById('file-size-display');
    const btnStartImport = document.getElementById('btn-start-import');
    const btnUseDefault = document.getElementById('btn-use-default');
    const resultsCard = document.getElementById('results-card');
    const importLog = document.getElementById('import-log');
    const successLog = document.getElementById('success-log');
    const warningLog = document.getElementById('warning-log');
    const errorLog = document.getElementById('error-log');
    const progressBar = document.getElementById('import-progress');
    const insertProgress = document.getElementById('insert-progress');
    const imagesProgress = document.getElementById('images-progress');
    
    let currentFile = null;
    let importOptions = {};
    
    // Gestione upload file
    jsonUploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById('json-file');
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            if (file.type !== 'application/json' && !file.name.endsWith('.json')) {
                alert('Per favore seleziona un file JSON valido.');
                return;
            }
            
            // Mostra info sul file
            showFileInfo(file);
            
            // Ottieni opzioni di importazione
            importOptions = {
                overwriteImages: document.querySelector('input[name="overwrite_images"]').checked,
                skipExisting: document.querySelector('input[name="skip_existing"]').checked
            };
            
            // Carica il file al server
            uploadJsonFile(file);
        }
    });
    
    // Usa file predefinito
    if (btnUseDefault) {
        btnUseDefault.addEventListener('click', function() {
            fileStatusCard.style.display = 'block';
            fileNameDisplay.textContent = 'File predefinito: catalogo.json';
            fileSizeDisplay.textContent = 'Percorso: ../00_IN_REVISIONE/dati/catalogo.json';
            
            // Imposta opzioni di importazione
            importOptions = {
                overwriteImages: document.querySelector('input[name="overwrite_images"]').checked,
                skipExisting: document.querySelector('input[name="skip_existing"]').checked,
                useDefault: true
            };
            
            currentFile = 'default';
        });
    }
    
    // Funzione per mostrare info sul file
    function showFileInfo(file) {
        fileStatusCard.style.display = 'block';
        fileNameDisplay.textContent = 'File selezionato: ' + file.name;
        
        // Formatta dimensione file
        let size = file.size;
        let sizeDisplay = '';
        if (size < 1024) {
            sizeDisplay = size + ' bytes';
        } else if (size < 1024 * 1024) {
            sizeDisplay = (size / 1024).toFixed(2) + ' KB';
        } else {
            sizeDisplay = (size / (1024 * 1024)).toFixed(2) + ' MB';
        }
        
        fileSizeDisplay.textContent = 'Dimensione: ' + sizeDisplay;
        currentFile = file;
    }
    
    // Funzione per caricare il file al server
    function uploadJsonFile(file) {
        const formData = new FormData();
        formData.append('json_file', file);
        
        fetch('upload_json.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Memorizza il nome del file caricato
                currentFile = data.filename;
                
                // Mostra messaggio di successo
                const alert = document.createElement('div');
                alert.className = 'alert alert-success';
                alert.textContent = 'File caricato con successo!';
                
                jsonUploadForm.appendChild(alert);
                
                // Rimuovi l'alert dopo 3 secondi
                setTimeout(() => {
                    alert.remove();
                }, 3000);
            } else {
                alert('Errore durante il caricamento: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            alert('Errore durante il caricamento del file');
        });
    }
    
    // Avvia importazione
    btnStartImport.addEventListener('click', async function() {
        if (!currentFile) {
            alert('Nessun file selezionato');
            return;
        }
        
        // Reset UI
        btnStartImport.disabled = true;
        resultsCard.style.display = 'block';
        importLog.innerHTML = '';
        successLog.innerHTML = '';
        warningLog.innerHTML = '';
        errorLog.innerHTML = '';
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
        insertProgress.style.width = '0%';
        insertProgress.textContent = '0%';
        imagesProgress.style.width = '0%';
        imagesProgress.textContent = '0%';
        
        // Reset contatori
        document.getElementById('total-products').textContent = '0';
        document.getElementById('inserted-count').textContent = '0';
        document.getElementById('updated-count').textContent = '0';
        document.getElementById('error-count').textContent = '0';
        
        // Crea URL con parametri di query
        const params = new URLSearchParams();
        
        if (currentFile !== 'default') {
            params.append('file', currentFile);
        }
        
        if (importOptions.overwriteImages) {
            params.append('overwrite_images', '1');
        }
        
        if (importOptions.skipExisting) {
            params.append('skip_existing', '1');
        }
        
        if (importOptions.useDefault) {
            params.append('use_default', '1');
        }
        
        try {
            const response = await fetch('import_json.php?' + params.toString());
            const reader = response.body.getReader();
            
            while(true) {
                const {done, value} = await reader.read();
                if (done) break;
                
                const text = new TextDecoder().decode(value);
                const lines = text.split('\n');
                
                lines.forEach(line => {
                    if (line.trim()) {
                        // Filtra il contenuto per evitare problemi di parsing
                        if (!line.startsWith('<script>')) {
                            const logEntry = document.createElement('div');
                            logEntry.className = 'mb-2';
                            logEntry.innerHTML = line;
                            importLog.appendChild(logEntry);
                            importLog.scrollTop = importLog.scrollHeight;
                            
                            // Categorizza i log
                            if (line.includes('text-success')) {
                                successLog.innerHTML += line;
                                successLog.scrollTop = successLog.scrollHeight;
                            } else if (line.includes('text-warning')) {
                                warningLog.innerHTML += line;
                                warningLog.scrollTop = warningLog.scrollHeight;
                            } else if (line.includes('text-danger')) {
                                errorLog.innerHTML += line;
                                errorLog.scrollTop = errorLog.scrollHeight;
                            }
                        } else {
                            // Esegui gli script per aggiornare i contatori e le barre di progresso
                            try {
                                const scriptContent = line.replace(/<script>/g, "").replace(/<\/script>/g, "");
                                eval(scriptContent);
                            } catch (e) {
                                console.error("Errore nell'esecuzione dello script:", e);
                            }
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Errore durante l\'importazione:', error);
            importLog.innerHTML += `<div class="text-danger">❌ Errore durante l'importazione: ${error.message}</div>`;
            errorLog.innerHTML += `<div class="text-danger">❌ Errore durante l'importazione: ${error.message}</div>`;
        } finally {
            btnStartImport.disabled = false;
        }
    });
});
</script>

<?php
// Script specifici per la pagina
$pageScripts = [
    'assets/js/import.js'
];

// Includi il footer
require_once 'includes/footer.php';
?>