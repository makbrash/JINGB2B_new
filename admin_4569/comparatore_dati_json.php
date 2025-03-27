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
            <div class="col-auto">
                <button type="button" class="btn btn-outline-warning" id="btn-split-json" style="display: none;">
                    <i class="ti ti-cut me-2"></i>
                    Dividi in più parti
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
    // Gestione per dividere file grandi
    document.addEventListener('DOMContentLoaded', function() {
        const btnSplitJson = document.getElementById('btn-split-json');

        // Mostra il pulsante "Dividi" quando viene selezionato un file grande
        document.getElementById('json-file').addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const file = this.files[0];
                // Mostra il pulsante di split solo per file grandi (>10MB)
                btnSplitJson.style.display = file.size > 10 * 1024 * 1024 ? 'inline-block' : 'none';
            }
        });

        // Gestisci la divisione del file
        btnSplitJson.addEventListener('click', async function() {
            const fileInput = document.getElementById('json-file');
            if (fileInput.files.length === 0) return;

            const file = fileInput.files[0];

            // Prima carica il file
            const formData = new FormData();
            formData.append('json_file', file);

            try {
                // Carica il file
                const uploadResponse = await fetch('upload_json.php', {
                    method: 'POST',
                    body: formData
                });
                const uploadResult = await uploadResponse.json();

                if (!uploadResult.success) {
                    alert('Errore durante il caricamento: ' + uploadResult.error);
                    return;
                }

                // Ora dividi il file
                const itemsPerFile = prompt('Quanti prodotti per file? (consigliato: 500)', '500');
                if (!itemsPerFile) return;

                const splitResponse = await fetch(`split_json.php?file=${uploadResult.filename}&items=${itemsPerFile}`);
                const splitResult = await splitResponse.json();

                if (splitResult.success) {
                    alert(`File diviso con successo in ${splitResult.count} parti!`);

                    // Aggiorna l'interfaccia con link ai file divisi
                    const filesDiv = document.createElement('div');
                    filesDiv.className = 'mt-3 alert alert-success';
                    filesDiv.innerHTML = `
                    <h4><i class="ti ti-files"></i> File divisi in ${splitResult.count} parti</h4>
                    <p>Puoi importare ogni parte separatamente:</p>
                    <ul class="list-group">
                        ${splitResult.files.map((file, index) => 
                            `<li class="list-group-item d-flex justify-content-between align-items-center">
                                Parte ${index + 1}
                                <button class="btn btn-sm btn-primary import-part" data-file="${file.split('/').pop()}">
                                    Importa questa parte
                                </button>
                            </li>`
                        ).join('')}
                    </ul>
                `;

                    document.querySelector('.card-body').appendChild(filesDiv);

                    // Aggiungi handler per importare le singole parti
                    document.querySelectorAll('.import-part').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const filename = this.getAttribute('data-file');
                            // Imposta il file come currentFile e mostra le info
                            currentFile = filename;
                            fileStatusCard.style.display = 'block';
                            fileNameDisplay.textContent = 'File selezionato: ' + filename;
                            fileSizeDisplay.textContent = 'File suddiviso (parte)';
                        });
                    });
                } else {
                    alert('Errore durante la divisione: ' + splitResult.error);
                }
            } catch (error) {
                console.error('Errore:', error);
                alert('Errore durante l\'elaborazione');
            }
        });
    });

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



    // AGGIUNGI al click del bottone di importazione
    btnStartImport.addEventListener('click', async function() {
        // [codice esistente]

        // Aggiungi un timeout di sicurezza per evitare blocchi infiniti
        const importTimeout = setTimeout(() => {
            importLog.innerHTML += `<div class="text-danger"><i class="ti ti-clock-cancel"></i> L'importazione è stata interrotta per timeout dopo 10 minuti. Prova a suddividere il file in parti più piccole.</div>`;
            errorLog.innerHTML += `<div class="text-danger"><i class="ti ti-clock-cancel"></i> L'importazione è stata interrotta per timeout dopo 10 minuti. Prova a suddividere il file in parti più piccole.</div>`;
            btnStartImport.disabled = false;
        }, 10 * 60 * 1000); // 10 minuti

        try {
            // [codice esistente per la fetch]
        } catch (error) {
            // [gestione errori esistente]
        } finally {
            clearTimeout(importTimeout);
            btnStartImport.disabled = false;
        }
    });




    // AGGIUNGI al fondo dello script in comparatore_dati_json.php
    // Funzione per eseguire gli script nei messaggi di log
    function processScriptsInLog() {
        // Trova tutti gli elementi script nei container di log
        const scriptContainers = document.querySelectorAll('#js-progress-update');

        scriptContainers.forEach(container => {
            try {
                // Estrai il contenuto dello script
                const scriptContent = container.querySelector('script').innerHTML;
                // Esegui lo script
                eval(scriptContent);
                // Rimuovi il container dopo l'esecuzione
                container.remove();
            } catch (e) {
                console.error("Errore nell'esecuzione dello script:", e);
            }
        });
    }

    // Modifica la funzione di lettura dello stream per processare gli script
    btnStartImport.addEventListener('click', async function() {
        // [codice esistente...]

        try {
            const response = await fetch('import_json.php?' + params.toString());
            const reader = response.body.getReader();

            while (true) {
                const {
                    done,
                    value
                } = await reader.read();
                if (done) break;

                const text = new TextDecoder().decode(value);
                const lines = text.split('\n');

                lines.forEach(line => {
                    if (line.trim()) {
                        // Aggiungi il contenuto al log
                        if (!line.includes('js-progress-update')) {
                            const logEntry = document.createElement('div');
                            logEntry.className = 'mb-2';
                            logEntry.innerHTML = line;
                            importLog.appendChild(logEntry);

                            // Categorizza i log
                            if (line.includes('text-success')) {
                                successLog.innerHTML += line;
                            } else if (line.includes('text-warning')) {
                                warningLog.innerHTML += line;
                            } else if (line.includes('text-danger')) {
                                errorLog.innerHTML += line;
                            }
                        } else {
                            // Aggiungi lo script nascosto al DOM
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = line;
                            document.body.appendChild(tempDiv);
                        }
                    }
                });

                // Esegui gli script dopo ogni chunk
                processScriptsInLog();

                // Scorri i log in basso
                importLog.scrollTop = importLog.scrollHeight;
                successLog.scrollTop = successLog.scrollHeight;
                warningLog.scrollTop = warningLog.scrollHeight;
                errorLog.scrollTop = errorLog.scrollHeight;
            }
        } catch (error) {
            // [gestione errori esistente...]
        }
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