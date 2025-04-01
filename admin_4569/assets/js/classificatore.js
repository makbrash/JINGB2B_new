// JavaScript Document classificatore.js

/**
 * classificatore.js - Funzionalità frontend per sistema classificazione prodotti
 * 
 * Questo file gestisce l'interazione utente e le comunicazioni AJAX
 * con il backend per il sistema di classificazione dei prodotti.
 */

// Configurazione
const config = {
    ajaxUrl: 'classificatore_ajax.php',
    batchSize: 5, // Numero prodotti elaborati in una volta
    autoProcessDelay: 1000, // Ritardo tra batch automatici (ms)
};

// Stato applicazione
const appState = {
    isProcessing: false,
    processingQueue: [],
    selectedProductIds: [],
    totalProcessed: 0,
    successCount: 0,
    errorCount: 0,
    stopRequested: false,
    currentPage: 1,
    productsPerPage: 20,
    productsList: [],
    lastSearchQuery: '',
    filterState: 'all', // 'all', 'pending', 'done', 'error'
};

// Inizializzazione quando DOM è pronto
$(document).ready(function() {
	
	
	// All'interno di $(document).ready, aggiungi:
    // Inizializza gestione filtri
    inizializzaPersistenzaFiltri();
    
    // Abilita modifica inline dei record
    abilitaModificaInlineRecord();
    
    // Inizializza statistiche switchabili
    inizializzaStatisticheSwitchable();
	
	if (typeof initAdvancedSelection === 'function') {
        initAdvancedSelection();
    }
	
	
    // Inizializza UI
    inizializzaUI();
    
    // Carica dati iniziali
    caricaStatistiche();
    caricaSelettori();
    
    // Imposta event handlers
    $('#search-form').on('submit', function(e) {
        e.preventDefault();
        cercaProdotti();
    });
    
    $('#range-form').on('submit', function(e) {
        e.preventDefault();
        caricaProdottiRange();
    });
    
    $('#btn-process-selected').on('click', function() {
        elaboraProdottiSelezionati();
    });
    
    $('#btn-reset-selected').on('click', function() {
        resetTagProdottiSelezionati();
    });
    
    $('#btn-select-all').on('click', function() {
        selezionaTuttiProdotti();
    });
    
    $('#btn-deselect-all').on('click', function() {
        deselezionaTuttiProdotti();
    });
    
    $('#stato-filter').on('change', function() {
        filtraProdottiPerStato($(this).val());
    });
    
    $('#btn-stop-processing').on('click', function() {
        if (appState.isProcessing) {
            if (confermaOperazione('Fermare l\'elaborazione in corso?')) {
                appState.stopRequested = true;
                $(this).prop('disabled', true);
                $('#processing-status').text('Arresto in corso...');
                mostraNotifica('Arresto elaborazione richiesto. Attendere...', 'warning');
                
                // Riabilita il pulsante dopo 5 secondi nel caso l'elaborazione non si fermi
                setTimeout(() => {
                    $(this).prop('disabled', false);
                }, 5000);
            }
        }
    });
    
    // Imposta handler per checkboxes prodotti (delegazione eventi)
    $('#products-container').on('change', '.product-checkbox', function() {
        const productId = $(this).val();
        if ($(this).is(':checked')) {
            if (!appState.selectedProductIds.includes(productId)) {
                appState.selectedProductIds.push(productId);
            }
        } else {
            const index = appState.selectedProductIds.indexOf(productId);
            if (index !== -1) {
                appState.selectedProductIds.splice(index, 1);
            }
        }
        
        aggiornaContatoreProdottiSelezionati();
    });
    
    // Handler per pulsante processo singolo prodotto
    $('#products-container').on('click', '.btn-process-product', function() {
        const productId = $(this).data('id');
        elaboraSingoloProdotto(productId);
    });
    
    // Handler per dettagli prodotto
    $('#products-container').on('click', '.product-title', function() {
        const productId = $(this).data('id');
        mostraDettagliProdotto(productId);
    });

    
    // Carica prodotti iniziali
    caricaProdotti();
    
    inizializzaRecuperoImmagini();
});


/**
 * Implementa la modifica inline dei campi nella tabella prodotti
 */




function inizializzaStatisticheSwitchable() {
    // Aggiungi i tab delle statistiche
    $('#stat-tabs').remove(); // Rimuovi se già esistenti
    
    $('#category-stats').before(`
        <div id="stat-tabs" class="stat-tabs">
            <button class="stat-tab active" data-stat="category">Distribuzione categorie</button>
            <button class="stat-tab" data-stat="marca">Marche principali</button>
            <button class="stat-tab" data-stat="tag">Tag più utilizzati</button>
            <button class="stat-tab" data-stat="subcategory">Sottocategorie</button>
        </div>
    `);
    
    // Inizialmente nascondi tutti tranne la categoria
    $('#brand-stats, #tag-cloud').hide();
    
    // Aggiungi contenitore per sottocategorie se non esiste
    if (!$('#subcategory-stats').length) {
        $('#category-stats').after('<div id="subcategory-stats" style="display: none;"></div>');
    }
    
    // Aggiungi handler per cambio tab
    $('#stat-tabs').on('click', '.stat-tab', function() {
        const statType = $(this).data('stat');
        
        // Attiva il tab corrente
        $('#stat-tabs .stat-tab').removeClass('active');
        $(this).addClass('active');
        
        // Nascondi tutti i contenuti delle statistiche
        $('#category-stats, #brand-stats, #tag-cloud, #subcategory-stats').hide();
        
        // Mostra il contenuto richiesto
        switch (statType) {
            case 'category':
                $('#category-stats').show();
                break;
            case 'marca':
                $('#brand-stats').show();
                break;
            case 'tag':
                $('#tag-cloud').show();
                break;
            case 'subcategory':
                $('#subcategory-stats').show();
                break;
        }
    });
    
    // Aggiungi stili per i tab se non esistono già
    if (!$('#stat-tabs-styles').length) {
        $('head').append(`
            <style id="stat-tabs-styles">
                .stat-tabs {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 5px;
                    margin-bottom: 15px;
                    border-bottom: 1px solid #ddd;
                    padding-bottom: 10px;
                }
                
                .stat-tab {
                    background-color: #f0f0f0;
                    border: 1px solid #ddd;
                    padding: 6px 12px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 13px;
                    transition: all 0.2s;
                }
                
                .stat-tab:hover {
                    background-color: #e0e0e0;
                }
                
                .stat-tab.active {
                    background-color: #007bff;
                    color: white;
                    border-color: #0069d9;
                }
                
                .stat-bar-item {
                    cursor: pointer;
                    transition: all 0.2s;
                }
                
                .stat-bar-item:hover {
                    background-color: #f0f8ff;
                }
                
                #tag-cloud .tag {
                    cursor: pointer;
                    transition: all 0.2s;
                }
                
                #tag-cloud .tag:hover {
                    background-color: #007bff;
                    color: white;
                }
            </style>
        `);
    }
}

function mostraStatistiche(stats) {
    // Aggiorna dati statistiche
    $('#stat-total').text(stats.totale_prodotti);
    $('#stat-processed').text(stats.prodotti_elaborati);
    $('#stat-pending').text(stats.prodotti_da_elaborare);
    $('#stat-error').text(stats.prodotti_in_errore);
    $('#stat-unique-tags').text(stats.tag_unici);
    
    // Aggiorna tag cloud con supporto per click
    let tagCloudHtml = '';
    if (stats.tag_popolari && stats.tag_popolari.length > 0) {
        stats.tag_popolari.forEach(function(tag) {
            const fontSize = Math.min(10 + Math.log2(tag.count) * 2, 24);
            tagCloudHtml += `<span class="tag" data-tag="${tag.tag}" style="font-size: ${fontSize}px" title="Usato ${tag.count} volte">${tag.tag} (${tag.count})</span> `;
        });
    } else {
        tagCloudHtml = '<em>Nessun tag disponibile</em>';
    }
    
    $('#tag-cloud').html(tagCloudHtml);
    
    // Aggiungi handler di click per filtrare per tag
    $('#tag-cloud').off('click', '.tag').on('click', '.tag', function() {
        const tagName = $(this).data('tag');
        $('#search-query').val(tagName);
        cercaProdotti();
    });
    
    // Aggiorna grafici categorie con supporto per click
    let categoriesHtml = '';
    if (stats.prodotti_per_categoria && stats.prodotti_per_categoria.length > 0) {
        stats.prodotti_per_categoria.forEach(function(cat) {
            const percentage = (cat.count / stats.totale_prodotti * 100).toFixed(1);
            categoriesHtml += `
                <div class="stat-bar-item" data-categoria-id="${cat.id}">
                    <div class="stat-bar-label">${cat.nome}</div>
                    <div class="stat-bar-container">
                        <div class="stat-bar-fill" style="width: ${percentage}%"></div>
                    </div>
                    <div class="stat-bar-value">${cat.count} (${percentage}%)</div>
                </div>
            `;
        });
    } else {
        categoriesHtml = '<em>Nessuna categoria disponibile</em>';
    }
    
    $('#category-stats').html(categoriesHtml);
    
    // Aggiungi handler di click per filtrare per categoria
    $('#category-stats').off('click', '.stat-bar-item').on('click', '.stat-bar-item', function() {
        const categoriaId = $(this).data('categoria-id');
        $('#categoria-filter').val(categoriaId).trigger('change');
        caricaProdotti();
    });
    
    // Aggiorna grafici marche con supporto per click
    let brandsHtml = '';
    if (stats.prodotti_per_marca && stats.prodotti_per_marca.length > 0) {
        stats.prodotti_per_marca.forEach(function(marca) {
            if (marca.count > 0) {
                const percentage = (marca.count / stats.totale_prodotti * 100).toFixed(1);
                brandsHtml += `
                    <div class="stat-bar-item" data-marca-id="${marca.id}">
                        <div class="stat-bar-label">${marca.nome}</div>
                        <div class="stat-bar-container">
                            <div class="stat-bar-fill" style="width: ${percentage}%"></div>
                        </div>
                        <div class="stat-bar-value">${marca.count} (${percentage}%)</div>
                    </div>
                `;
            }
        });
    } else {
        brandsHtml = '<em>Nessuna marca disponibile</em>';
    }
    
    $('#brand-stats').html(brandsHtml);
    
    // Aggiungi handler di click per filtrare per marca
    $('#brand-stats').off('click', '.stat-bar-item').on('click', '.stat-bar-item', function() {
        const marcaId = $(this).data('marca-id');
        $('#marca-filter').val(marcaId).trigger('change');
        caricaProdotti();
    });
    
    // Aggiungi statistiche sottocategorie
    mostraStatisticheSottocategorie(stats);
    
    // Assicurati che i tab siano inizializzati
    inizializzaStatisticheSwitchable();
}

function mostraStatisticheSottocategorie(stats) {
    // Se non abbiamo i dati necessari, facciamo una richiesta apposita
    if (!stats.prodotti_per_sottocategoria) {
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_subcategory_stats'
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderizzaStatisticheSottocategorie(response.data.prodotti_per_sottocategoria);
                }
            }
        });
    } else {
        renderizzaStatisticheSottocategorie(stats.prodotti_per_sottocategoria);
    }
}


function renderizzaStatisticheSottocategorie(sottocategorie) {
    let html = '';
    
    if (sottocategorie && sottocategorie.length > 0) {
        // Calcola il totale per le percentuali
        let totaleProdotti = 0;
        sottocategorie.forEach(function(subcat) {
            totaleProdotti += subcat.count;
        });
        
        // Ordina per conteggio decrescente
        sottocategorie.sort(function(a, b) {
            return b.count - a.count;
        });
        
        // Limita a 15 sottocategorie più popolari per non affollare la UI
        const topSubcats = sottocategorie.slice(0, 15);
        
        topSubcats.forEach(function(subcat) {
            const percentage = (subcat.count / totaleProdotti * 100).toFixed(1);
            html += `
                <div class="stat-bar-item" data-sottocategoria-id="${subcat.id}" data-categoria-id="${subcat.categoria_id}">
                    <div class="stat-bar-label">${subcat.nome}</div>
                    <div class="stat-bar-container">
                        <div class="stat-bar-fill" style="width: ${percentage}%"></div>
                    </div>
                    <div class="stat-bar-value">${subcat.count} (${percentage}%)</div>
                </div>
            `;
        });
    } else {
        html = '<em>Nessuna sottocategoria disponibile</em>';
    }
    
    $('#subcategory-stats').html(html);
    
    // Aggiungi handler di click per filtrare per sottocategoria
    $('#subcategory-stats').off('click', '.stat-bar-item').on('click', '.stat-bar-item', function() {
        const categoriaId = $(this).data('categoria-id');
        const sottocategoriaId = $(this).data('sottocategoria-id');
        
        // Imposta prima la categoria per garantire che le sottocategorie siano disponibili
        $('#categoria-filter').val(categoriaId).trigger('change');
        
        // Imposta la sottocategoria con un breve ritardo per assicurarsi che il select sia stato aggiornato
        setTimeout(function() {
            $('#sottocategoria-filter').val(sottocategoriaId);
            caricaProdotti();
        }, 100);
    });
}


function inizializzaUI() {
    // Nascondi pulsante stop all'inizio
    $('#btn-stop-processing').hide();
    
    // Imposta valori predefiniti per range
    $('#range_start').val('1');
    $('#range_end').val('50');
	
	// Aggiungi questa parte nella funzione inizializzazione
$('#categoria-filter, #sottocategoria-filter, #marca-filter').on('change', function() {
    caricaProdotti();
});



// E poi aggiungi questo event handler
$('#limit-selector').on('change', function() {
    appState.productsPerPage = parseInt($(this).val());
    caricaProdotti();
});


}

function caricaStatistiche() {
    $.ajax({
        url: config.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'get_stats'
        },
        success: function(response) {
            if (response.success) {
                mostraStatistiche(response.data);
            } else {
                mostraNotifica('Errore nel caricamento delle statistiche: ' + response.message, 'error');
            }
        },
        error: function() {
            mostraNotifica('Errore di connessione al server7', 'error');
        }
    });
}

function mostraStatistiche(stats) {
    // Aggiorna dati statistiche
    $('#stat-total').text(stats.totale_prodotti);
    $('#stat-processed').text(stats.prodotti_elaborati);
    $('#stat-pending').text(stats.prodotti_da_elaborare);
    $('#stat-error').text(stats.prodotti_in_errore);
    $('#stat-unique-tags').text(stats.tag_unici);
    
    // Aggiorna tag cloud
    let tagCloudHtml = '';
    stats.tag_popolari.forEach(function(tag) {
        const fontSize = Math.min(10 + Math.log2(tag.count) * 2, 24);
        tagCloudHtml += `<span class="tag" style="font-size: ${fontSize}px" title="Usato ${tag.count} volte">${tag.tag} (${tag.count})</span> `;
    });
    
    $('#tag-cloud').html(tagCloudHtml);
    
    // Aggiorna grafici categorie e marche
    let categoriesHtml = '';
    stats.prodotti_per_categoria.forEach(function(cat) {
        const percentage = (cat.count / stats.totale_prodotti * 100).toFixed(1);
        categoriesHtml += `
            <div class="stat-bar-item">
                <div class="stat-bar-label">${cat.nome}</div>
                <div class="stat-bar-container">
                    <div class="stat-bar-fill" style="width: ${percentage}%"></div>
                </div>
                <div class="stat-bar-value">${cat.count} (${percentage}%)</div>
            </div>
        `;
    });
    
    $('#category-stats').html(categoriesHtml);
    
    let brandsHtml = '';
    stats.prodotti_per_marca.forEach(function(marca) {
        if (marca.count > 0) {
            const percentage = (marca.count / stats.totale_prodotti * 100).toFixed(1);
            brandsHtml += `
                <div class="stat-bar-item">
                    <div class="stat-bar-label">${marca.nome}</div>
                    <div class="stat-bar-container">
                        <div class="stat-bar-fill" style="width: ${percentage}%"></div>
                    </div>
                    <div class="stat-bar-value">${marca.count} (${percentage}%)</div>
                </div>
            `;
        }
    });
    
    $('#brand-stats').html(brandsHtml);
}

function caricaSelettori() {
    $.ajax({
        url: config.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'get_selectors'
        },
        success: function(response) {
            if (response.success) {
                // Selettore categorie
                let categorieHtml = '<option value="">Tutte le categorie</option>';
                response.data.categorie.forEach(function(cat) {
                    categorieHtml += `<option value="${cat.id}">${cat.nome}</option>`;
                });
                $('#categoria-filter').html(categorieHtml);
                
                // Selettore sottocategorie (salvate in data attribute per filtro dinamico)
                $('#categoria-filter').data('sottocategorie', response.data.sottocategorie);
                
                // Selettore marche
                let marcheHtml = '<option value="">Tutte le marche</option>';
                response.data.marche.forEach(function(marca) {
                    marcheHtml += `<option value="${marca.id}">${marca.nome}</option>`;
                });
                $('#marca-filter').html(marcheHtml);
            } else {
                mostraNotifica('Errore nel caricamento dei selettori: ' + response.message, 'error');
            }
        },
        error: function() {
            mostraNotifica('Errore di connessione al server1', 'error');
        }
    });
    
    // Event handler per aggiornare sottocategorie
    $('#categoria-filter').on('change', function() {
        aggiornaSelettoreSottocategorie($(this).val());
    });
}

function aggiornaSelettoreSottocategorie(categoriaId) {
    const sottocategorie = $('#categoria-filter').data('sottocategorie');
    let sottocategorieHtml = '<option value="">Tutte le sottocategorie</option>';
    
    if (categoriaId) {
        sottocategorie.forEach(function(subcat) {
            if (subcat.categoria_id == categoriaId) {
                sottocategorieHtml += `<option value="${subcat.id}">${subcat.nome}</option>`;
            }
        });
    }
    
    $('#sottocategoria-filter').html(sottocategorieHtml);
}

function caricaProdotti(options = {}) {
    // Visualizza loader
    $('#products-loader').show();
    
    // Imposta opzioni predefinite
    const defaultOptions = {
        range_start: $('#range_start').val() || 0,
        range_end: $('#range_end').val() || 0,
        stato_tag: appState.filterState === 'all' ? '' : 
                  (appState.filterState === 'pending' ? 0 : 
                   appState.filterState === 'done' ? 1 : 2),
        categoria_id: $('#categoria-filter').val() || '',
        sottocategoria_id: $('#sottocategoria-filter').val() || '',
        marca_id: $('#marca-filter').val() || '',
        limit: $('#limit-selector').val() || appState.productsPerPage, // Se hai un selettore di limite
        page: appState.currentPage
    };
    
    // Log per debug
    console.log('Opzioni caricamento prodotti:', {...defaultOptions, ...options});
    
    // Merge opzioni con defaults
    const params = {...defaultOptions, ...options};
    
    // Aggiungi action
    params.action = 'get_products';
    
    $.ajax({
        url: config.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: params,
        success: function(response) {
            if (response.success) {
                // Salva i prodotti nello stato
                appState.productsList = response.data;
                
                // Visualizza prodotti
                mostraProdotti(response.data);
                
                // Nascondi loader
                $('#products-loader').hide();
                
                // Mostra notifica
                mostraNotifica(response.message, 'success');
            } else {
                $('#products-loader').hide();
                mostraNotifica('Errore nel caricamento dei prodotti: ' + response.message, 'error');
            }
        },
        error: function() {
            $('#products-loader').hide();
            mostraNotifica('Errore di connessione al server2', 'error');
        }
    });
}

function caricaProdottiRange() {
    const rangeStart = $('#range_start').val();
    const rangeEnd = $('#range_end').val();
    
    if (!rangeStart || !rangeEnd) {
        mostraNotifica('Inserisci un range valido', 'warning');
        return;
    }
    
    // Reset selezione e pagina
    appState.selectedProductIds = [];
    appState.currentPage = 1;
    
    caricaProdotti({
        range_start: rangeStart,
        range_end: rangeEnd
    });
}


function cercaProdotti() {
    const query = $('#search-query').val();
    
    if (!query) {
        mostraNotifica('Inserisci un termine di ricerca', 'warning');
        return;
    }
    
    appState.lastSearchQuery = query;
    
    // Reset selezione e pagina
    appState.selectedProductIds = [];
    appState.currentPage = 1;
    
    // Visualizza loader
    $('#products-loader').show();
    
    $.ajax({
        url: config.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'search_products',
            query: query,
            limit: 100 // Limite più alto per ricerche
        },
        success: function(response) {
            if (response.success) {
                // Salva i prodotti nello stato
                appState.productsList = response.data;
                
                // Visualizza prodotti
                mostraProdotti(response.data);
                
                // Nascondi loader
                $('#products-loader').hide();
                
                // Mostra notifica
                mostraNotifica(`Trovati ${response.data.length} prodotti per "${query}"`, 'success');
            } else {
                $('#products-loader').hide();
                mostraNotifica('Errore nella ricerca: ' + response.message, 'error');
            }
        },
        error: function() {
            $('#products-loader').hide();
            mostraNotifica('Errore di connessione al server3', 'error');
        }
    });
}


function filtraProdottiPerStato(stato) {
    appState.filterState = stato;
    caricaProdotti();
}

function mostraProdotti(products) {
    if (!products || products.length === 0) {
        $('#products-container').html('<div class="empty-state">Nessun prodotto trovato</div>');
        return;
    }
    
    let html = `
        <table class="products-table">
            <thead>
                <tr>
                    <th width="30"><input type="checkbox" id="select-all-checkbox"></th>
                    <th width="60">ID</th>
                    <th width="70">Immagine</th>
                    <th width="250">Titolo</th>
                    <th width="120">Categoria</th>
                    <th width="120">Sottocategoria</th>
                    <th width="120">Marca</th>
                    <th width="200">Tags</th>
                    <th width="80">Stato</th>
                    <th width="120">Azioni</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    products.forEach(function(product) {
        // Determina la classe di stato
        let statusClass = '';
        let statusText = '';
        
        switch(product.stato_tag) {
            case 0:
                statusClass = 'status-pending';
                statusText = 'Da elaborare';
                break;
            case 1:
                statusClass = 'status-done';
                statusText = 'Elaborato';
                break;
            case 2:
                statusClass = 'status-error';
                statusText = 'Errore';
                break;
            default:
                statusClass = 'status-unknown';
                statusText = 'Sconosciuto';
        }
        
        // Formatta i tag
        let tagsHtml = '';
        if (product.tags && product.tags.length > 0) {
            tagsHtml = '<div class="tag-container">';
            product.tags.forEach(function(tag) {
                tagsHtml += `<span class="tag">${tag}</span>`;
            });
            tagsHtml += '</div>';
        } else {
            tagsHtml = '<em>Nessun tag</em>';
        }
        
        // Formatta percorso immagine
        const imgSrc = product.immagine ? `../public/catalogo/${product.immagine}` : 'img/no-image.jpg';
        
        // Determina se il prodotto è selezionato
        const isChecked = appState.selectedProductIds.includes(product.id.toString()) ? 'checked' : '';
        
        html += `
            <tr data-id="${product.id}" class="${statusClass}">
                <td><input type="checkbox" class="product-checkbox" value="${product.id}" ${isChecked}></td>
                <td>${product.id}</td>
                <td><img src="${imgSrc}" class="product-thumbnail" alt="Immagine prodotto"></td>
                <td>
                    ${product.ean ? `<div class="product-ean">${product.ean}</div>` : ''}
                    <a href="#" class="product-title" data-id="${product.id}">${product.titolo}</a>
                </td>
                <td class="editable-cell" data-field-type="categoria" data-value="${product.categoria_id}">${product.categoria_nome}</td>
                <td class="editable-cell" data-field-type="sottocategoria" data-value="${product.sottocategoria_id}">${product.sottocategoria_nome}</td>
                <td class="editable-cell" data-field-type="marca" data-value="${product.marca_id}">${product.marca_nome}</td>
                <td class="editable-cell" data-field-type="tags" data-value='${JSON.stringify(product.tags)}'>${tagsHtml}</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td>
                    <button class="btn-process-product" data-id="${product.id}">Elabora</button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    
    $('#products-container').html(html);
    
    // Handler per checkbox "seleziona tutti"
    $('#select-all-checkbox').on('change', function() {
        if ($(this).is(':checked')) {
            selezionaTuttiProdotti();
        } else {
            deselezionaTuttiProdotti();
        }
    });
    
    // Assicurati che le funzionalità di modifica inline siano attive
    aggiungiStiliModificaInline();
}


function selezionaTuttiProdotti() {
    $('.product-checkbox').prop('checked', true);
    appState.selectedProductIds = appState.productsList.map(p => p.id.toString());
    aggiornaContatoreProdottiSelezionati();
}

function deselezionaTuttiProdotti() {
    $('.product-checkbox').prop('checked', false);
    appState.selectedProductIds = [];
    aggiornaContatoreProdottiSelezionati();
}

function aggiornaContatoreProdottiSelezionati() {
    const count = appState.selectedProductIds.length;
    $('#selected-count').text(count);
    
    // Abilita/disabilita pulsanti in base alla selezione
    if (count > 0) {
        $('#btn-process-selected, #btn-reset-selected').prop('disabled', false);
    } else {
        $('#btn-process-selected, #btn-reset-selected').prop('disabled', true);
    }
}

function elaboraProdottiSelezionati() {
    if (appState.selectedProductIds.length === 0) {
        mostraNotifica('Seleziona almeno un prodotto', 'warning');
        return;
    }
    
    // Conferma elaborazione
    if (!confirm(`Elaborare ${appState.selectedProductIds.length} prodotti selezionati?`)) {
        return;
    }
    
    // Imposta coda di elaborazione
    appState.processingQueue = [...appState.selectedProductIds];
    appState.totalProcessed = 0;
    appState.successCount = 0;
    appState.errorCount = 0;
    appState.stopRequested = false;
    
    // Mostra interfaccia elaborazione
    mostraInterfacciaElaborazione();
    
    // Avvia elaborazione
    elaboraProssimiProdotti();
}

function elaboraProssimiProdotti() {
    if (appState.stopRequested) {
        completaElaborazione('Elaborazione interrotta');
        return;
    }
    
    // Se la coda è vuota, termina elaborazione
    if (appState.processingQueue.length === 0) {
        completaElaborazione('Elaborazione completata');
        return;
    }
    
    // Ottieni prossimo batch di prodotti
    const batchSize = Math.min(config.batchSize, appState.processingQueue.length);
    const currentBatch = appState.processingQueue.splice(0, batchSize);
    
    // Aggiorna UI
    appState.isProcessing = true;
    $('#processing-status').text(`Elaborazione in corso: ${appState.totalProcessed} di ${appState.totalProcessed + appState.processingQueue.length}`);
    
    // Elabora batch
    $.ajax({
        url: config.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'batch_classify',
            product_ids: currentBatch
        },
        success: function(response) {
            if (response.success) {
                // Aggiorna contatori
                appState.totalProcessed += currentBatch.length;
                appState.successCount += response.data.successo;
                appState.errorCount += response.data.errore;
                
                // Aggiorna UI con risultati
                aggiornaUIElaborazione();
                
                // Aggiorna singole righe prodotti
                response.data.dettagli.forEach(function(result) {
                    aggiornaRigaProdotto(result);
                });
                
                // Continua elaborazione dopo breve ritardo
                setTimeout(elaboraProssimiProdotti, config.autoProcessDelay);
            } else {
                mostraNotifica('Errore nell\'elaborazione batch: ' + response.message, 'error');
                completaElaborazione('Elaborazione interrotta per errore');
            }
        },
        error: function() {
            mostraNotifica('Errore di connessione al server4', 'error');
            completaElaborazione('Elaborazione interrotta per errore di connessione');
        }
    });
}


function aggiornaUIElaborazione() {
    // Aggiorna progresso
    const total = appState.totalProcessed + appState.processingQueue.length;
    const progress = Math.round((appState.totalProcessed / total) * 100);
    
    $('#processing-progress').css('width', progress + '%');
    $('#processing-status').text(`Elaborazione in corso: ${appState.totalProcessed} di ${total}`);
    $('#processing-success').text(appState.successCount);
    $('#processing-error').text(appState.errorCount);
}


function mostraInterfacciaElaborazione() {
    // Mostra UI elaborazione
    $('#processing-container').show();
    $('#btn-stop-processing').show();
    
    // Nascondi pulsanti di azione
    $('#btn-process-selected, #btn-reset-selected').hide();
    
    // Reset progresso
    $('#processing-progress').css('width', '0%');
    $('#processing-success').text('0');
    $('#processing-error').text('0');
}

function completaElaborazione(message) {
    // Resetta stato
    appState.isProcessing = false;
    appState.processingQueue = [];
    
    // Aggiorna UI
    $('#btn-stop-processing').hide();
    $('#btn-process-selected, #btn-reset-selected').show();
    
    // Mostra messaggio
    mostraNotifica(message + `: ${appState.successCount} successi, ${appState.errorCount} errori`, 'info');
    
    // Nascondi UI elaborazione dopo un attimo
    setTimeout(function() {
        $('#processing-container').hide();
    }, 3000);
    
    // Ricarica statistiche
    caricaStatistiche();
}

function stoppaElaborazione() {
    if (confirm('Sei sicuro di voler interrompere l\'elaborazione?')) {
        appState.stopRequested = true;
        mostraNotifica('Interruzione in corso...', 'warning');
    }
}

function elaboraSingoloProdotto(productId) {
    // Disabilita pulsante
    $(`.btn-process-product[data-id="${productId}"]`).prop('disabled', true).text('In corso...');
    
    $.ajax({
        url: config.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'classify_product',
            product_id: productId
        },
        success: function(response) {
            if (response.success) {
                mostraNotifica(`Prodotto ${productId} elaborato con successo`, 'success');
                aggiornaRigaProdotto(response.data);
            } else {
                mostraNotifica(`Errore nell'elaborazione del prodotto ${productId}: ${response.message}`, 'error');
                $(`.btn-process-product[data-id="${productId}"]`).prop('disabled', false).text('Riprova');
            }
        },
        error: function() {
            mostraNotifica('Errore di connessione al server5', 'error');
            $(`.btn-process-product[data-id="${productId}"]`).prop('disabled', false).text('Riprova');
        }
    });
}


function aggiornaRigaProdotto(result) {
    const productId = result.id;
    const row = $(`tr[data-id="${productId}"]`);
    
    if (row.length === 0) return; // Riga non trovata
    
    // Aggiorna stato
    let statusClass = '';
    let statusText = '';
    
    if (result.stato === 'successo') {
        statusClass = 'status-done';
        statusText = 'Elaborato';
        
        // Aggiorna dati
        row.find('td:eq(4)').text(result.nuovo.categoria.nome);
        row.find('td:eq(5)').text(result.nuovo.sottocategoria.nome);
        row.find('td:eq(6)').text(result.nuovo.marca.nome);
        
        // Aggiorna tags
        let tagsHtml = '';
        if (result.nuovo.tags && result.nuovo.tags.length > 0) {
            tagsHtml = '<div class="tag-container">';
            result.nuovo.tags.forEach(function(tag) {
                tagsHtml += `<span class="tag">${tag}</span>`;
            });
            tagsHtml += '</div>';
        } else {
            tagsHtml = '<em>Nessun tag</em>';
        }
        row.find('td:eq(7)').html(tagsHtml);
    } else {
        statusClass = 'status-error';
        statusText = 'Errore';
    }
    
    // Aggiorna classe e testo stato
    row.removeClass('status-pending status-done status-error')
       .addClass(statusClass);
    row.find('td:eq(8)').html(`<span class="status-badge ${statusClass}">${statusText}</span>`);
    
    // Reset pulsante
    row.find('.btn-process-product').prop('disabled', false).text('Elabora');
}

function resetTagProdottiSelezionati() {
    if (appState.selectedProductIds.length === 0) {
        mostraNotifica('Seleziona almeno un prodotto', 'warning');
        return;
    }
    
    // Conferma reset
    if (!confirm(`Resettare lo stato dei tag per ${appState.selectedProductIds.length} prodotti selezionati?`)) {
        return;
    }
    
    $.ajax({
        url: config.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'reset_tags',
            product_ids: appState.selectedProductIds
        },
        success: function(response) {
            if (response.success) {
                mostraNotifica(`Reset completato per ${response.data.count} prodotti`, 'success');
                
                // Ricarica prodotti e statistiche
                caricaProdotti();
                caricaStatistiche();
            } else {
                mostraNotifica('Errore nel reset dei tag: ' + response.message, 'error');
            }
        },
        error: function() {
            mostraNotifica('Errore di connessione al server6', 'error');
        }
    });
}


function mostraDettagliProdotto(productId) {
    // Trova prodotto nella lista
    const product = appState.productsList.find(p => p.id == productId);
    
    if (!product) {
        mostraNotifica('Prodotto non trovato', 'error');
        return;
    }
    
    // Formatta i tag
    let tagsHtml = '';
    if (product.tags && product.tags.length > 0) {
        tagsHtml = '<div class="tag-container">';
        product.tags.forEach(function(tag) {
            tagsHtml += `<span class="tag">${tag}</span>`;
        });
        tagsHtml += '</div>';
    } else {
        tagsHtml = '<em>Nessun tag</em>';
    }
    
    // Formatta percorso immagine
    const imgSrc = product.immagine ? `../public/catalogo/${product.immagine}` : 'img/no-image.jpg';
    
    // Costruisci contenuto modal
    const modalContent = `
        <div class="product-detail">
            <div class="product-detail-header">
                <h2>${product.titolo}</h2>
                <p class="product-id">ID: ${product.id}${product.ean ? ` | EAN: ${product.ean}` : ''}</p>
            </div>
            
            <div class="product-detail-body">
                <div class="product-detail-image">
                    <img src="${imgSrc}" alt="Immagine prodotto">
                </div>
                
                <div class="product-detail-info">
                    <table class="detail-table">
                        <tr>
                            <th>Categoria:</th>
                            <td>${product.categoria_nome}</td>
                        </tr>
                        <tr>
                            <th>Sottocategoria:</th>
                            <td>${product.sottocategoria_nome}</td>
                        </tr>
                        <tr>
                            <th>Marca:</th>
                            <td>${product.marca_nome}</td>
                        </tr>
                        <tr>
                            <th>Tags:</th>
                            <td>${tagsHtml}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="product-detail-actions">
                <button class="btn-process-single" data-id="${product.id}">Elabora questo prodotto</button>
                <button class="btn-close-modal">Chiudi</button>
            </div>
        </div>
    `;
    
    // Mostra modal
    $('#product-detail-modal .modal-content').html(modalContent);
    $('#product-detail-modal').show();
    
    // Event handlers per modal
    $('.btn-close-modal').on('click', function() {
        $('#product-detail-modal').hide();
    });
    
    $('.btn-process-single').on('click', function() {
        const productId = $(this).data('id');
        elaboraSingoloProdotto(productId);
        $('#product-detail-modal').hide();
    });
}

function mostraNotifica(message, type = 'info') {
    // Crea elemento notifica
    const notification = $(`<div class="notification ${type}">${message}</div>`);
    
    // Aggiungi alla lista
    $('#notifications').append(notification);
    
    // Rimuovi dopo alcuni secondi
    setTimeout(function() {
        notification.fadeOut(300, function() {
            notification.remove();
        });
    }, 5000);
}

/**
 * Funzione utility per richiedere conferma all'utente
 * @param {string} message Messaggio da mostrare nella finestra di conferma
 * @returns {boolean} true se l'utente ha confermato, false altrimenti
 */
function confermaOperazione(message) {
    return confirm(message);
}

/**
 * Implementa la modifica inline dei campi nella tabella prodotti
 */
function abilitaModificaInlineRecord() {
    // Delegazione evento per i clic sulle celle modificabili
    $('#products-container').on('click', '.editable-cell', function(e) {
        // Evita di aprire in caso di click su elementi interni
        if ($(e.target).hasClass('editable-cell-input') || 
            $(e.target).hasClass('editable-cell-select')) {
            return;
        }
        
        const $cell = $(this);
        const fieldType = $cell.data('field-type');
        const productId = $cell.closest('tr').data('id');
        const currentValue = $cell.data('value');
        
        // Se la cella è già in modalità modifica, non fare nulla
        if ($cell.hasClass('editing')) return;
        
        // Aggiungi classe editing
        $cell.addClass('editing');
        
        // Salva il contenuto originale
        const originalContent = $cell.html();
        
        // Gestisci la creazione dell'input in base al tipo di campo
        switch (fieldType) {
            case 'categoria':
                renderizzaSelectModifica($cell, 'categoria-filter', currentValue, function(newValue) {
                    salvaModificaRecord(productId, 'categoria_id', newValue, $cell, originalContent);
                });
                break;
                
            case 'sottocategoria':
                // Per sottocategoria, dobbiamo ottenere la categoria attuale
                const categoriaId = $cell.closest('tr').find('td[data-field-type="categoria"]').data('value');
                renderizzaSelectModificaSottocategoria($cell, categoriaId, currentValue, function(newValue) {
                    salvaModificaRecord(productId, 'sottocategoria_id', newValue, $cell, originalContent);
                });
                break;
                
            case 'marca':
                renderizzaSelectModifica($cell, 'marca-filter', currentValue, function(newValue) {
                    salvaModificaRecord(productId, 'marca_id', newValue, $cell, originalContent);
                });
                break;
                
            case 'tags':
                // Per i tag, mostriamo un campo di testo
                renderizzaInputModificaTag($cell, currentValue, function(newValue, mode) {
                    salvaModificaTagRecord(productId, newValue, mode, $cell, originalContent);
                });
                break;
        }
    });
    
    // Click fuori dal campo per annullare
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.editable-cell').length && 
            !$(e.target).closest('.editable-cell-input').length && 
            !$(e.target).closest('.editable-cell-select').length) {
            
            // Trova tutte le celle in modifica
            $('.editable-cell.editing').each(function() {
                const $cell = $(this);
                // Ripristina contenuto originale
                $cell.html($cell.data('original-content'));
                $cell.removeClass('editing');
            });
        }
    });
    
    // Previeni la propagazione del click sugli elementi di modifica
    $('#products-container').on('click', '.editable-cell-input, .editable-cell-select', function(e) {
        e.stopPropagation();
    });
}

/**
 * Renderizza un select per modifica in base al selettore di origine
 */
function renderizzaSelectModifica($cell, sourceSelectId, currentValue, onChangeCallback) {
    // Ottieni il contenuto originale per ripristino
    const originalContent = $cell.html();
    $cell.data('original-content', originalContent);
    
    // Crea un clone del selettore principale
    const $select = $(`<select class="editable-cell-select"></select>`);
    const options = $(`#${sourceSelectId} option`).clone();
    
    // Aggiungi le opzioni al nuovo select
    $select.append(options);
    
    // Imposta il valore corrente
    $select.val(currentValue);
    
    // Svuota la cella e aggiungi il selettore
    $cell.empty().append($select);
    
    // Focus sul selettore
    $select.focus();
    
    // Gestisci cambio
    $select.on('change', function() {
        const newValue = $(this).val();
        onChangeCallback(newValue);
    });
    
    // Gestisci tasto ESC per annullare
    $select.on('keydown', function(e) {
        if (e.keyCode === 27) { // ESC
            $cell.html(originalContent);
            $cell.removeClass('editing');
        }
    });
}

/**
 * Renderizza un select specifico per sottocategorie
 */
function renderizzaSelectModificaSottocategoria($cell, categoriaId, currentValue, onChangeCallback) {
    // Ottieni il contenuto originale per ripristino
    const originalContent = $cell.html();
    $cell.data('original-content', originalContent);
    
    // Crea un nuovo select
    const $select = $(`<select class="editable-cell-select"></select>`);
    
    // Ottieni le sottocategorie per la categoria selezionata
    const sottocategorie = $('#categoria-filter').data('sottocategorie');
    let options = '<option value="">-- Seleziona --</option>';
    
    if (sottocategorie) {
        sottocategorie.forEach(function(subcat) {
            if (subcat.categoria_id == categoriaId) {
                const selected = (subcat.id == currentValue) ? 'selected' : '';
                options += `<option value="${subcat.id}" ${selected}>${subcat.nome}</option>`;
            }
        });
    }
    
    // Aggiungi le opzioni al select
    $select.html(options);
    
    // Imposta il valore corrente
    $select.val(currentValue);
    
    // Svuota la cella e aggiungi il selettore
    $cell.empty().append($select);
    
    // Focus sul selettore
    $select.focus();
    
    // Gestisci cambio
    $select.on('change', function() {
        const newValue = $(this).val();
        onChangeCallback(newValue);
    });
    
    // Gestisci tasto ESC per annullare
    $select.on('keydown', function(e) {
        if (e.keyCode === 27) { // ESC
            $cell.html(originalContent);
            $cell.removeClass('editing');
        }
    });
}

/**
 * Renderizza input per modifica tags
 */
function renderizzaInputModificaTag($cell, currentValue, onSaveCallback) {
    // Ottieni il contenuto originale per ripristino
    const originalContent = $cell.html();
    $cell.data('original-content', originalContent);
    
    // Prepara i tag come stringa separata da virgole
    let tagsString = '';
    if (Array.isArray(currentValue)) {
        tagsString = currentValue.join(', ');
    }
    
    // Crea il campo di input
    const $input = $(`<input type="text" class="editable-cell-input" value="${tagsString}" placeholder="Tag1, Tag2, Tag3...">`);
    
    // Crea i radio button per modalità
    const $radioContainer = $(`
        <div class="editable-cell-radio-container">
            <label><input type="radio" name="tags-mode-${$cell.closest('tr').data('id')}" value="replace" checked> Sostituisci</label>
            <label><input type="radio" name="tags-mode-${$cell.closest('tr').data('id')}" value="append"> Aggiungi</label>
            <button class="editable-cell-save-btn">Salva</button>
            <button class="editable-cell-cancel-btn">Annulla</button>
        </div>
    `);
    
    // Svuota la cella e aggiungi gli elementi
    $cell.empty().append($input).append($radioContainer);
    
    // Focus sull'input
    $input.focus();
    
    // Gestisci salvataggio con pulsante
    $cell.find('.editable-cell-save-btn').on('click', function() {
        const newValue = $input.val();
        const mode = $cell.find('input[type="radio"]:checked').val();
        onSaveCallback(newValue, mode);
    });
    
    // Gestisci annullamento
    $cell.find('.editable-cell-cancel-btn').on('click', function() {
        $cell.html(originalContent);
        $cell.removeClass('editing');
    });
    
    // Gestisci tasto ENTER per salvare e ESC per annullare
    $input.on('keydown', function(e) {
        if (e.keyCode === 13) { // ENTER
            const newValue = $input.val();
            const mode = $cell.find('input[type="radio"]:checked').val();
            onSaveCallback(newValue, mode);
        } else if (e.keyCode === 27) { // ESC
            $cell.html(originalContent);
            $cell.removeClass('editing');
        }
    });
}

/**
 * Salva la modifica di un record al database
 */
function salvaModificaRecord(productId, field, newValue, $cell, originalContent) {
    // Mostra indicatore caricamento
    $cell.html('<div class="loader"></div> Salvataggio...');
    
    // Prepara i dati per la richiesta
    const data = {
        action: 'manual_classify',
        product_ids: [productId],
        update_fields: {}
    };
    
    // Imposta il campo da aggiornare
    data.update_fields[field] = newValue;
    
    // Invia richiesta al server
    $.ajax({
        url: config.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: data,
        success: function(response) {
            if (response.success) {
                // Ottieni il nome visualizzato per il valore selezionato
                let displayText = newValue;
                
                if (field === 'categoria_id') {
                    // Trova il testo dell'opzione selezionata
                    displayText = $('#categoria-filter option[value="' + newValue + '"]').text();
                    
                    // Aggiorna anche il valore memorizzato
                    $cell.data('value', newValue);
                    
                    // Potremmo dover aggiornare anche le sottocategorie disponibili
                    const $sottocategoriaCell = $cell.closest('tr').find('td[data-field-type="sottocategoria"]');
                    if ($sottocategoriaCell.length) {
                        // Reset della sottocategoria se necessario
                        // Questa logica potrebbe essere migliorata basandosi sulle tue esigenze
                    }
                } 
                else if (field === 'sottocategoria_id') {
                    // Trova il testo dalla lista sottocategorie
                    const sottocategorie = $('#categoria-filter').data('sottocategorie');
                    if (sottocategorie) {
                        for (let i = 0; i < sottocategorie.length; i++) {
                            if (sottocategorie[i].id == newValue) {
                                displayText = sottocategorie[i].nome;
                                break;
                            }
                        }
                    }
                    $cell.data('value', newValue);
                }
                else if (field === 'marca_id') {
                    displayText = $('#marca-filter option[value="' + newValue + '"]').text();
                    $cell.data('value', newValue);
                }
                
                // Mostra il nuovo valore
                $cell.html(displayText);
                $cell.removeClass('editing');
                
                // Notifica
                mostraNotifica('Aggiornamento completato con successo', 'success');
                
                // Ricarica statistiche per riflettere i cambiamenti
                caricaStatistiche();
            } else {
                // Ripristina il contenuto originale in caso di errore
                $cell.html(originalContent);
                $cell.removeClass('editing');
                mostraNotifica('Errore: ' + response.message, 'error');
            }
        },
        error: function() {
            // Ripristina il contenuto originale in caso di errore
            $cell.html(originalContent);
            $cell.removeClass('editing');
            mostraNotifica('Errore di connessione al server', 'error');
        }
    });
}

/**
 * Salva la modifica dei tag di un record
 */
function salvaModificaTagRecord(productId, tagsString, mode, $cell, originalContent) {
    // Mostra indicatore caricamento
    $cell.html('<div class="loader"></div> Salvataggio...');
    
    // Prepara i dati per la richiesta
    const data = {
        action: 'manual_classify',
        product_ids: [productId],
        update_fields: {
            tags: tagsString,
            tags_mode: mode
        }
    };
    
    // Invia richiesta al server
    $.ajax({
        url: config.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: data,
        success: function(response) {
            if (response.success) {
                // Ricarica il record aggiornato per mostrare i nuovi tag
                caricaSingoloProdotto(productId, function(product) {
                    if (product) {
                        // Formatta i tag
                        let tagsHtml = '';
                        if (product.tags && product.tags.length > 0) {
                            tagsHtml = '<div class="tag-container">';
                            product.tags.forEach(function(tag) {
                                tagsHtml += `<span class="tag">${tag}</span>`;
                            });
                            tagsHtml += '</div>';
                        } else {
                            tagsHtml = '<em>Nessun tag</em>';
                        }
                        
                        // Aggiorna la cella
                        $cell.html(tagsHtml);
                        $cell.data('value', product.tags);
                        $cell.removeClass('editing');
                        
                        // Notifica
                        mostraNotifica('Tag aggiornati con successo', 'success');
                        
                        // Ricarica statistiche per riflettere i cambiamenti
                        caricaStatistiche();
                    } else {
                        // Ripristina in caso di errore
                        $cell.html(originalContent);
                        $cell.removeClass('editing');
                        mostraNotifica('Impossibile aggiornare i tag', 'error');
                    }
                });
            } else {
                // Ripristina il contenuto originale in caso di errore
                $cell.html(originalContent);
                $cell.removeClass('editing');
                mostraNotifica('Errore: ' + response.message, 'error');
            }
        },
        error: function() {
            // Ripristina il contenuto originale in caso di errore
            $cell.html(originalContent);
            $cell.removeClass('editing');
            mostraNotifica('Errore di connessione al server', 'error');
        }
    });
}

/**
 * Carica un singolo prodotto dal database
 */
function caricaSingoloProdotto(productId, callback) {
    $.ajax({
        url: config.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'get_products',
            ids: [productId]
        },
        success: function(response) {
            if (response.success && response.data && response.data.length > 0) {
                callback(response.data[0]);
            } else {
                callback(null);
            }
        },
        error: function() {
            callback(null);
        }
    });
}

/**
 * Aggiungi stili CSS per la modifica inline
 */
function aggiungiStiliModificaInline() {
    // Aggiungi gli stili solo se non esistono già
    if (!$('#inline-edit-styles').length) {
        // Aggiungi stile per gli elementi modificabili
        const styleHtml = `
            <style id="inline-edit-styles">
                .editable-cell { position: relative; cursor: pointer; }
                .editable-cell:hover { background-color: #f9f9f9; }
                .editable-cell:hover::after { 
                    content: "✏️"; 
                    position: absolute; 
                    right: 5px; 
                    top: 50%; 
                    transform: translateY(-50%);
                    font-size: 12px;
                    opacity: 0.6;
                }
                .editable-cell-input, .editable-cell-select {
                    width: 100%;
                    padding: 5px;
                    border: 1px solid #4c84ff;
                    border-radius: 3px;
                }
                .editable-cell-buttons {
                    display: flex;
                    justify-content: space-between;
                    margin-top: 5px;
                }
                .editable-cell-buttons button {
                    padding: 3px 8px;
                    font-size: 12px;
                    border-radius: 3px;
                }
                .editable-cell-save { background-color: #4c84ff; color: white; border: none; }
                .editable-cell-cancel { background-color: #f5f5f5; border: 1px solid #ddd; }
                .tag-input-container { margin-bottom: 10px; }
                .tag-input-container label { display: block; margin-bottom: 3px; font-weight: bold; }
                .tag-input { width: 100%; height: 80px; }
                .tag-mode-selector { margin-top: 5px; }
            </style>
        `;
        $('head').append(styleHtml);
    }
    
    // Aggiungi stili per il codice EAN
    aggiungiStiliEan();
}

/**
 * Aggiunge gli stili CSS per il codice EAN
 */
function aggiungiStiliEan() {
    // Aggiungi gli stili solo se non esistono già
    if (!$('#ean-styles').length) {
        const styleHtml = `
            <style id="ean-styles">
                .product-ean {
                    font-size: 11px;
                    color: #888;
                    margin-bottom: 2px;
                    font-family: monospace;
                }
            </style>
        `;
        $('head').append(styleHtml);
    }
}

/**
 * Inizializza le funzionalità per il recupero delle immagini da barcodelookup.com
 */
function inizializzaRecuperoImmagini() {
    // Riferimento al pulsante
    const $btnFetchImages = $('#btn-fetch-images');
    
    // Handler click pulsante
    $btnFetchImages.on('click', function() {
        if (!confermaOperazione('Recuperare le immagini per i prodotti selezionati?')) {
            return;
        }
        
        // DEBUG: Mostra tutti i prodotti selezionati prima del filtraggio
        console.log("Prodotti selezionati (IDs):", appState.selectedProductIds);
        console.log("Lista completa prodotti:", appState.productsList);
        
        // Recupera codici EAN dei prodotti selezionati
        const prodottiSelezionati = appState.selectedProductIds.map(id => {
            const prodotto = appState.productsList.find(p => p.id.toString() === id.toString());
            console.log("Prodotto trovato per ID " + id + ":", prodotto);
            
            // Verifica che l'EAN sia una stringa valida e numerica
            const eanValido = prodotto && prodotto.ean && /^\d+$/.test(prodotto.ean);
            if (prodotto && !eanValido) {
                console.warn("EAN non valido per prodotto ID " + id + ":", prodotto.ean);
            }
            
            return eanValido ? { id: prodotto.id, ean: prodotto.ean } : null;
        }).filter(p => p !== null);
        
        // DEBUG: Mostra prodotti con EAN dopo il filtraggio
        console.log("Prodotti con EAN valido:", prodottiSelezionati);
        
        if (prodottiSelezionati.length === 0) {
            mostraNotifica('Nessun prodotto selezionato con codice EAN valido', 'warning');
            return;
        }
        
        // Mostra interfaccia elaborazione
        $('#processing-container').show();
        $('#processing-progress').css('width', '0%');
        $('#processing-status').text('Recupero immagini in corso...');
        $('#processing-success').text('0');
        $('#processing-error').text('0');
        
        // Inizializza contatori
        appState.isProcessing = true;
        appState.totalProcessed = 0;
        appState.successCount = 0;
        appState.errorCount = 0;
        appState.stopRequested = false;
        appState.processingQueue = [...prodottiSelezionati];
        
        // Avvia elaborazione
        elaboraCodeImmagini();
    });
    
    // Monitora selezione prodotti per abilitare/disabilitare pulsante
    $(document).on('change', '.product-checkbox', function() {
        $btnFetchImages.prop('disabled', appState.selectedProductIds.length === 0);
    });
    
    // Aggiungi handler per pulsanti seleziona/deseleziona tutti
    $('#btn-select-all').on('click', function() {
        $btnFetchImages.prop('disabled', false);
    });
    
    $('#btn-deselect-all').on('click', function() {
        $btnFetchImages.prop('disabled', true);
    });
}

/**
 * Elabora la coda di prodotti per recuperare le immagini
 */
function elaboraCodeImmagini() {
    if (appState.stopRequested) {
        completaElaborazioneImmagini('Elaborazione interrotta dall\'utente');
        return;
    }
    
    if (appState.processingQueue.length === 0) {
        completaElaborazioneImmagini('Recupero immagini completato');
        return;
    }
    
    // Prendi il prossimo prodotto dalla coda
    const prodotto = appState.processingQueue.shift();
    
    // DEBUG: Mostra informazioni sul prodotto in elaborazione
    console.log("Elaborazione prodotto:", prodotto);
    console.log("EAN che verrà inviato:", prodotto.ean);
    
    // Aggiorna interfaccia
    const progressPercent = Math.round((appState.totalProcessed / (appState.totalProcessed + appState.processingQueue.length)) * 100);
    $('#processing-progress').css('width', progressPercent + '%');
    $('#processing-status').html(`Recupero immagine per prodotto <strong>${prodotto.ean}</strong>...`);
    
    // Trova il nome del prodotto nella lista completa per usarlo come description
    const prodottoCompleto = appState.productsList.find(p => p.id.toString() === prodotto.id.toString());
    const description = prodottoCompleto ? prodottoCompleto.titolo : '';
    
    // Dati per la richiesta
    const requestData = {
        ean: prodotto.ean,
        description: description
    };
    
    // DEBUG: Mostra dati richiesta
    console.log("Dati richiesta AJAX:", requestData);
    
    // Recupera immagine
    $.ajax({
        url: 'fetch_images.php',
        type: 'POST',
        dataType: 'json',
        data: requestData,
        success: function(response) {
            // DEBUG: Mostra risposta dal server
            console.log("Risposta dal server:", response);
            
            appState.totalProcessed++;
            
            if (response.success) {
                appState.successCount++;
                $('#processing-success').text(appState.successCount);
                
                // Aggiorna l'immagine nella UI se il prodotto è visualizzato
                const $prodottoRow = $(`tr[data-id="${prodotto.id}"]`);
                if ($prodottoRow.length) {
                    const $imgCell = $prodottoRow.find('td img');
                    if ($imgCell.length) {
                        const timestamp = new Date().getTime(); // Evita caching
                        $imgCell.attr('src', `../public/catalogo/${response.data.filename}?t=${timestamp}`);
                    }
                }
                
                mostraNotifica(`Immagine recuperata per ${prodotto.ean}`, 'success', 2000);
            } else {
                appState.errorCount++;
                $('#processing-error').text(appState.errorCount);
                mostraNotifica(`Errore per ${prodotto.ean}: ${response.message}`, 'error', 3000);
            }
            
            // Processa il prossimo dopo un breve ritardo
            setTimeout(elaboraCodeImmagini, 500);
        },
        error: function(xhr, status, error) {
            // DEBUG: Mostra errore AJAX
            console.error("Errore AJAX:", { xhr, status, error });
            
            appState.totalProcessed++;
            appState.errorCount++;
            $('#processing-error').text(appState.errorCount);
            
            mostraNotifica(`Errore di connessione per ${prodotto.ean}`, 'error', 3000);
            
            // Processa il prossimo dopo un breve ritardo
            setTimeout(elaboraCodeImmagini, 500);
        }
    });
}

/**
 * Completa l'elaborazione delle immagini
 */
function completaElaborazioneImmagini(message) {
    appState.isProcessing = false;
    appState.processingQueue = [];
    
    // Aggiorna interfaccia
    $('#processing-progress').css('width', '100%');
    $('#processing-status').html(message);
    
    // Mostra riepilogo
    const riepilogo = `${appState.successCount} immagini recuperate, ${appState.errorCount} errori`;
    mostraNotifica(riepilogo, appState.errorCount > 0 ? 'warning' : 'success');
    
    // Ricarica la lista prodotti per mostrare le nuove immagini
    setTimeout(function() {
        caricaProdotti();
    }, 2000);
}
