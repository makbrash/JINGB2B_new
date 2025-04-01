<?php
// Step 2: Mappatura Colonne
function renderMappingStep($preview_data = [], $suggested_mappings = []) {
    if (empty($preview_data)) {
        return;
    }
    ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Mappatura Colonne</h3>
        </div>
        <div class="card-body">
            <!-- Anteprima dati Excel -->
            <div class="mb-4">
                <h4>Anteprima Dati Excel</h4>
                <div class="table-responsive">
                    <table class="table table-vcenter table-bordered">
                        <thead>
                            <tr>
                                <?php 
                                $columns = count($preview_data[0]);
                                for ($i = 0; $i < $columns; $i++): 
                                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                                ?>
                                    <th>Colonna <?php echo $colLetter; ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview_data as $row): ?>
                                <tr>
                                    <?php foreach ($row as $cell): ?>
                                        <td><?php echo htmlspecialchars($cell); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <form action="process.php" method="post">
                <input type="hidden" name="step" value="process">

                <div class="row mb-4">
                    <div class="col-12">
                        <h4>Colonne Richieste</h4>
                    </div>
                    <div class="col-md-4">
                        <div class="form-label">Colonna EAN</div>
                        <select class="form-select" name="ean_column" required>
                            <?php foreach (range(1, $columns) as $col): 
                                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                                $selected = ($suggested_mappings['ean'] == $col) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $col; ?>" <?php echo $selected; ?>>
                                    Colonna <?php echo $colLetter; ?>
                                    <?php if (!empty($preview_data[0][$col - 1])): ?>
                                        - <?php echo htmlspecialchars($preview_data[0][$col - 1]); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="form-label">Colonna Titolo</div>
                        <select class="form-select" name="title_column" required>
                            <?php foreach (range(1, $columns) as $col): 
                                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                                $selected = ($suggested_mappings['titolo'] == $col) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $col; ?>" <?php echo $selected; ?>>
                                    Colonna <?php echo $colLetter; ?>
                                    <?php if (!empty($preview_data[0][$col - 1])): ?>
                                        - <?php echo htmlspecialchars($preview_data[0][$col - 1]); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="form-label">Colonna Prezzo</div>
                        <select class="form-select" name="price_column" required>
                            <?php foreach (range(1, $columns) as $col): 
                                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                                $selected = ($suggested_mappings['prezzo'] == $col) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $col; ?>" <?php echo $selected; ?>>
                                    Colonna <?php echo $colLetter; ?>
                                    <?php if (!empty($preview_data[0][$col - 1])): ?>
                                        - <?php echo htmlspecialchars($preview_data[0][$col - 1]); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="form-label">Righe da saltare</div>
                        <input type="number" class="form-control" name="skip_rows" value="1" min="0">
                        <div class="form-hint">Imposta a 0 se non ci sono intestazioni</div>
                    </div>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-refresh me-1"></i> Avvia Sincronizzazione
                    </button>
                    <a href="sincronizza_catalogo.php" class="btn btn-link">Annulla</a>
                </div>
            </form>
        </div>
    </div>
    <?php
}
?> 