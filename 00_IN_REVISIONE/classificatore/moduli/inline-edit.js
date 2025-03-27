/**
 * inline-edit.js - Gestione della modifica inline dei record
 * 
 * Questo modulo implementa la funzionalità di modifica inline
 * dei campi nella tabella prodotti (categorie, sottocategorie, marche e tag).
 */

const InlineEdit = {
    /**
     * Inizializza la modifica inline
     */
    inizializza: function() {
        this.abilitaModificaInlineRecord();
        this.aggiungiStiliModificaInline();
    },
    
    /**
     * Abilita la modifica inline dei record
     */
    abilitaModificaInlineRecord: function() {
        // Delegazione evento per i clic sulle celle modificabili
        $('#products-container').on('click', '.editable-cell', function(e) {
            // Evita di aprire in caso di click su elementi interni
            if ($(e.target).hasClass('editable-cell-input') || 
                $(e.target).hasClass('editable-cell-select')) {
                return;
            }
            
            const $cell = $(this);
            const fieldType = $cell.data('field-type');
            const productId = $cell.closest('tr').data('id');
            const currentValue = $cell.data('value');
            
            // Se la cella è già in modalità modifica, non fare nulla
            if ($cell.hasClass('editing')) return;
            
            // Aggiungi classe editing
            $cell.addClass('editing');
            
            // Salva il contenuto originale
            const originalContent = $cell.html();
            
            // Gestisci la creazione dell'input in base al tipo di campo
            switch (fieldType) {
                case 'categoria':
                    InlineEdit.renderizzaSelectModifica($cell, 'categoria-filter', currentValue, function(newValue) {
                        InlineEdit.salvaModificaRecord(productId, 'categoria_id', newValue, $cell, originalContent);
                    });
                    break;
                    
                case 'sottocategoria':
                    // Per sottocategoria, dobbiamo ottenere la categoria attuale
                    const categoriaId = $cell.closest('tr').find('td[data-field-type="categoria"]').data('value');
                    InlineEdit.renderizzaSelectModificaSottocategoria($cell, categoriaId, currentValue, function(newValue) {
                        InlineEdit.salvaModificaRecord(productId, 'sottocategoria_id', newValue, $cell, originalContent);
                    });
                    break;
                    
                case 'marca':
                    InlineEdit.renderizzaSelectModifica($cell, 'marca-filter', currentValue, function(newValue) {
                        InlineEdit.salvaModificaRecord(productId, 'marca_id', newValue, $cell, originalContent);
                    });
                    break;
                    
                case 'tags':
                    // Per i tag, mostriamo un campo di testo
                    InlineEdit.renderizzaInputModificaTag($cell, currentValue, function(newValue, mode) {
                        InlineEdit.salvaModificaTagRecord(productId, newValue, mode, $cell, originalContent);
                    });
                    break;
            }
        });
        
        // Click fuori dal campo per annullare
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.editable-cell').length && 
                !$(e.target).closest('.editable-cell-input').length && 
                !$(e.target).closest('.editable-cell-select').length) {
                
                // Trova tutte le celle in modifica
                $('.editable-cell.editing').each(function() {
                    const $cell = $(this);
                    // Ripristina contenuto originale
                    $cell.html($cell.data('original-content'));
                    $cell.removeClass('editing');
                });
            }
        });
        
        // Previeni la propagazione del click sugli elementi di modifica
        $('#products-container').on('click', '.editable-cell-input, .editable-cell-select', function(e) {
            e.stopPropagation();
        });
    },
    
    /**
     * Renderizza un select per modifica in base al selettore di origine
     * @param {jQuery} $cell - Cella da modificare
     * @param {string} sourceSelectId - ID del selettore sorgente
     * @param {string} currentValue - Valore attuale
     * @param {Function} onChangeCallback - Callback per il cambio
     */
    renderizzaSelectModifica: function($cell, sourceSelectId, currentValue, onChangeCallback) {
        // Ottieni il contenuto originale per ripristino
        const originalContent = $cell.html();
        $cell.data('original-content', originalContent);
        
        // Crea un clone del selettore principale
        const $select = $(`<select class="editable-cell-select"></select>`);
        const options = $(`#${sourceSelectId} option`).clone();
        
        // Aggiungi le opzioni al nuovo select
        $select.append(options);
        
        // Imposta il valore corrente
        $select.val(currentValue);
        
        // Svuota la cella e aggiungi il selettore
        $cell.empty().append($select);
        
        // Focus sul selettore
        $select.focus();
        
        // Gestisci cambio
        $select.on('change', function() {
            const newValue = $(this).val();
            onChangeCallback(newValue);
        });
        
        // Gestisci tasto ESC per annullare
        $select.on('keydown', function(e) {
            if (e.keyCode === 27) { // ESC
                $cell.html(originalContent);
                $cell.removeClass('editing');
            }
        });
    },
    
    /**
     * Renderizza un select specifico per sottocategorie
     * @param {jQuery} $cell - Cella da modificare
     * @param {string} categoriaId - ID della categoria
     * @param {string} currentValue - Valore attuale
     * @param {Function} onChangeCallback - Callback per il cambio
     */
    renderizzaSelectModificaSottocategoria: function($cell, categoriaId, currentValue, onChangeCallback) {
        // Ottieni il contenuto originale per ripristino
        const originalContent = $cell.html();
        $cell.data('original-content', originalContent);
        
        // Crea un nuovo select
        const $select = $(`<select class="editable-cell-select"></select>`);
        
        // Ottieni le sottocategorie per la categoria selezionata
        const sottocategorie = $('#categoria-filter').data('sottocategorie');
        let options = '<option value="">-- Seleziona --</option>';
        
        if (sottocategorie) {
            sottocategorie.forEach(function(subcat) {
                if (subcat.categoria_id == categoriaId) {
                    const selected = (subcat.id == currentValue) ? 'selected' : '';
                    options += `<option value="${subcat.id}" ${selected}>${subcat.nome}</option>`;
                }
            });
        }
        
        // Aggiungi le opzioni al select
        $select.html(options);
        
        // Imposta il valore corrente
        $select.val(currentValue);
        
        // Svuota la cella e aggiungi il selettore
        $cell.empty().append($select);
        
        // Focus sul selettore
        $select.focus();
        
        // Gestisci cambio
        $select.on('change', function() {
            const newValue = $(this).val();
            onChangeCallback(newValue);
        });
        
        // Gestisci tasto ESC per annullare
        $select.on('keydown', function(e) {
            if (e.keyCode === 27) { // ESC
                $cell.html(originalContent);
                $cell.removeClass('editing');
            }
        });
    },
    
    /**
     * Renderizza input per modifica tags
     * @param {jQuery} $cell - Cella da modificare
     * @param {Array} currentValue - Valore attuale
     * @param {Function} onSaveCallback - Callback per il salvataggio
     */
    renderizzaInputModificaTag: function($cell, currentValue, onSaveCallback) {
        // Ottieni il contenuto originale per ripristino
        const originalContent = $cell.html();
        $cell.data('original-content', originalContent);
        
        // Prepara i tag come stringa separata da virgole
        let tagsString = '';
        if (Array.isArray(currentValue)) {
            tagsString = currentValue.join(', ');
        }
        
        // Crea il campo di input
        const $input = $(`<input type="text" class="editable-cell-input" value="${tagsString}" placeholder="Tag1, Tag2, Tag3...">`);
        
        // Crea i radio button per modalità
        const $radioContainer = $(`
            <div class="editable-cell-radio-container">
                <label><input type="radio" name="tags-mode-${$cell.closest('tr').data('id')}" value="replace" checked> Sostituisci</label>
                <label><input type="radio" name="tags-mode-${$cell.closest('tr').data('id')}" value="append"> Aggiungi</label>
                <button class="editable-cell-save-btn">Salva</button>
                <button class="editable-cell-cancel-btn">Annulla</button>
            </div>
        `);
        
        // Svuota la cella e aggiungi gli elementi
        $cell.empty().append($input).append($radioContainer);
        
        // Focus sull'input
        $input.focus();
        
        // Gestisci salvataggio con pulsante
        $cell.find('.editable-cell-save-btn').on('click', function() {
            const newValue = $input.val();
            const mode = $cell.find('input[type="radio"]:checked').val();
            onSaveCallback(newValue, mode);
        });
        
        // Gestisci annullamento
        $cell.find('.editable-cell-cancel-btn').on('click', function() {
            $cell.html(originalContent);
            $cell.removeClass('editing');
        });
        
        // Gestisci tasto ENTER per salvare e ESC per annullare
        $input.on('keydown', function(e) {
            if (e.keyCode === 13) { // ENTER
                const newValue = $input.val();
                const mode = $cell.find('input[type="radio"]:checked').val();
                onSaveCallback(newValue, mode);
            } else if (e.keyCode === 27) { // ESC
                $cell.html(originalContent);
                $cell.removeClass('editing');
            }
        });
    },
    
    /**
     * Salva la modifica di un record al database
     * @param {string} productId - ID del prodotto
     * @param {string} field - Campo da modificare
     * @param {string} newValue - Nuovo valore
     * @param {jQuery} $cell - Cella da aggiornare
     * @param {string} originalContent - Contenuto originale
     */
    salvaModificaRecord: function(productId, field, newValue, $cell, originalContent) {
        // Mostra indicatore caricamento
        $cell.html('<div class="loader"></div> Salvataggio...');
        
        // Prepara i dati per la richiesta
        const data = {
            action: 'manual_classify',
            product_ids: [productId],
            update_fields: {}
        };
        
        // Imposta il campo da aggiornare
        data.update_fields[field] = newValue;
        
        // Invia richiesta al server
        API.applicaClassificazioneManuale(data).then(response => {
            // Ottieni il nome visualizzato per il valore selezionato
            let displayText = newValue;
            
            if (field === 'categoria_id') {
                // Trova il testo dell'opzione selezionata
                displayText = $('#categoria-filter option[value="' + newValue + '"]').text();
                
                // Aggiorna anche il valore memorizzato
                $cell.data('value', newValue);
                
                // Potremmo dover aggiornare anche le sottocategorie disponibili
                const $sottocategoriaCell = $cell.closest('tr').find('td[data-field-type="sottocategoria"]');
                if ($sottocategoriaCell.length) {
                    // Reset della sottocategoria se necessario
                    // Questa logica potrebbe essere migliorata basandosi sulle tue esigenze
                }
            } 
            else if (field === 'sottocategoria_id') {
                // Trova il testo dalla lista sottocategorie
                const sottocategorie = $('#categoria-filter').data('sottocategorie');
                if (sottocategorie) {
                    for (let i = 0; i < sottocategorie.length; i++) {
                        if (sottocategorie[i].id == newValue) {
                            displayText = sottocategorie[i].nome;
                            break;
                        }
                    }
                }
                $cell.data('value', newValue);
            }
            else if (field === 'marca_id') {
                displayText = $('#marca-filter option[value="' + newValue + '"]').text();
                $cell.data('value', newValue);
            }
            
            // Mostra il nuovo valore
            $cell.html(displayText);
            $cell.removeClass('editing');
            
            // Notifica
            UI.mostraNotifica('Aggiornamento completato con successo', 'success');
            
            // Ricarica statistiche per riflettere i cambiamenti
            API.caricaStatistiche();
        }).catch(() => {
            // Ripristina il contenuto originale in caso di errore
            $cell.html(originalContent);
            $cell.removeClass('editing');
        });
    },
    
    /**
     * Salva la modifica dei tag di un record
     * @param {string} productId - ID del prodotto
     * @param {string} tagsString - Stringa di tag
     * @param {string} mode - Modalità (replace o append)
     * @param {jQuery} $cell - Cella da aggiornare
     * @param {string} originalContent - Contenuto originale
     */
    salvaModificaTagRecord: function(productId, tagsString, mode, $cell, originalContent) {
        // Mostra indicatore caricamento
        $cell.html('<div class="loader"></div> Salvataggio...');
        
        // Prepara i dati per la richiesta
        const data = {
            action: 'manual_classify',
            product_ids: [productId],
            update_fields: {
                tags: tagsString,
                tags_mode: mode
            }
        };
        
        // Invia richiesta al server
        API.applicaClassificazioneManuale(data).then(() => {
            // Ricarica il record aggiornato per mostrare i nuovi tag
            API.caricaSingoloProdotto(productId).then(product => {
                if (product) {
                    // Formatta i tag
                    let tagsHtml = '';
                    if (product.tags && product.tags.length > 0) {
                        tagsHtml = '<div class="tag-container">';
                        product.tags.forEach(function(tag) {
                            tagsHtml += `<span class="tag">${tag}</span>`;
                        });
                        tagsHtml += '</div>';
                    } else {
                        tagsHtml = '<em>Nessun tag</em>';
                    }
                    
                    // Aggiorna la cella
                    $cell.html(tagsHtml);
                    $cell.data('value', product.tags);
                    $cell.removeClass('editing');
                    
                    // Notifica
                    UI.mostraNotifica('Tag aggiornati con successo', 'success');
                    
                    // Ricarica statistiche per riflettere i cambiamenti
                    API.caricaStatistiche();
                } else {
                    // Ripristina in caso di errore
                    $cell.html(originalContent);
                    $cell.removeClass('editing');
                    UI.mostraNotifica('Impossibile aggiornare i tag', 'error');
                }
            });
        }).catch(() => {
            // Ripristina il contenuto originale in caso di errore
            $cell.html(originalContent);
            $cell.removeClass('editing');
        });
    },
    
    /**
     * Popola i selettori del modal con i valori disponibili
     */
    popolaSelettoriModal: function() {
        // Recupera le liste di categorie, sottocategorie e marche
        const categorie = $('#categoria-filter').html();
        const marche = $('#marca-filter').html();
        
        // Imposta le opzioni nei selettori
        $('#manual-categoria').html(categorie);
        $('#manual-marca').html(marche);
        
        // La sottocategoria verrà aggiornata quando si seleziona la categoria
        Filters.aggiornaSelettoreSottocategorieModal($('#manual-categoria').val());
    },
    
    /**
     * Aggiungi stili CSS per la modifica inline
     */
    aggiungiStiliModificaInline: function() {
        // Aggiungi stili CSS solo se non esistono già
        if (!$('#inline-edit-styles').length) {
            $('head').append(`
                <style id="inline-edit-styles">
                    .editable-cell {
                        position: relative;
                        cursor: pointer;
                    }
                    
                    .editable-cell:hover:not(.editing) {
                        background-color: #f0f8ff;
                    }
                    
                    .editable-cell:hover:not(.editing)::after {
                        content: "✏️";
                        position: absolute;
                        right: 5px;
                        top: 5px;
                        font-size: 12px;
                    }
                    
                    .editable-cell.editing {
                        padding: 0;
                        background-color: #f5f5f5;
                    }
                    
                    .editable-cell-input,
                    .editable-cell-select {
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #007bff;
                        border-radius: 4px;
                        margin-bottom: 5px;
                    }
                    
                    .editable-cell-radio-container {
                        padding: 5px;
                        display: flex;
                        flex-wrap: wrap;
                        gap: 10px;
                        align-items: center;
                    }
                    
                    .editable-cell-save-btn,
                    .editable-cell-cancel-btn {
                        padding: 2px 5px;
                        font-size: 12px;
                        margin-left: auto;
                    }
                    
                    .editable-cell-save-btn {
                        background-color: #28a745;
                    }
                    
                    .editable-cell-cancel-btn {
                        background-color: #dc3545;
                    }
                </style>
            `);
        }
    }
};