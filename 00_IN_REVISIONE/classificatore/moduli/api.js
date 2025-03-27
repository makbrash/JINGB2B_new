/**
 * api.js - Comunicazione con il backend
 * 
 * Questo modulo gestisce tutte le chiamate AJAX al backend,
 * centralizzando e standardizzando le comunicazioni.
 */

const API = {
    /**
     * Effettua una chiamata AJAX al backend
     * @param {Object} options - Opzioni per la chiamata AJAX
     * @returns {Promise} Promise per la chiamata AJAX
     */
    call: function(options) {
        // Opzioni di default
        const defaults = {
            url: Config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {}
        };
        
        // Merge opzioni con defaults
        const settings = $.extend({}, defaults, options);
        
        // Restituisci una Promise per standardizzare la gestione delle chiamate
        return new Promise((resolve, reject) => {
            $.ajax({
                url: settings.url,
                type: settings.type,
                dataType: settings.dataType,
                data: settings.data,
                success: function(response) {
                    if (response.success) {
                        resolve(response);
                    } else {
                        UI.mostraNotifica('Errore: ' + response.message, 'error');
                        reject(response);
                    }
                },
                error: function(xhr, status, error) {
                    UI.mostraNotifica('Errore di connessione al server', 'error');
                    reject({ xhr, status, error });
                }
            });
        });
    },
    
    /**
     * Carica statistiche dal server
     */
    caricaStatistiche: function() {
        this.call({
            data: { action: 'get_stats' }
        }).then(response => {
            Statistics.mostraStatistiche(response.data);
        });
    },
    
    /**
     * Carica i selettori dal server (categorie, sottocategorie, marche)
     */
    caricaSelettori: function() {
        this.call({
            data: { action: 'get_selectors' }
        }).then(response => {
            // Selettore categorie
            let categorieHtml = '<option value="">Tutte le categorie</option>';
            response.data.categorie.forEach(function(cat) {
                categorieHtml += `<option value="${cat.id}">${cat.nome}</option>`;
            });
            $('#categoria-filter').html(categorieHtml);
            
            // Selettore sottocategorie (salvate in data attribute per filtro dinamico)
            $('#categoria-filter').data('sottocategorie', response.data.sottocategorie);
            
            // Selettore marche
            let marcheHtml = '<option value="">Tutte le marche</option>';
            response.data.marche.forEach(function(marca) {
                marcheHtml += `<option value="${marca.id}">${marca.nome}</option>`;
            });
            $('#marca-filter').html(marcheHtml);
            
            // Event handler per aggiornare sottocategorie
            $('#categoria-filter').on('change', function() {
                Filters.aggiornaSelettoreSottocategorie($(this).val());
            });
        });
    },
    
    /**
     * Carica prodotti dal server in base ai filtri
     * @param {Object} options - Opzioni aggiuntive per il caricamento
     */
    caricaProdotti: function(options = {}) {
        // Visualizza loader
        $('#products-loader').show();
        
        // Imposta opzioni predefinite
        const defaultOptions = {
            range_start: $('#range_start').val() || 0,
            range_end: $('#range_end').val() || 0,
            stato_tag: State.filterState === 'all' ? '' : 
                      (State.filterState === 'pending' ? 0 : 
                       State.filterState === 'done' ? 1 : 2),
            categoria_id: $('#categoria-filter').val() || '',
            sottocategoria_id: $('#sottocategoria-filter').val() || '',
            marca_id: $('#marca-filter').val() || '',
            limit: $('#limit-selector').val() || State.productsPerPage,
            page: State.currentPage
        };
        
        // Merge opzioni con defaults
        const params = {...defaultOptions, ...options, action: 'get_products'};
        
        this.call({
            data: params
        }).then(response => {
            // Salva i prodotti nello stato
            State.productsList = response.data;
            
            // Visualizza prodotti
            Products.renderizzaProdotti(response.data);
            
            // Nascondi loader
            $('#products-loader').hide();
            
            // Mostra notifica
            UI.mostraNotifica(response.message, 'success');
        }).catch(() => {
            $('#products-loader').hide();
        });
    },
    
    /**
     * Cerca prodotti in base ad una query di ricerca
     * @param {string} query - Query di ricerca
     */
    cercaProdotti: function(query) {
        // Visualizza loader
        $('#products-loader').show();
        
        this.call({
            data: {
                action: 'search_products',
                query: query,
                limit: 100 // Limite più alto per ricerche
            }
        }).then(response => {
            // Salva i prodotti nello stato
            State.productsList = response.data;
            
            // Visualizza prodotti
            Products.renderizzaProdotti(response.data);
            
            // Nascondi loader
            $('#products-loader').hide();
            
            // Mostra notifica
            UI.mostraNotifica(`Trovati ${response.data.length} prodotti per "${query}"`, 'success');
        }).catch(() => {
            $('#products-loader').hide();
        });
    },
    
    /**
     * Elabora un batch di prodotti
     * @param {Array} productIds - Array di ID prodotti
     * @returns {Promise} Promise con il risultato dell'elaborazione
     */
    elaboraBatchProdotti: function(productIds) {
        return this.call({
            data: {
                action: 'batch_classify',
                product_ids: productIds
            }
        });
    },
    
    /**
     * Elabora un singolo prodotto
     * @param {string} productId - ID del prodotto
     * @returns {Promise} Promise con il risultato dell'elaborazione
     */
    elaboraSingoloProdotto: function(productId) {
        return this.call({
            data: {
                action: 'classify_product',
                product_id: productId
            }
        });
    },
    
    /**
     * Resetta i tag di prodotti selezionati
     * @param {Array} productIds - Array di ID prodotti
     * @returns {Promise} Promise con il risultato del reset
     */
    resetTagProdotti: function(productIds) {
        return this.call({
            data: {
                action: 'reset_tags',
                product_ids: productIds
            }
        });
    },
    
    /**
     * Applica classificazione manuale ai prodotti
     * @param {Object} updateData - Dati da aggiornare
     * @returns {Promise} Promise con il risultato dell'aggiornamento
     */
    applicaClassificazioneManuale: function(updateData) {
        return this.call({
            data: updateData
        });
    },
    
    /**
     * Carica un singolo prodotto dal database
     * @param {string} productId - ID del prodotto
     * @returns {Promise} Promise con il prodotto
     */
    caricaSingoloProdotto: function(productId) {
        return this.call({
            data: {
                action: 'get_products',
                ids: [productId]
            }
        }).then(response => {
            if (response.data && response.data.length > 0) {
                return response.data[0];
            }
            return null;
        });
    }
};