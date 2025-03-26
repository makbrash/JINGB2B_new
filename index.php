<?php
// index.php
// session_start(); // se serve
// Include header (HTML <head>, se hai un file header separato)
include_once('includes/header.php');
?>

<body>

  <header>
    <h1>Catalogo JINGB2B</h1>
    <!-- Eventuali menu, filtri, ecc. -->
  </header>

  <!-- Contenitore catalogo -->
  <div class="catalog-container" id="catalogContainer"></div>

  <!-- Barra di avanzamento per il download massivo -->
  <div id="progressBarContainer" style="display:none;">
    <div id="progressBarWRAP">
      <div id="progressBar_BG"></div>
      <div id="progressBar"></div>
    </div>
  </div>

  <!-- Template nascosto per le card (tuo codice) -->
<div id="productTemplate" style="display:none;">
  <div class="product-card" data-ean="" data-prev="0">
    <div class='imagelayer'>
        <img loading="lazy"  class="imgLazy" src="assets/img/notfound.jpg" alt="Immagine prodotto" />
        <div class='wrap_counter_cart' style="display:none;">
        <div class='counter_cart'></div>
        </div>
    </div>
    <div class='flexInfo'>
      <div class="info">
        <div class="product-disp"></div>
        <div class="ean-label"><span class="ean-value trueselect"></span></div>
        <div class="product-title"></div>
      </div>

      <div class='flexPrice'>
         <div class="product-price">
            <span class="normal-price"></span>
            <span class="promo-block">
              <del class="old-price">€ 1,23</del>
              <span class="discounted-price"></span>
            </span>
         </div>
         <div class="product-box"></div>

      </div>
    </div>
    <div class="quantity-controls">
        <input class="qty gost" type="number" step="" value="0" min="0" data-prev="0">
        <div class='minusplus'>
          <button class="minus"> </button>
          <button class="plus"> </button>
        </div>
        <!--<button class="check" style="display:none;">v</button>-->
    </div>
  </div>
</div>


<? include_once('includes/footer.php');?>
