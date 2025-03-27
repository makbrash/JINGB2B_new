/**
 * config.js - Configurazione del sistema di classificazione prodotti
 * 
 * Questo modulo contiene tutte le impostazioni di configurazione
 * dell'applicazione, centralizzate in un unico punto.
 */

const Config = {
    // Endpoint AJAX per le comunicazioni con il backend
    ajaxUrl: 'classificatore_ajax.php',
    
    // Dimensione del batch per l'elaborazione dei prodotti
    batchSize: 5,
    
    // Ritardo tra batch automatici (millisecondi)
    autoProcessDelay: 1000,
    
    // Timeout per le notifiche (millisecondi)
    notificationTimeout: 3000,
    
    // Limite predefinito di prodotti per pagina
    defaultProductsPerPage: 100,
    
    // Valore predefinito per il range di prodotti
    defaultRangeStart: 1,
    defaultRangeEnd: 100,
    
    // Classi CSS per i vari stati dei prodotti
    statusClasses: {
        pending: 'status-pending',
        done: 'status-done',
        error: 'status-error',
        unknown: 'status-unknown'
    },
    
    // Testi per gli stati dei prodotti
    statusText: {
        0: 'Da elaborare',
        1: 'Elaborato',
        2: 'Errore',
        default: 'Sconosciuto'
    },
    
    // Tipi di notifiche
    notificationTypes: {
        success: 'success',
        error: 'error',
        warning: 'warning',
        info: 'info'
    }
};