<?php
// Configurazioni di base
$pageTitle = "Dashboard Admin";
$username = "Marco Vitaletti";
$userAvatar = "https://www.gravatar.com/avatar/" . md5(strtolower(trim("marco@example.com"))) . "?s=200&d=mp";
$notificationCount = 5;
$messageCount = 3;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    
    <!-- CSS files via CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.1.1/dist/css/tabler.min.css" />-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">    
    <!-- Custom CSS -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>


<style>
  :root {
    --tblr-font-sans-serif: "Inter";
  }
</style>
</head>
<body data-bs-theme="dark">
<div class="page">
    <!-- Sidebar / Menu laterale -->
    <aside class="navbar navbar-vertical navbar-expand-sm" data-bs-theme="dark">
        <div class="container-fluid">
            <!-- Logo in alto -->
            <h1 class="navbar-brand navbar-brand-autodark">
                <a href=".">
                    <img src="https://preview.tabler.io/static/logo-white.svg" width="110" height="32" alt="Logo" class="navbar-brand-image">
                </a>
            </h1>
            
            <!-- Toggle per mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Menu principale -->
            <div class="collapse navbar-collapse" id="sidebar-menu">
                <ul class="navbar-nav pt-lg-3">
                    <!-- Dashboard -->
                    <li class="nav-item active">
                        <a class="nav-link" href="#">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-home"></i>
                            </span>
                            <span class="nav-link-title">Dashboard</span>
                        </a>
                    </li>
                    
                    <!-- Menu Utenti con sottomenu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#navbar-base" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-users"></i>
                            </span>
                            <span class="nav-link-title">Utenti</span>
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#">
                                Lista Utenti
                            </a>
                            <a class="dropdown-item" href="#">
                                Aggiungi Utente
                            </a>
                            <a class="dropdown-item" href="#">
                                Gruppi e Permessi
                            </a>
                        </div>
                    </li>
                    
                    <!-- Menu Contenuti con sottomenu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#navbar-base" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-article"></i>
                            </span>
                            <span class="nav-link-title">Contenuti</span>
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#">
                                Articoli
                            </a>
                            <a class="dropdown-item" href="#">
                                Pagine
                            </a>
                            <a class="dropdown-item" href="#">
                                Media Gallery
                            </a>
                        </div>
                    </li>
                    
                    <!-- Modulo Upload -->
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-upload"></i>
                            </span>
                            <span class="nav-link-title">Upload</span>
                        </a>
                    </li>
                    
                    <!-- Impostazioni -->
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-settings"></i>
                            </span>
                            <span class="nav-link-title">Impostazioni</span>
                        </a>
                    </li>
                    
                    <!-- Separatore -->
                    <li class="nav-item mt-auto">
                        <a class="nav-link" href="#">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-logout"></i>
                            </span>
                            <span class="nav-link-title">Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </aside>
    
    <!-- Contenuto principale -->
    <div class="page-wrapper">
        <!-- Header in alto -->
        <header class="navbar navbar-expand-md navbar-light d-none d-lg-flex d-print-none">
            <div class="container-xl">
                <!-- Pulsante collapse menu -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <!-- Ricerca -->
                <div class="navbar-nav flex-row order-md-last">
                    <div class="d-none d-md-flex me-3">
                        <a href="?theme=dark" class="nav-link px-0 hide-theme-dark" title="Attiva dark mode" data-bs-toggle="tooltip" data-bs-placement="bottom">
                            <i class="ti ti-moon"></i>
                        </a>
                        <a href="?theme=light" class="nav-link px-0 hide-theme-light" title="Attiva light mode" data-bs-toggle="tooltip" data-bs-placement="bottom">
                            <i class="ti ti-sun"></i>
                        </a>
                    </div>
                    
                    <!-- Notifiche dropdown -->
                    <div class="nav-item dropdown d-none d-md-flex me-3">
                        <a href="#" class="nav-link px-0 position-relative" data-bs-toggle="dropdown" tabindex="-1" aria-label="Mostra notifiche">
                            <i class="ti ti-bell"></i>
                            <?php if ($notificationCount > 0): ?>
                            <span class="badge bg-red badge-pill position-absolute top-0 end-0"><?php echo $notificationCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-card">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Notifiche</h3>
                                </div>
                                <div class="list-group list-group-flush list-group-hoverable">
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto"><span class="status-dot status-dot-animated bg-red d-block"></span></div>
                                            <div class="col text-truncate">
                                                <a href="#" class="text-body d-block">Nuovo utente registrato</a>
                                                <div class="d-block text-muted text-truncate mt-n1">Registrato 10 minuti fa</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto"><span class="status-dot bg-green d-block"></span></div>
                                            <div class="col text-truncate">
                                                <a href="#" class="text-body d-block">Backup completato</a>
                                                <div class="d-block text-muted text-truncate mt-n1">Completato con successo</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Messaggi dropdown -->
                    <div class="nav-item dropdown d-none d-md-flex me-3">
                        <a href="#" class="nav-link px-0 position-relative" data-bs-toggle="dropdown" tabindex="-1" aria-label="Mostra messaggi">
                            <i class="ti ti-mail"></i>
                            <?php if ($messageCount > 0): ?>
                            <span class="badge bg-blue badge-pill position-absolute top-0 end-0"><?php echo $messageCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-card">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Messaggi</h3>
                                </div>
                                <div class="list-group list-group-flush list-group-hoverable">
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="avatar avatar-sm">CL</span>
                                            </div>
                                            <div class="col text-truncate">
                                                <a href="#" class="text-body d-block">Cliente Rossi</a>
                                                <div class="d-block text-muted text-truncate mt-n1">Ho bisogno di supporto...</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Menu utente -->
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Menu utente">
                            <span class="avatar avatar-sm avatar-custom" style="background-image: url(<?php echo $userAvatar; ?>)"></span>
                            <div class="d-none d-xl-block ps-2">
                                <div><?php echo $username; ?></div>
                                <div class="mt-1 small text-muted">Amministratore</div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="#" class="dropdown-item">Profilo</a>
                            <a href="#" class="dropdown-item">Impostazioni</a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item">Logout</a>
                        </div>
                    </div>
                </div>
                
                <!-- Breadcrumb -->
                <div class="collapse navbar-collapse" id="navbar-menu">
                    <div>
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Corpo della pagina -->
        <div class="page-body">
            <div class="container-xl">
                <!-- Titolo pagina -->
                <div class="page-header d-print-none">
                    <div class="row align-items-center">
                        <div class="col">
                            <h2 class="page-title">
                                <?php echo $pageTitle; ?>
                            </h2>
                            <div class="text-muted mt-1">
                                <span class="text-nowrap">Pannello amministrativo</span>
                            </div>
                        </div>
                        <!-- Pulsanti azione -->
                        <div class="col-auto ms-auto d-print-none">
                            <div class="btn-list">
                                <a href="#" class="btn btn-primary d-none d-sm-inline-block">
                                    <i class="ti ti-plus"></i>
                                    Crea nuovo
                                </a>
                                <a href="#" class="btn btn-primary d-sm-none btn-icon">
                                    <i class="ti ti-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Riepilogo statistiche -->
                <div class="row row-deck row-cards">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Visite</div>
                                </div>
                                <div class="h1 mb-3">1.352</div>
                                <div class="d-flex mb-2">
                                    <div>Tasso di conversione</div>
                                    <div class="ms-auto">
                                        <span class="text-green d-inline-flex align-items-center lh-1">
                                            +4.1% <i class="ti ti-trending-up"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-primary" style="width: 75%" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Vendite</div>
                                </div>
                                <div class="h1 mb-3">€ 4.752</div>
                                <div class="d-flex mb-2">
                                    <div>Rispetto ieri</div>
                                    <div class="ms-auto">
                                        <span class="text-green d-inline-flex align-items-center lh-1">
                                            +12.2% <i class="ti ti-trending-up"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-success" style="width: 60%" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Utenti attivi</div>
                                </div>
                                <div class="h1 mb-3">135</div>
                                <div class="d-flex mb-2">
                                    <div>Nuovi utenti</div>
                                    <div class="ms-auto">
                                        <span class="text-red d-inline-flex align-items-center lh-1">
                                            -2.3% <i class="ti ti-trending-down"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-danger" style="width: 30%" role="progressbar" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Ticket supporto</div>
                                </div>
                                <div class="h1 mb-3">24</div>
                                <div class="d-flex mb-2">
                                    <div>Risolti oggi</div>
                                    <div class="ms-auto">
                                        <span class="text-green d-inline-flex align-items-center lh-1">
                                            5 <i class="ti ti-check"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-warning" style="width: 45%" role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contenuto tabella esempio -->
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
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <tr>
                                            <td><?php echo $i; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="avatar me-2">U<?php echo $i; ?></span>
                                                    <div>
                                                        <div>Utente <?php echo $i; ?></div>
                                                        <div class="text-muted">ID: <?php echo rand(1000, 9999); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime("-{$i} days")); ?></td>
                                            <td>user<?php echo $i; ?>@example.com</td>
                                            <td>
                                                <?php if ($i % 3 == 0): ?>
                                                <span class="badge bg-warning">In attesa</span>
                                                <?php elseif ($i % 3 == 1): ?>
                                                <span class="badge bg-success">Attivo</span>
                                                <?php else: ?>
                                                <span class="badge bg-danger">Bloccato</span>
                                                <?php endif; ?>
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
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer d-flex align-items-center">
                                <p class="m-0 text-muted">Mostrando <span>1</span> a <span>5</span> di <span>25</span> elementi</p>
                                <ul class="pagination m-0 ms-auto">
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">
                                            <!-- Download SVG icon from http://tabler-icons.io/i/chevron-left -->
                                            <i class="ti ti-chevron-left"></i>
                                            prev
                                        </a>
                                    </li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                    <li class="page-item"><a class="page-link" href="#">4</a></li>
                                    <li class="page-item"><a class="page-link" href="#">5</a></li>
                                    <li class="page-item">
                                        <a class="page-link" href="#">
                                            next
                                            <i class="ti ti-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="footer footer-transparent d-print-none">
            <div class="container-xl">
                <div class="row text-center align-items-center flex-row-reverse">
                    <div class="col-lg-auto ms-lg-auto">
                        <ul class="list-inline list-inline-dots mb-0">
                            <li class="list-inline-item"><a href="#" class="link-secondary">Documentazione</a></li>
                            <li class="list-inline-item"><a href="#" class="link-secondary">Supporto</a></li>
                        </ul>
                    </div>
                    <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                        <ul class="list-inline list-inline-dots mb-0">
                            <li class="list-inline-item">
                                Copyright &copy; <?php echo date('Y'); ?>
                                <a href="." class="link-secondary">Marco Vitaletti Admin</a>.
                                Tutti i diritti riservati.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- JavaScript files -->
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.1.1/dist/js/tabler.min.js"></script>

</body>
</html>