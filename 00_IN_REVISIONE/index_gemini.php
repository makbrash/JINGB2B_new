<?php
// public/index.php
// session_start();  // Se serve
// require_once '../config/db.php'; // Se serve la connessione

// Inclusione header (inizio pagina)
include_once __DIR__ . '/includes/header.php';
?>
<style>
/* Stili per migliorare la fluidità del catalogo */

#catalogContainer {
  width: 100%;
  /* Importante: usa un layout flessibile per i blocchi */
  display: flex;
  flex-direction: column; /* Impila i blocchi verticalmente */
  align-items: stretch; /* Assicura che i blocchi si estendano in larghezza */
}

#blocksContainer {
  display: grid; /* Utilizza CSS Grid per un layout reattivo */
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Colonne responsive, min 250px, massimo 1fr (frazione disponibile) */
  gap: 15px; /* Spazio tra i blocchi */
  width: 100%;
}


.block {
  /* Stili di base per i blocchi, rimuovi min-height iniziale e placeholder */
  /* min-height: 200px;  Rimosso, gestiamo l'altezza dinamicamente o lasciamo che sia il contenuto a definirla */
  padding: 15px;
  background-color: #f0f0f0; /* Colore di esempio per i blocchi placeholder */
  border-radius: 8px;
  box-sizing: border-box; /* Importante: padding e border inclusi nella larghezza/altezza */
}

.block.placeholder {
  background-color: #e0e0e0; /* Colore leggermente diverso per i placeholder */
  /* min-height può essere rimosso o gestito diversamente, vedi sotto */
  min-height: 200px; /* Esempio di min-height per placeholder, regolabile */
}


.block-content {
  display: grid; /* Grid layout interno per i product-card */
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Card responsive, min 200px, massimo 1fr */
  gap: 15px; /* Spazio tra le card */
}


.product-card {
  background-color: #fff;
  border-radius: 8px;
  padding: 10px;
  display: flex; /* Usa flexbox per la card */
  flex-direction: column; /* Impila gli elementi verticalmente nella card */
  box-sizing: border-box;
  border: 1px solid #ddd; /* Bordo leggero per le card */
}

.product-card .imagelayer {
  /* Stili per l'area immagine */
  position: relative; /* Necessario per posizionare elementi assoluti all'interno se serve */
  margin-bottom: 10px;
  text-align: center; /* Centra l'immagine se è più piccola del contenitore */
}

.product-card .imagelayer img {
  max-width: 100%;
  height: auto; /* Mantiene le proporzioni dell'immagine */
  display: block; /* Rimuove spazio extra sotto l'immagine se inline */
  border-radius: 6px; /* Bordo arrotondato per l'immagine */
}

.product-card .flexInfo {
  display: flex;
  flex-direction: column;
  margin-bottom: 10px;
}

.product-card .info {
  margin-bottom: 10px;
}

.product-card .flexPrice {
  display: flex;
  justify-content: space-between; /* Spazia prezzo e box */
  align-items: center; /* Allinea verticalmente prezzo e box */
}

.product-card .product-price {
  /* Stili per il prezzo */
}

.product-card .product-box {
  /* Stili per il box (pezzi) */
}

.product-card .quantity-controls {
  display: flex;
  justify-content: flex-end; /* Sposta i controlli di quantità a destra */
  align-items: center;
}

.product-card .quantity-controls input.qty {
  width: 50px; /* Larghezza input quantità */
  text-align: center;
  margin-right: 5px;
}

.product-card .quantity-controls .minusplus button {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: none;
  background-color: #eee;
  cursor: pointer;
  margin: 0 2px;
  display: flex;
  justify-content: center;
  align-items: center;
}
.product-card .quantity-controls .minusplus button.minus::before {
  content: '-';
}
.product-card .quantity-controls .minusplus button.plus::before {
  content: '+';
}


/* Stili aggiuntivi per migliorare l'aspetto (opzionali) */
.product-title {
  font-weight: bold;
  margin-bottom: 5px;
}

.ean-label {
  font-size: 0.9em;
  color: #777;
}

.product-disp {
  font-size: 0.9em;
  color: green; /* o altro colore per la disponibilità */
  margin-bottom: 5px;
}

.normal-price {
  font-weight: bold;
  font-size: 1.1em;
  color: #333;
}

.promo-block {
  margin-left: 10px;
  display: inline-flex;
  align-items: center;
}

.old-price {
  color: #888;
  font-size: 0.9em;

  margin-right: 5px;
}

.discounted-price {
  color: red;
  font-weight: bold;
  font-size: 1.1em;
}

.pezzi {
  font-size: 0.9em;
  color: #555;
}

.wrap_counter_cart {
  position: absolute;
  top: 10px;
  right: 10px;
  background-color: rgba(0, 123, 255, 0.8);
  color: white;
  border-radius: 50%;
  width: 25px;
  height: 25px;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 0.8em;
}
.counter_cart {
  /* Stili per il contatore nel carrello */
}
</style>
<!-- Contenitore per i prodotti -->
<div class="catalog-container" id="catalogContainer"></div>

<!-- Spinner infinite scroll -->
<div class="loading-spinner" id="loadingSpinner">Caricamento...</div>

<!-- Template Nascosto -->
<div id="productTemplate" style="display:none;">
  <div class="product-card" data-ean="" data-prev="0">
    <div class='imagelayer'>
        <img class="lazy-img" data-src="assets/img/notfound.jpg" src="assets/img/notfound.jpg" alt="Immagine prodotto" />
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
<?php
// Inclusione footer (fine pagina)
include_once __DIR__ . '/includes/footer.php';
?>

  <!-- jQuery -->

 <script>
$(document).ready(function(){


	// Coda globale delle immagini da caricare
  var imageQueue = [];

  // Quante immagini puoi caricare simultaneamente
  var MAX_CONCURRENT_LOADS = 20;

  // Contatore di quante immagini stai caricando al momento
  var currentLoads = 0;

  aggiornaRichiesta('list');
  // Questa funzione chiama il proxy e, in caso di successo,
  // richiama renderCatalog() con i dati ottenuti
  function aggiornaRichiesta(action) {
    $.ajax({
      url: "/api/proxy_request.php", // Indirizzo del tuo proxy
      type: "POST",
      dataType: "json",
      contentType: "application/json",
      data: JSON.stringify({ action: action }),
      success: function(data) {
        console.log("Risposta AJAX:", data);
        if (data.success) {
		    //alert('');
          // data.data conterrà l'array di prodotti
         // alert("Richiesta aggiornata con successo!");
          renderCatalog(data.data);
        } else {
          alert("Errore: " + data.error);
        }
      },
      error: function(xhr) {
        alert("Errore di rete, riprova più tardi.");
      }
    });
  }

  // Costruisce il catalogo "lazy" tramite IntersectionObserver
  function renderCatalog(allProducts) {
    var totalProducts = allProducts.length;      // Quanti prodotti totali
    var productsPerBlock = 40;                  // Quanti prodotti per blocco
    var totalBlocks = Math.ceil(totalProducts / productsPerBlock);

    var $catalogContainer = $('#catalogContainer');
    $catalogContainer.empty(); // Puliamo l’area (se serve)

    // Contenitore generale dove andranno i blocchi
    var $blocksContainer = $('<div id="blocksContainer"></div>');
    $catalogContainer.append($blocksContainer);

    // Creiamo i blocchi “placeholder” (vuoti)
    for(var b = 0; b < totalBlocks; b++){
      var $block = $('<div class="block placeholder" data-block-index="'+b+'" data-loaded="false"></div>');
      $blocksContainer.append($block);
    }

    // IntersectionObserver: quando un blocco entra in vista lo carichiamo, altrimenti lo scarichiamo
    var blockObserver = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry){
        var $block = $(entry.target);
        if(entry.isIntersecting) {
          // Se non è mai stato caricato prima, lo carico
          if($block.data('loaded') !== true) {
            loadBlock($block);
          }
        } else {
          // Se il blocco esce e risulta caricato, lo scarico
          if($block.data('loaded') === true) {
            unloadBlock($block);
          }
        }
      });
    }, {
      root: document.getElementById('catalogContainer'),
      rootMargin: '1600px 0px',
      threshold: 0.01
    });

    // Osserva ciascun blocco creato
    $('.block').each(function(){
      blockObserver.observe(this);
    });

    // Funzione per caricare i prodotti di un blocco
    function loadBlock($block) {
      var blockIndex = parseInt($block.data('block-index'));
      var startIndex = blockIndex * productsPerBlock;
      var endIndex = Math.min(startIndex + productsPerBlock, totalProducts);

      // Prendo solo i prodotti destinati a questo blocco
      var products = allProducts.slice(startIndex, endIndex);
      var $content = $('<div class="block-content"></div>');

      products.forEach(function(product){
        // Cloniamo il template
        var $card = $('#productTemplate .product-card').clone();

        // Mappiamo i campi (adatta i nomi a seconda del tuo JSON)
        // Esempio JSON:
        // { "id":1, "titolo":"ACE 1 LITRO CLASSICA", "ean":"testagi", "prezzo":"0.99", "immagine":"xxx.jpg", "disponibilita":1224 }
        $card.attr('data-ean', product.ean);
        $card.find('.ean-value').text(product.ean);
        $card.find('.product-title').text(product.titolo);
        $card.find('.product-box').html('<span class=\'pezzi\'>Conf. <strong>' +product.pezzi+'</strong> pezzi</span>');
		$card.find('.product-disp').text( product.disponibilita);
        $card.find('.normal-price').text('€ ' + product.prezzo);

        // Se hai un prezzo vecchio e uno scontato, gestisci .promo-block:
        // Esempio banale: se product.old_price e product.sconto sono presenti
        /*
        if (product.old_price && product.sconto) {
          $card.find('.promo-block').show();
          $card.find('.old-price').text('€ ' + product.old_price);
          $card.find('.discounted-price').text('€ ' + product.sconto);
        }
        */

        // Impostiamo l’immagine
		if(product.immagine != 'no-image.jpg'){
			var imagePath = '/public/catalogo/' + product.immagine;
			$card.find('img.lazy-img').attr('data-src', imagePath);
		}else{
			var imagePath = 'assets/img/notfound.jpg';
			$card.find('img.lazy-img').removeClass('lazy-img')
		}


        // Se il JSON contiene "immagine":"nomefile.jpg"


        // Gestione plus/minus sul quantity input (se serve):
        $card.find('.minus').on('click', function(){
          var $qty = $card.find('.qty');
		  var $qty_display = $card.find('.counter_cart')
          var currentVal = parseInt($qty.val()) || 0;

		  $qty_display.text(currentVal);

          if (currentVal > 0) {
			  $qty.val(currentVal - 1);
			  $qty_display.text(currentVal - 1);
			  $card.find('.qty');

			  if((currentVal - 1) <= 0){
				  $card.find('.wrap_counter_cart').fadeOut()
			  }
		  }

        });
        $card.find('.plus').on('click', function(){
          var $qty = $card.find('.qty');
		  var $qty_display = $card.find('.counter_cart')
          var currentVal = parseInt($qty.val()) || 0;
          $qty.val(currentVal + 1);
		  $qty_display.text(currentVal + 1);
		  $card.find('.wrap_counter_cart').fadeIn();
        });

        // Aggiungo la card
        $content.append($card);
      });

      // Metto il contenuto nel blocco
      $block.empty().append($content);
      $block.data('loaded', true).removeClass('placeholder');





function queueImageLoad($img, finalSrc) {
  // $img   => oggetto jQuery dell'img
  // finalSrc => URL dell'immagine che vogliamo caricare
  imageQueue.push({ $img: $img, src: finalSrc });
  processImageQueue();  // Avvia/continua il processo
}

function processImageQueue() {
  // Finché possiamo caricare e ci sono elementi in coda, procedi
  while (currentLoads < MAX_CONCURRENT_LOADS && imageQueue.length > 0) {
    // Estraggo il primo elemento dalla coda
    var item = imageQueue.shift();
    loadSingleImage(item.$img, item.src);
  }
}

function loadSingleImage($img, finalSrc) {
  currentLoads++; // Aumenta di 1 il numero di caricamenti in corso

  // Creo un oggetto Image “fittizio” per fare il download “dietro le quinte”
  var tempImg = new Image();

  // Quando l’immagine è caricata con successo
  tempImg.onload = function() {
    // Assegno il src effettivo al tag <img> sulla pagina
    $img.attr('src', finalSrc);
    currentLoads--;
    processImageQueue(); // Appena finito, proviamo a caricare la prossima in coda
  };

  // In caso di errore: assegno una immagine di fallback
  tempImg.onerror = function() {
    $img.attr('src', 'assets/img/notfound.jpg');
    currentLoads--;
    processImageQueue();
  };

  // Avvio il download
  tempImg.src = finalSrc;
}
      // Carico subito le immagini (puoi fare un lazy load più evoluto se vuoi)
     /* $block.find('img.lazy-img').each(function(){
        var $img = $(this);
        $img.attr('src', $img.attr('data-src'));
        $img.removeAttr('data-src');
      });*/

$block.find('img.lazy-img').each(function(){
  var $img = $(this);
  var finalSrc = $img.attr('data-src');
  $img.removeAttr('data-src');
  // Metti in coda
  queueImageLoad($img, finalSrc);
});




      // Memorizzo l’altezza, per poter piazzare un placeholder alla dismissione
      var blockHeight = $block.outerHeight();
      $block.data('block-height', blockHeight);
    }

    // Scarica (svuota) il blocco quando esce dalla viewport
    function unloadBlock($block) {
      if (!$block.data('block-height')) {
        $block.data('block-height', $block.outerHeight());
      }
      $block.empty();
      $block.addClass('placeholder');
      $block.css('min-height', $block.data('block-height') + 'px');
      $block.data('loaded', false);
    }
  }
});
</script>

