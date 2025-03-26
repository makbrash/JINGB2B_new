<?php
        // Chiusura del container-xl e page-body che sono aperti in page-header.php
        ?>
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
                        <a href="<?php echo BASE_URL; ?>" class="link-secondary">Marco Vitaletti Admin</a>.
                        Tutti i diritti riservati.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>
</div><!-- Chiusura del page-wrapper -->
</div><!-- Chiusura del page -->

<!-- JavaScript files -->
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<?php
// Includi script aggiuntivi specifici per la pagina
if (isset($pageScripts) && is_array($pageScripts)) {
    foreach ($pageScripts as $script) {
        echo '<script src="' . htmlspecialchars($script) . '"></script>' . PHP_EOL;
    }
}

// Includi script inline specifici per la pagina
if (isset($inlineScripts) && is_array($inlineScripts)) {
    echo '<script>' . PHP_EOL;
    foreach ($inlineScripts as $script) {
        echo $script . PHP_EOL;
    }
    echo '</script>' . PHP_EOL;
}
?>

<!-- Gestione del tema chiaro/scuro -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestione pulsanti cambio tema
    const darkModeLink = document.querySelector('a[href="?theme=dark"]');
    const lightModeLink = document.querySelector('a[href="?theme=light"]');
    
    if(darkModeLink && lightModeLink) {
        darkModeLink.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.setAttribute('data-bs-theme', 'dark');
            document.cookie = "theme=dark; path=/; max-age=31536000"; // 1 anno
        });
        
        lightModeLink.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.setAttribute('data-bs-theme', 'light');
            document.cookie = "theme=light; path=/; max-age=31536000"; // 1 anno
        });
    }
});
</script>
</body>
</html>