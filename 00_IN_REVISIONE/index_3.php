<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Scroll Efficiente</title>
    <style>
        :root {
            --card-bg: #ffffff;
            --text-primary: #2d3748;
            --text-secondary: #4a5568;
            --price-color: #2b6cb0;
            --discount-color: #c53030;
            --border-color: #e2e8f0;
            --button-bg: #f7fafc;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
        }

        .scroll-container {
            height: 100vh;
            overflow-y: auto;
            position: relative;
        }

        .catalog-container {
            position: relative;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            padding: 1rem;
        }

        .product-card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
            position: absolute;
            width: calc(100% - 2rem);
        }

        .lazy-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 6px;
            background: #f1f5f9;
        }

        .info {
            margin-top: 0.75rem;
            flex-grow: 1;
        }

        .ean-label {
            font-size: 0.7rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }

        .product-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            margin-top: 0.5rem;
            font-weight: 700;
        }

        .normal-price {
            color: var(--price-color);
            font-size: 1.1rem;
        }

        .old-price {
            color: var(--discount-color);
            font-size: 0.9rem;
            margin-right: 0.5rem;
        }

        .discounted-price {
            color: var(--discount-color);
        }

        .quantity-controls {
            margin-top: 1rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .quantity-controls button {
            padding: 0.25rem 0.5rem;
            border: 1px solid var(--border-color);
            background: var(--button-bg);
            border-radius: 4px;
            cursor: pointer;
        }

        .qty {
            width: 40px;
            text-align: center;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0.25rem;
        }

        @media (min-width: 640px) {
            .catalog-container {
                grid-template-columns: repeat(3, minmax(180px, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .catalog-container {
                grid-template-columns: repeat(6, minmax(180px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="scroll-container" id="scrollContainer">
        <div class="catalog-container" id="catalogContainer" style="height: 900000px;"></div>
    </div>

    <template id="productTemplate">
        <div class="product-card">
            <img class="lazy-img" data-src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=" alt="Product image">
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
                <input class="qty" type="number" value="0" min="0">
                <button class="plus">+</button>
                <button class="check" style="display:none;">✓</button>
            </div>
        </div>
    </template>

    <script>
        const TOTAL_ITEMS = 3000;
        const ITEM_HEIGHT = 300;
        const BUFFER_ITEMS = 10;
        let visibleItems = [];
        
        // FUNZIONE MANCANTE AGGIUNTA
        function generateFakeProduct(index) {
            const hasDiscount = Math.random() > 0.8;
            const basePrice = (Math.random() * 100 + 10).toFixed(2);
            
            return {
                ean: `EAN${String(index).padStart(8, '0')}`,
                title: `Prodotto ${index + 1} ${['Premium', 'Super', 'Deluxe'][index % 3]}`,
                box: `Confezione da ${[1, 3, 5, 10][index % 4]} pezzi`,
                normalPrice: basePrice,
                oldPrice: hasDiscount ? (parseFloat(basePrice) * 1.2).toFixed(2) : null,
                imageId: index % 1000
            };
        }

        // FUNZIONE MANCANTE AGGIUNTA
        function createProductCard(product) {
            const template = document.getElementById('productTemplate');
            const clone = template.content.cloneNode(true);
            
            clone.querySelector('.ean-value').textContent = product.ean;
            clone.querySelector('.product-title').textContent = product.title;
            clone.querySelector('.product-box').textContent = product.box;
            
            const priceElement = clone.querySelector('.product-price');
            if(product.oldPrice) {
                priceElement.querySelector('.normal-price').style.display = 'none';
                const promoBlock = priceElement.querySelector('.promo-block');
                promoBlock.style.display = 'inline';
                promoBlock.querySelector('.old-price').textContent = `€${product.oldPrice}`;
                promoBlock.querySelector('.discounted-price').textContent = `€${product.normalPrice}`;
            } else {
                priceElement.querySelector('.normal-price').textContent = `€${product.normalPrice}`;
            }

            const img = clone.querySelector('img');
            img.dataset.src = `https://picsum.photos/id/${product.imageId}/200/200`;
            
            return clone.firstElementChild;
        }

        function getVisibleRange() {
            const scrollTop = scrollContainer.scrollTop;
            const containerHeight = scrollContainer.clientHeight;
            const start = Math.max(0, Math.floor(scrollTop / ITEM_HEIGHT) - BUFFER_ITEMS);
            const end = Math.min(
                TOTAL_ITEMS,
                Math.ceil((scrollTop + containerHeight) / ITEM_HEIGHT) + BUFFER_ITEMS
            );
            return { start, end };
        }

        function renderVisibleItems() {
            const range = getVisibleRange();
            
            // Rimuovi elementi non visibili
            document.querySelectorAll('.product-card').forEach(card => {
                const index = parseInt(card.dataset.index);
                if(index < range.start || index >= range.end) {
                    card.remove();
                }
            });

            // Aggiungi nuovi elementi
            for(let i = range.start; i < range.end; i++) {
                if(!document.querySelector(`[data-index="${i}"]`)) {
                    const product = generateFakeProduct(i);
                    const card = createProductCard(product);
                    card.dataset.index = i;
                    card.style.top = `${i * ITEM_HEIGHT}px`;
                    catalogContainer.appendChild(card);
                }
            }
            
            lazyLoadImages();
        }

        function lazyLoadImages() {
            const lazyImages = document.querySelectorAll('.lazy-img[data-src]');
            const imgObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if(entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        imgObserver.unobserve(img);
                    }
                });
            }, { rootMargin: '100px' });

            lazyImages.forEach(img => {
                imgObserver.observe(img);
            });
        }

        function handleScroll() {
            requestAnimationFrame(renderVisibleItems);
        }

        // Inizializzazione
        const scrollContainer = document.getElementById('scrollContainer');
        const catalogContainer = document.getElementById('catalogContainer');
        scrollContainer.addEventListener('scroll', handleScroll);
        new ResizeObserver(renderVisibleItems).observe(scrollContainer);
        renderVisibleItems();

        // Gestione quantità
        document.body.addEventListener('click', (e) => {
            const input = e.target.closest('.product-card')?.querySelector('.qty');
            if(!input) return;

            if(e.target.classList.contains('plus')) {
                input.value = parseInt(input.value) + 1;
            } else if(e.target.classList.contains('minus')) {
                input.value = Math.max(0, parseInt(input.value) - 1);
            }
        });
    </script>
</body>
</html>