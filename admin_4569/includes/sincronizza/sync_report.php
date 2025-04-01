<?php
// Step 3: Report
function renderReportStep($results = null) {
    if (!$results) {
        return;
    }

    // Verifica sicura dei valori nell'array results
    if (!isset($results['totals'])) $results['totals'] = [];
    if (!isset($results['backup_file'])) $results['backup_file'] = 'backup_non_disponibile.xlsx';
    
    // Verifica valori nei totali
    $totals = [
        'total_rows' => isset($results['totals']['total_rows']) ? $results['totals']['total_rows'] : 0,
        'processed_rows' => isset($results['totals']['processed_rows']) ? $results['totals']['processed_rows'] : 0,
        'disabled' => isset($results['totals']['disabled']) ? $results['totals']['disabled'] : 0,
        'price_updated' => isset($results['totals']['price_updated']) ? $results['totals']['price_updated'] : 0,
        'title_updated' => isset($results['totals']['title_updated']) ? $results['totals']['title_updated'] : 0,
        'new_added' => isset($results['totals']['new_added']) ? $results['totals']['new_added'] : 0,
        'unchanged' => isset($results['totals']['unchanged']) ? $results['totals']['unchanged'] : 0,
        'reactivated' => isset($results['totals']['reactivated']) ? $results['totals']['reactivated'] : 0
    ];
    
    // Sovrascrive l'array originale con quello sicuro
    $results['totals'] = $totals;
    
    // Verifica che le liste di prodotti esistano
    if (!isset($results['disabled'])) $results['disabled'] = [];
    if (!isset($results['price_updated'])) $results['price_updated'] = [];
    if (!isset($results['title_updated'])) $results['title_updated'] = [];
    if (!isset($results['new_added'])) $results['new_added'] = [];
    if (!isset($results['unchanged'])) $results['unchanged'] = [];
    if (!isset($results['reactivated'])) $results['reactivated'] = [];
    if (!isset($results['skip_count'])) $results['skip_count'] = 0;
    ?>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Report Sincronizzazione</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-success">
                <div class="d-flex">
                    <div>
                        <i class="ti ti-check icon alert-icon"></i>
                    </div>
                    <div>
                        <h4 class="alert-title">Sincronizzazione completata con successo!</h4>
                        <div class="text-secondary">
                            File elaborato: <strong><?php echo htmlspecialchars($_SESSION['excel_file_name'] ?? ''); ?></strong><br>
                            File di backup: <strong><?php echo htmlspecialchars($results['backup_file']); ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4 mb-4">
                <div class="col-md-6">
                    <h4>Riepilogo Operazioni:</h4>
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Righe totali nel file</div>
                            <div class="datagrid-content"><?php echo $results['totals']['total_rows']; ?></div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Righe saltate (intestazioni)</div>
                            <div class="datagrid-content"><?php echo $results['skip_count']; ?></div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Righe elaborate</div>
                            <div class="datagrid-content"><?php echo $results['totals']['processed_rows']; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h4>Dettaglio modifiche:</h4>
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Prodotti disattivati</div>
                            <div class="datagrid-content">
                                <span class="status status-red">
                                    <?php echo $results['totals']['disabled']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Prodotti con prezzo aggiornato</div>
                            <div class="datagrid-content">
                                <span class="status status-yellow">
                                    <?php echo $results['totals']['price_updated']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Prodotti con titolo aggiornato</div>
                            <div class="datagrid-content">
                                <span class="status status-blue">
                                    <?php echo $results['totals']['title_updated']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Nuovi prodotti aggiunti</div>
                            <div class="datagrid-content">
                                <span class="status status-green">
                                    <?php echo $results['totals']['new_added']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Prodotti riattivati</div>
                            <div class="datagrid-content">
                                <span class="status status-azure">
                                    <?php echo $results['totals']['reactivated']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Prodotti invariati</div>
                            <div class="datagrid-content">
                                <span class="status status-gray">
                                    <?php echo $results['totals']['unchanged']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($results['totals']['disabled'] > 0): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title text-danger">
                            <i class="ti ti-x-circle me-1"></i>
                            Prodotti disattivati (<?php echo $results['totals']['disabled']; ?>)
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>EAN</th>
                                        <th>Titolo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results['disabled'] as $product): ?>
                                        <tr>
                                            <td><?php echo $product['id']; ?></td>
                                            <td><?php echo htmlspecialchars($product['ean']); ?></td>
                                            <td><?php echo htmlspecialchars($product['titolo']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($results['totals']['price_updated'] > 0): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title text-warning">
                            <i class="ti ti-currency-euro me-1"></i>
                            Prodotti con prezzo aggiornato (<?php echo $results['totals']['price_updated']; ?>)
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>EAN</th>
                                        <th>Titolo</th>
                                        <th>Prezzo vecchio</th>
                                        <th>Prezzo nuovo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results['price_updated'] as $product): ?>
                                        <tr>
                                            <td><?php echo $product['id']; ?></td>
                                            <td><?php echo htmlspecialchars($product['ean']); ?></td>
                                            <td><?php echo htmlspecialchars($product['titolo']); ?></td>
                                            <td class="text-danger"><?php echo $product['prezzo_vecchio']; ?> €</td>
                                            <td class="text-success"><?php echo $product['prezzo_nuovo']; ?> €</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($results['totals']['new_added'] > 0): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title text-success">
                            <i class="ti ti-plus-circle me-1"></i>
                            Nuovi prodotti aggiunti (<?php echo $results['totals']['new_added']; ?>)
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>EAN</th>
                                        <th>Titolo</th>
                                        <th>Prezzo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results['new_added'] as $product): ?>
                                        <tr>
                                            <td><?php echo $product['id']; ?></td>
                                            <td><?php echo htmlspecialchars($product['ean']); ?></td>
                                            <td><?php echo htmlspecialchars($product['titolo']); ?></td>
                                            <td><?php echo $product['prezzo']; ?> €</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Pulsanti di navigazione -->
            <div class="card-footer">
                <div class="d-flex">
                    <a href="sincronizza_catalogo.php" class="btn btn-primary">
                        <i class="ti ti-home me-1"></i> Torna alla Home
                    </a>
                    <a href="sincronizza_catalogo.php" class="btn btn-outline-success ms-auto">
                        <i class="ti ti-upload me-1"></i> Nuova Importazione
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?> 