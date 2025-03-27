/**
 * multiselect.js - Gestione della selezione multipla avanzata
 * 
 * Questo modulo implementa funzionalità avanzate di selezione prodotti,
 * come selezione con CTRL/SHIFT e selezione di tutti i prodotti.
 */

const MultiSelect = {
    /**
     * Inizializza la selezione multipla avanzata
     */
    inizializza: function() {
        this.implementaSelezioneProdottiAvanzata();
        this.aggiungiPulsanteClassificazioneManuale();
    },
    
    /**
     * Implementa selezione avanzata per i prodotti con supporto CTRL/SHIFT
     */
    implementaSelezioneProdottiAvanzata: function() {
        // Delegazione evento per gestire click su checkbox
        $('#products-container').on('click', '.product-checkbox', function(e) {
            const $this = $(this);
            const currentProductId = $this.val();
            
            // Gestione SHIFT+click per selezionare intervallo
            if (e.shiftKey && State.lastChecked !== null) {
                const start = $('.product-checkbox').index(State.lastChecked);
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
                        State.addSelectedProduct(productId);
                    } else {
                        State.removeSelectedProduct(productId);
                    }
                });
                
                UI.aggiornaContatoreProdottiSelezionati();
                e.preventDefault(); // Previeni toggle default del browser
            } 
            
            State.lastChecked = $this;
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
    },
    
    /**
     * Gestisce il cambio stato di un checkbox
     * @param {jQuery} $checkbox - Elemento checkbox
     */
    gestisciCambioCheckbox: function($checkbox) {
        const productId = $checkbox.val();
        if ($checkbox.is(':checked')) {
            State.addSelectedProduct(productId);
        } else {
            State.removeSelectedProduct(productId);
        }
        
        UI.aggiornaContatoreProdottiSelezionati();
    },
    
    /**
     * Seleziona tutti i prodotti visualizzati
     */
    selezionaTutti: function() {
        $('.product-checkbox').prop('checked', true);
        State.selectedProductIds = State.productsList.map(p => p.id.toString());
        UI.aggiornaContatoreProdottiSelezionati();
    },
    
    /**
     * Deseleziona tutti i prodotti
     */
    deselezionaTutti: function() {
        $('.product-checkbox').prop('checked', false);
        State.selectedProductIds = [];
        UI.aggiornaContatoreProdottiSelezionati();
    },
    
    /**
     * Aggiunge pulsante per classificazione manuale nella toolbar
     */
    aggiungiPulsanteClassificazioneManuale: function() {
        // Aggiungi pulsante nella toolbar se non esiste già
        if (!$('#btn-manual-classify').length) {
            $('.toolbar-group:first').append(`
                <button id="btn-manual-classify" class="button button-info" disabled>
                    🗃 Classificazione manuale
                </button>
            `);
            
            // Handler per pulsante classificazione manuale
            $('#btn-manual-classify').on('click', UI.mostraModalClassificazioneManuale);
            
            // Aggiungi HTML per modal se non esiste
            if (!$('#manual-classify-modal').length) {
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
                $('#btn-apply-manual').on('click', Products.applicaClassificazioneManuale);
                
                // Handler cambiamento categoria per aggiornare sottocategorie disponibili
                $('#manual-categoria').on('change', function() {
                    Filters.aggiornaSelettoreSottocategorieModal($(this).val());
                });
            }
        }
    }
};