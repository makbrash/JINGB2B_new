<?php
// public/index.php
// session_start();  // Se serve
// require_once '../config/db.php'; // Se serve la connessione

// Inclusione header (inizio pagina)
include_once __DIR__ . '/includes/header.php';
?>

<!-- CONTENITORE CATALOGO -->


  <!-- Contenitore per i prodotti -->
  <div class="catalog-container" id="catalogContainer"></div>

  <!-- Spinner infinite scroll -->
  <div class="loading-spinner" id="loadingSpinner">Caricamento...</div>

  <!-- Template Nascosto -->
  <div id="productTemplate" style="display:none;">
    <div class="product-card" data-ean="" data-prev="0">
      <img class="lazy-img" data-src="assets/img/notfound.jpg" alt="Immagine prodotto" />
      <div class="info">
        <div class="ean-label">EAN: <span class="ean-value"></span></div>
        <div class="product-title"></div>
        <div class="product-box"></div>
        <div class="product-price">
          <span class="normal-price"></span>
          <span class="promo-block" style="display:none;">
            <del class="old-price"></del>
            <span class="discounted-price"></span>
          </span>
        </div>
      </div>
      <div class="quantity-controls">
        <button class="minus">-</button>
        <input class="qty" type="number" value="0" min="0" data-prev="0">
        <button class="plus">+</button>
        <button class="check" style="display:none;">v</button>
      </div>
    </div>
  </div>
    
    <script src="/assets/js/main.js"></script>
    
<?php
// Inclusione footer (fine pagina)
include_once __DIR__ . '/includes/footer.php';
?>
