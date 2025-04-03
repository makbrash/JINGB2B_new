/* =========================================
   GESTIONE IndexedDB + Logica Offline/Online
   ========================================= */

let db = null; // Riferimento al DB IndexedDB
// Coda globale per le immagini
var imageQueue = [];
var MAX_CONCURRENT_LOADS = 20;
var currentLoads = 0;
/**
 * Inizializza il DB "jingb2bDB" e crea lo store "products" se non esiste.
 */
function initDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open("jingb2bDB", 1);
    request.onupgradeneeded = (e) => {
      db = e.target.result;
      if (!db.objectStoreNames.contains("products")) {
        db.createObjectStore("products", { keyPath: "ean" });
      }
    };
    request.onsuccess = (e) => {
      db = e.target.result;
      resolve(db);
    };
    request.onerror = (e) => {
      reject(e);
    };
  });
}

/**
 * Salva/aggiorna un array di prodotti nello store "products".
 * Assicurati che ogni prodotto abbia "ean" unico (o modifica la chiavePath).
 */
function saveProductsToDB(products) {
  return new Promise((resolve, reject) => {
    const tx = db.transaction(["products"], "readwrite");
    const store = tx.objectStore("products");
    products.forEach((prod) => store.put(prod));
    tx.oncomplete = () => resolve();
    tx.onerror = (err) => reject(err);
  });
}

/**
 * Legge tutti i prodotti da IndexedDB e li restituisce come array.
 */
function getAllProductsFromDB() {
  return new Promise((resolve, reject) => {
    const tx = db.transaction(["products"], "readonly");
    const store = tx.objectStore("products");
    const req = store.getAll();
    req.onsuccess = (e) => resolve(e.target.result);
    req.onerror = (err) => reject(err);
  });
}

/* =========================================
   FUNZIONI DI AGGIORNAMENTO CATALOGO
   ========================================= */
/**
 * Scarica le immagini a blocchi (chunk) per evitare di sovraccaricare il server.
 * @param {Array} products - Array di prodotti (ciascuno con product.immagine).
 * @param {Number} chunkSize - Dimensione del blocco (default 50).
 */
// Nuova funzione da aggiungere a assets/js/main.js

/**
 * Scarica solo i prodotti modificati (rispetto all'ultimo aggiornamento)
 * e le nuove immagini.
 */
async function downloadModifiedProducts() {
  console.log("downloadModifiedProducts chiamato");
  showProgressBar();

  try {
    if (!navigator.onLine) {
      alert("Sei offline, impossibile aggiornare il catalogo.");
      hideProgressBar();
      return;
    }

    // 1) Ottieni la versione locale attualmente salvata
    const localVersion = localStorage.getItem("jingb2b_version") || "1970-01-01 00:00:00";
    
    // 2) Richiedi i dati modificati dall'ultimo aggiornamento
    const response = await $.ajax({
      url: "/api/getModifiedData.php",
      type: "POST",
      dataType: "json",
      contentType: "application/json",
      data: JSON.stringify({ 
        last_update: localVersion 
      }),
    });
    
    if (!response.success) {
      console.log("Errore getModifiedData:", response.error);
      hideProgressBar();
      return;
    }
    
    // 3) Estrai i dati dalla risposta
    const prodottiModificati = response.prodotti_modificati || [];
    const eansImmaginiModificate = response.immagini_modificate || [];
    const nuovaVersione = response.last_update;
    
    console.log(`Prodotti modificati: ${prodottiModificati.length}`);
    console.log(`Immagini modificate: ${eansImmaginiModificate.length}`);
    
    // 4) Se ci sono aggiornamenti...
    if (prodottiModificati.length > 0) {
      // Ottieni tutti i prodotti dal DB locale
      const prodottiEsistenti = await getAllProductsFromDB();
      
      // Crea un dizionario per accesso rapido
      const prodottiMap = {};
      prodottiEsistenti.forEach(prod => {
        prodottiMap[prod.ean] = prod;
      });
      
      // Aggiorna il dizionario con i nuovi dati
      prodottiModificati.forEach(prod => {
        prodottiMap[prod.ean] = prod;
      });
      
      // Converti in array e salva in IndexedDB
      const prodottiAggiornati = Object.values(prodottiMap);
      await saveProductsToDB(prodottiAggiornati);
    }
    
    // 5) Se ci sono immagini modificate, scaricale
    if (eansImmaginiModificate.length > 0) {
      // Filtra prodotti con immagini modificate
      const prodottiConImmaginiModificate = (prodottiModificati.length > 0 ? prodottiModificati : await getAllProductsFromDB())
        .filter(prod => eansImmaginiModificate.includes(prod.ean));
      
      // Scarica solo le immagini modificate
      if (prodottiConImmaginiModificate.length > 0) {
        await prefetchImagesInChunks(prodottiConImmaginiModificate, 10);
      }
    }
    
    // 6) Aggiorna la versione locale
    localStorage.setItem("jingb2b_version", nuovaVersione);
    
    // 7) Notifica all'utente
    hideProgressBar();
    alert(`Aggiornamento completato:\n- ${prodottiModificati.length} prodotti aggiornati\n- ${eansImmaginiModificate.length} immagini aggiornate`);
    
    // 8) Ricarica i prodotti dal DB
    renderProductsFromDB();
    
  } catch (err) {
    console.error("downloadModifiedProducts() error:", err);
    hideProgressBar();
  }
}
/**
 * Verifica se esiste una versione più recente del catalogo
 * (usando l'endpoint /api/getLastUpdate.php) e, se necessario,
 * chiede all'utente se vuole scaricare tutto.
 */
async function checkAndUpdateCatalog() {
  try {
    // Se siamo offline, saltiamo
    if (!navigator.onLine) {
      console.log("Offline, salto checkAndUpdateCatalog");
      return;
    }

    const resp = await $.ajax({
      url: "/api/getLastUpdate.php",
      dataType: "json",
    });
    if (!resp.success) {
      console.log("Errore getLastUpdate:", resp.error);
      return;
    }

    const serverVersion = resp.lastUpdate;
    const localVersion = localStorage.getItem("jingb2b_version") || "";

    // Se c'è una versione più nuova
    if (serverVersion > localVersion) {
      if (confirm("Nuova versione del catalogo disponibile. Vuoi aggiornare?")) {
        // Verifica se è la prima installazione o un aggiornamento
        if (!localVersion) {
          // Prima installazione: scarica tutto
          await downloadAllProducts();
        } else {
          // Aggiornamento: scarica solo le modifiche
          await downloadModifiedProducts();
        }
        
        localStorage.setItem("jingb2b_version", serverVersion);
        // Ricarichiamo i prodotti dal DB e li mostriamo
        renderProductsFromDB();
      }
    }
  } catch (err) {
    console.log("checkAndUpdateCatalog() error:", err);
  }
}
/**
 * Scarica l'elenco completo dei prodotti (POST action:list) e
 * li salva su IndexedDB, poi pre-carica le immagini (facoltativo).
 */
async function downloadAllProducts() {
  console.log("downloadAllProducts chiamato");

  try {
    if (!navigator.onLine) {
      alert("Sei offline, impossibile scaricare il catalogo completo.");
      return;
    }

    // Esempio: supponiamo che ogni immagine sia ~8 KB, e hai ~2000 prodotti => ~16 MB
    // Aggiungi un margine, diciamo 20 MB
    let approxNeededMB = 20;

    // 1) Controlla spazio e chiedi storage persistente se necessario
    await checkAndRequestStorage(approxNeededMB);

    // 2) Scarica l'elenco prodotti
    const response = await $.ajax({
      url: "/api/proxy_request.php",
      type: "POST",
      dataType: "json",
      contentType: "application/json",
      data: JSON.stringify({ action: "list" }),
    });
    if (!response.success) {
      console.log("Errore proxy_request:", response.error);
      return;
    }
    const products = response.data;

    // 3) Salva in IndexedDB
    await saveProductsToDB(products);

    // 4) Pre-caricamento immagini con chunk e timeout
    showProgressBar();
    const missingImages = await prefetchImagesInChunks(products, 50, 5000);
    hideProgressBar();

    // 5) Report finale
    if (missingImages.length > 0) {
      console.warn("Immagini non caricate o in errore:", missingImages);
      alert(
        `Catalogo aggiornato con successo, ma ${missingImages.length} immagini non sono state caricate (vedi console).`
      );
    } else {
      alert("Catalogo aggiornato con successo! Tutte le immagini caricate.");
    }

    // Imposta il flag di catalogo caricato e inizializza i filtri se le categorie sono pronte
    catalogLoaded = true;
    initializeFiltersIfReady();
  } catch (err) {
    console.error("downloadAllProducts() error:", err);
    hideProgressBar();
  }
}
/**
 * Verifica lo spazio disponibile e, se richiesto, prova a chiedere storage persistente.
 * @param {number} approxNeededMB - spazio (in MB) stimato necessario.
 * @returns {Promise<void>}
 */
async function checkAndRequestStorage(approxNeededMB) {
  if (!("storage" in navigator && "estimate" in navigator.storage)) {
    console.log(
      "API StorageManager non supportata, impossibile stimare spazio."
    );
    return;
  }

  try {
    const { usage, quota } = await navigator.storage.estimate();
    // usage e quota sono in byte
    const usageMB = usage / (1024 * 1024);
    const quotaMB = quota / (1024 * 1024);

    console.log(
      `Spazio usato: ${usageMB.toFixed(2)} MB / Quota: ${quotaMB.toFixed(2)} MB`
    );

    if (usageMB + approxNeededMB > quotaMB) {
      alert(
        `Attenzione: potresti non avere abbastanza spazio. Ti servono ~${approxNeededMB} MB, ma la quota è ~${quotaMB.toFixed(
          2
        )} MB. Provo a chiedere lo storage persistente...`
      );
    }

    // Prova a chiedere storage persistente (non sempre funziona)
    if (navigator.storage && navigator.storage.persist) {
      const granted = await navigator.storage.persist();
      console.log("Persist storage granted?", granted);
      if (!granted && usageMB + approxNeededMB > quotaMB) {
        alert(
          "Spazio insufficiente, o impossibile ottenere storage persistente. Potrebbero verificarsi errori di quota."
        );
      }
    }
  } catch (err) {
    console.warn("Errore durante la stima dello spazio:", err);
  }
}

/**
 * Scarica le immagini a blocchi (chunk) con un timeout su ciascuna fetch.
 * Se un'immagine fallisce, la registra in missingImages.
 *
 * @param {Array} products - Array di prodotti con { immagine: 'nome.jpg', ... }
 * @param {Number} chunkSize - Dimensione del blocco, es. 50
 * @param {Number} timeoutMs - Timeout per ogni immagine (ms), es. 5000
 * @returns {Promise<Array>} - Ritorna un array di immagini mancanti (missingImages).
 */
async function prefetchImagesInChunks(products, chunkSize = 10) {
  let total = products.length;
  let count = 0;

  for (let i = 0; i < total; i += chunkSize) {
    const chunk = products.slice(i, i + chunkSize);

    await Promise.all(
      chunk.map(async (prod) => {
        // Salta se è "no-image.jpg"
        if (prod.immagine === "no-image.jpg") {
          // Aggiorna comunque la progress bar e basta

          count++;
          updateProgressBar(count, total);
          return;
        }

        const imageUrl = "/public/catalogo/" + prod.immagine;
        try {
          await fetch(imageUrl);
        } catch (err) {
          console.log("Impossibile scaricare immagine:", imageUrl, err);
        } finally {
          count++;
          updateProgressBar(count, total);
        }
      })
    );

    await new Promise((resolve) => setTimeout(resolve, 1500));
  }
}
/* =========================================
   FUNZIONI DI RENDER E RICHIESTE
   ========================================= */
/**
 * Funzione AJAX che chiama il proxy e poi mostra i prodotti.
 * Se offline, restituisce un errore di rete (catch).
 */
function aggiornaRichiesta(action) {
  if (!navigator.onLine) {
    alert("Sei offline, non posso chiamare il server. Uso DB locale.");
    renderProductsFromDB();
    return;
  }

  $.ajax({
    url: "/api/proxy_request.php",
    type: "POST",
    dataType: "json",
    contentType: "application/json",
    data: JSON.stringify({ action: action }),
    success: function (data) {
      console.log("Risposta AJAX:", data);
      if (data.success) {
        // Salviamo su IndexedDB
        saveProductsToDB(data.data).then(() => {
          renderCatalog(data.data);
        });
      } else {
        alert("Errore: " + data.error);
      }
    },
    error: function (xhr) {
      alert("Errore di rete: " + (xhr.statusText || "sconosciuto"));
    },
  });
}
/**
 * Renderizza il catalogo leggendo TUTTI i prodotti da IndexedDB
 * e passando i dati a "renderCatalog()".
 */

function renderProductsFromDB() {
  getAllProductsFromDB().then((products) => {
    // Imposta il flag di catalogo caricato
    catalogLoaded = true;
    initializeFiltersIfReady();

    // Se ci sono filtri attivi, applica il filtraggio
    if (
      currentFilters.categoria_id ||
      currentFilters.sottocategoria_id ||
      currentFilters.searchText
    ) {
      // Applica filtri
      const filteredProducts = products.filter((product) => {
        // Filtro categoria
        if (
          currentFilters.categoria_id &&
          product.categoria_id != currentFilters.categoria_id
        ) {
          return false;
        }

        // Filtro sottocategoria (se specificato)
        if (
          currentFilters.sottocategoria_id &&
          product.sottocategoria_id != currentFilters.sottocategoria_id
        ) {
          return false;
        }

        // Filtro ricerca testuale
        if (currentFilters.searchText) {
          const searchLower = currentFilters.searchText.toLowerCase();
          const titleMatch =
            product.titolo &&
            product.titolo.toLowerCase().includes(searchLower);
          const eanMatch =
            product.ean && product.ean.toLowerCase().includes(searchLower);
          const tagsMatch =
            product.tags && product.tags.toLowerCase().includes(searchLower);

          if (!(titleMatch || eanMatch || tagsMatch)) {
            return false;
          }
        }

        return true;
      });

      // Aggiorna contatore
      updateFilterCounter(filteredProducts.length, products.length);

      // Renderizza i prodotti filtrati
      renderCatalog(filteredProducts);
    } else {
      // Seleziona automaticamente la prima categoria se c'è
      if (hierarchyData.length > 0) {
        $("#categoriaFilter")
          .val("cat_" + hierarchyData[0].id)
          .trigger("change");
      } else {
        // Renderizza tutti i prodotti (nessun filtro attivo)
        renderCatalog(products);
      }
    }
  });
}
/**
 * Esempio di funzione "renderCatalog" che crea la griglia,
 * i blocchi, e popola le card. (Estratta dal tuo codice attuale)
 */
// Renderizza il catalogo a blocchi (IntersectionObserver)
function renderCatalog(allProducts) {
  var totalProducts = allProducts.length;
  var productsPerBlock = 40;
  var totalBlocks = Math.ceil(totalProducts / productsPerBlock);

  var $catalogContainer = $("#catalogContainer");
  $catalogContainer.empty();

  var $blocksContainer = $('<div id="blocksContainer"></div>');
  $catalogContainer.append($blocksContainer);

  for (var b = 0; b < totalBlocks; b++) {
    var $block = $(
      '<div class="block placeholder" data-block-index="' +
        b +
        '" data-loaded="false"></div>'
    );
    $blocksContainer.append($block);
  }

  var blockObserver = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        var $block = $(entry.target);
        if (entry.isIntersecting) {
          if ($block.data("loaded") !== true) {
            loadBlock($block);
          }
        } else {
          if ($block.data("loaded") === true) {
            unloadBlock($block);
          }
        }
      });
    },
    {
      root: document.getElementById("catalogContainer"),
      rootMargin: "1600px 0px",
      threshold: 0.01,
    }
  );

  $(".block").each(function () {
    blockObserver.observe(this);
  });

  function loadBlock($block) {
    var blockIndex = parseInt($block.data("block-index"));
    var startIndex = blockIndex * productsPerBlock;
    var endIndex = Math.min(startIndex + productsPerBlock, totalProducts);

    var products = allProducts.slice(startIndex, endIndex);
    var $content = $('<div class="block-content"></div>');

    products.forEach(function (product) {
      // Cloniamo il template
      var $card = $("#productTemplate .product-card").clone();

      // Mappiamo i campi
      $card.attr("data-ean", product.ean);
      $card.find(".ean-value").text(product.ean);
      $card.find(".product-title").text(product.titolo || "Prodotto");

      // Esempio: conf. pezzi
      $card
        .find(".product-box")
        .html(
          "<span class='pezzi'>Conf. <strong>" +
            (product.pezzi || 0) +
            "</strong> pezzi</span>"
        );
      $card.find(".product-disp").text(product.disponibilita || "");
      $card.find(".normal-price").text("€ " + (product.prezzo || "0"));

      // Se hai old_price e sconto:
      // ...
      // $card.find('.promo-block')...

      // Gestione immagine
      // Se "product.immagine" è definito:
      if (product.immagine && product.immagine !== "no-image.jpg") {
        var imagePath = "/public/catalogo/" + product.immagine;
        $card.find("img.imgLazy").attr("src", imagePath);
        // $card.find('img.lazy-img').attr('src', imagePath);
      } else {
        // Rimuove lazy se no immagine
        // $card.find('img.lazy-img').removeClass('lazy-img')
      }

      // Pulsanti + / -
      $card.find(".minus").on("click", function () {
        var $qty = $card.find(".qty");
        var $qty_display = $card.find(".counter_cart");
        var currentVal = parseInt($qty.val()) || 0;
        if (currentVal > 0) {
          $qty.val(currentVal - 1);
          $qty_display.text(currentVal - 1);
          if (currentVal - 1 <= 0) {
            $card.find(".wrap_counter_cart").fadeOut();
          }
        }
      });
      $card.find(".plus").on("click", function () {
        var $qty = $card.find(".qty");
        var $qty_display = $card.find(".counter_cart");
        var currentVal = parseInt($qty.val()) || 0;
        $qty.val(currentVal + 1);
        $qty_display.text(currentVal + 1);
        $card.find(".wrap_counter_cart").fadeIn();
      });

      $content.append($card);
    });

    $block.empty().append($content);
    $block.data("loaded", true).removeClass("placeholder");

    // Caricamento immagini in coda
    $block.find("img.lazy-img").each(function () {
      var $img = $(this);
      var finalSrc = $img.attr("data-src");
      $img.removeAttr("data-src");
      queueImageLoad($img, finalSrc);
    });

    var blockHeight = $block.outerHeight();
    $block.data("block-height", blockHeight);
  }

  function unloadBlock($block) {
    if (!$block.data("block-height")) {
      $block.data("block-height", $block.outerHeight());
    }
    $block.empty();
    $block.addClass("placeholder");
    $block.css("min-height", $block.data("block-height") + "px");
    $block.data("loaded", false);
  }
}

function queueImageLoad($img, finalSrc) {
  imageQueue.push({ $img: $img, src: finalSrc });
  processImageQueue();
}

function processImageQueue() {
  while (currentLoads < MAX_CONCURRENT_LOADS && imageQueue.length > 0) {
    var item = imageQueue.shift();
    loadSingleImage(item.$img, item.src);
  }
}

function loadSingleImage($img, finalSrc) {
  currentLoads++;
  var tempImg = new Image();
  tempImg.onload = function () {
    $img.attr("src", finalSrc);
    currentLoads--;
    processImageQueue();
  };
  tempImg.onerror = function () {
    $img.attr("src", "assets/img/no_image.jpg");
    currentLoads--;
    processImageQueue();
  };
  tempImg.src = finalSrc;
}

/* =========================================
   GESTIONE PROGRESS BAR (se usi downloadAllProducts)
   ========================================= */

function showProgressBar() {
  $("#progressBarContainer").show();
  $("#progressBar").css("width", "0%");
}
function updateProgressBar(completed, total) {
  let perc = Math.floor((completed / total) * 100);
  $("#progressBar").css("width", perc + "%");
}
function hideProgressBar() {
  $("#progressBarContainer").hide();
}

/* =========================================
   READY: avvio iniziale
   ========================================= */
// *** Inizializzazione "on document ready" ***
$(document).ready(function () {
  catalogLoaded = true;
  // Carica categorie se non già caricate
  if (typeof loadCategoriesHierarchy === "function") {
    loadCategoriesHierarchy();
  }

  // Inizializza IndexedDB
  initDB()
    .then(() => {
      // Carica le categorie
      loadCategoriesHierarchy();

      if (navigator.onLine) {
        // Controlla se il catalogo è da aggiornare
        checkAndUpdateCatalog();

        // Richiama la funzione che esegue la richiesta via proxy
        aggiornaRichiesta("list");
      } else {
        alert("Sei offline. Carico i dati da IndexedDB.");
        // Nessuna chiamata AJAX, mostriamo i dati presenti in DB
        renderProductsFromDB();
        // Imposta il flag che il catalogo è caricato
        catalogLoaded = true;
        initializeFiltersIfReady();
      }

      // Se torni online in un secondo momento
      window.addEventListener("online", () => {
        // Possiamo rifare la verifica e la richiesta
        checkAndUpdateCatalog();
        aggiornaRichiesta("list");
      });
    })
    .catch((err) => {
      console.error("Impossibile inizializzare IndexedDB:", err);
    });

  // Blocca mouse destro contestuale sulle immagini
  document.addEventListener("contextmenu", function (event) {
    if (event.target.tagName === "IMG") {
      event.preventDefault();
    }
  });
});

