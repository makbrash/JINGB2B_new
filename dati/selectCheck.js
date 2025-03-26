// ===== PARTE 1: Miglioramento selezione multipla prodotti =====
/**
 * Salva filtri e selezioni in sessionStorage
 */

/**
 * Carica filtri da sessionStorage
 */








// Aggiungi al document.ready in classificatore.js
$(document).ready(function() {
    // ... codice esistente ...
    
    // Abilita selezione multipla con CTRL/SHIFT
    implementaSelezioneProdottiAvanzata();
    
    // Aggiungi pulsante classificazione manuale nella toolbar
    aggiungiPulsanteClassificazioneManuale();
    
    // Carica filtri da sessionStorage
    caricaFiltriSalvati();
    
    // Aggiungi handler salvataggio filtri
    $('#categoria-filter, #sottocategoria-filter, #marca-filter, #stato-filter, #limit-selector')
        .on('change', salvaFiltri);
});

/**
 * Implementa selezione avanzata per i prodotti con supporto CTRL/SHIFT
 */
function implementaSelezioneProdottiAvanzata() {
    // Inizializza variabili per tracking selezione
    let lastChecked = null;
    let startShiftSelect = null;
    
    // Delegazione evento per gestire click su checkbox
    $('#products-container').on('click', '.product-checkbox', function(e) {
        const $this = $(this);
        const currentProductId = $this.val();
        
        // Gestione SHIFT+click per selezionare intervallo
        if (e.shiftKey && lastChecked !== null) {
            const start = $('.product-checkbox').index(lastChecked);
            const end = $('.product-checkbox').index($this);
            
            // Seleziona/deseleziona tutti i checkbox nell'intervallo
            const checkStatus = $this.prop('checked');
            
            $('.product-checkbox').slice(
                Math.min(start, end),
                Math.max(start, end) + 1
            ).prop('checked', checkStatus).each(function() {
                // Aggiorna array prodotti selezionati
                const productId = $(this).val();
                
                if (checkStatus) {
                    if (!appState.selectedProductIds.includes(productId)) {
                        appState.selectedProductIds.push(productId);
                    }
                } else {
                    const index = appState.selectedProductIds.indexOf(productId);
                    if (index !== -1) {
                        appState.selectedProductIds.splice(index, 1);
                    }
                }
            });
            
            aggiornaContatoreProdottiSelezionati();
            e.preventDefault(); // Previeni toggle default del browser
        } 
        // CTRL+click lascia il comportamento normale
        else if (e.ctrlKey || e.metaKey) {
            // Aggiorna array prodotti selezionati (gestito dall'handler esistente)
            // Questo viene già gestito dall'handler esistente su .product-checkbox
        }
        
        lastChecked = $this;
    });
    
    // Permetti selezione delle righe della tabella
    $('#products-container').on('click', 'tr[data-id]', function(e) {
        // Evita click su elementi interattivi all'interno della riga
        if ($(e.target).is('a, button, input, .product-checkbox')) {
            return;
        }
        
        // Trova checkbox nella riga
        const checkbox = $(this).find('.product-checkbox');
        
        // Toggle stato
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });
}

/**
 * Aggiunge pulsante per classificazione manuale nella toolbar
 */
function aggiungiPulsanteClassificazioneManuale() {
    // Aggiungi pulsante nella toolbar
    $('.toolbar-group:first').append(`
        <button id="btn-manual-classify" class="button button-info" disabled>
            📋 Classificazione manuale
        </button>
    `);
    
    // Handler per pulsante classificazione manuale
    $('#btn-manual-classify').on('click', mostraModalClassificazioneManuale);
    
    // Aggiorna handler contatore selezionati per abilitare/disabilitare pulsante
    const originalHandler = aggiornaContatoreProdottiSelezionati;
    aggiornaContatoreProdottiSelezionati = function() {
        originalHandler();
        
        const count = appState.selectedProductIds.length;
        if (count > 0) {
            $('#btn-manual-classify').prop('disabled', false);
        } else {
            $('#btn-manual-classify').prop('disabled', true);
        }
    };
    
    // Aggiungi HTML per modal
    $('body').append(`
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
    `);
    
    // Handlers per i checkbox di attivazione campi
    $('#manual-update-categoria').on('change', function() {
        $('#manual-categoria').prop('disabled', !$(this).prop('checked'));
    });
    
    $('#manual-update-sottocategoria').on('change', function() {
        $('#manual-sottocategoria').prop('disabled', !$(this).prop('checked'));
    });
    
    $('#manual-update-marca').on('change', function() {
        $('#manual-marca').prop('disabled', !$(this).prop('checked'));
    });
    
    $('#manual-update-tags').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('#manual-tags').prop('disabled', !isChecked);
        $('#manual-tags-container').toggle(isChecked);
    });
    
    // Handler per chiusura modal
    $('#btn-cancel-manual').on('click', function() {
        $('#manual-classify-modal').hide();
    });
    
    // Handler per applicazione modifiche
    $('#btn-apply-manual').on('click', applicaClassificazioneManuale);
    
    // Handler cambiamento categoria per aggiornare sottocategorie disponibili
    $('#manual-categoria').on('change', function() {
        aggiornaSelettoreSottocategorieModal($(this).val());
    });
}

/**
 * Mostra il modal per la classificazione manuale
 */
function mostraModalClassificazioneManuale() {
    // Popola selettori con valori disponibili
    popolaSelettoriModal();
    
    // Aggiorna contatore prodotti
    $('#manual-count').text(appState.selectedProductIds.length);
    
    // Mostra modal
    $('#manual-classify-modal').show();
}

/**
 * Popola i selettori del modal con i valori disponibili
 */
function popolaSelettoriModal() {
    // Recupera le liste di categorie, sottocategorie e marche
    const categorie = $('#categoria-filter').html();
    const marche = $('#marca-filter').html();
    
    // Imposta le opzioni nei selettori
    $('#manual-categoria').html(categorie);
    $('#manual-marca').html(marche);
    
    // La sottocategoria verrà aggiornata quando si seleziona la categoria
    aggiornaSelettoreSottocategorieModal($('#manual-categoria').val());
}

/**
 * Aggiorna selettore sottocategorie nel modal in base alla categoria selezionata
 * @param {string} categoriaId ID categoria selezionata
 */
function aggiornaSelettoreSottocategorieModal(categoriaId) {
    // Usa la stessa logica di aggiornaSelettoreSottocategorie
    const sottocategorie = $('#categoria-filter').data('sottocategorie');
    let sottocategorieHtml = '<option value="">-- Seleziona Sottocategoria --</option>';
    
    if (categoriaId) {
        sottocategorie.forEach(function(subcat) {
            if (subcat.categoria_id == categoriaId) {
                sottocategorieHtml += `<option value="${subcat.id}">${subcat.nome}</option>`;
            }
        });
    }
    
    $('#manual-sottocategoria').html(sottocategorieHtml);
}

/**
 * Applica classificazione manuale ai prodotti selezionati
 */
function applicaClassificazioneManuale() {
    // Verifica se è stato selezionato almeno un campo da aggiornare
    if (!$('#manual-update-categoria').prop('checked') && 
        !$('#manual-update-sottocategoria').prop('checked') && 
        !$('#manual-update-marca').prop('checked') && 
        !$('#manual-update-tags').prop('checked')) {
        mostraNotifica('Seleziona almeno un campo da aggiornare', 'warning');
        return;
    }
    
    // Prepara dati da inviare
    const updateData = {
        action: 'manual_classify',
        product_ids: appState.selectedProductIds,
        update_fields: {}
    };
    
    // Aggiungi campi da aggiornare
    if ($('#manual-update-categoria').prop('checked')) {
        updateData.update_fields.categoria_id = $('#manual-categoria').val();
    }
    
    if ($('#manual-update-sottocategoria').prop('checked')) {
        updateData.update_fields.sottocategoria_id = $('#manual-sottocategoria').val();
    }
    
    if ($('#manual-update-marca').prop('checked')) {
        updateData.update_fields.marca_id = $('#manual-marca').val();
    }
    
    if ($('#manual-update-tags').prop('checked')) {
        updateData.update_fields.tags = $('#manual-tags').val();
        updateData.update_fields.tags_mode = $('input[name="manual-tags-mode"]:checked').val();
    }
    
    // Mostra loader
    const $applyBtn = $('#btn-apply-manual');
    const originalText = $applyBtn.text();
    $applyBtn.prop('disabled', true).html('<span class="loader" style="width: 16px; height: 16px; border-width: 2px; margin-right: 8px;"></span> Aggiornamento...');
    
    // Invia richiesta al server
    $.ajax({
        url: config.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: updateData,
        success: function(response) {
            if (response.success) {
                // Chiudi modal
                $('#manual-classify-modal').hide();
                
                // Mostra notifica
                mostraNotifica(response.message, 'success');
                
                // Ricarica prodotti e statistiche
                caricaProdotti();
                caricaStatistiche();
            } else {
                mostraNotifica('Errore: ' + response.message, 'error');
                $applyBtn.prop('disabled', false).text(originalText);
            }
        },
        error: function() {
            mostraNotifica('Errore di connessione al server', 'error');
            $applyBtn.prop('disabled', false).text(originalText);
        }
    });
}

// ===== PARTE 2: Persistenza filtri =====

/**
 * Salva filtri e selezioni in sessionStorage
 */
function salvaFiltri() {
    const filtriDaSalvare = {
        categoria_id: $('#categoria-filter').val(),
        sottocategoria_id: $('#sottocategoria-filter').val(),
        marca_id: $('#marca-filter').val(),
        stato_tag: $('#stato-filter').val(),
        limit: $('#limit-selector').val(),
        range_start: $('#range_start').val(),
        range_end: $('#range_end').val()
    };
    
    sessionStorage.setItem('classificatore_filtri', JSON.stringify(filtriDaSalvare));
}
/**
 * Inizializza la persistenza dei filtri
 */
function inizializzaPersistenzaFiltri() {
    // Aggiungi gli handler di salvataggio a tutti i selettori
    $('#categoria-filter, #sottocategoria-filter, #marca-filter, #stato-filter, #limit-selector')
        .on('change', salvaFiltri);
    
    // Aggiungi handler di salvataggio ai campi del range quando vengono modificati
    $('#range_start, #range_end').on('blur', salvaFiltri);
    
    // Carica i filtri salvati
    setTimeout(caricaFiltriSalvati, 300);
}

/**
 * Carica filtri da sessionStorage
 */
function caricaFiltriSalvati() {
    const filtriSalvati = sessionStorage.getItem('classificatore_filtri');
    
    if (!filtriSalvati) return;
    
    const filtri = JSON.parse(filtriSalvati);
    
    // Imposta prima i valori indipendenti
    if (filtri.stato_tag) $('#stato-filter').val(filtri.stato_tag);
    if (filtri.limit) $('#limit-selector').val(filtri.limit);
    if (filtri.range_start) $('#range_start').val(filtri.range_start);
    if (filtri.range_end) $('#range_end').val(filtri.range_end);
    if (filtri.marca_id) $('#marca-filter').val(filtri.marca_id);
    
    // Gestione categoria e sottocategoria con promesse
    if (filtri.categoria_id) {
        $('#categoria-filter').val(filtri.categoria_id);
        
        // Se non abbiamo ancora caricato le sottocategorie, attendiamo
        if (!$('#categoria-filter').data('sottocategorie')) {
            // Creiamo una promessa che si risolve quando caricaSelettori completa
            window.selettoriPromise = window.selettoriPromise || new Promise((resolve) => {
                // Aggiungiamo un handler temporaneo per catturare il completamento
                const originalFunc = aggiornaSelettoreSottocategorie;
                window.tempSelettoriCallback = resolve;
                
                aggiornaSelettoreSottocategorie = function(categoriaId) {
                    originalFunc(categoriaId);
                    // Ripristina la funzione originale
                    aggiornaSelettoreSottocategorie = originalFunc;
                    // Risolvi la promessa
                    if (window.tempSelettoriCallback) window.tempSelettoriCallback();
                };
            });
            
            // Quando i selettori sono caricati, imposta la sottocategoria
            window.selettoriPromise.then(() => {
                if (filtri.sottocategoria_id) {
                    $('#sottocategoria-filter').val(filtri.sottocategoria_id);
                }
            });
        } else {
            // Se abbiamo gi� caricato i selettori, aggiorniamo immediatamente le sottocategorie
            aggiornaSelettoreSottocategorie(filtri.categoria_id);
            
            // Poi impostiamo il valore della sottocategoria
            if (filtri.sottocategoria_id) {
                setTimeout(() => {
                    $('#sottocategoria-filter').val(filtri.sottocategoria_id);
                }, 100);
            }
        }
    }
}