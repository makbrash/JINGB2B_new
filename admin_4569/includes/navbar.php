<?php
// Ottieni il percorso per il breadcrumb
function getBreadcrumb() {
    $currentPage = basename($_SERVER['PHP_SELF']);
    $breadcrumbs = [];
    
    // Aggiungi home
    $breadcrumbs[] = ['title' => 'Home', 'link' => BASE_URL . 'index.php', 'active' => false];
    
    // Determina breadcrumb basato sulla pagina corrente
    if ($currentPage === 'index.php') {
        $breadcrumbs[] = ['title' => 'Dashboard', 'link' => '', 'active' => true];
    } elseif (strpos($currentPage, 'users_') === 0) {
        $breadcrumbs[] = ['title' => 'Utenti', 'link' => BASE_URL . 'modules/users/list.php', 'active' => false];
        
        if ($currentPage === 'users_list.php') {
            $breadcrumbs[] = ['title' => 'Lista', 'link' => '', 'active' => true];
        } elseif ($currentPage === 'users_add.php') {
            $breadcrumbs[] = ['title' => 'Aggiungi', 'link' => '', 'active' => true];
        } elseif ($currentPage === 'users_groups.php') {
            $breadcrumbs[] = ['title' => 'Gruppi', 'link' => '', 'active' => true];
        }
    } elseif (strpos($currentPage, 'content_') === 0) {
        $breadcrumbs[] = ['title' => 'Contenuti', 'link' => BASE_URL . 'modules/content/articles.php', 'active' => false];
        
        if ($currentPage === 'content_articles.php') {
            $breadcrumbs[] = ['title' => 'Articoli', 'link' => '', 'active' => true];
        } elseif ($currentPage === 'content_pages.php') {
            $breadcrumbs[] = ['title' => 'Pagine', 'link' => '', 'active' => true];
        } elseif ($currentPage === 'content_gallery.php') {
            $breadcrumbs[] = ['title' => 'Media Gallery', 'link' => '', 'active' => true];
        }
    } elseif ($currentPage === 'media_upload.php') {
        $breadcrumbs[] = ['title' => 'Upload', 'link' => '', 'active' => true];
    } elseif (strpos($currentPage, 'settings') === 0) {
        $breadcrumbs[] = ['title' => 'Impostazioni', 'link' => '', 'active' => true];
    }
    
    return $breadcrumbs;
}

// Ottieni il breadcrumb corrente
$breadcrumbs = getBreadcrumb();
?>
<!-- Header in alto -->
<header class="navbar navbar-expand-md navbar-light d-none d-lg-flex d-print-none">
    <div class="container-xl">
        <!-- Pulsante collapse menu -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Ricerca -->
        <div class="navbar-nav flex-row order-md-last">
            <!-- Tema chiaro/scuro -->
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
                    <span class="badge bg-red text-white badge-pill position-absolute top-0 end-0"><?php echo $notificationCount; ?></span>
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
                        <div class="card-footer">
                            <a href="#" class="btn btn-outline-primary w-100">
                                Vedi tutte le notifiche
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Messaggi dropdown -->
            <div class="nav-item dropdown d-none d-md-flex me-3">
                <a href="#" class="nav-link px-0 position-relative" data-bs-toggle="dropdown" tabindex="-1" aria-label="Mostra messaggi">
                    <i class="ti ti-mail"></i>
                    <?php if ($messageCount > 0): ?>
                    <span class="badge bg-blue text-white badge-pill position-absolute top-0 end-0"><?php echo $messageCount; ?></span>
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
                        <div class="card-footer">
                            <a href="#" class="btn btn-outline-primary w-100">
                                Vedi tutti i messaggi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Menu utente -->
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Menu utente">
                    <span class="avatar avatar-sm" style="background-image: url(<?php echo $userAvatar; ?>)"></span>
                    <div class="d-none d-xl-block ps-2">
                        <div><?php echo $username; ?></div>
                        <div class="mt-1 small text-muted"><?php echo $userRole; ?></div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a href="<?php echo BASE_URL; ?>modules/users/profile.php" class="dropdown-item">Profilo</a>
                    <a href="<?php echo BASE_URL; ?>modules/settings/general.php" class="dropdown-item">Impostazioni</a>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo BASE_URL; ?>logout.php" class="dropdown-item">Logout</a>
                </div>
            </div>
        </div>
        
        <!-- Breadcrumb -->
        <div class="collapse navbar-collapse" id="navbar-menu">
            <div>
                <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                    <?php foreach ($breadcrumbs as $bc): ?>
                        <?php if ($bc['active']): ?>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo $bc['title']; ?></li>
                        <?php else: ?>
                            <li class="breadcrumb-item">
                                <a href="<?php echo $bc['link']; ?>"><?php echo $bc['title']; ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
    </div>
</header>

<!-- Contenitore principale inizio -->
<div class="page-wrapper"><?php
// La page-header.php e il contenuto saranno inclusi qui
?>