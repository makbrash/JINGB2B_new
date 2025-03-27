/**
 * classificatore.js - Funzionalità frontend per sistema classificazione prodotti
 * 
 * Questo file gestisce l'interazione utente e le comunicazioni AJAX
 * con il backend per il sistema di classificazione dei prodotti.
 * 
 * @author Marco Vitaletti (originale), Trae AI (refactoring)
 * @version 2.0
 */

// Utilizziamo IIFE per evitare variabili globali e organizzare meglio il codice
(function() {
    'use strict';
    
    // ===== CONFIGURAZIONE =====
    const config = {
        ajaxUrl: 'classificatore_ajax.php',
        batchSize: 5, // Numero prodotti elaborati in una volta
        autoProcessDelay: 1000, // Ritardo tra batch automatici (ms)
    };
    
    // ===== STATO APPLICAZIONE =====
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
    
    // ===== MODULI APPLICAZIONE =====
    
    /**
     * Modulo Utility - Funzioni di utilità generiche
     */
    const Utils = {
        /**
         * Mostra una notifica all'utente
         * @param {string} message Messaggio da mostrare
         * @param {string} type Tipo di notifica (success, error, warning, info)
         * @param {number} duration Durata in ms (default: 3000)
         */
        showNotification: function(message, type = 'info', duration = 3000) {
            // Crea elemento notifica
            const $notification = $(`<div class="notification notification-${type}">${message}</div>`);
            
            // Aggiungi al container
            $('#notifications').append($notification);
            
            // Mostra con animazione
            $notification.animate({ opacity: 1, right: '10px' }, 300);
            
            // Rimuovi dopo il timeout
            setTimeout(function() {
                $notification.animate({ opacity: 0, right: '-300px' }, 300, function() {
                    $notification.remove();
                });
            }, duration);
        },
        
        /**
         * Formatta un array di tag in HTML
         * @param {Array} tags Array di tag
         * @returns {string} HTML formattato
         */
        formatTagsHtml: function(tags) {
            if (!tags || tags.length === 0) {
                return '<em>Nessun tag</em>';
            }
            
            let html = '<div class="tag-container">';
            tags.forEach(function(tag) {
                html += `<span class="tag">${tag}</span>`;
            });
            html += '</div>';
            
            return html;
        },
        
        /**
         * Formatta lo stato di un prodotto in HTML
         * @param {number} stato Stato del prodotto (0=pending, 1=done, 2=error)
         * @returns {string} HTML formattato
         */
        formatStatusHtml: function(stato) {
            if (stato === 1) {
                return '<span class="status-badge status-success">Elaborato</span>';
            } else if (stato === 2) {
                return '<span class="status-badge status-error">Errore</span>';
            } else {
                return '<span class="status-badge status-pending">Da elaborare</span>';
            }
        }
    };
    
    /**
     * Modulo API - Gestisce le chiamate AJAX al backend
     */
    const API = {
        /**
         * Esegue una chiamata AJAX generica
         * @param {Object} params Parametri per la chiamata
         * @param {Function} onSuccess Callback di successo
         * @param {Function} onError Callback di errore
         */
        call: function(params, onSuccess, onError) {
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: params,
                success: function(response) {
                    if (response.success) {
                        if (typeof onSuccess === 'function') {
                            onSuccess(response);
                        }
                    } else {
                        if (typeof onError === 'function') {
                            onError(response.message || 'Errore sconosciuto');
                        } else {
                            Utils.showNotification('Errore: ' + (response.message || 'Errore sconosciuto'), 'error');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    const errorMsg = 'Errore di connessione al server: ' + (error || status);
                    if (typeof onError === 'function') {
                        onError(errorMsg);
                    } else {
                        Utils.showNotification(errorMsg, 'error');
                    }
                }
            });
        },
        
        /**
         * Carica le statistiche
         * @param {Function} onSuccess Callback di successo
         */
        loadStats: function(onSuccess) {
            this.call(
                { action: 'get_stats' },
                function(response) {
                    if (typeof onSuccess === 'function') {
                        onSuccess(response.data);
                    }
                }
            );
        },
        
        /**
         * Carica i selettori (categorie, sottocategorie, marche)
         * @param {Function} onSuccess Callback di successo
         */
        loadSelectors: function(onSuccess) {
            this.call(
                { action: 'get_selectors' },
                function(response) {
                    if (typeof onSuccess === 'function') {
                        onSuccess(response.data);
                    }
                }
            );
        },
        
        /**
         * Carica i prodotti con filtri
         * @param {Object} options Opzioni di filtro
         * @param {Function} onSuccess Callback di successo
         */
        loadProducts: function(options, onSuccess) {
            // Aggiungi action
            options.action = 'get_products';
            
            this.call(
                options,
                function(response) {
                    if (typeof onSuccess === 'function') {
                        onSuccess(response.data, response.message);
                    }
                }
            );
        },
        
        /**
         * Classifica un singolo prodotto
         * @param {number} productId ID del prodotto
         * @param {Function} onSuccess Callback di successo
         */
        classifyProduct: function(productId, onSuccess) {
            this.call(
                {
                    action: 'classify_product',
                    product_id: productId
                },
                function(response) {
                    if (typeof onSuccess === 'function') {
                        onSuccess(response.data);
                    }
                }
            );
        },
        
        /**
         * Classifica un batch di prodotti
         * @param {Object} options Opzioni per il batch
         * @param {Function} onSuccess Callback di successo
         */
        classifyBatch: function(options, onSuccess) {
            options.action = 'batch_classify';
            
            this.call(
                options,
                function(response) {
                    if (typeof onSuccess === 'function') {
                        onSuccess(response.data);
                    }
                }
            );
        },
        
        /**
         * Resetta i tag di prodotti
         * @param {Object} options Opzioni per il reset
         * @param {Function} onSuccess Callback di successo
         */
        resetTags: function(options, onSuccess) {
            options.action = 'reset_tags';
            
            this.call(
                options,
                function(response) {
                    if (typeof onSuccess === 'function') {
                        onSuccess(response.data);
                    }
                }
            );
        },
        
        /**
         * Aggiorna manualmente la classificazione di prodotti
         * @param {Array} productIds Array di ID prodotti
         * @param {Object} updateFields Campi da aggiornare
         * @param {Function} onSuccess Callback di successo
         */
        manualClassify: function(productIds, updateFields, onSuccess) {
            this.call(
                {
                    action: 'manual_classify',
                    product_ids: productIds,
                    update_fields: updateFields
                },
                function(response) {
                    if (typeof onSuccess === 'function') {
                        onSuccess(response.data);
                    }
                }
            );
        },
        
        /**
         * Carica i dettagli di un prodotto
         * @param {number} productId ID del prodotto
         * @param {Function} onSuccess Callback di successo
         */
        loadProductDetails: function(productId, onSuccess) {
            this.call(
                {
                    action: 'get_product_details',
                    product_id: productId
                },
                function(response) {
                    if (typeof onSuccess === 'function') {
                        onSuccess(response.data);
                    }
                }
            );
        }
    };
    
    /**
     * Modulo StatsManager - Gestisce le statistiche
     */
    const StatsManager = {
        /**
         * Carica e visualizza le statistiche
         */
        loadStats: function() {
            API.loadStats(function(stats) {
                StatsManager.displayStats(stats);
            });
        },
        
        /**
         * Visualizza le statistiche nell'interfaccia
         * @param {Object} stats Dati statistiche
         */
        displayStats: function(stats) {
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
                ProductsManager.searchProducts();
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
                ProductsManager.loadProducts();
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