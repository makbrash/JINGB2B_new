<?php
// Imposta titolo pagina e descrizione
$pageTitle = "Ripristino Catalogo";
$pageSubtitle = "Gestione backup database prodotti";

// Includi file di configurazione e funzioni
require_once '../config/config.php';
require_once 'includes/functions.php';
require_once 'includes/backup/bk_prodotti.php';

// Verifica se è una richiesta AJAX per eliminare un backup
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'delete_backup' && !empty($_POST['backup_id'])) {
    header('Content-Type: application/json');
    $backupId = (int)$_POST['backup_id'];
    $result = deleteBackup($backupId);
    echo json_encode($result);
    exit;
}

// Verifica se è stata richiesta un'azione di ripristino
if (isset($_POST['restore_backup']) && !empty($_POST['backup_id'])) {
    $backupId = (int)$_POST['backup_id'];
    
    // Utilizza la nuova funzione restoreProductsFromBackup
    $result = restoreProductsFromBackup($backupId);
    
    if ($result['success']) {
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => $result['message']
        ];
    } else {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => "Errore durante il ripristino del catalogo: " . ($result['error_message'] ?? $result['message'])
        ];
    }
    
    // Reindirizza per evitare ricaricamenti del POST
    header("Location: ripristina_catalogo.php");
    exit;
}

// Verifica preventiva della compatibilità delle strutture delle tabelle
$structureCheck = checkTableStructures();
$hasStructureWarning = ($structureCheck !== true);

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
        <div id="ajax-message" class="alert d-none alert-dismissible" role="alert">
            <div class="d-flex">
                <div id="ajax-message-icon"></div>
                <div id="ajax-message-text"></div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
        
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

        <?php if ($hasStructureWarning): ?>
        <div class="alert alert-danger mb-3" role="alert">
            <h4 class="alert-title">Attenzione: Incompatibilità Rilevata!</h4>
            <p>
                È stata rilevata un'incompatibilità tra le strutture delle tabelle "prodotti" e "prodotti_backup_data":
            </p>
            <p><strong><?php echo $structureCheck; ?></strong></p>
            <p>
                È necessario risolvere questa incompatibilità prima di procedere con operazioni di backup o ripristino.
                Si prega di contattare l'amministratore di sistema.
            </p>
        </div>
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
                            <tr id="backup-row-<?php echo $backup['id']; ?>">
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
                                    <div class="btn-group">
                                        <form method="post" class="restore-form" data-backup-name="<?php echo htmlspecialchars($backup['backup_name']); ?>">
                                            <input type="hidden" name="backup_id" value="<?php echo $backup['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo md5(session_id() . time()); ?>">
                                            <button type="button" class="btn btn-sm btn-warning restore-button" <?php echo $hasStructureWarning ? 'disabled' : ''; ?>>
                                                <i class="ti ti-refresh me-1"></i>
                                                Ripristina
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-danger delete-backup-btn ms-2" 
                                                data-backup-id="<?php echo $backup['id']; ?>" 
                                                data-backup-name="<?php echo htmlspecialchars($backup['backup_name']); ?>">
                                            <i class="ti ti-trash me-1"></i>
                                            Elimina
                                        </button>
                                    </div>
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
                
                <p><strong>Nota importante sui codici EAN:</strong></p>
                <p>Il ripristino potrebbe fallire se nel backup sono presenti prodotti con EAN duplicati o vuoti, poiché la tabella prodotti richiede valori EAN univoci.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal per ripristino backup -->
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

<!-- Modal per eliminazione backup -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Conferma Eliminazione</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="ti ti-alert-triangle me-2"></i>
                    Sei sicuro di voler eliminare definitivamente il backup <strong id="delete-backup-name"></strong>?
                    <br>
                    Questa operazione non può essere annullata.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Elimina Backup</button>
            </div>
        </div>
    </div>
</div>


<?php
// Aggiungiamo script JavaScript direttamente nella pagina per assicurarci che funzioni
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Riferimento ai modal e pulsanti
    var confirmRestoreModal = new bootstrap.Modal(document.getElementById('confirmRestoreModal'));
    var confirmDeleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    var confirmRestoreButton = document.getElementById('confirmRestore');
    var confirmDeleteButton = document.getElementById('confirmDelete');
    var currentForm = null;
    var currentBackupId = null;
    
    // ---- GESTIONE RIPRISTINO ----
    
    // Aggiungi listener a tutti i pulsanti di ripristino
    document.querySelectorAll('.restore-button').forEach(function(button) {
        button.addEventListener('click', function() {
            // Ottieni il form e il nome del backup
            currentForm = this.closest('form');
            var backupName = currentForm.getAttribute('data-backup-name');
            
            // Aggiorna il testo nel modal
            document.getElementById('backup-name-confirm').textContent = backupName;
            
            // Mostra il modal
            confirmRestoreModal.show();
        });
    });
    
    // Quando si clicca su "Conferma Ripristino"
    confirmRestoreButton.addEventListener('click', function() {
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
            confirmRestoreModal.hide();
        }
    });
    
    // ---- GESTIONE ELIMINAZIONE ----
    
    // Funzione per mostrare messaggi AJAX
    function showAjaxMessage(message, type) {
        const alertElement = document.getElementById('ajax-message');
        const textElement = document.getElementById('ajax-message-text');
        const iconElement = document.getElementById('ajax-message-icon');
        
        alertElement.classList.remove('d-none', 'alert-success', 'alert-danger');
        alertElement.classList.add('alert-' + type);
        
        textElement.textContent = message;
        
        // Aggiunge l'icona appropriata
        if (type === 'success') {
            iconElement.innerHTML = '<i class="ti ti-check icon alert-icon me-2"></i>';
        } else {
            iconElement.innerHTML = '<i class="ti ti-alert-circle icon alert-icon me-2"></i>';
        }
        
        // Nasconde il messaggio dopo 5 secondi
        setTimeout(function() {
            alertElement.classList.add('d-none');
        }, 5000);
    }
    
    // Aggiungi listener ai pulsanti di eliminazione
    document.querySelectorAll('.delete-backup-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            currentBackupId = this.getAttribute('data-backup-id');
            var backupName = this.getAttribute('data-backup-name');
            
            // Aggiorna il testo nel modal di eliminazione
            document.getElementById('delete-backup-name').textContent = backupName;
            
            // Mostra il modal di eliminazione
            confirmDeleteModal.show();
        });
    });
    
    // Quando si clicca su "Elimina Backup"
    confirmDeleteButton.addEventListener('click', function() {
        if (currentBackupId) {
            // Prepara i dati per la richiesta AJAX
            var formData = new FormData();
            formData.append('ajax_action', 'delete_backup');
            formData.append('backup_id', currentBackupId);
            
            // Invia la richiesta AJAX
            fetch('ripristina_catalogo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Rimuovi la riga della tabella
                    document.getElementById('backup-row-' + currentBackupId).remove();
                    showAjaxMessage(data.message, 'success');
                    
                    // Se non ci sono più backup, aggiorna la visualizzazione
                    if (document.querySelectorAll('table tbody tr').length === 0) {
                        location.reload(); // Ricarica la pagina per mostrare il messaggio "nessun backup"
                    }
                } else {
                    showAjaxMessage(data.error_message || data.message, 'danger');
                }
                
                // Chiudi il modal
                confirmDeleteModal.hide();
            })
            .catch(error => {
                console.error('Errore:', error);
                showAjaxMessage('Si è verificato un errore durante l\'eliminazione del backup', 'danger');
                confirmDeleteModal.hide();
            });
        }
    });
});
</script>

<?php
require_once 'includes/footer.php';
?> 