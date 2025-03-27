$(document).ready(function(){
    // Esempio di generazione di 30 prodotti
    const totalProducts = 3000;

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

function flyToCartEffect(plusButton) {
    let productCard = plusButton.closest('.product-card');
    let productImg = productCard.find('img').eq(0);

    // Recupera (o imposta) un valore esplicito per il z-index dell'immagine master
    let masterZ = parseInt(productImg.css('z-index'));
    if (isNaN(masterZ)) {
        masterZ = 2;
        productImg.css('z-index', masterZ);
    }
    // Il clone dovrà avere un z-index appena inferiore
    let cloneZ = masterZ - 1;

    // Clona direttamente l'immagine (senza buffer)
    let imgClone = productImg.clone();
    let productImgOffset = productImg.offset();
    let imgWidth = productImg.width();
    let imgHeight = productImg.height();

    // Imposta lo stile iniziale usando transform/scale
    imgClone.css({
        position: 'absolute',
        top: productImgOffset.top,
        left: productImgOffset.left,
        width: imgWidth,
        height: imgHeight,
        zIndex: cloneZ,
        opacity: 1,
        pointerEvents: 'none',
        transition: 'transform 0.8s ease-out, opacity 0.8s ease-out',
        transform: 'translate(0, 0) scale(1)'
    });
    $('body').append(imgClone);

    // Calcola il centro del carrello e dell'immagine
    let cartIcon = $('#cartIcon');
    let cartOffset = cartIcon.offset();
    let cartCenterX = cartOffset.left + cartIcon.width() / 2;
    let cartCenterY = cartOffset.top + cartIcon.height() / 2;
    let productCenterX = productImgOffset.left + imgWidth / 2;
    let productCenterY = productImgOffset.top + imgHeight / 2;
    let deltaX = cartCenterX - productCenterX;
    let deltaY = cartCenterY - productCenterY;
    // Fattore di scala: ad esempio, ridurre l'immagine a 20px di larghezza
    let finalScale = 20 / imgWidth;

    // Forza il reflow per assicurare che il browser applichi lo stile iniziale
    imgClone[0].offsetWidth;

    // Avvia l'animazione con transform e modifica di opacity
    imgClone.css({
        transform: `translate(${deltaX}px, ${deltaY}px) scale(${finalScale})`,
        opacity: 0
    });

    // Rimuovi il clone al termine della transizione
    imgClone.one('transitionend', function() {
        imgClone.remove();
    });
}

function minusEffect(minusButton) {
    let productCard = minusButton.closest('.product-card');
    let productImg = productCard.find('img').eq(0);

    // Recupera (o imposta) il z-index esplicito della master
    let masterZ = parseInt(productImg.css('z-index'));
    if (isNaN(masterZ)) {
        masterZ = 2;
        productImg.css('z-index', masterZ);
    }
    let cloneZ = masterZ - 1;

    // Clona direttamente l'immagine
    let imgClone = productImg.clone();
    let productImgOffset = productImg.offset();
    let productWidth = productImg.width();
    let productHeight = productImg.height();
    let productCenterX = productImgOffset.left + productWidth / 2;
    let productCenterY = productImgOffset.top + productHeight / 2;

    // Coordinate di partenza: centro del carrello
    let cartIcon = $('#cartIcon');
    let cartOffset = cartIcon.offset();
    let cartCenterX = cartOffset.left + cartIcon.width() / 2;
    let cartCenterY = cartOffset.top + cartIcon.height() / 2;
    let initialSize = 20; // dimensione iniziale (es. quelle dell'icona)
    let finalScale = productWidth / initialSize;

    // Imposta lo stile iniziale del clone al centro del carrello
    imgClone.css({
        position: 'absolute',
        top: cartOffset.top,
        left: cartOffset.left,
        width: initialSize,
        height: initialSize,
        zIndex: cloneZ,
        opacity: 1,
        pointerEvents: 'none',
        transition: 'transform 0.8s ease-out, opacity 0.8s ease-out',
        transform: 'translate(0, 0) scale(1)'
    });
    $('body').append(imgClone);

    // Forza il reflow
    imgClone[0].offsetWidth;

    // Calcola il delta dal centro del carrello al centro dell'immagine
    let deltaX = productCenterX - cartCenterX;
    let deltaY = productCenterY - cartCenterY;

    // Avvia l'animazione verso l'immagine del prodotto
    imgClone.css({
        transform: `translate(${deltaX}px, ${deltaY}px) scale(${finalScale})`,
        opacity: 0
    });

    imgClone.one('transitionend', function() {
        imgClone.remove();
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