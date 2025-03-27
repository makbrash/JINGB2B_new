<?php
// Imposta valori predefiniti per il page header
$pageSubtitle = $pageSubtitle ?? 'Pannello amministrativo';
$showCreateButton = $showCreateButton ?? true;
$createButtonText = $createButtonText ?? 'Crea nuovo';
$createButtonIcon = $createButtonIcon ?? 'ti ti-plus';
$createButtonLink = $createButtonLink ?? '#';
$additionalButtons = $additionalButtons ?? [];
?>

<!-- Titolo pagina -->
<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <?php echo $pageTitle; ?>
                </h2>
                <div class="text-muted mt-1">
                    <span class="text-nowrap"><?php echo $pageSubtitle; ?></span>
                </div>
            </div>
            
            <?php if ($showCreateButton || !empty($additionalButtons)): ?>
            <!-- Pulsanti azione -->
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <?php foreach ($additionalButtons as $btn): ?>
                        <a href="<?php echo $btn['link']; ?>" class="btn <?php echo $btn['class'] ?? 'btn-outline-secondary'; ?> d-none d-sm-inline-block">
                            <?php if (!empty($btn['icon'])): ?>
                                <i class="<?php echo $btn['icon']; ?>"></i>
                            <?php endif; ?>
                            <?php echo $btn['text']; ?>
                        </a>
                    <?php endforeach; ?>
                    
                    <?php if ($showCreateButton): ?>
                        <a href="<?php echo $createButtonLink; ?>" class="btn btn-primary d-none d-sm-inline-block">
                            <i class="<?php echo $createButtonIcon; ?>"></i>
                            <?php echo $createButtonText; ?>
                        </a>
                        <a href="<?php echo $createButtonLink; ?>" class="btn btn-primary d-sm-none btn-icon">
                            <i class="<?php echo $createButtonIcon; ?>"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Inizio del corpo della pagina -->
<div class="page-body">
    <div class="container-xl">
        <?php
        // Il contenuto specifico della pagina sarà inserito qui
        ?>