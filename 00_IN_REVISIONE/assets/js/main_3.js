$(document).ready(function(){
    // Esempio di generazione di 30 prodotti
    const totalProducts = 300;

    for (let i = 1; i <= totalProducts; i++) {
        const ean = 'FITTIZIO-' + i;
        const title = 'Prodotto ' + i;
        const box = Math.floor(Math.random() * 10) + 1 + ' pz';
        const price = (Math.random() * 100).toFixed(2);

        // 50% di probabilità di promo
        const hasPromo = Math.random() > 0.5;
        const promoPrice = hasPromo ? (price * 0.8).toFixed(2) : null;

        // Crea la card clonando dal template
        let $card = createProductCard(ean, title, box, price, hasPromo, promoPrice);

        // Aggiungi la card nel container
        $('#catalogContainer').append($card);
    }

    
    // -----------------------------------------------
    // Lazy load con IntersectionObserver (opzionale)
    // -----------------------------------------------
    const lazyImages = document.querySelectorAll('.lazy-img');
    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.getAttribute('data-src');
                img.removeAttribute('data-src');
                obs.unobserve(img);
            }
        });
    }, {
        root: null,
        rootMargin: '100px',
        threshold: 0.01
    });
    lazyImages.forEach(img => {
        observer.observe(img);
    });

    // -----------------------------------------------
    // Gestione pulsanti + e -
    // -----------------------------------------------


    // -----------------------------------------------
    // Inserimento manuale input => mostra pulsante v
    // -----------------------------------------------
    // Appena l'utente digita cambia la qty, nascondiamo + e mostriamo check
    $(document).on('input', '.qty', function(){
        let parent = $(this).closest('.quantity-controls');
        parent.find('.plus').hide();
        parent.find('.check').show();
    });

    // Al click sul check => confermiamo la qty e ripristiniamo
    $(document).on('click', '.check', function(){
        let parent = $(this).closest('.quantity-controls');
        let qtyInput = parent.find('.qty');

        let newQty = parseInt(qtyInput.val());
        if (isNaN(newQty) || newQty < 0) {
            newQty = 0;
        }
        qtyInput.val(newQty);

        // Mostriamo di nuovo il + e nascondiamo la check
        parent.find('.plus').show();
        $(this).hide();

        updateCartBadge();
    });


    // -----------------------------------------------
    // Funzioni di utilità
    // -----------------------------------------------

    // Aggiorna il badge del carrello


    // Animazione “vola al carrello”
    function flyToCartEffect(plusButton) {
        let productCard = plusButton.closest('.product-card');
        let productImg = productCard.find('img').eq(0);

        // Clona l'immagine
        let imgClone = productImg.clone();

        // Offset immagine prodotto
        let productImgOffset = productImg.offset();

        // Stili di base per il clone
        imgClone.css({
            'position': 'absolute',
            'top': productImgOffset.top,
            'left': productImgOffset.left,
            'width': productImg.width(),
            'height': productImg.height(),
            'z-index': 9999,
            'opacity': 1,
            'pointer-events': 'none' // Non blocca i click sottostanti
        });

        // Aggiungo il clone al body
        $('body').append(imgClone);

        // Coordinate del carrello
        let cartIcon = $('#cartIcon');
        let cartOffset = cartIcon.offset();
        let cartWidth = cartIcon.width() / 2;
        let cartHeight = cartIcon.height() / 2;

        // Animo il clone
        imgClone.animate({
            top: cartOffset.top + cartHeight,
            left: cartOffset.left + cartWidth,
            width: 20,
            height: 20,
            opacity: 0
        }, 800, function(){
            // Rimuovo il clone alla fine
            $(this).remove();
        });
    }

    // Animazione minus: blur/scale dell'immagine
function minusEffect(minusButton) {
    // Troviamo card e immagine del prodotto
    let productCard = minusButton.closest('.product-card');
    let productImg = productCard.find('img').eq(0);

    // Offset dell’immagine del prodotto (destinazione)
    let productImgOffset = productImg.offset();

    // Dimensioni reali del prodotto
    let productWidth = productImg.width();
    let productHeight = productImg.height();

    // Coordinate di partenza (carrello)
    let cartIcon = $('#cartIcon');
    let cartOffset = cartIcon.offset();
    let cartSize = 20; // dimensione (larghezza e altezza) di partenza

    // Cloniamo l’immagine del prodotto (o un’icona generica)
    let imgClone = productImg.clone();

    // Impostiamo stile di partenza (presso l’icona carrello)
    imgClone.css({
        position: 'absolute',
        top: cartOffset.top,
        left: cartOffset.left,
        width: cartSize,
        height: cartSize,
        zIndex: 9999,
        opacity: 1,
        pointerEvents: 'none'
    });

    // Aggiungiamo il clone al body
    $('body').append(imgClone);

    // Animizziamo lo spostamento verso la card
    imgClone.animate({
        top: productImgOffset.top,
        left: productImgOffset.left,
        width: productWidth,
        height: productHeight,
        opacity: 0
    }, 800, function() {
        // Alla fine, rimuoviamo il clone
        $(this).remove();
    });
}









  let pressTimer = null;         // Timer iniziale (mezzo secondo)
    let accelerating = false;      // true se è partito l'autoincremento
    let keepPressing = false;      // finché l'utente tiene premuto
    let accelerateTimeout = null;  // timer ricorsivo per l’accelerazione
    const INITIAL_DELAY = 500;     // tempo prima di avviare l’autoincremento
    const START_SPEED = 300;       // primo intervallo (ms) dell’accelerazione

    //------------------------------------------------------
    // CLICK su .plus => incrementa di 1 SOLO SE
    // NON è partito l'autoincremento
    //------------------------------------------------------
    $(document).on('click', '.plus', function(e){
        // Se stava accelerando, ignoriamo
		
        if (accelerating) return;

        // Altrimenti, facciamo il singolo incremento
        let qtyInput = $(this).siblings('.qty');
        singleIncrement(qtyInput, +1);
		// flyToCartEffect($(this));
    });

    //------------------------------------------------------
    // MOUSEDOWN su .plus => parte il timer di 500 ms
    // Se scade => autoincremento
    //------------------------------------------------------
    $(document).on('mousedown', '.plus', function(e){
        // Attiviamo solo con tasto sinistro
        if (e.which !== 1) return;
        e.preventDefault();

        let qtyInput = $(this).siblings('.qty');

        // Impostiamo un timer di 500ms
        pressTimer = setTimeout(()=>{
            // Se dopo 500ms non hanno rilasciato
            // => avvia accelerazione
			//flyToCartEffect($(this));
            startAccelerating(qtyInput, +1);
			 
			 
        }, INITIAL_DELAY);
    });

    //------------------------------------------------------
    // MOUSEUP su .plus => se era partita l’accelerazione,
    // la fermiamo. Altrimenti, lasciamo che faccia “click”.
    //------------------------------------------------------
    $(document).on('mouseup mouseleave', '.plus', function(e){
        clearTimeout(pressTimer);
        // Se stavamo accelerando, fermiamo
        if (accelerating) {
            stopAccelerating();
			
        }
    });

    //------------------------------------------------------
    // (OPZIONALE) mouseleave su .plus => se usiamo, fermiamo
    // l’accelerazione se il mouse esce dal pulsante
    //------------------------------------------------------








    //------------------------------------------------------
    // STESSO APPROCCIO PER .minus
    //------------------------------------------------------
    $(document).on('click', '.minus', function(e){
        if (accelerating) return;

        let qtyInput = $(this).siblings('.qty');
        singleIncrement(qtyInput, -1);
		
    });

    $(document).on('mousedown', '.minus', function(e){
        if (e.which !== 1) return;
        e.preventDefault();

        let qtyInput = $(this).siblings('.qty');

        pressTimer = setTimeout(()=>{
            startAccelerating(qtyInput, -1);
			
        }, INITIAL_DELAY);
    });

    $(document).on('mouseup mouseleave', '.minus', function(e){
        clearTimeout(pressTimer);
        if (accelerating) {
            stopAccelerating();
			
        }
    });




    // -----------------------------------------------------
    // FUNZIONI DI SUPPORTO
    // -----------------------------------------------------
    function singleIncrement(qtyInput, step){
        let val = parseInt(qtyInput.val()) || 0;
        val += step;
        if (val < 0) val = 0;
        qtyInput.val(val);
        
		updateCartBadge(qtyInput);
		
		if(step == +1){
           flyToCartEffect(qtyInput);
		}else{
			minusEffect(qtyInput);
		}
    }

    function startAccelerating(qtyInput, step){
        accelerating = true;
        keepPressing = true;
        accelerateIncrement(qtyInput, step, START_SPEED);
    }

    function stopAccelerating(){
        accelerating = false;
        keepPressing = false;
        clearTimeout(accelerateTimeout);
    }

    function accelerateIncrement(qtyInput, step, delay){
        // Eseguo uno scatto
        let currentVal = parseInt(qtyInput.val()) || 0;
        currentVal += step;
        if (currentVal < 0) currentVal = 0;
        qtyInput.val(currentVal);
		
		updateCartBadge(qtyInput);
		
		if(step == +1){
           flyToCartEffect(qtyInput);
		}else{
			minusEffect(qtyInput);
		}
        
        // Riduco progressivamente il delay
        let newDelay = Math.max(delay * 0.8, 50);

        if (accelerating && keepPressing) {
            accelerateTimeout = setTimeout(function(){
                accelerateIncrement(qtyInput, step, newDelay);
            }, newDelay);
        }
    }

    function updateCartBadge(this_id){
        let totalQty = 0;
        $('.qty').each(function(){
            totalQty += parseInt($(this).val()) || 0;
        });
		
		//flyToCartEffect(this_id);
		
        $('#cartBadge').text(totalQty);
    }
});

/**
 * Clona il template e riempie i dati prodotto
 */
function createProductCard(ean, title, box, price, hasPromo, promoPrice) {
    // Cloniamo la card dal template
    let $template = $('#productTemplate .product-card').clone();

    // Imposta l'attributo data-ean
    $template.attr('data-ean', ean);

    // EAN
    $template.find('.ean-value').text(ean);

    // Titolo
    $template.find('.product-title').text(title);

    // Box
    $template.find('.product-box').text('Confezione: ' + box);

    // Prezzi
    if (hasPromo) {
        // Nascondo prezzo normale, anzi no: 
        // - mostro un blocco promo e un del
        $template.find('.normal-price').hide();
        $template.find('.promo-block').show();
        $template.find('.old-price').text('€' + price);
        $template.find('.discounted-price').text('€' + promoPrice);
    } else {
        // Mostro solo normal-price
        $template.find('.normal-price').text('€' + price);
        $template.find('.promo-block').hide();
    }

    // Restituisco il card pronto
    return $template;
}