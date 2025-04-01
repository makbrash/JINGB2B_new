<?php
// Step 1: Upload File
function renderUploadStep() {
    ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Caricamento File Excel</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <div class="d-flex">
                    <div>
                        <i class="ti ti-info-circle icon alert-icon"></i>
                    </div>
                    <div>
                        <h4 class="alert-title">Istruzioni</h4>
                        <div class="text-secondary">
                            <p>Carica un file Excel (.xls o .xlsx) contenente i dati dei prodotti.</p>
                            <p>Il file deve contenere almeno le colonne per <strong>EAN</strong>, <strong>Titolo</strong> e <strong>Prezzo</strong>.</p>
                            <p>Nella prossima fase potrai specificare quali colonne contengono questi dati.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <form action="process.php" method="post" enctype="multipart/form-data" class="mt-4">
                <input type="hidden" name="step" value="upload">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <div class="form-label">File Excel (.xls, .xlsx)</div>
                        <input type="file" class="form-control" name="excelFile" accept=".xls,.xlsx" required>
                        <div class="form-hint">Seleziona un file Excel contenente i dati dei prodotti</div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end mb-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-upload me-1"></i> Carica File
                        </button>
                    </div>
                </div>
            </form>

            <!-- Debug info -->
            <div class="mt-3">
                <?php if (isset($_SESSION['debug'])): ?>
                    <div class="alert alert-info">
                        <pre><?php print_r($_SESSION['debug']); ?></pre>
                    </div>
                    <?php unset($_SESSION['debug']); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
?> 