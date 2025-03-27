/**
 * classificatore.js - File principale per il sistema di classificazione prodotti
 * 
 * Questo file si occupa dell'inizializzazione dell'applicazione, 
 * caricando tutti i moduli necessari e configurando gli eventi principali.
 */

// Assicuriamoci che il documento sia pronto prima di inizializzare
$(document).ready(function() {
    // Inizializza l'interfaccia utente
    UI.inizializza();
    
    // Carica i dati iniziali
    API.caricaStatistiche();
    API.caricaSelettori();
    
    // Inizializza la gestione dei filtri
    Filters.inizializza();
    
    // Inizializza la selezione multipla avanzata
    MultiSelect.inizializza();
    
    // Inizializza la modifica inline
    InlineEdit.inizializza();
    
    // Inizializza le statistiche switchabili
    Statistics.inizializzaTab();
    
    // Configura i form handler principali
    configuraPulsantiPrincipali();
    
    // Carica i prodotti iniziali
    API.caricaProdotti();
});

/**
 * Configura i pulsanti e form principali dell'applicazione
 */
function configuraPulsantiPrincipali() {
    // Form di ricerca
    $('#search-form').on('submit', function(e) {
        e.preventDefault();
        Products.cercaProdotti();
    });
    
    // Form range
    $('#range-form').on('submit', function(e) {
        e.preventDefault();
        Products.caricaProdottiRange();
    });
    
    // Pulsanti operazioni sui prodotti selezionati
    $('#btn-process-selected').on('click', Products.elaboraProdottiSelezionati);
    $('#btn-reset-selected').on('click', Products.resetTagProdottiSelezionati);
    $('#btn-select-all').on('click', MultiSelect.selezionaTutti);
    $('#btn-deselect-all').on('click', MultiSelect.deselezionaTutti);
    
    // Filtro per stato
    $('#stato-filter').on('change', function() {
        Products.filtraProdottiPerStato($(this).val());
    });
    
    // Pulsante per interrompere l'elaborazione
    $('#btn-stop-processing').on('click', Products.stoppaElaborazione);
    
    // Handler per i checkbox dei prodotti (delegazione eventi)
    $('#products-container').on('change', '.product-checkbox', function() {
        MultiSelect.gestisciCambioCheckbox($(this));
    });
    
    // Handler per processare un singolo prodotto
    $('#products-container').on('click', '.btn-process-product', function() {
        Products.elaboraSingoloProdotto($(this).data('id'));
    });
    
    // Handler per visualizzare i dettagli di un prodotto
    $('#products-container').on('click', '.product-title', function() {
        UI.mostraDettagliProdotto($(this).data('id'));
    });
    
    // Filtri categorie, sottocategorie, marche e limit
    $('#categoria-filter, #sottocategoria-filter, #marca-filter').on('change', function() {
        API.caricaProdotti();
    });
    
    $('#limit-selector').on('change', function() {
        State.productsPerPage = parseInt($(this).val());
        API.caricaProdotti();
    });
}