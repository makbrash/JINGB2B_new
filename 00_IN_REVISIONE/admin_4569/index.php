<?php
/**
 * Dashboard - Pagina principale del pannello di amministrazione
 */

// Imposta titolo pagina e descrizione
$pageTitle = "Dashboard Admin";
$pageSubtitle = "Panoramica generale";

// Includi file di configurazione e funzioni
require_once 'config/config.php';
require_once 'includes/functions.php';

// Dati di esempio (in un'app reale otterresti questi dati dal database)
$stats = [
    'visits' => [
        'value' => 1352,
        'label' => 'Visite',
        'subtext' => 'Tasso di conversione',
        'change' => '+4.1%',
        'trend' => 'up',
        'progress' => 75,
        'color' => 'primary'
    ],
    'sales' => [
        'value' => '€ 4.752',
        'label' => 'Vendite',
        'subtext' => 'Rispetto ieri',
        'change' => '+12.2%',
        'trend' => 'up',
        'progress' => 60,
        'color' => 'success'
    ],
    'users' => [
        'value' => 135,
        'label' => 'Utenti attivi',
        'subtext' => 'Nuovi utenti',
        'change' => '-2.3%',
        'trend' => 'down',
        'progress' => 30,
        'color' => 'danger'
    ],
    'tickets' => [
        'value' => 24,
        'label' => 'Ticket supporto',
        'subtext' => 'Risolti oggi',
        'change' => '5',
        'trend' => 'check',
        'progress' => 45,
        'color' => 'warning'
    ]
];

// Dati utenti di esempio
$users = [
    [
        'id' => 1,
        'name' => 'Utente 1',
        'user_id' => '5674',
        'date' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'email' => 'user1@example.com',
        'status' => 'active'
    ],
    [
        'id' => 2,
        'name' => 'Utente 2',
        'user_id' => '5678',
        'date' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'email' => 'user2@example.com',
        'status' => 'blocked'
    ],
    [
        'id' => 3,
        'name' => 'Utente 3',
        'user_id' => '8921',
        'date' => date('Y-m-d H:i:s', strtotime('-3 days')),
        'email' => 'user3@example.com',
        'status' => 'pending'
    ],
    [
        'id' => 4,
        'name' => 'Utente 4',
        'user_id' => '1282',
        'date' => date('Y-m-d H:i:s', strtotime('-4 days')),
        'email' => 'user4@example.com',
        'status' => 'active'
    ],
    [
        'id' => 5,
        'name' => 'Utente 5',
        'user_id' => '6070',
        'date' => date('Y-m-d H:i:s', strtotime('-5 days')),
        'email' => 'user5@example.com',
        'status' => 'blocked'
    ]
];

// Impostazioni paginazione
$totalUsers = 25;
$currentPage = 1;
$perPage = 5;
$totalPages = ceil($totalUsers / $perPage);

// Include header e altri componenti dell'interfaccia
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/navbar.php';
require_once 'includes/page-header.php';
?>

<!-- Riepilogo statistiche -->
<div class="row row-deck row-cards">
    <?php foreach ($stats as $key => $stat): ?>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader"><?php echo $stat['label']; ?></div>
                </div>
                <div class="h1 mb-3"><?php echo $stat['value']; ?></div>
                <div class="d-flex mb-2">
                    <div><?php echo $stat['subtext']; ?></div>
                    <div class="ms-auto">
                        <span class="text-<?php echo $stat['trend'] === 'down' ? 'red' : 'green'; ?> d-inline-flex align-items-center lh-1">
                            <?php echo $stat['change']; ?>
                            <i class="ti ti-trending-<?php echo $stat['trend']; ?>"></i>
                        </span>
                    </div>
                </div>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-<?php echo $stat['color']; ?>" style="width: <?php echo $stat['progress']; ?>%" 
                         role="progressbar" aria-valuenow="<?php echo $stat['progress']; ?>" 
                         aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Contenuto tabella utenti -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ultimi utenti registrati</h3>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utente</th>
                            <th>Data registrazione</th>
                            <th>Email</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar me-2">U<?php echo $user['id']; ?></span>
                                    <div>
                                        <div><?php echo $user['name']; ?></div>
                                        <div class="text-muted">ID: <?php echo $user['user_id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo formatDate($user['date'], true); ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td>
                                <?php echo getStatusBadge($user['status']); ?>
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="#" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-edit"></i>
                                        Modifica
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-danger">
                                        <i class="ti ti-trash"></i>
                                        Elimina
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Mostrando <span><?php echo ($currentPage - 1) * $perPage + 1; ?></span> 
                    a <span><?php echo min($currentPage * $perPage, $totalUsers); ?></span> 
                    di <span><?php echo $totalUsers; ?></span> elementi
                </p>
                <ul class="pagination m-0 ms-auto">
                    <li class="page-item <?php echo ($currentPage == 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>" tabindex="-1" aria-disabled="<?php echo ($currentPage == 1) ? 'true' : 'false'; ?>">
                            <i class="ti ti-chevron-left"></i>
                            prev
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($currentPage == $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>">
                            next
                            <i class="ti ti-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
// Script specifici per la dashboard
$pageScripts = [
    'assets/js/dashboard.js'
];

// Includi il footer
require_once 'includes/footer.php';
?>