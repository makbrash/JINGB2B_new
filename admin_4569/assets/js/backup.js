$(document).ready(function() {
    // Gestione form ripristino con conferma
    let currentForm = null;
    let confirmModal = null;
    
    // Inizializza il modal
    confirmModal = new bootstrap.Modal(document.getElementById('confirmRestoreModal'));
    
    $('.restore-form').on('submit', function(e) {
        e.preventDefault();
        currentForm = $(this);
        
        const backupName = $(this).data('backup-name');
        $('#backup-name-confirm').text(backupName);
        
        // Mostra modal di conferma
        confirmModal.show();
    });
    
    $('#confirmRestore').on('click', function() {
        if (currentForm) {
            confirmModal.hide();
            // Rimuovi l'event handler submit e invia il form
            currentForm[0].submit();
        }
    });
});