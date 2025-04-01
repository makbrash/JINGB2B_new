// Gestione caricamento file
function handleFileUpload(formData) {
    // Mostra indicatore di caricamento
    showLoading('Caricamento del file in corso...');
    
    // Aggiungi parametro action=upload
    formData.append('action', 'upload');
    
    $.ajax({
        url: 'process.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            hideLoading();
            
            if (response.success) {
                // Genera e mostra l'anteprima
                showPreview(response.preview, response.columns, response.mappings);
                
                // Scroll all'anteprima
                $('html, body').animate({
                    scrollTop: $('#previewContainer').offset().top - 100
                }, 500);
            } else {
                showError(response.error || 'Errore durante il caricamento del file');
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            console.error('Errore AJAX:', {xhr, status, error});
            
            let errorMsg;
            try {
                const response = JSON.parse(xhr.responseText);
                errorMsg = response.error || 'Errore durante il caricamento';
            } catch(e) {
                errorMsg = 'Errore durante il caricamento: ' + error;
            }
            showError(errorMsg);
        }
    });
}

// Gestione processo sincronizzazione
function handleSync(mappings, skipRows) {
    // Ottiene i valori del negozio e della modalità promo
    const negozioid = $('#selectedNegozioid').val();
    const isPromo = $('#selectedIsPromo').val() === '1';
    
    // Mostra indicatore di caricamento
    showLoading('Sincronizzazione in corso...');
    
    $.ajax({
        url: 'process.php',
        type: 'POST',
        data: {
            action: 'process',
            mappings: JSON.stringify(mappings),
            skip_rows: skipRows,
            negozio_id: negozioid,
            is_promo: isPromo ? 1 : 0
        },
        success: function(response) {
            hideLoading();
            console.log('Risposta sincronizzazione:', response);
            
            if (response.success) {
                // Nascondi form mappatura
                $('#mappingContainer').hide();
                
                // Mostra report
                showReport(response.results);
                
                // Scroll al report
                $('html, body').animate({
                    scrollTop: $('#reportContainer').offset().top - 100
                }, 500);
            } else {
                showError(response.error || 'Errore durante la sincronizzazione');
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            console.error('Errore AJAX:', {xhr, status, error});
            
            let errorMsg;
            try {
                const response = JSON.parse(xhr.responseText);
                errorMsg = response.error || 'Errore durante la sincronizzazione';
            } catch(e) {
                errorMsg = 'Errore durante la sincronizzazione: ' + error;
            }
            showError(errorMsg);
        }
    });
}

// Mostra l'anteprima e il form di mappatura
function showPreview(data, columns, suggestedMappings) {
    // Ottiene i valori dal form di upload
    const negozioid = $('select[name="negozio_id"]').val();
    const negozioText = $('select[name="negozio_id"] option:selected').text();
    const isPromo = $('input[name="is_promo"]').is(':checked') ? '1' : '0';
    const isPromoText = isPromo === '1' ? 'Sì' : 'No';
    
    // Prepara l'HTML per l'anteprima
    let html = `
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">Anteprima dati</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <div class="d-flex">
                        <div><i class="ti ti-info-circle icon alert-icon"></i></div>
                        <div>
                            <strong>Negozio selezionato:</strong> ${negozioText}<br>
                            <strong>Lista promozionale:</strong> ${isPromoText}
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-vcenter table-bordered">
                        <thead>
                            <tr>`;
    
    // Determina il numero di colonne
    const numColumns = typeof columns === 'number' ? columns : columns.length;
    
    // Aggiungiamo le intestazioni
    for (let i = 0; i < numColumns; i++) {
        html += `<th>COLONNA ${i + 1}</th>`;
    }
    
    html += `
                            </tr>
                        </thead>
                        <tbody>`;
    
    // Aggiungiamo le prime 5 righe
    let maxRows = Math.min(5, data.length);
    for (let i = 0; i < maxRows; i++) {
        html += '<tr>';
        for (let j = 0; j < numColumns; j++) {
            html += `<td>${data[i][j] || ''}</td>`;
        }
        html += '</tr>';
    }
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>`;
    
    // Aggiungiamo il form di mappatura
    html += `
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">Mappatura colonne</h3>
            </div>
            <div class="card-body">
                <form id="mappingForm">
                    <input type="hidden" id="selectedNegozioid" value="${negozioid}">
                    <input type="hidden" id="selectedIsPromo" value="${isPromo}">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Colonna EAN</label>
                                <select class="form-select" name="ean" required>
                                    <option value="">Seleziona colonna</option>
                                    ${generateColumnOptions(numColumns, suggestedMappings.ean)}
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Colonna Titolo</label>
                                <select class="form-select" name="titolo" required>
                                    <option value="">Seleziona colonna</option>
                                    ${generateColumnOptions(numColumns, suggestedMappings.titolo)}
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Colonna Prezzo</label>
                                <select class="form-select" name="prezzo" required>
                                    <option value="">Seleziona colonna</option>
                                    ${generateColumnOptions(numColumns, suggestedMappings.prezzo)}
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Righe di intestazione da saltare</label>
                        <input type="number" class="form-control" name="skip_rows" value="1" min="0" max="10">
                        <div class="form-hint">Numero di righe iniziali da ignorare (es. intestazioni)</div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-refresh me-1"></i>
                            Avvia Sincronizzazione
                        </button>
                    </div>
                </form>
            </div>
        </div>`;
    
    // Mostriamo l'anteprima
    $('#previewContainer').html(html).show();
    
    // Aggiungiamo l'event listener per il form di mappatura
    $('#mappingForm').on('submit', function(e) {
        e.preventDefault();
        
        const mappings = {
            ean: parseInt($(this).find('[name="ean"]').val()),
            titolo: parseInt($(this).find('[name="titolo"]').val()),
            prezzo: parseInt($(this).find('[name="prezzo"]').val())
        };
        
        const skipRows = parseInt($(this).find('[name="skip_rows"]').val());
        
        // Verifichiamo che tutti i campi siano selezionati
        if (!mappings.ean || !mappings.titolo || !mappings.prezzo) {
            showError('Seleziona tutte le colonne necessarie');
            return;
        }
        
        // Avviamo la sincronizzazione
        handleSync(mappings, skipRows);
    });
}

// Funzioni UI
function showPreviewAndMapping(data) {
    // Nascondi form upload e errori precedenti
    $('#uploadForm').hide();
    $('#errorContainer').hide();
    
    // Genera tabella anteprima
    let previewHtml = generatePreviewTable(data.preview);
    
    // Genera form mappatura con suggerimenti
    let mappingHtml = generateMappingForm(data.columns, data.mappings);
    
    // Mostra contenuti
    $('#previewContainer').html(previewHtml).show();
    $('#mappingContainer').html(mappingHtml).show();
}

function showReport(results) {
    console.log('Mostro report con risultati:', results); // Debug
    
    if (!results) {
        showError('Dati report non validi');
        return;
    }

    // Genera HTML del report
    let reportHtml = generateReportHtml(results);
    
    // Mostra il report
    $('#reportContainer')
        .html(reportHtml)
        .show()
        .find('.alert')
        .fadeIn();
}

function showError(message) {
    hideLoading();
    $('#errorContainer').html(`
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex">
                <div><i class="ti ti-alert-circle icon alert-icon"></i></div>
                <div>
                    <h4 class="alert-title">Errore!</h4>
                    <div class="text-secondary">${message}</div>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `).show();
}

function showLoading(message) {
    $('#loadingContainer').html(`
        <div class="progress progress-sm">
            <div class="progress-bar progress-bar-indeterminate"></div>
        </div>
        <div class="text-center mt-2">${message}</div>
    `).show();
}

function hideLoading() {
    $('#loadingContainer').hide();
}

// Funzioni helper per generare HTML
function generatePreviewTable(data) {
    if (!data || !data.length) {
        return '<div class="alert alert-warning">Nessun dato da visualizzare</div>';
    }

    let html = `
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Anteprima dati</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-vcenter table-bordered">
                        <thead><tr>`;
    
    // Intestazioni colonne
    for (let i = 0; i < data[0].length; i++) {
        html += `<th>Colonna ${i + 1}</th>`;
    }
    html += '</tr></thead><tbody>';
    
    // Righe dati
    data.forEach(row => {
        html += '<tr>';
        row.forEach(cell => {
            html += `<td>${cell}</td>`;
        });
        html += '</tr>';
    });
    
    html += '</tbody></table></div></div></div>';
    return html;
}

function generateMappingForm(columns, suggestions) {
    let html = `
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Mappatura colonne</h3>
            </div>
            <div class="card-body">
                <form id="mappingForm" class="form">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Colonna EAN</label>
                            <select class="form-select" id="eanColumn" required>
                                <option value="">Seleziona colonna</option>
                                ${generateColumnOptions(columns, suggestions.ean)}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Colonna Titolo</label>
                            <select class="form-select" id="titleColumn" required>
                                <option value="">Seleziona colonna</option>
                                ${generateColumnOptions(columns, suggestions.titolo)}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Colonna Prezzo</label>
                            <select class="form-select" id="priceColumn" required>
                                <option value="">Seleziona colonna</option>
                                ${generateColumnOptions(columns, suggestions.prezzo)}
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Righe da saltare</label>
                            <input type="number" class="form-control" id="skipRows" value="1" min="0">
                        </div>
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary">Procedi con la sincronizzazione</button>
                    </div>
                </form>
            </div>
        </div>`;
    return html;
}

function generateColumnOptions(columns, selected) {
    let options = '';
    for (let i = 1; i <= columns; i++) {
        options += `<option value="${i}" ${i === selected ? 'selected' : ''}>Colonna ${i}</option>`;
    }
    return options;
}

function generateReportHtml(results) {
    let totals = results.totals || {};
    let excelTotals = totals.excel_total || 0;
    let excelRawRows = totals.excel_raw_rows || 0;
    let excelSkippedRows = totals.excel_skipped_rows || 0;
    let excelEmptyRows = totals.excel_empty_rows || 0;
    let excelDuplicateEans = totals.excel_duplicate_eans || 0;
    let dbTotals = totals.db_total || 0;
    let duplicateEans = results.duplicate_eans || [];
    let backupId = results.backup_id || 0;
    let negozio = results.negozio || null;
    let skippedProducts = totals.skipped || 0;
    let isPromo = results.is_promo || false;
    let promoReset = totals.promo_reset || 0;
    
    let html = `
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Report Sincronizzazione${isPromo ? ' Promozionale' : ''}</h3>
                <div class="card-actions">
                    <a href="ripristina_catalogo.php" class="btn btn-outline-warning">
                        <i class="ti ti-history me-1"></i>
                        Gestisci Backup
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Informazioni negozio selezionato -->
                ${negozio ? `
                <div class="alert ${isPromo ? 'alert-warning' : 'alert-info'} mb-4">
                    <div class="d-flex">
                        <div><i class="ti ${isPromo ? 'ti-discount' : 'ti-building-store'} icon alert-icon"></i></div>
                        <div>
                            <strong>Sincronizzazione per negozio:</strong> ${negozio.nome} (ID: ${negozio.id})<br>
                            ${isPromo ? '<strong>Modalità promozionale attiva</strong><br>' : ''}
                            <div class="text-muted">
                                ${isPromo ? 
                                    'Sono stati modificati solo i prezzi promozionali dei prodotti presenti nel file.' : 
                                    'La sincronizzazione è stata eseguita considerando solo i prodotti di questo negozio.'}
                            </div>
                            ${promoReset > 0 ? `
                            <div class="mt-2">
                                <span class="badge bg-purple">
                                    <i class="ti ti-eraser me-1"></i>
                                    Reset ${promoReset} prezzi promozionali
                                </span>
                            </div>` : ''}
                        </div>
                    </div>
                </div>` : ''}

                <!-- Riepilogo statistiche -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Statistiche di Sincronizzazione</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">PRODOTTI NEL FILE EXCEL</div>
                                    <div class="ms-auto lh-1">
                                        <div class="badge bg-blue">${excelTotals}</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <div class="h1 mb-0 me-2">${excelTotals}</div>
                                </div>
                                <div class="mt-3">
                                    <div class="row g-2 align-items-center mb-2">
                                        <div class="col">Righe totali nel file</div>
                                        <div class="col-auto text-muted">${excelRawRows}</div>
                                    </div>
                                    <div class="row g-2 align-items-center mb-2">
                                        <div class="col">Righe intestazione</div>
                                        <div class="col-auto text-muted">${excelSkippedRows}</div>
                                    </div>
                                    <div class="row g-2 align-items-center mb-2">
                                        <div class="col">Righe vuote/senza EAN</div>
                                        <div class="col-auto text-muted">${excelEmptyRows}</div>
                                    </div>
                                    <div class="row g-2 align-items-center" id="duplicateEansRow" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#duplicate-eans" aria-expanded="false">
                                        <div class="col">EAN duplicati</div>
                                        <div class="col-auto text-muted">
                                            ${excelDuplicateEans}
                                            ${excelDuplicateEans > 0 ? '<i class="ti ti-chevron-down ms-1"></i>' : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">PRODOTTI NEL DATABASE</div>
                                    <div class="ms-auto lh-1">
                                        <div class="badge bg-blue">${dbTotals}</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <div class="h1 mb-0 me-2">${dbTotals}</div>
                                </div>
                                <div class="mt-3">
                                    ${backupId ? `
                                    <div class="alert alert-success">
                                        <div class="d-flex">
                                            <div><i class="ti ti-archive icon alert-icon"></i></div>
                                            <div>
                                                <div>Backup del catalogo creato automaticamente</div>
                                                <div class="text-muted">ID Backup: ${backupId}</div>
                                            </div>
                                        </div>
                                    </div>` : ''}
                                    ${skippedProducts > 0 ? `
                                    <div class="alert alert-warning">
                                        <div class="d-flex">
                                            <div><i class="ti ti-filter-off icon alert-icon"></i></div>
                                            <div>
                                                <div>${skippedProducts} prodotti esclusi dalla sincronizzazione</div>
                                                <div class="text-muted">Prodotti con EAN presenti ma assegnati ad altri negozi</div>
                                            </div>
                                        </div>
                                    </div>` : ''}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">DIFFERENZA</div>
                                    <div class="ms-auto lh-1">
                                        <div class="badge ${excelTotals > dbTotals ? 'bg-green' : 'bg-red'}">${Math.abs(excelTotals - dbTotals)}</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <div class="h1 mb-0 me-2">${Math.abs(excelTotals - dbTotals)}</div>
                                    <div class="text-muted">${excelTotals > dbTotals ? 'nuovi prodotti potenziali' : 'prodotti mancanti'}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sezione EAN duplicati -->
                <div class="collapse mb-3" id="duplicate-eans">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="ti ti-alert-triangle text-warning me-2"></i>
                                EAN Duplicati (${duplicateEans.length})
                            </h4>
                        </div>
                        <div class="card-body">
                            ${generateDuplicateEansList(duplicateEans)}
                        </div>
                    </div>
                </div>

                <!-- Card riassuntive in 3 colonne -->
                <div class="row row-cards mb-4">
                    <div class="col-sm-6 col-lg-4">
                        <div class="card card-sm card-link" data-bs-toggle="collapse" data-bs-target="#new-products" aria-expanded="false">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="bg-green text-white avatar">
                                            <i class="ti ti-plus"></i>
                                        </span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">
                                            ${totals.new_added || 0} Nuovi Prodotti
                                        </div>
                                        <div class="text-muted">
                                            Aggiunti al catalogo
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="ti ti-chevron-down"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card card-sm card-link" data-bs-toggle="collapse" data-bs-target="#price-updated" aria-expanded="false">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="bg-yellow text-white avatar">
                                            <i class="ti ti-refresh"></i>
                                        </span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">
                                            ${totals.price_updated || 0} Prezzi Aggiornati
                                        </div>
                                        <div class="text-muted">
                                            Modifiche ai prezzi
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="ti ti-chevron-down"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card card-sm card-link" data-bs-toggle="collapse" data-bs-target="#title-updated" aria-expanded="false">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="bg-primary text-white avatar">
                                            <i class="ti ti-edit"></i>
                                        </span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">
                                            ${totals.title_updated || 0} Titoli Aggiornati
                                        </div>
                                        <div class="text-muted">
                                            Modifiche ai titoli
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="ti ti-chevron-down"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    ${!isPromo ? `
                    <div class="col-sm-6 col-lg-4">
                        <div class="card card-sm card-link" data-bs-toggle="collapse" data-bs-target="#disabled-products" aria-expanded="false">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="bg-red text-white avatar">
                                            <i class="ti ti-ban"></i>
                                        </span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">
                                            ${totals.disabled || 0} Disattivati
                                        </div>
                                        <div class="text-muted">
                                            Prodotti non presenti
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="ti ti-chevron-down"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>` : ''}
                    <div class="col-sm-6 col-lg-4">
                        <div class="card card-sm card-link" data-bs-toggle="collapse" data-bs-target="#reactivated-products" aria-expanded="false">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="bg-green text-white avatar">
                                            <i class="ti ti-check"></i>
                                        </span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">
                                            ${totals.reactivated || 0} Riattivati
                                        </div>
                                        <div class="text-muted">
                                            Prodotti riattivati
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="ti ti-chevron-down"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    ${totals.promo_updated ? `
                    <div class="col-sm-6 col-lg-4">
                        <div class="card card-sm card-link" data-bs-toggle="collapse" data-bs-target="#promo-updated" aria-expanded="false">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="bg-purple text-white avatar">
                                            <i class="ti ti-discount"></i>
                                        </span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">
                                            ${totals.promo_updated} Promo Aggiornate
                                        </div>
                                        <div class="text-muted">
                                            Prodotti in promozione
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="ti ti-chevron-down"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>` : ''}
                    ${skippedProducts > 0 ? `
                    <div class="col-sm-6 col-lg-4">
                        <div class="card card-sm card-link" data-bs-toggle="collapse" data-bs-target="#skipped-products" aria-expanded="false">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="bg-orange text-white avatar">
                                            <i class="ti ti-filter-off"></i>
                                        </span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">
                                            ${skippedProducts} Saltati
                                        </div>
                                        <div class="text-muted">
                                            Altri negozi
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="ti ti-chevron-down"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>` : ''}
                </div>

                <!-- Sezioni collassabili per i dettagli -->
                ${generateDetailSections(results)}
                
                <!-- Pulsante nuova sincronizzazione -->
                <div class="mt-4 text-center">
                    <button type="button" class="btn btn-primary" onclick="location.reload()">
                        <i class="ti ti-refresh me-1"></i>
                        Nuova Sincronizzazione
                    </button>
                </div>
            </div>
        </div>`;

    return html;
}

function generateDetailSections(results) {
    let sections = {
        'new-products': {
            title: 'Nuovi Prodotti',
            icon: 'ti-plus',
            color: 'green',
            data: results.new_added || [],
            fields: ['ean', 'titolo', 'prezzo']
        },
        'price-updated': {
            title: 'Prezzi Aggiornati',
            icon: 'ti-refresh',
            color: 'yellow',
            data: results.price_updated || [],
            fields: ['ean', 'titolo', 'prezzo_vecchio', 'prezzo_nuovo']
        },
        'title-updated': {
            title: 'Titoli Aggiornati',
            icon: 'ti-edit',
            color: 'blue',
            data: results.title_updated || [],
            fields: ['ean', 'titolo_vecchio', 'titolo_nuovo']
        },
        'disabled-products': {
            title: 'Prodotti Disattivati',
            icon: 'ti-ban',
            color: 'red',
            data: results.disabled || [],
            fields: ['ean', 'titolo']
        },
        'reactivated-products': {
            title: 'Prodotti Riattivati',
            icon: 'ti-check',
            color: 'green',
            data: results.reactivated || [],
            fields: ['ean', 'titolo']
        },
        'promo-updated': {
            title: 'Promozioni Aggiornate',
            icon: 'ti-discount',
            color: 'purple',
            data: results.promo_updated || [],
            fields: ['ean', 'titolo', 'prezzo_promo_vecchio', 'prezzo_promo_nuovo']
        },
        'skipped-products': {
            title: 'Prodotti Saltati (Altri Negozi)',
            icon: 'ti-filter-off',
            color: 'orange',
            data: results.skipped || [],
            fields: ['ean', 'titolo', 'negozio_attuale', 'negozio_richiesto']
        }
    };

    let html = '';
    
    Object.entries(sections).forEach(([id, section]) => {
        // Salta le sezioni senza dati
        if (!section.data || section.data.length === 0) {
            return;
        }
        
        html += `
            <div class="collapse mb-3" id="${id}">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="ti ${section.icon} text-${section.color} me-2"></i>
                            ${section.title} (${section.data.length})
                        </h4>
                    </div>
                    <div class="card-body p-0">
                        ${section.data.length > 0 ? generateTableForSection(section) : '<div class="p-3">Nessun dato disponibile</div>'}
                    </div>
                </div>
            </div>`;
    });
    
    return html;
}

function generateTableForSection(section) {
    return `
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        ${section.fields.map(field => 
                            `<th>${formatFieldName(field)}</th>`
                        ).join('')}
                    </tr>
                </thead>
                <tbody>
                    ${section.data.map(item => `
                        <tr>
                            ${section.fields.map(field => 
                                `<td>${item[field] || ''}</td>`
                            ).join('')}
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>`;
}

function formatFieldName(field) {
    const names = {
        ean: 'EAN',
        titolo: 'Titolo',
        prezzo: 'Prezzo',
        prezzo_vecchio: 'Prezzo Vecchio',
        prezzo_nuovo: 'Prezzo Nuovo',
        titolo_vecchio: 'Titolo Vecchio',
        titolo_nuovo: 'Titolo Nuovo',
        prezzo_promo_vecchio: 'Promo Vecchio',
        prezzo_promo_nuovo: 'Promo Nuovo',
        negozio_attuale: 'Negozio Attuale',
        negozio_richiesto: 'Negozio Richiesto',
        is_promo: 'In Promozione',
        negozio_id: 'ID Negozio'
    };
    return names[field] || field;
}

function generateDuplicateEansList(duplicateEans) {
    if (!duplicateEans || duplicateEans.length === 0) {
        return '<div class="text-muted">Nessun EAN duplicato trovato</div>';
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-vcenter table-sm">
                <thead>
                    <tr>
                        <th>EAN</th>
                        <th>Occorrenze</th>
                    </tr>
                </thead>
                <tbody>`;
    
    duplicateEans.forEach(item => {
        html += `
            <tr>
                <td><code>${item.ean}</code></td>
                <td>${item.count} volte</td>
            </tr>`;
    });
    
    html += `
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <textarea class="form-control" rows="6" readonly>${duplicateEans.map(item => item.ean).join('\n')}</textarea>
            <small class="text-muted">Lista EAN duplicati (uno per riga) - puoi selezionare e copiare questo testo</small>
        </div>`;
    
    return html;
}

// Event listeners
$(document).ready(function() {
    // Inizializza contenitori nascosti
    $('#previewContainer, #mappingContainer, #reportContainer, #errorContainer, #loadingContainer').hide();
    
    // Upload file
    $('#excelUploadForm').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        let fileInput = $(this).find('input[type="file"]');
        
        if (!fileInput[0].files.length) {
            showError('Seleziona un file Excel da caricare');
            return;
        }
        handleFileUpload(formData);
    });
    
    // Submit mappatura
    $(document).on('submit', '#mappingForm', function(e) {
        e.preventDefault();
        let mappings = {
            ean: $('#eanColumn').val(),
            titolo: $('#titleColumn').val(),
            prezzo: $('#priceColumn').val()
        };
        
        // Validazione
        if (!mappings.ean || !mappings.titolo || !mappings.prezzo) {
            showError('Seleziona tutte le colonne richieste');
            return;
        }
        
        handleSync(mappings, $('#skipRows').val());
    });
    
    // Gestione chiusura alert
    $(document).on('click', '.alert .btn-close', function() {
        $(this).closest('.alert').alert('close');
    });
}); 