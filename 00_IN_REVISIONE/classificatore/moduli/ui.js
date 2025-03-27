// Aggiornamento del modulo ui.js per implementare SweetAlert2

const UI = {
    /**
     * Inizializza l'interfaccia utente
     */
    inizializza: function() {
        // Nascondi pulsante stop all'inizio
        $('#btn-stop-processing').hide();
        
        // Imposta valori predefiniti per range
        $('#range_start').val(Config.defaultRangeStart);
        $('#range_end').val(Config.defaultRangeEnd);
        
        // Configura il tema SweetAlert2
        this.configuraSweetAlert();
    },
    
    /**
     * Configura il tema e le opzioni di default per SweetAlert2
     */
    configuraSweetAlert: function() {
        // Verifica che SweetAlert2 sia disponibile
        if (typeof Swal === 'undefined') {
            console.error('SweetAlert2 non disponibile');
            return;
        }
        
        // Configura tema
        Swal.mixin({
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Conferma',
            cancelButtonText: 'Annulla',
            reverseButtons: true,
            customClass: {
                confirmButton: 'button button-success',
                cancelButton: 'button button-danger',
                actions: 'swal-actions'
            }
        });
    },
    
    /**
     * Mostra una notifica all'utente utilizzando SweetAlert2
     * @param {string} message - Messaggio da mostrare
     * @param {string} type - Tipo di notifica (success, error, warning, info)
     */
    mostraNotifica: function(message, type = 'info') {
        // Verifica che SweetAlert2 sia disponibile
        if (typeof Swal === 'undefined') {
            // Fallback al vecchio sistema di notifiche
            const notification = $(`<div class="notification ${type}">${message}</div>`);
            $('#notifications').append(notification);
            setTimeout(function() {
                notification.fadeOut(300, function() {
                    notification.remove();
                });
            }, Config.notificationTimeout);
            return;
        }
        
        // Configura icona in base al tipo
        let icon = type;
        // SweetAlert2 usa "question" invece di "info" per l'icona
        if (type === 'info') icon = 'info';
        
        // Toast in alto a destra per notifiche
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: Config.notificationTimeout,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        
        Toast.fire({
            icon: icon,
            title: message
        });
    },
    
    /**
     * Mostra conferma all'utente con SweetAlert2
     * @param {string} message - Messaggio di conferma
     * @param {string} title - Titolo della conferma
     * @param {Function} onConfirm - Funzione callback se l'utente conferma
     * @param {string} type - Tipo di conferma (question, warning, etc)
     */
    mostraConferma: function(message, title, onConfirm, type = 'question') {
        // Verifica che SweetAlert2 sia disponibile
        if (typeof Swal === 'undefined') {
            // Fallback al confirm nativo
            if (confirm(message)) {
                if (typeof onConfirm === 'function') onConfirm();
            }
            return;
        }
        
        Swal.fire({
            title: title,
            text: message,
            icon: type,
            showCancelButton: true,
            confirmButtonText: 'Conferma',
            cancelButtonText: 'Annulla'
        }).then((result) => {
            if (result.isConfirmed && typeof onConfirm === 'function') {
                onConfirm();
            }
        });
    },
    
    /**
     * Mostra i dettagli di un prodotto utilizzando SweetAlert2
     * @param {string} productId - ID del prodotto
     */
    mostraDettagliProdotto: function(productId) {
        // Trova prodotto nella lista
        const product = State.findProduct(productId);
        
        if (!product) {
            this.mostraNotifica('Prodotto non trovato', 'error');
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
        
        // Verifica che SweetAlert2 sia disponibile
        if (typeof Swal === 'undefined') {
            // Fallback al modal originale
            const modalContent = `
                <div class="product-detail">
                    <div class="product-detail-header">
                        <h2>${product.titolo}</h2>
                        <p class="product-id">ID: ${product.id}</p>
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
                        <button class="btn-process-single button" data-id="${product.id}">Elabora questo prodotto</button>
                        <button class="btn-close-modal button">Chiudi</button>
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
                Products.elaboraSingoloProdotto(productId);
                $('#product-detail-modal').hide();
            });
            
            return;
        }
        
        // Costruisci HTML contenuto con SweetAlert2
        const content = `
            <div class="product-detail-swal">
                <div class="product-detail-image">
                    <img src="${imgSrc}" alt="Immagine prodotto" style="max-width: 100%; max-height: 200px;">
                </div>
                
                <div class="product-detail-info">
                    <table class="detail-table" style="width: 100%;">
                        <tr>
                            <th>ID:</th>
                            <td>${product.id}</td>
                        </tr>
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
        `;
        
        // Mostra SweetAlert2 con i dettagli del prodotto
        Swal.fire({
            title: product.titolo,
            html: content,
            showCloseButton: true,
            showCancelButton: true,
            confirmButtonText: 'Elabora prodotto',
            cancelButtonText: 'Chiudi',
            customClass: {
                container: 'product-detail-container',
                popup: 'product-detail-popup',
                content: 'product-detail-content'
            },
            width: '700px'
        }).then((result) => {
            if (result.isConfirmed) {
                Products.elaboraSingoloProdotto(productId);
            }
        });
    },
    
    /**
     * Mostra l'interfaccia di elaborazione batch
     */
    mostraInterfacciaElaborazione: function() {
        // Mostra UI elaborazione
        $('#processing-container').show();
        $('#btn-stop-processing').show();
        
        // Nascondi pulsanti di azione
        $('#btn-process-selected, #btn-reset-selected').hide();
        
        // Reset progresso
        $('#processing-progress').css('width', '0%');
        $('#processing-success').text('0');
        $('#processing-error').text('0');
    },
    
    /**
     * Aggiorna l'interfaccia di elaborazione con i progressi
     */
    aggiornaUIElaborazione: function() {
        // Calcola progresso
        const total = State.totalProcessed + State.processingQueue.length;
        const progress = Math.round((State.totalProcessed / total) * 100);
        
        // Aggiorna elementi UI
        $('#processing-progress').css('width', progress + '%');
        $('#processing-status').text(`Elaborazione in corso: ${State.totalProcessed} di ${total}`);
        $('#processing-success').text(State.successCount);
        $('#processing-error').text(State.errorCount);
    },
    
    /**
     * Completa l'interfaccia di elaborazione
     * @param {string} message - Messaggio da mostrare
     */
    completaInterfacciaElaborazione: function(message) {
        // Aggiorna UI
        $('#btn-stop-processing').hide();
        $('#btn-process-selected, #btn-reset-selected').show();
        
        // Mostra messaggio
        this.mostraNotifica(message + `: ${State.successCount} successi, ${State.errorCount} errori`, 'info');
        
        // Nascondi UI elaborazione dopo un attimo
        setTimeout(function() {
            $('#processing-container').hide();
        }, 3000);
    },
    
    /**
     * Aggiorna contatore prodotti selezionati
     */
    aggiornaContatoreProdottiSelezionati: function() {
        const count = State.selectedProductIds.length;
        $('#selected-count').text(count);
        
        // Abilita/disabilita pulsanti in base alla selezione
        if (count > 0) {
            $('#btn-process-selected, #btn-reset-selected, #btn-manual-classify').prop('disabled', false);
        } else {
            $('#btn-process-selected, #btn-reset-selected, #btn-manual-classify').prop('disabled', true);
        }
    },
    
    /**
     * Mostra modal per classificazione manuale
     */
    mostraModalClassificazioneManuale: function() {
        // Popola selettori con valori disponibili
        InlineEdit.popolaSelettoriModal();
        
        // Aggiorna contatore prodotti
        $('#manual-count').text(State.selectedProductIds.length);
        
        // Mostra modal
        $('#manual-classify-modal').show();
    }
};

// Aggiorniamo Products per usare SweetAlert2 nelle conferme
const Products_Original = {
    elaboraProdottiSelezionati: Products.elaboraProdottiSelezionati,
    resetTagProdottiSelezionati: Products.resetTagProdottiSelezionati,
    stoppaElaborazione: Products.stoppaElaborazione
};

/**
 * Elabora i prodotti selezionati (versione con SweetAlert2)
 */
Products.elaboraProdottiSelezionati = function() {
    if (State.selectedProductIds.length === 0) {
        UI.mostraNotifica('Seleziona almeno un prodotto', 'warning');
        return;
    }
    
    // Usa mostraConferma invece di confirm nativo
    UI.mostraConferma(
        `Vuoi elaborare ${State.selectedProductIds.length} prodotti selezionati?`,
        'Conferma elaborazione',
        function() {
            // Imposta coda di elaborazione
            State.setupProcessingQueue(State.selectedProductIds);
            
            // Mostra interfaccia elaborazione
            UI.mostraInterfacciaElaborazione();
            
            // Avvia elaborazione
            Products.elaboraProssimiProdotti();
        },
        'question'
    );
};

/**
 * Resetta i tag dei prodotti selezionati (versione con SweetAlert2)
 */
Products.resetTagProdottiSelezionati = function() {
    if (State.selectedProductIds.length === 0) {
        UI.mostraNotifica('Seleziona almeno un prodotto', 'warning');
        return;
    }
    
    // Usa mostraConferma invece di confirm nativo
    UI.mostraConferma(
        `Sei sicuro di voler resettare lo stato dei tag per ${State.selectedProductIds.length} prodotti selezionati?`,
        'Conferma reset',
        function() {
            API.resetTagProdotti(State.selectedProductIds).then(response => {
                UI.mostraNotifica(`Reset completato per ${response.data.count} prodotti`, 'success');
                
                // Ricarica prodotti e statistiche
                API.caricaProdotti();
                API.caricaStatistiche();
            });
        },
        'warning'
    );
};

/**
 * Ferma l'elaborazione in corso (versione con SweetAlert2)
 */
Products.stoppaElaborazione = function() {
    UI.mostraConferma(
        'Sei sicuro di voler interrompere l\'elaborazione?',
        'Conferma interruzione',
        function() {
            State.stopRequested = true;
            UI.mostraNotifica('Interruzione in corso...', 'warning');
        },
        'warning'
    );
};

