<?php
// Funzione per determinare se il menu è attivo
function isActiveMenu($page) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    if ($page === $currentPage) {
        return "active";
    }
    return "";
}

// Funzione per determinare se il dropdown è espanso
function isExpandedDropdown($pages) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    foreach ($pages as $page) {
        if ($page === $currentPage) {
            return "show";
        }
    }
    return "";
}
?>
<!-- Sidebar / Menu laterale -->
<aside class="navbar navbar-vertical navbar-expand-sm" data-bs-theme="dark">
    <div class="container-fluid">
        <!-- Logo in alto -->
        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="index.php">
                <img src="<?php echo BASE_URL; ?>assets/images/logo-white.svg" width="110" height="32" alt="Logo" class="navbar-brand-image">
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
                <li class="nav-item <?php echo isActiveMenu('index.php'); ?>">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>index.php">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-home"></i>
                        </span>
                        <span class="nav-link-title">Dashboard</span>
                    </a>
                </li>
                
                <!-- Menu Utenti con sottomenu -->
                <li class="nav-item dropdown <?php echo isExpandedDropdown(['users_list.php', 'users_add.php', 'users_groups.php']); ?>">
                    <a class="nav-link dropdown-toggle" href="#navbar-users" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-users"></i>
                        </span>
                        <span class="nav-link-title">Utenti</span>
                    </a>
                    <div class="dropdown-menu <?php echo isExpandedDropdown(['users_list.php', 'users_add.php', 'users_groups.php']); ?>">
                        <a class="dropdown-item <?php echo isActiveMenu('users_list.php'); ?>" href="<?php echo BASE_URL; ?>modules/users/list.php">
                            Lista Utenti
                        </a>
                        <a class="dropdown-item <?php echo isActiveMenu('users_add.php'); ?>" href="<?php echo BASE_URL; ?>modules/users/add.php">
                            Aggiungi Utente
                        </a>
                        <a class="dropdown-item <?php echo isActiveMenu('users_groups.php'); ?>" href="<?php echo BASE_URL; ?>modules/users/groups.php">
                            Gruppi e Permessi
                        </a>
                    </div>
                </li>
                
                <!-- Menu Contenuti con sottomenu -->
                <li class="nav-item dropdown <?php echo isExpandedDropdown(['content_articles.php', 'content_pages.php', 'content_gallery.php']); ?>">
                    <a class="nav-link dropdown-toggle" href="#navbar-content" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-article"></i>
                        </span>
                        <span class="nav-link-title">Contenuti</span>
                    </a>
                    <div class="dropdown-menu <?php echo isExpandedDropdown(['content_articles.php', 'content_pages.php', 'content_gallery.php']); ?>">
                        <a class="dropdown-item <?php echo isActiveMenu('content_articles.php'); ?>" href="<?php echo BASE_URL; ?>modules/content/articles.php">
                            Articoli
                        </a>
                        <a class="dropdown-item <?php echo isActiveMenu('content_pages.php'); ?>" href="<?php echo BASE_URL; ?>modules/content/pages.php">
                            Pagine
                        </a>
                        <a class="dropdown-item <?php echo isActiveMenu('content_gallery.php'); ?>" href="<?php echo BASE_URL; ?>modules/content/gallery.php">
                            Media Gallery
                        </a>
                    </div>
                </li>
                
                <!-- Modulo Upload -->
                <li class="nav-item <?php echo isActiveMenu('media_upload.php'); ?>">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>modules/media/upload.php">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-upload"></i>
                        </span>
                        <span class="nav-link-title">Upload</span>
                    </a>
                </li>
                
                <!-- Impostazioni -->
                <li class="nav-item <?php echo isActiveMenu('settings.php'); ?>">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>modules/settings/general.php">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-settings"></i>
                        </span>
                        <span class="nav-link-title">Impostazioni</span>
                    </a>
                </li>
                
                <!-- Separatore -->
                <li class="nav-item mt-auto">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>logout.php">
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