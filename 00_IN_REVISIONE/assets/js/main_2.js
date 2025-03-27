// JavaScript Document
$(document).ready(function(){
    // Effetto shrink dell'header allo scroll
    $(window).on('scroll', function(){
        if ($(window).scrollTop() > 50) {
            $('#main-header').addClass('shrink');
        } else {
            $('#main-header').removeClass('shrink');
        }
    });

  // =========================
  // CONFIG GENERALE
  // =========================
  const PRODUCTS_TOTAL = 300;   // Numero totale di prodotti
  const PRODUCTS_PER_PAGE = 30;  // Quanti prodotti carichiamo per “blocco”
  let currentPage = 0;           // A che “blocco” siamo arrivati
  let loadingInProgress = false; // Se stiamo già caricando un blocco
  
  // Contatore globale dei prodotti (evitiamo di scorrere tutto il DOM).
  let totalQty = 0;
  
  // =========================
  // Caricamento Iniziale
  // =========================
  loadNextBlock();  // Carichiamo il primo blocco

  // Inizializziamo l’infinite scroll: quando l’utente scorre verso il fondo della pagina, carichiamo altri prodotti.
  $(window).on('scroll', function(){
    // Se siamo vicini al fondo e non stiamo già caricando, carichiamo il prossimo blocco
    if(!loadingInProgress && $(window).scrollTop() + $(window).height() > $(document).height() - 200) {
      loadNextBlock();
    }
  });

  /**
   * Carica il blocco successivo di prodotti (fino a PRODUCTS_PER_PAGE).
   */
  function loadNextBlock(){
    // Se abbiamo già caricato tutti i prodotti, non fare nulla.
    if(currentPage * PRODUCTS_PER_PAGE >= PRODUCTS_TOTAL) return;

    loadingInProgress = true;
    $('#loadingSpinner').show();

    // Simuliamo un piccolo delay (es. 500 ms) come se stessimo fetchando dal server.
    setTimeout(function(){
      // Calcoliamo quanti prodotti generare in questo blocco
      let startIndex = currentPage * PRODUCTS_PER_PAGE + 1;
      let endIndex = Math.min(startIndex + PRODUCTS_PER_PAGE - 1, PRODUCTS_TOTAL);

      for(let i = startIndex; i <= endIndex; i++){
        // Generiamo i dati fittizi
        const ean = 'FITTIZIO-' + i;
        const title = 'Prodotto ' + i;
        const box = Math.floor(Math.random() * 10) + 1 + ' pz';
        const price = (Math.random() * 100).toFixed(2);
        const hasPromo = Math.random() > 0.5;
        const promoPrice = hasPromo ? (price * 0.8).toFixed(2) : null;
        
        // Creiamo la card
        let $card = createProductCard(ean, title, box, price, hasPromo, promoPrice);
        // Appendiamo al container
        $('#catalogContainer').append($card);
      }

      currentPage++;
      loadingInProgress = false;
      $('#loadingSpinner').hide();

      // Inizializziamo lazy load sulle nuove immagini
      initLazyLoad();
    }, 500); // finto delay per simulare caricamento server
  }

  /**
   * Inizializza IntersectionObserver per lazy load delle immagini
   */
  function initLazyLoad(){
    const lazyImages = document.querySelectorAll('.lazy-img[data-src]');
    const observer = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.getAttribute('data-src');
          img.removeAttribute('data-src');
          obs.unobserve(img);
        }
      });
    },{
      root: null, rootMargin: '100px', threshold: 0.01
    });
    lazyImages.forEach(img => observer.observe(img));
  }

  // =========================
  // EVENTI + E - (con autoincrement)
  // =========================

  // Variabili per autoincrement
  let pressTimer = null;
  let accelerating = false; 
  let keepPressing = false; 
  let accelerateTimeout = null;
  const INITIAL_DELAY = 500; 
  const START_SPEED = 300;

  // GESTIONE + (click singolo)
  $(document).on('click', '.plus', function(e){
    if(accelerating) return; // se sta accelerando, evitiamo un click singolo
    
    let $qtyInput = $(this).siblings('.qty');
    singleIncrement($qtyInput, +1, true); // “true” se vogliamo l’effetto scia una sola volta
  });

  // MOUSEDOWN / TOUCHSTART su .plus => avvio timer
  $(document).on('mousedown touchstart', '.plus', function(e){
    // Solo tasto sinistro o tocco singolo
    if(e.type === 'mousedown' && e.which !== 1) return;

    e.preventDefault();
    let $this = $(this);
    let $qtyInput = $this.siblings('.qty');

    pressTimer = setTimeout(()=>{
      startAccelerating($qtyInput, +1);
    }, INITIAL_DELAY);
  });

  // MOUSEUP / TOUCHEND => fermiamo
  $(document).on('mouseup touchend', '.plus', function(e){
    clearTimeout(pressTimer);
    if(accelerating){
      stopAccelerating();
    }
  });

  // mouseleave => se stiamo accelerando, stop
  $(document).on('mouseleave', '.plus', function(){
    clearTimeout(pressTimer);
    if(accelerating) stopAccelerating();
  });

  // STESSO APPROCCIO PER .minus
  $(document).on('click', '.minus', function(e){
    if(accelerating) return;
    let $qtyInput = $(this).siblings('.qty');
    singleIncrement($qtyInput, -1, true);
  });

  $(document).on('mousedown touchstart', '.minus', function(e){
    if(e.type === 'mousedown' && e.which !== 1) return;
    e.preventDefault();
    let $this = $(this);
    let $qtyInput = $this.siblings('.qty');
    pressTimer = setTimeout(()=>{
      startAccelerating($qtyInput, -1);
    }, INITIAL_DELAY);
  });

  $(document).on('mouseup touchend', '.minus', function(e){
    clearTimeout(pressTimer);
    if(accelerating){
      stopAccelerating();
    }
  });

  $(document).on('mouseleave', '.minus', function(e){
    clearTimeout(pressTimer);
    if(accelerating) stopAccelerating();
  });

  // =========================
  // INSERIMENTO MANUALE
  // =========================
  // Mostra check, nasconde + se l’utente inserisce un valore manuale
  $(document).on('input', '.qty', function(){
    let parent = $(this).closest('.quantity-controls');
    parent.find('.plus').hide();
    parent.find('.check').show();
  });

  // Click su check => conferma
  $(document).on('click', '.check', function(){
    let parent = $(this).closest('.quantity-controls');
    let $qtyInput = parent.find('.qty');

    let newVal = parseInt($qtyInput.val());
    if(isNaN(newVal) || newVal < 0) newVal = 0;

    // Calcoliamo la differenza
    let oldVal = parseInt($qtyInput.attr('data-prev')) || 0;
    let diff = newVal - oldVal;
    $qtyInput.val(newVal);
    $qtyInput.attr('data-prev', newVal);

    // Aggiorniamo il contatore globale
    totalQty += diff;
    if(totalQty < 0) totalQty = 0;
    $('#cartBadge').text(totalQty);

    // Ripristiniamo pulsanti
    parent.find('.plus').show();
    $(this).hide();
  });

  // =========================
  // FUNZIONI
  // =========================

  /**
   * Crea la card clonando il template e riempiendo i dati.
   */
  function createProductCard(ean, title, box, price, hasPromo, promoPrice){
    let $template = $('#productTemplate .product-card').clone();
    
    // Mettiamo un data-ean e data-prev="0" (quantità iniziale)
    $template.attr('data-ean', ean);
    $template.attr('data-prev', '0');
    let $qty = $template.find('.qty');
    $qty.attr('data-prev', '0').val('0');

    // Riempimento campi
    $template.find('.ean-value').text(ean);
    $template.find('.product-title').text(title);
    $template.find('.product-box').text('Confezione: ' + box);

    if(hasPromo){
      $template.find('.normal-price').hide();
      $template.find('.promo-block').show();
      $template.find('.old-price').text('€' + price);
      $template.find('.discounted-price').text('€' + promoPrice);
    } else {
      $template.find('.normal-price').text('€' + price);
      $template.find('.promo-block').hide();
    }

    return $template;
  }

  /**
   * Incrementa/Decrementa di 1 un singolo input, aggiornando totalQty globale
   * @param {jQuery} $qtyInput - L’input quantity
   * @param {number} step - +1 o -1
   * @param {boolean} animateOnce - Se true, facciamo l’animazione scia singola
   */
  function singleIncrement($qtyInput, step, animateOnce){
    let oldVal = parseInt($qtyInput.attr('data-prev')) || 0;
    let newVal = oldVal + step;
    if(newVal < 0) newVal = 0;

    let diff = newVal - oldVal;

    // Aggiorno input e data-prev
    $qtyInput.val(newVal);
    $qtyInput.attr('data-prev', newVal);

    // Aggiungo/rimuovo dal totale
    totalQty += diff;
    if(totalQty < 0) totalQty = 0;
    $('#cartBadge').text(totalQty);

    // Effetto animazione
    if(diff > 0 && animateOnce){
      // Volo dal prodotto al carrello
      flyToCartEffect($qtyInput.closest('.quantity-controls').find('.plus'));
    } else if(diff < 0 && animateOnce){
      // Volo inverso dal carrello al prodotto
      minusEffect($qtyInput.closest('.quantity-controls').find('.minus'));
    }
  }

  /**
   * Avvia l’autoincremento
   */
  function startAccelerating($qtyInput, step){
    accelerating = true;
    keepPressing = true;
    accelerateIncrement($qtyInput, step, START_SPEED);
  }

  /**
   * Ferma l’autoincremento
   */
  function stopAccelerating(){
    accelerating = false;
    keepPressing = false;
    clearTimeout(accelerateTimeout);
  }

  /**
   * Effettua uno scatto + animazioni multiple (scia)
   * riducendo progressivamente il delay.
   */
  function accelerateIncrement($qtyInput, step, delay){
    // Eseguo uno scatto
    let oldVal = parseInt($qtyInput.attr('data-prev')) || 0;
    let newVal = oldVal + step;
    if(newVal < 0) newVal = 0;
    let diff = newVal - oldVal;

    $qtyInput.val(newVal);
    $qtyInput.attr('data-prev', newVal);
    totalQty += diff;
    if(totalQty < 0) totalQty = 0;
    $('#cartBadge').text(totalQty);

    // Effetto "scia": generiamo più cloni in rapida sequenza
    // per dare l'idea di un flusso continuo. Per esempio 3 cloni.
    // Se preferisci una scia più lunga, aumenta i cloni.
    for(let i=0; i<3; i++){
      setTimeout(()=>{
        if(step > 0){
          flyToCartEffect($qtyInput.closest('.quantity-controls').find('.plus'), 0.5+i*0.2);
        } else {
          minusEffect($qtyInput.closest('.quantity-controls').find('.minus'), 0.5+i*0.2);
        }
      }, i*70); 
    }

    // Riduciamo progressivamente il delay
    let newDelay = Math.max(delay * 0.8, 50);

    if(accelerating && keepPressing){
      accelerateTimeout = setTimeout(function(){
        accelerateIncrement($qtyInput, step, newDelay);
      }, newDelay);
    }
  }

  /**
   * Animazione volo dal prodotto al carrello
   * @param {jQuery} $plusButton - Pulsante .plus (o un selettore nella card)
   * @param {number} [opacityFactor=1] - Se vogliamo variare un po' l'opacity
   */
  function flyToCartEffect($plusButton, opacityFactor=1){
    let productCard = $plusButton.closest('.product-card');
    let productImg = productCard.find('img').eq(0);

    // Clona l'immagine
    let imgClone = productImg.clone();

    // Offset immagine prodotto
    let productImgOffset = productImg.offset();

    imgClone.css({
      position:'absolute',
      top: productImgOffset.top,
      left: productImgOffset.left,
      width: productImg.width(),
      height: productImg.height(),
      zIndex:9999,
      opacity: opacityFactor,
      pointerEvents:'none'
    });

    $('body').append(imgClone);

    // Coordinate del carrello
    let $cartIcon = $('#cartIcon');
    let cartOffset = $cartIcon.offset();
    let cartWidth = $cartIcon.width()/2;
    let cartHeight = $cartIcon.height()/2;

    imgClone.animate({
      top: cartOffset.top + cartHeight,
      left: cartOffset.left + cartWidth,
      width:20,
      height:20,
      opacity:0
    }, 500, function(){
      $(this).remove();
    });
  }

  /**
   * Animazione volo dal carrello al prodotto (effetto rimozione)
   */
  function minusEffect($minusButton, opacityFactor=1){
    let productCard = $minusButton.closest('.product-card');
    let productImg = productCard.find('img').eq(0);

    // Offset immagine prodotto (destinazione)
    let productImgOffset = productImg.offset();
    let productW = productImg.width();
    let productH = productImg.height();

    // Coordinate di partenza = carrello
    let $cartIcon = $('#cartIcon');
    let cartOffset = $cartIcon.offset();
    let cartSize = 20;

    let imgClone = productImg.clone();
    imgClone.css({
      position:'absolute',
      top: cartOffset.top,
      left: cartOffset.left,
      width:cartSize,
      height:cartSize,
      zIndex:9999,
      opacity:opacityFactor,
      pointerEvents:'none'
    });

    $('body').append(imgClone);

    // Animo fino al prodotto
    imgClone.animate({
      top: productImgOffset.top,
      left: productImgOffset.left,
      width:productW,
      height:productH,
      opacity:0
    }, 500, function(){
      $(this).remove();
    });
  }

});