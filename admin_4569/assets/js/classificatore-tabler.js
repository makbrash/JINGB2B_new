/**
 * classificatore-tabler.js - Adattamento per Tabler UI framework
 * 
 * Questo file estende le funzionalità di classificatore.js per renderle compatibili
 * con l'interfaccia Tabler. Include wrapper per le funzioni di visualizzazione
 * e interazione con l'utente.
 */

// Definiamo le funzioni critiche immediatamente per evitare errori di riferimento
if (typeof window.showProcessingUI !== 'function') {
    window.showProcessingUI = function(show) {
        console.log('showProcessingUI chiamata iniziale con:', show);
        // Verifichiamo prima se l'elemento esiste nel DOM
        const container = document.getElementById('processing-container');
        // Se non esiste, non fare nulla
        if (!container) {
            console.log('Elemento processing-container non trovato nel DOM');
            return;
        }
        
        // Se esiste, procedi con la modifica
        if (show) {
            container.classList.remove('d-none');
        } else {
            container.classList.add('d-none');
        }
    };
}

// Attendi che il documento sia pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM completamente caricato, inizializzazione applicazione');
    
    // Verifica se classificatore.js è stato caricato
    if (typeof appState === 'undefined') {
        console.error('classificatore.js non caricato correttamente');
        return;
    }
    
    // Aggiungi funzioni stub di compatibilità prima di qualsiasi altra cosa
    aggiungiStubCompatibilita();
    
    // Verifica che gli elementi fondamentali esistano prima di procedere
    const elementiNecessari = [
        'processing-container',
        'btn-toggle-stats',
        'stats-panel',
        'limit-selector'
    ];
    
    const elementiMancanti = elementiNecessari.filter(id => !document.getElementById(id));
    if (elementiMancanti.length > 0) {
        console.warn('Elementi necessari mancanti nel DOM:', elementiMancanti);
    }
    
    // Override delle funzioni originali
    sostituisciFunzioni();
    
    // Inizializzazione UI Tabler
    inizializzaUITabler();
    
    // Configura il selettore di prodotti per pagina
    configuraLimitSelector();
    
    // Gestione bottone mostra/nascondi statistiche
    const btnToggleStats = document.getElementById('btn-toggle-stats');
    if (btnToggleStats) {
        btnToggleStats.addEventListener('click', function() {
            const statsPanel = document.getElementById('stats-panel');
            if (statsPanel) {
                statsPanel.classList.toggle('d-none');
            }
        });
    }
    
    // Gestione tab statistiche
    configuraTabStatistiche();
    
    // Aggiungi classe di evidenziazione per la toolbar fissa
    configuraToobarFissa();
    
    console.log('Inizializzazione completata');
});

/**
 * Aggiunge funzioni stub di compatibilità per evitare errori di riferimento
 */
function aggiungiStubCompatibilita() {
    // Lista di funzioni che potrebbero essere chiamate dal classificatore originale
    const funzioniStub = [
        'inizializzaPersistenzaFiltri',
        'abilitaModificaInlineRecord',
        'inizializzaStatisticheSwitchable',
        'inizializzaUI',
        'confermaOperazione',
        'openPopup',
        'closePopup',
        'showProcessingUI'
    ];
    
    // Per ogni funzione nella lista, controlla se esiste e se non esiste, creala come stub
    funzioniStub.forEach(function(nomeFunzione) {
        if (typeof window[nomeFunzione] !== 'function') {
            window[nomeFunzione] = function() {
                console.log('Funzione stub chiamata: ' + nomeFunzione);
                return true; // Ritorna true come valore predefinito
            };
        }
    });
    
    // Funzione specifica inizializzaUI più robusta
    window.inizializzaUI = function() {
        console.log('Inizializzazione UI attraverso stub');
        
        try {
            // Nascondere loader iniziale se presente
            const loader = document.getElementById('products-loader');
            if (loader) {
                loader.style.display = 'none';
            } else {
                console.log('Elemento products-loader non trovato nel DOM');
            }
            
            // Prova a nascondere la barra di elaborazione solo se esiste
            if (typeof window.showProcessingUI === 'function') {
                window.showProcessingUI(false);
            }
            
            // Dialog di conferma
            window.confermaOperazione = function(message) {
                return confirm(message);
            };
        } catch (error) {
            console.error('Errore durante l\'inizializzazione UI:', error);
        }
    };
    
    // Definizione esplicita di showProcessingUI
    window.showProcessingUI = function(show) {
        console.log('showProcessingUI stub chiamata con:', show);
        // Verifichiamo prima se l'elemento esiste nel DOM
        const container = document.getElementById('processing-container');
        // Se non esiste, non fare nulla
        if (!container) {
            console.log('Elemento processing-container non trovato nel DOM');
            return;
        }
        
        if (show) {
            container.classList.remove('d-none');
        } else {
            container.classList.add('d-none');
        }
    };
}

/**
 * Sostituisce le funzioni originali del classificatore con versioni compatibili con Tabler
 */
function sostituisciFunzioni() {
    // Salva riferimenti alle funzioni originali
    const originalShowProcessingUI = window.showProcessingUI;
    const originalUpdateProgress = window.updateProgress;
    const originalMostraNotifica = window.mostraNotifica;
    const originalMostraMessaggioStato = window.mostraMessaggioStato;
    const originalRenderizzaGrigliaImmagini = window.renderizzaGrigliaImmagini;
    const originalRenderizzaProdotti = window.renderizzaProdotti;
    
    // Sostituisci la funzione di visualizzazione barra progresso
    if (typeof originalShowProcessingUI === 'function') {
        window.showProcessingUI = function(show) {
            // Verifichiamo prima se l'elemento esiste nel DOM
            const container = document.getElementById('processing-container');
            // Se non esiste, logga e passa alla funzione originale
            if (!container) {
                console.log('Elemento processing-container non trovato nel DOM');
            } else {
                // Se esiste, procedi con la modifica
                if (show) {
                    container.classList.remove('d-none');
                    // Rendi visibile il pulsante di stop
                    const stopButton = document.getElementById('btn-stop-processing');
                    if (stopButton) stopButton.classList.remove('d-none');
                } else {
                    container.classList.add('d-none');
                    // Nascondi il pulsante di stop
                    const stopButton = document.getElementById('btn-stop-processing');
                    if (stopButton) stopButton.classList.add('d-none');
                }
            }
            
            // Chiama la funzione originale se esiste
            if (typeof originalShowProcessingUI === 'function') {
                originalShowProcessingUI(show);
            }
        };
    } else {
        // Definisci la funzione se non esiste
        window.showProcessingUI = function(show) {
            // Verifichiamo prima se l'elemento esiste nel DOM
            const container = document.getElementById('processing-container');
            // Se non esiste, non fare nulla
            if (!container) {
                console.log('Elemento processing-container non trovato nel DOM');
                return;
            }
            
            if (show) {
                container.classList.remove('d-none');
                // Rendi visibile il pulsante di stop
                const stopButton = document.getElementById('btn-stop-processing');
                if (stopButton) stopButton.classList.remove('d-none');
            } else {
                container.classList.add('d-none');
                // Nascondi il pulsante di stop
                const stopButton = document.getElementById('btn-stop-processing');
                if (stopButton) stopButton.classList.add('d-none');
            }
        };
    }
    
    // Sostituisci la funzione di aggiornamento progresso
    if (typeof originalUpdateProgress === 'function') {
        window.updateProgress = function(percent, successCount, errorCount) {
            // Aggiorna la barra di progresso con stile Tabler
            const progressBar = document.getElementById('processing-progress');
            progressBar.style.width = percent + '%';
            
            // Aggiorna i contatori
            document.getElementById('processing-success').textContent = successCount;
            document.getElementById('processing-error').textContent = errorCount;
            
            // Chiama la funzione originale se esiste
            if (typeof originalUpdateProgress === 'function') {
                originalUpdateProgress(percent, successCount, errorCount);
            }
        };
    }
    
    // Sostituisci la funzione di notifica
    if (typeof originalMostraNotifica === 'function') {
        window.mostraNotifica = function(message, type) {
            // Utilizza le notifiche toast di Tabler, se disponibili
            if (typeof Toastify === 'function') {
                Toastify({
                    text: message,
                    duration: 5000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    className: type
                }).showToast();
            } else {
                // Fallback alla versione originale
                if (typeof originalMostraNotifica === 'function') {
                    originalMostraNotifica(message, type);
                }
            }
        };
    }
    
    // Sostituisci la funzione di visualizzazione stato
    if (typeof originalMostraMessaggioStato === 'function') {
        window.mostraMessaggioStato = function(message, type, withRetry) {
            const statusElement = document.getElementById('image-selection-status');
            if (!statusElement) return;
            
            // Rimuovi tutte le classi di tipo e mostra l'elemento
            statusElement.classList.remove('alert-info', 'alert-success', 'alert-danger', 'alert-warning', 'd-none');
            
            // Aggiungi la classe appropriata in base al tipo
            switch (type) {
                case 'info':
                    statusElement.classList.add('alert-info');
                    break;
                case 'success':
                    statusElement.classList.add('alert-success');
                    break;
                case 'error':
                    statusElement.classList.add('alert-danger');
                    break;
                case 'warning':
                    statusElement.classList.add('alert-warning');
                    break;
            }
            
            // Imposta il messaggio
            statusElement.innerHTML = message;
            
            // Aggiungi pulsante retry se richiesto
            if (withRetry && type === 'error') {
                statusElement.innerHTML += `
                    <div class="mt-2">
                        <button class="btn btn-sm btn-outline-secondary retry-button">
                            <i class="ti ti-refresh me-1"></i> Prova un'altra immagine
                        </button>
                    </div>
                    <div class="small text-muted mt-1">
                        Suggerimenti: convertire l'immagine in JPG, verificare che non sia danneggiata, ridurre le dimensioni.
                    </div>
                `;
                
                // Aggiungi event listener al pulsante
                statusElement.querySelector('.retry-button').addEventListener('click', function() {
                    document.getElementById('image-file').value = '';
                    statusElement.classList.add('d-none');
                });
            }
            
            // Esegui la funzione originale se esiste
            if (typeof originalMostraMessaggioStato === 'function') {
                originalMostraMessaggioStato(message, type, withRetry);
            }
        };
    }
    
    // Sostituzione della funzione di rendering griglia immagini
    if (typeof originalRenderizzaGrigliaImmagini === 'function') {
        window.renderizzaGrigliaImmagini = function(images, ean) {
            if (!images || images.length === 0) {
                $('#image-grid').html('<div class="empty-state text-center p-4"><i class="ti ti-photo-off text-muted mb-2" style="font-size: 2rem;"></i><p class="text-muted">Nessuna immagine trovata</p></div>');
                return;
            }
            
            let html = '';
            images.forEach((imageUrl, index) => {
                html += `
                    <div class="image-option" data-url="${imageUrl}">
                        <img src="${imageUrl}" alt="Opzione ${index + 1}">
                    </div>
                `;
            });
            
            html += `
                <div class="d-flex justify-content-center w-100 mt-2">
                    <button id="btn-save-selected-image" class="btn btn-primary" data-ean="${ean}">
                        <i class="ti ti-device-floppy me-1"></i> Salva immagine selezionata
                    </button>
                </div>
            `;
            
            $('#image-grid').html(html);
            
            // Handler per selezione immagine
            $('.image-option').on('click', function() {
                $('.image-option').removeClass('selected');
                $(this).addClass('selected');
            });
            
            // Handler per pulsante salvataggio
            $('#btn-save-selected-image').on('click', function() {
                const $selected = $('.image-option.selected');
                if ($selected.length === 0) {
                    mostraMessaggioStato('Seleziona prima un\'immagine', 'error');
                    return;
                }
                
                const url = $selected.data('url');
                const ean = $(this).data('ean');
                
                salvaImmagineURL(ean, url);
            });
            
            // Esegui la funzione originale se esiste
            if (typeof originalRenderizzaGrigliaImmagini === 'function' && originalRenderizzaGrigliaImmagini !== window.renderizzaGrigliaImmagini) {
                originalRenderizzaGrigliaImmagini(images, ean);
            }
        };
    }
    
    // Sostituzione della funzione di rendering prodotti
    if (typeof originalRenderizzaProdotti === 'function') {
        window.renderizzaProdotti = function(products) {
            if (!products || products.length === 0) {
                $('#products-container').html('<div class="empty-state text-center p-5"><i class="ti ti-mood-sad text-muted mb-3" style="font-size: 3rem;"></i><p class="text-muted">Nessun prodotto trovato</p></div>');
                return;
            }
            
            let html = `
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th class="w-1">
                                <input class="form-check-input m-0 align-middle" type="checkbox" id="check-all-products">
                            </th>
                            <th class="w-1">ID</th>
                            <th class="w-1">Immagine</th>
                            <th>Titolo</th>
                            <th>Categoria</th>
                            <th>Sottocategoria</th>
                            <th>Marca</th>
                            <th>Tags</th>
                            <th class="w-1">Stato</th>
                            <th class="w-1">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            products.forEach(function(product) {
                // Determina la classe di stato
                let statusClass = '';
                let statusText = '';
                
                switch (product.stato) {
                    case 'pending':
                    case 'da_elaborare':
                        statusClass = 'status-pending';
                        statusText = '<span class="badge bg-warning">Da elaborare</span>';
                        break;
                    case 'done':
                    case 'completato':
                        statusClass = 'status-done';
                        statusText = '<span class="badge bg-success">Completato</span>';
                        break;
                    case 'error':
                        statusClass = 'status-error';
                        statusText = '<span class="badge bg-danger">Errore</span>';
                        break;
                    default:
                        statusClass = '';
                        statusText = '<span class="badge bg-secondary">Non assegnato</span>';
                }
                
                // Prepara i tag
                let tagsHtml = '';
                if (product.tags && product.tags.length > 0) {
                    product.tags.forEach(function(tag) {
                        // Determina la classe del tag
                        let tagClass = 'bg-blue-lt';
                        if (tag.includes(':tipologia:')) {
                            tagClass = 'bg-green-lt';
                        } else if (tag.includes(':variante:')) {
                            tagClass = 'bg-red-lt';
                        }
                        
                        tagsHtml += `<span class="badge ${tagClass} me-1">${tag}</span>`;
                    });
                } else {
                    tagsHtml = '<span class="text-muted">Nessun tag</span>';
                }
                
                // Prepara l'immagine
                const imgSrc = product.immagine ? 
                    `../public/catalogo/${product.immagine}` : 
                    'assets/img/no-image.png';
                
                // Aggiungi la riga
                html += `
                    <tr class="${statusClass}" data-id="${product.id}" data-ean="${product.ean}">
                        <td>
                            <input class="form-check-input m-0 align-middle product-checkbox" type="checkbox" value="${product.id}">
                        </td>
                        <td>${product.id}</td>
                        <td>
                            <img src="${imgSrc}" alt="Prodotto" class="avatar">
                        </td>
                        <td>
                            <a href="#" class="product-title" data-id="${product.id}">${product.titolo || 'Titolo non disponibile'}</a>
                            <div class="text-muted small">EAN: ${product.ean || 'N/A'}</div>
                        </td>
                        <td>${product.categoria || '<span class="text-muted">Non assegnata</span>'}</td>
                        <td>${product.sottocategoria || '<span class="text-muted">Non assegnata</span>'}</td>
                        <td>${product.marca || '<span class="text-muted">Non assegnata</span>'}</td>
                        <td class="tags-container">${tagsHtml}</td>
                        <td>${statusText}</td>
                        <td>
                            <div class="btn-list flex-nowrap">
                                <button class="btn btn-sm btn-primary btn-process-product" data-id="${product.id}">
                                    <i class="ti ti-refresh"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary btn-edit-product" data-id="${product.id}">
                                    <i class="ti ti-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                    </tbody>
                </table>
            `;
            
            $('#products-container').html(html);
            
            // Inizializza il checkbox "Seleziona tutto"
            $('#check-all-products').on('change', function() {
                const isChecked = $(this).prop('checked');
                $('.product-checkbox').prop('checked', isChecked);
                
                // Aggiorna la selezione nello stato dell'applicazione
                appState.selectedProductIds = [];
                if (isChecked) {
                    $('.product-checkbox').each(function() {
                        appState.selectedProductIds.push($(this).val());
                    });
                }
                
                aggiornaContatoreProdottiSelezionati();
            });
            
            // Esegui la funzione originale se esiste
            if (typeof originalRenderizzaProdotti === 'function' && originalRenderizzaProdotti !== window.renderizzaProdotti) {
                originalRenderizzaProdotti(products);
            }
        };
    }
}

/**
 * Inizializza l'interfaccia utente per Tabler
 */
function inizializzaUITabler() {
    // Inizializza modali con Bootstrap
    document.querySelectorAll('.modal').forEach(function(modal) {
        new bootstrap.Modal(modal);
    });
    
    // Listener per chiusura modali
    document.querySelectorAll('.modal .btn-close, .modal [data-bs-dismiss="modal"]').forEach(function(closeBtn) {
        closeBtn.addEventListener('click', function() {
            const modalId = this.closest('.modal').id;
            const modalInstance = bootstrap.Modal.getInstance(document.getElementById(modalId));
            if (modalInstance) {
                modalInstance.hide();
            }
        });
    });
    
    // Inizializza tooltip
    if (typeof bootstrap !== 'undefined' && typeof bootstrap.Tooltip !== 'undefined') {
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(function(tooltip) {
            new bootstrap.Tooltip(tooltip);
        });
    }
}

/**
 * Configura il selettore di limite prodotti per pagina
 */
function configuraLimitSelector() {
    const limitSelector = document.getElementById('limit-selector');
    if (!limitSelector) return;
    
    // Imposta il valore iniziale se presente nello stato
    if (appState.productsPerPage) {
        limitSelector.value = appState.productsPerPage;
    }
    
    // Ascolta i cambiamenti
    limitSelector.addEventListener('change', function() {
        const newLimit = parseInt(this.value, 10);
        appState.productsPerPage = newLimit;
        
        // Ricarica i prodotti con il nuovo limite
        caricaProdotti();
        
        // Salva la preferenza dell'utente se disponibile localStorage
        if (window.localStorage) {
            localStorage.setItem('productsPerPage', newLimit);
        }
    });
}

/**
 * Helper per aprire le modali bootstrap
 */
function apriModal(modalId) {
    const modalElement = document.getElementById(modalId);
    if (!modalElement) return;
    
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
    modalInstance.show();
}

/**
 * Helper per chiudere le modali bootstrap
 */
function chiudiModal(modalId) {
    const modalElement = document.getElementById(modalId);
    if (!modalElement) return;
    
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    if (modalInstance) {
        modalInstance.hide();
    }
}

/**
 * Inizializza la persistenza dei filtri usando localStorage
 * Questa funzione salva e ripristina i filtri applicati dall'utente
 */
window.inizializzaPersistenzaFiltri = function() {
    // Salvataggio stato dei filtri al cambiamento
    $('#stato-filter, #categoria-filter, #sottocategoria-filter, #marca-filter, #limit-selector').on('change', function() {
        const $this = $(this);
        const filterId = $this.attr('id');
        const filterValue = $this.val();
        
        // Salva nel localStorage
        if (window.localStorage) {
            localStorage.setItem('filter_' + filterId, filterValue);
        }
    });
    
    // Ripristina i filtri salvati, se presenti
    if (window.localStorage) {
        const filtri = ['stato-filter', 'categoria-filter', 'sottocategoria-filter', 'marca-filter', 'limit-selector'];
        
        filtri.forEach(function(filterId) {
            const savedValue = localStorage.getItem('filter_' + filterId);
            if (savedValue !== null) {
                $('#' + filterId).val(savedValue);
            }
        });
    }
};

/**
 * Abilita la modifica inline dei campi nella tabella prodotti
 * Implementazione per evitare errori di riferimento
 */
window.abilitaModificaInlineRecord = function() {
    // Listener per campi modificabili
    $(document).on('click', '.editable-field', function() {
        // Se già in modifica, non fare nulla
        if ($(this).hasClass('editing')) {
            return;
        }
        
        const value = $(this).text().trim();
        const field = $(this).data('field');
        const id = $(this).data('id');
        
        // Sostituisci con input
        $(this).addClass('editing')
               .html(`<input type="text" class="editable-input" value="${value}">`);
        
        // Focus sull'input
        $(this).find('input').focus();
        
        // Gestione perdita focus o tasto invio
        $(this).find('input').on('blur keydown', function(e) {
            // Se non è invio né perdita focus, ignora
            if (e.type === 'keydown' && e.keyCode !== 13) {
                return;
            }
            
            const newValue = $(this).val().trim();
            const $field = $(this).closest('.editable-field');
            
            // Ripristina testo
            $field.removeClass('editing').text(newValue);
            
            // Se il valore è cambiato, salva
            if (newValue !== value) {
                salvaValoreCampo(id, field, newValue);
            }
        });
    });
};

/**
 * Funzione per salvare il valore di un campo dopo la modifica inline
 */
function salvaValoreCampo(id, field, value) {
    // Implementazione minima per evitare errori
    $.ajax({
        url: config.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'update_field',
            id: id,
            field: field,
            value: value
        },
        success: function(response) {
            if (response.success) {
                mostraNotifica('Campo aggiornato con successo', 'success');
            } else {
                mostraNotifica('Errore nell\'aggiornamento: ' + response.message, 'error');
            }
        },
        error: function() {
            mostraNotifica('Errore di connessione', 'error');
        }
    });
}

/**
 * Inizializza il sistema di tab per le statistiche
 * Questa funzione è adattata per il layout Tabler
 */
window.inizializzaStatisticheSwitchable = function() {
    // La gestione dei tab è già implementata direttamente in classificator.php
    // tramite il navbar di Bootstrap di Tabler
    // Questa funzione rimane come stub per compatibilità
    
    // Verifica che i contenitori esistano
    if (!document.getElementById('stat-tabs')) return;
    
    // Assicurati che inizialmente sia visibile solo la prima tab
    $('#category-stats').removeClass('d-none');
    $('#brand-stats, #tag-cloud, #subcategory-stats').addClass('d-none');
};

/**
 * Configura i tab delle statistiche
 */
function configuraTabStatistiche() {
    const tabLinks = document.querySelectorAll('#stat-tabs .nav-link');
    if (tabLinks.length === 0) {
        console.log('Tab statistiche non trovati nel DOM');
        return;
    }
    
    tabLinks.forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Attiva il tab corrente
            tabLinks.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Ottieni il tipo di statistica
            const statType = this.getAttribute('data-stat');
            
            // Verifica l'esistenza dei contenitori prima di manipolarli
            const categoryStats = document.getElementById('category-stats');
            const brandStats = document.getElementById('brand-stats');
            const tagCloud = document.getElementById('tag-cloud');
            const subcategoryStats = document.getElementById('subcategory-stats');
            
            // Nascondi tutti i contenuti se esistono
            if (categoryStats) categoryStats.classList.add('d-none');
            if (brandStats) brandStats.classList.add('d-none');
            if (tagCloud) tagCloud.classList.add('d-none');
            if (subcategoryStats) subcategoryStats.classList.add('d-none');
            
            // Mostra il contenuto richiesto
            const activeContainer = document.getElementById(statType + '-stats');
            if (activeContainer) {
                activeContainer.classList.remove('d-none');
            }
        });
    });
}

/**
 * Configura la toolbar fissa
 */
function configuraToobarFissa() {
    const stickyToolbar = document.querySelector('.sticky-top');
    if (!stickyToolbar) {
        console.log('Toolbar fissa non trovata nel DOM');
        return;
    }
    
    const originalToolbarOffset = stickyToolbar.offsetTop;
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > originalToolbarOffset) {
            stickyToolbar.classList.add('shadow-sm');
        } else {
            stickyToolbar.classList.remove('shadow-sm');
        }
    });
} 