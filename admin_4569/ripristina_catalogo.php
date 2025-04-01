<?php
// Imposta titolo pagina e descrizione
$pageTitle = "Ripristino Catalogo";
$pageSubtitle = "Gestione backup database prodotti";

// Includi file di configurazione e funzioni
require_once '../config/config.php';
require_once 'includes/functions.php';
require_once 'includes/backup/bk_prodotti.php';

// Verifica se è stata richiesta un'azione di ripristino
if (isset($_POST['restore_backup']) && !empty($_POST['backup_id'])) {
    $backupId = (int)$_POST['backup_id'];
    $success = restoreBackup($backupId);
    
    if ($success) {
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => "Catalogo ripristinato con successo al backup selezionato."
        ];
    } else {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => "Errore durante il ripristino del catalogo."
        ];
    }
    
    // Reindirizza per evitare ricaricamenti del POST
    header("Location: ripristina_catalogo.php");
    exit;
}

// Ottieni l'elenco dei backup disponibili
$backups = getAvailableBackups();


// Include i componenti dell'interfaccia
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/navbar.php';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <?php echo $pageTitle; ?>
                </h2>
                <div class="text-muted mt-1"><?php echo $pageSubtitle; ?></div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message']['type']; ?> alert-dismissible" role="alert">
            <div class="d-flex">
                <div>
                    <?php if ($_SESSION['message']['type'] == 'success'): ?>
                    <i class="ti ti-check icon alert-icon"></i>
                    <?php else: ?>
                    <i class="ti ti-alert-circle icon alert-icon"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <?php echo $_SESSION['message']['text']; ?>
                </div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
        <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Backup Disponibili</h3>
                <div class="card-actions">
                    <a href="sincronizza_catalogo.php" class="btn btn-primary">
                        <i class="ti ti-refresh me-1"></i>
                        Nuova Sincronizzazione
                    </a>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Prima di ogni sincronizzazione, viene creato automaticamente un backup completo del catalogo. 
                    Usa questa pagina per ripristinare il catalogo ad uno stato precedente in caso di problemi.
                </p>
                
                <?php if (empty($backups)): ?>
                <div class="alert alert-info">
                    <i class="ti ti-info-circle me-2"></i>
                    Nessun backup disponibile. I backup vengono creati automaticamente quando sincronizzi il catalogo.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Nome Backup</th>
                                <th>Data e ora</th>
                                <th>Note</th>
                                <th>Stato</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($backup['backup_name']); ?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($backup['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($backup['note']); ?></td>
                                <td>
                                    <?php if ($backup['is_restored']): ?>
                                    <span class="badge bg-success">
                                        <i class="ti ti-check me-1"></i>
                                        Ripristinato il <?php echo date('d/m/Y H:i:s', strtotime($backup['restored_at'])); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-muted">
                                        <i class="ti ti-archive me-1"></i>
                                        Disponibile
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" class="restore-form" data-backup-name="<?php echo htmlspecialchars($backup['backup_name']); ?>">
                                        <input type="hidden" name="backup_id" value="<?php echo $backup['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo md5(session_id() . time()); ?>">
                                        <button type="button" class="btn btn-sm btn-warning restore-button">
                                            <i class="ti ti-refresh me-1"></i>
                                            Ripristina
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">Informazioni sul Ripristino</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <div class="d-flex">
                        <div>
                            <i class="ti ti-alert-triangle icon alert-icon"></i>
                        </div>
                        <div>
                            <h4 class="alert-title">Attenzione</h4>
                            <div class="text-secondary">
                                Il ripristino sovrascriverà completamente l'attuale catalogo prodotti. 
                                Questa operazione non può essere annullata. Assicurati di selezionare il backup corretto.
                            </div>
                        </div>
                    </div>
                </div>
                
                <p><strong>Durante il ripristino:</strong></p>
                <ul>
                    <li>Tutti i prodotti attuali verranno rimossi</li>
                    <li>I prodotti presenti nel backup saranno ripristinati con tutti i loro attributi (titolo, prezzo, stato, ecc.)</li>
                    <li>Le modifiche apportate dopo la data del backup andranno perse</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmRestoreModal" tabindex="-1" aria-labelledby="confirmRestoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmRestoreModalLabel">Conferma Ripristino</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="ti ti-alert-circle me-2"></i>
                    Sei sicuro di voler ripristinare il catalogo al backup <strong id="backup-name-confirm"></strong>?
                    <br>
                    Questa operazione sovrascriverà tutte le modifiche apportate dopo la data del backup.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-danger" id="confirmRestore">Conferma Ripristino</button>
            </div>
        </div>
    </div>
</div>


<?php

$pageScripts = [
    'assets/js/backup.js'
];

// Aggiungiamo script JavaScript direttamente nella pagina per assicurarci che funzioni
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Riferimento al modal e al pulsante di conferma
    var confirmModal = new bootstrap.Modal(document.getElementById('confirmRestoreModal'));
    var confirmButton = document.getElementById('confirmRestore');
    var currentForm = null;
    
    // Aggiungi listener a tutti i pulsanti di ripristino
    document.querySelectorAll('.restore-button').forEach(function(button) {
        button.addEventListener('click', function() {
            // Ottieni il form e il nome del backup
            currentForm = this.closest('form');
            var backupName = currentForm.getAttribute('data-backup-name');
            
            // Aggiorna il testo nel modal
            document.getElementById('backup-name-confirm').textContent = backupName;
            
            // Mostra il modal
            confirmModal.show();
        });
    });
    
    // Quando si clicca su "Conferma Ripristino"
    confirmButton.addEventListener('click', function() {
        if (currentForm) {
            // Aggiungi l'input hidden per il restore_backup
            var restoreInput = document.createElement('input');
            restoreInput.type = 'hidden';
            restoreInput.name = 'restore_backup';
            restoreInput.value = '1';
            currentForm.appendChild(restoreInput);
            
            // Invia il form
            currentForm.submit();
            
            // Nascondi il modal
            confirmModal.hide();
        }
    });
});
</script>

<?php
require_once 'includes/footer.php';
?> 