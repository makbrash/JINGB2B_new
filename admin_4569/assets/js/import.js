/**
 * Script di supporto per la pagina di importazione JSON
 */

// Funzioni di utility per formattare i numeri
function formatNumber(num) {
    return new Intl.NumberFormat('it-IT').format(num);
}

// Funzione per formattare i prezzi
function formatPrice(price) {
    return new Intl.NumberFormat('it-IT', {
        style: 'currency',
        currency: 'EUR'
    }).format(price);
}

// Funzione per formattare le dimensioni dei file
function formatFileSize(bytes) {
    if (bytes < 1024) {
        return bytes + ' bytes';
    } else if (bytes < 1024 * 1024) {
        return (bytes / 1024).toFixed(2) + ' KB';
    } else {
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }
}

// Aggiunge tooltip ai bottoni della pagina
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza i tooltip se Tabler supporta questa funzionalità
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Inizializza i tab se necessario
    if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
        const tabElList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tab"]'));
        tabElList.map(function (tabEl) {
            return new bootstrap.Tab(tabEl);
        });
    }
}); 