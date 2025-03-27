/**
 * state.js - Gestione dello stato dell'applicazione
 * 
 * Questo modulo centralizza la gestione dello stato dell'applicazione,
 * fornendo un'unica fonte di verità per tutti i moduli.
 */

const State = {
    // Flag di elaborazione
    isProcessing: false,
    stopRequested: false,
    
    // Code e contatori per l'elaborazione batch
    processingQueue: [],
    totalProcessed: 0,
    successCount: 0,
    errorCount: 0,
    
    // Selezione prodotti
    selectedProductIds: [],
    lastChecked: null, // Per la selezione con SHIFT
    
    // Navigazione e filtri
    currentPage: 1,
    productsPerPage: Config.defaultProductsPerPage,
    filterState: 'all', // 'all', 'pending', 'done', 'error'
    lastSearchQuery: '',
    
    // Cache dati
    productsList: [],
    
    /**
     * Aggiunge un ID prodotto alla selezione se non presente
     * @param {string} productId - ID del prodotto da aggiungere
     */
    addSelectedProduct: function(productId) {
        if (!this.selectedProductIds.includes(productId)) {
            this.selectedProductIds.push(productId);
        }
    },
    
    /**
     * Rimuove un ID prodotto dalla selezione
     * @param {string} productId - ID del prodotto da rimuovere
     */
    removeSelectedProduct: function(productId) {
        const index = this.selectedProductIds.indexOf(productId);
        if (index !== -1) {
            this.selectedProductIds.splice(index, 1);
        }
    },
    
    /**
     * Resetta lo stato dell'elaborazione
     */
    resetProcessingState: function() {
        this.isProcessing = false;
        this.processingQueue = [];
        this.stopRequested = false;
    },
    
    /**
     * Prepara la coda per l'elaborazione batch
     * @param {Array} productIds - Array di ID prodotti da elaborare
     */
    setupProcessingQueue: function(productIds) {
        this.processingQueue = [...productIds];
        this.totalProcessed = 0;
        this.successCount = 0;
        this.errorCount = 0;
        this.stopRequested = false;
        this.isProcessing = true;
    },
    
    /**
     * Resetta la selezione dei prodotti
     */
    resetSelection: function() {
        this.selectedProductIds = [];
        this.lastChecked = null;
    },
    
    /**
     * Aggiorna i contatori di elaborazione
     * @param {Object} result - Risultato dell'elaborazione
     */
    updateProcessingCounters: function(result) {
        this.totalProcessed += result.totalProcessed || 0;
        this.successCount += result.successo || 0;
        this.errorCount += result.errore || 0;
    },
    
    /**
     * Trova un prodotto nella lista
     * @param {string} productId - ID del prodotto da trovare
     * @return {Object|null} Il prodotto trovato o null
     */
    findProduct: function(productId) {
        return this.productsList.find(p => p.id == productId) || null;
    }
};