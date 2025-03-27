// Aggiornamento del modulo filters.js per implementare Select2

const Filters = {
    /**
     * Inizializza la gestione dei filtri
     */
    inizializza: function() {
        this.inizializzaPersistenzaFiltri();
        this.inizializzaSelect2();
    },
    
    /**
     * Inizializza le select con Select2
     */
    inizializzaSelect2: function() {
        // Inizializza Select2 per il filtro categorie
        $('#categoria-filter').select2({
            placeholder: 'Tutte le categorie',
            allowClear: true,
            width: '100%',
            language: 'it',
            templateResult: this.formatoRisultatoSelect2
        });
        
        // Inizializza Select2 per il filtro sottocategorie
        $('#sottocategoria-filter').select2({
            placeholder: 'Tutte le sottocategorie',
            allowClear: true,
            width: '100%',
            language: 'it',
            templateResult: this.formatoRisultatoSelect2
        });
        
        // Inizializza Select2 per il filtro marche
        $('#marca-filter').select2({
            placeholder: 'Tutte le marche',
            allowClear: true,
            width: '100%',
            language: 'it',
            templateResult: this.formatoRisultatoSelect2
        });
        
        // Inizializza Select2 per il filtro stato
        $('#stato-filter').select2({
            minimumResultsForSearch: Infinity, // Disabilita ricerca per select corte
            width: '100%',
            language: 'it'
        });
        
        // Inizializza Select2 per il selettore limite
        $('#limit-selector').select2({
            minimumResultsForSearch: Infinity, // Disabilita ricerca per select corte
            width: '100%',
            language: 'it'
        });
        
        // Gestisci eventi change delle select
        $('#categoria-filter').on('change', function() {
            const categoriaId = $(this).val();
            Filters.aggiornaSelettoreSottocategorie(categoriaId);
            Filters.salvaFiltri();
            
            // Carica prodotti con piccolo ritardo per permettere
            // l'aggiornamento delle sottocategorie prima della richiesta
            setTimeout(() => {
                API.caricaProdotti();
            }, 100);
        });
        
        $('#sottocategoria-filter, #marca-filter, #stato-filter').on('change', function() {
            Filters.salvaFiltri();
            API.caricaProdotti();
        });
        
        $('#limit-selector').on('change', function() {
            State.productsPerPage = parseInt($(this).val());
            Filters.salvaFiltri();
            API.caricaProdotti();
        });
    },
    
    /**
     * Formatta i risultati nelle dropdown Select2
     * @param {Object} data - Dati dell'opzione
     * @returns {jQuery|string} Elemento jQuery formattato o testo
     */
    formatoRisultatoSelect2: function(data) {
        if (!data.id) {
            return data.text; // Ritorna testo non formattato per opzioni placeholder
        }
        
        // Se ci sono dati personalizzati nell'elemento (come conteggio prodotti)
        if (data.element && data.element.dataset.count) {
            return $(`<span>${data.text} <span class="select2-count">(${data.element.dataset.count})</span></span>`);
        }
        
        return data.text;
    },
    
    /**
     * Inizializza la persistenza dei filtri
     */
    inizializzaPersistenzaFiltri: function() {
        // Aggiungi gli handler di salvataggio ai campi del range quando vengono modificati
        $('#range_start, #range_end').on('blur', this.salvaFiltri);
        
        // Carica i filtri salvati
        setTimeout(this.caricaFiltriSalvati, 300);
    },
    
    /**
     * Salva filtri e selezioni in sessionStorage
     */
    salvaFiltri: function() {
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
    },
    
    /**
     * Carica filtri da sessionStorage
     */
    caricaFiltriSalvati: function() {
        const filtriSalvati = sessionStorage.getItem('classificatore_filtri');
        
        if (!filtriSalvati) return;
        
        const filtri = JSON.parse(filtriSalvati);
        
        // Imposta prima i valori indipendenti
        if (filtri.stato_tag) $('#stato-filter').val(filtri.stato_tag).trigger('change');
        if (filtri.limit) $('#limit-selector').val(filtri.limit).trigger('change');
        if (filtri.range_start) $('#range_start').val(filtri.range_start);
        if (filtri.range_end) $('#range_end').val(filtri.range_end);
        if (filtri.marca_id) $('#marca-filter').val(filtri.marca_id).trigger('change');
        
        // Gestione categoria e sottocategoria con promesse
        if (filtri.categoria_id) {
            // Con Select2 dobbiamo aggiornare sia il valore che l'UI
            $('#categoria-filter').val(filtri.categoria_id).trigger('change');
            
            // Se non abbiamo ancora caricato le sottocategorie, attendiamo
            if (!$('#categoria-filter').data('sottocategorie')) {
                // Creiamo una promessa che si risolve quando caricaSelettori completa
                window.selettoriPromise = window.selettoriPromise || new Promise((resolve) => {
                    // Aggiungiamo un handler temporaneo per catturare il completamento
                    const originalFunc = Filters.aggiornaSelettoreSottocategorie;
                    window.tempSelettoriCallback = resolve;
                    
                    Filters.aggiornaSelettoreSottocategorie = function(categoriaId) {
                        originalFunc(categoriaId);
                        // Ripristina la funzione originale
                        Filters.aggiornaSelettoreSottocategorie = originalFunc;
                        // Risolvi la promessa
                        if (window.tempSelettoriCallback) window.tempSelettoriCallback();
                    };
                });
                
                // Quando i selettori sono caricati, imposta la sottocategoria
                window.selettoriPromise.then(() => {
                    if (filtri.sottocategoria_id) {
                        $('#sottocategoria-filter').val(filtri.sottocategoria_id).trigger('change');
                    }
                });
            } else {
                // Se abbiamo già caricato i selettori, aggiorniamo immediatamente le sottocategorie
                Filters.aggiornaSelettoreSottocategorie(filtri.categoria_id);
                
                // Poi impostiamo il valore della sottocategoria
                if (filtri.sottocategoria_id) {
                    setTimeout(() => {
                        $('#sottocategoria-filter').val(filtri.sottocategoria_id).trigger('change');
                    }, 100);
                }
            }
        }
    },
    
    /**
     * Aggiorna selettore sottocategorie in base alla categoria selezionata
     * @param {string} categoriaId ID categoria selezionata
     */
    aggiornaSelettoreSottocategorie: function(categoriaId) {
        const sottocategorie = $('#categoria-filter').data('sottocategorie');
        let sottocategorieHtml = '<option value="">Tutte le sottocategorie</option>';
        
        if (categoriaId) {
            sottocategorie.forEach(function(subcat) {
                if (subcat.categoria_id == categoriaId) {
                    sottocategorieHtml += `<option value="${subcat.id}">${subcat.nome}</option>`;
                }
            });
        }
        
        // Aggiorniamo il selettore e notifichiamo Select2
        $('#sottocategoria-filter').html(sottocategorieHtml).trigger('change');
    },
    
    /**
     * Aggiorna selettore sottocategorie nel modal in base alla categoria selezionata
     * @param {string} categoriaId ID categoria selezionata
     */
    aggiornaSelettoreSottocategorieModal: function(categoriaId) {
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
        
        // Se il selettore ha Select2, aggiornalo
        if ($.fn.select2 && $('#manual-sottocategoria').hasClass('select2-hidden-accessible')) {
            $('#manual-sottocategoria').trigger('change');
        }
    },
    
    /**
     * Aggiunge metadati sui conteggi ai selettori
     * @param {Object} stats - Statistiche con conteggi
     */
    aggiungiMetadatiSelettori: function(stats) {
        // Aggiungi conteggi per le categorie
        if (stats.prodotti_per_categoria && stats.prodotti_per_categoria.length > 0) {
            stats.prodotti_per_categoria.forEach(function(cat) {
                $(`#categoria-filter option[value="${cat.id}"]`).attr('data-count', cat.count);
            });
        }
        
        // Aggiungi conteggi per le marche
        if (stats.prodotti_per_marca && stats.prodotti_per_marca.length > 0) {
            stats.prodotti_per_marca.forEach(function(marca) {
                $(`#marca-filter option[value="${marca.id}"]`).attr('data-count', marca.count);
            });
        }
        
        // Ridisegna Select2 per mostrare i conteggi aggiornati
        if ($.fn.select2) {
            $('#categoria-filter, #marca-filter').select2('destroy').select2({
                placeholder: 'Seleziona...',
                allowClear: true,
                width: '100%',
                language: 'it',
                templateResult: this.formatoRisultatoSelect2
            });
        }
    }
};

// Aggiorna il modulo per supportare Select2 nei modal di classificazione manuale

/**
 * Popola i selettori del modal con i valori disponibili
 */
InlineEdit.popolaSelettoriModal = function() {
    // Recupera le liste di categorie, sottocategorie e marche
    const categorie = $('#categoria-filter').html();
    const marche = $('#marca-filter').html();
    
    // Imposta le opzioni nei selettori
    $('#manual-categoria').html(categorie);
    $('#manual-marca').html(marche);
    
    // Inizializza Select2 sui selettori del modal se non già fatto
    if ($.fn.select2) {
        if (!$('#manual-categoria').hasClass('select2-hidden-accessible')) {
            $('#manual-categoria').select2({
                dropdownParent: $('#manual-classify-modal'),
                placeholder: '-- Seleziona Categoria --',
                width: '100%',
                language: 'it'
            });
        }
        
        if (!$('#manual-sottocategoria').hasClass('select2-hidden-accessible')) {
            $('#manual-sottocategoria').select2({
                dropdownParent: $('#manual-classify-modal'),
                placeholder: '-- Seleziona Sottocategoria --',
                width: '100%',
                language: 'it'
            });
        }
        
        if (!$('#manual-marca').hasClass('select2-hidden-accessible')) {
            $('#manual-marca').select2({
                dropdownParent: $('#manual-classify-modal'),
                placeholder: '-- Seleziona Marca --',
                width: '100%',
                language: 'it'
            });
        }
    }
    
    // La sottocategoria verrà aggiornata quando si seleziona la categoria
    Filters.aggiornaSelettoreSottocategorieModal($('#manual-categoria').val());
};

