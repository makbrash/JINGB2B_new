/**
 * products.js - Gestione prodotti e operazioni correlate
 * 
 * Questo modulo gestisce la visualizzazione dei prodotti e tutte le
 * operazioni ad essi correlate (elaborazione, reset, ricerca, ecc.).
 */

const Products = {
    /**
     * Renderizza i prodotti nella tabella usando DataTables
     * @param {Array} products - Lista di prodotti da visualizzare
     */
    renderizzaProdotti: function(products) {
        if (!products || products.length === 0) {
            $('#products-container').html('<div class="empty-state">Nessun prodotto trovato</div>');
            return;
        }
        
        // Se la tabella DataTable esiste già, distruggerla per ricaricare i dati
        if ($.fn.DataTable.isDataTable('#products-table')) {
            $('#products-table').DataTable().destroy();
        }
        
        // Se la tabella non esiste, crearla
        if ($('#products-table').length === 0) {
            $('#products-container').html(`
                <table id="products-table" class="display" style="width:100%">
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
                    <tbody></tbody>
                </table>
            `);
        }
        
        const self = this;
        // Inizializza DataTables
        const table = $('#products-table').DataTable({
            data: products,
            columns: [
                { 
                    // Colonna checkbox
                    data: null,
                    orderable: false,
                    className: 'select-checkbox',
                    render: function(data) {
                        const isChecked = State.selectedProductIds.includes(data.id.toString()) ? 'checked' : '';
                        return `<input type="checkbox" class="product-checkbox" value="${data.id}" ${isChecked}>`;
                    }
                },
                { data: 'id' },
                { 
                    // Colonna immagine
                    data: 'immagine',
                    orderable: false,
                    render: function(data) {
                        const imgSrc = data ? `../public/catalogo/${data}` : 'img/no-image.jpg';
                        return `<img src="${imgSrc}" class="product-thumbnail" alt="Immagine prodotto">`;
                    }
                },
                { 
                    // Colonna titolo
                    data: 'titolo',
                    render: function(data, type, row) {
                        return `<a href="#" class="product-title" data-id="${row.id}">${data}</a>`;
                    }
                },
                { 
                    // Colonna categoria
                    data: 'categoria_nome',
                    className: 'editable-cell',
                    render: function(data, type, row) {
                        return `<div data-field-type="categoria" data-value="${row.categoria_id}">${data}</div>`;
                    }
                },
                { 
                    // Colonna sottocategoria
                    data: 'sottocategoria_nome',
                    className: 'editable-cell',
                    render: function(data, type, row) {
                        return `<div data-field-type="sottocategoria" data-value="${row.sottocategoria_id}">${data}</div>`;
                    }
                },
                { 
                    // Colonna marca
                    data: 'marca_nome',
                    className: 'editable-cell',
                    render: function(data, type, row) {
                        return `<div data-field-type="marca" data-value="${row.marca_id}">${data}</div>`;
                    }
                },
                { 
                    // Colonna tags
                    data: 'tags',
                    className: 'editable-cell',
                    render: function(data, type, row) {
                        let tagsHtml = '';
                        if (data && data.length > 0) {
                            tagsHtml = '<div class="tag-container">';
                            data.forEach(function(tag) {
                                tagsHtml += `<span class="tag">${tag}</span>`;
                            });
                            tagsHtml += '</div>';
                        } else {
                            tagsHtml = '<em>Nessun tag</em>';
                        }
                        return `<div data-field-type="tags" data-value='${JSON.stringify(data || [])}'>${tagsHtml}</div>`;
                    }
                },
                { 
                    // Colonna stato
                    data: 'stato_tag',
                    render: function(data, type, row) {
                        let statusClass = '';
                        let statusText = '';
                        
                        switch(parseInt(data)) {
                            case 0:
                                statusClass = Config.statusClasses.pending;
                                statusText = Config.statusText[0];
                                break;
                            case 1:
                                statusClass = Config.statusClasses.done;
                                statusText = Config.statusText[1];
                                break;
                            case 2:
                                statusClass = Config.statusClasses.error;
                                statusText = Config.statusText[2];
                                break;
                            default:
                                statusClass = Config.statusClasses.unknown;
                                statusText = Config.statusText.default;
                        }
                        
                        return `<span class="status-badge ${statusClass}">${statusText}</span>`;
                    }
                },
                { 
                    // Colonna azioni
                    data: null,
                    orderable: false,
                    render: function(data) {
                        return `<button class="btn-process-product button button-sm" data-id="${data.id}">Elabora</button>`;
                    }
                }
            ],
            responsive: true,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/it-IT.json'
            },
            paging: true,
            pageLength: State.productsPerPage,
            lengthChange: false, // Usiamo il nostro selettore per il limite
            searching: false, // Usiamo la nostra ricerca
            info: true,
            autoWidth: false,
            order: [[1, 'asc']], // Ordina per ID di default
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: 'Excel',
                    className: 'button button-sm',
                    exportOptions: {
                        columns: [1, 3, 4, 5, 6, 7, 8]
                    }
                },
                {
                    extend: 'csv',
                    text: 'CSV',
                    className: 'button button-sm',
                    exportOptions: {
                        columns: [1, 3, 4, 5, 6, 7, 8]
                    }
                },
                {
                    extend: 'pdf',
                    text: 'PDF',
                    className: 'button button-sm',
                    exportOptions: {
                        columns: [1, 3, 4, 5, 6, 7, 8]
                    }
                }
            ],
            rowId: function(data) {
                return 'row-' + data.id;
            },
            rowCallback: function(row, data) {
                // Aggiungi classe di stato alla riga
                let rowClass = '';
                switch(parseInt(data.stato_tag)) {
                    case 0: rowClass = Config.statusClasses.pending; break;
                    case 1: rowClass = Config.statusClasses.done; break;
                    case 2: rowClass = Config.statusClasses.error; break;
                }
                $(row).addClass(rowClass);
                
                // Se il prodotto è selezionato, aggiungi classe selected
                if (State.selectedProductIds.includes(data.id.toString())) {
                    $(row).addClass('selected');
                }
            },
            drawCallback: function() {
                // Usa il contesto memorizzato
                self.configuraTabellaEventi();
            }
        });
        
        // Nascondi loader
        $('#products-loader').hide();
        
        return table;
    },
    
    /**
     * Configura eventi sulla tabella DataTables
     */
    configuraTabellaEventi: function() {
        // Gestione select-all checkbox
        $('#select-all-checkbox').off('change').on('change', function() {
            if ($(this).is(':checked')) {
                MultiSelect.selezionaTutti();
            } else {
                MultiSelect.deselezionaTutti();
            }
        });
        
        // Handler per i checkbox dei prodotti
        $('#products-table').off('change', '.product-checkbox').on('change', '.product-checkbox', function() {
            MultiSelect.gestisciCambioCheckbox($(this));
        });
        
        // Handler per processare un singolo prodotto
        $('#products-table').off('click', '.btn-process-product').on('click', '.btn-process-product', function() {
            Products.elaboraSingoloProdotto($(this).data('id'));
        });
        
        // Handler per visualizzare i dettagli di un prodotto
        $('#products-table').off('click', '.product-title').on('click', '.product-title', function(e) {
            e.preventDefault();
            UI.mostraDettagliProdotto($(this).data('id'));
        });
        
        // Abilita modifica inline
        if (typeof InlineEdit !== 'undefined' && typeof InlineEdit.abilitaModificaInlineRecord === 'function') {
            InlineEdit.abilitaModificaInlineRecord();
        }
    },
    
    /**
     * Aggiorna la riga di un prodotto dopo l'elaborazione
     * @param {Object} result - Risultato dell'elaborazione
     */
    aggiornaRigaProdotto: function(result) {
        const productId = result.id;
        
        // Verifica se stiamo usando DataTables
        if ($.fn.DataTable.isDataTable('#products-table')) {
            const table = $('#products-table').DataTable();
            
            // Trova il prodotto nei dati attuali
            const rowData = table.rows().data().toArray().find(p => p.id == productId);
            if (!rowData) return; // Prodotto non trovato
            
            // Aggiorna lo stato
            if (result.stato === 'successo') {
                rowData.stato_tag = 1; // Stato: elaborato
                
                // Aggiorna dati
                rowData.categoria_nome = result.nuovo.categoria.nome;
                rowData.categoria_id = result.nuovo.categoria.id;
                rowData.sottocategoria_nome = result.nuovo.sottocategoria.nome;
                rowData.sottocategoria_id = result.nuovo.sottocategoria.id;
                rowData.marca_nome = result.nuovo.marca.nome;
                rowData.marca_id = result.nuovo.marca.id;
                rowData.tags = result.nuovo.tags;
            } else {
                rowData.stato_tag = 2; // Stato: errore
            }
            
            // Cerca l'indice della riga
            const rowIndex = table.rows().indexes().toArray()
                .find(index => table.row(index).data().id == productId);
            
            if (rowIndex !== undefined) {
                // Ridisegna la riga con i nuovi dati
                table.row(rowIndex).data(rowData).draw(false);
            }
        } else {
            // Fallback per tabella tradizionale (non DataTables)
            const row = $(`tr[data-id="${productId}"]`);
            
            if (row.length === 0) return; // Riga non trovata
            
            // Aggiorna stato
            let statusClass = '';
            let statusText = '';
            
            if (result.stato === 'successo') {
                statusClass = Config.statusClasses.done;
                statusText = Config.statusText[1];
                
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
                statusClass = Config.statusClasses.error;
                statusText = Config.statusText[2];
            }
            
            // Aggiorna classe e testo stato
            row.removeClass('status-pending status-done status-error')
               .addClass(statusClass);
            row.find('td:eq(8)').html(`<span class="status-badge ${statusClass}">${statusText}</span>`);
            
            // Reset pulsante
            row.find('.btn-process-product').prop('disabled', false).text('Elabora');
        }
    },
    
    /**
     * Carica prodotti in base a un range
     */
    caricaProdottiRange: function() {
        const rangeStart = $('#range_start').val();
        const rangeEnd = $('#range_end').val();
        
        if (!rangeStart || !rangeEnd) {
            UI.mostraNotifica('Inserisci un range valido', 'warning');
            return;
        }
        
        // Reset selezione e pagina
        State.resetSelection();
        State.currentPage = 1;
        
        API.caricaProdotti({
            range_start: rangeStart,
            range_end: rangeEnd
        });
    },
    
    /**
     * Cerca prodotti in base al termine di ricerca
     */
    cercaProdotti: function() {
        const query = $('#search-query').val();
        
        if (!query) {
            UI.mostraNotifica('Inserisci un termine di ricerca', 'warning');
            return;
        }
        
        State.lastSearchQuery = query;
        
        // Reset selezione e pagina
        State.resetSelection();
        State.currentPage = 1;
        
        API.cercaProdotti(query);
    },
    
    /**
     * Filtra i prodotti in base allo stato
     * @param {string} stato - Stato del filtro (all, pending, done, error)
     */
    filtraProdottiPerStato: function(stato) {
        State.filterState = stato;
        API.caricaProdotti();
    },
    
    /**
     * Elabora i prodotti selezionati
     */
    elaboraProdottiSelezionati: function() {
        if (State.selectedProductIds.length === 0) {
            UI.mostraNotifica('Seleziona almeno un prodotto', 'warning');
            return;
        }
        
        // Usa mostraConferma se disponibile, altrimenti conf. nativo
        if (typeof UI !== 'undefined' && typeof UI.mostraConferma === 'function') {
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
        } else {
            // Conferma elaborazione
            if (!confirm(`Elaborare ${State.selectedProductIds.length} prodotti selezionati?`)) {
                return;
            }
            
            // Imposta coda di elaborazione
            State.setupProcessingQueue(State.selectedProductIds);
            
            // Mostra interfaccia elaborazione
            UI.mostraInterfacciaElaborazione();
            
            // Avvia elaborazione
            Products.elaboraProssimiProdotti();
        }
    },
    
    /**
     * Elabora il prossimo batch di prodotti nella coda
     */
    elaboraProssimiProdotti: function() {
        if (State.stopRequested) {
            Products.completaElaborazione('Elaborazione interrotta');
            return;
        }
        
        // Se la coda è vuota, termina elaborazione
        if (State.processingQueue.length === 0) {
            Products.completaElaborazione('Elaborazione completata');
            return;
        }
        
        // Ottieni prossimo batch di prodotti
        const batchSize = Math.min(Config.batchSize, State.processingQueue.length);
        const currentBatch = State.processingQueue.splice(0, batchSize);
        
        // Aggiorna UI
        $('#processing-status').text(`Elaborazione in corso: ${State.totalProcessed} di ${State.totalProcessed + State.processingQueue.length}`);
        
        // Elabora batch
        API.elaboraBatchProdotti(currentBatch).then(response => {
            // Aggiorna contatori
            State.totalProcessed += currentBatch.length;
            State.successCount += response.data.successo;
            State.errorCount += response.data.errore;
            
            // Aggiorna UI con risultati
            UI.aggiornaUIElaborazione();
            
            // Aggiorna singole righe prodotti
            response.data.dettagli.forEach(function(result) {
                Products.aggiornaRigaProdotto(result);
            });
            
            // Continua elaborazione dopo breve ritardo
            setTimeout(Products.elaboraProssimiProdotti, Config.autoProcessDelay);
        }).catch(() => {
            Products.completaElaborazione('Elaborazione interrotta per errore');
        });
    },
    
    /**
     * Completa l'elaborazione dei prodotti
     * @param {string} message - Messaggio da mostrare
     */
    completaElaborazione: function(message) {
        // Resetta stato
        State.resetProcessingState();
        
        // Aggiorna UI
        UI.completaInterfacciaElaborazione(message);
        
        // Ricarica statistiche
        API.caricaStatistiche();
    },
    
    /**
     * Elabora un singolo prodotto
     * @param {string} productId - ID del prodotto
     */
    elaboraSingoloProdotto: function(productId) {
        // Disabilita pulsante
        $(`.btn-process-product[data-id="${productId}"]`).prop('disabled', true).text('In corso...');
        
        API.elaboraSingoloProdotto(productId).then(response => {
            UI.mostraNotifica(`Prodotto ${productId} elaborato con successo`, 'success');
            Products.aggiornaRigaProdotto(response.data);
        }).catch(() => {
            $(`.btn-process-product[data-id="${productId}"]`).prop('disabled', false).text('Riprova');
        });
    },
    
    /**
     * Resetta i tag dei prodotti selezionati
     */
    resetTagProdottiSelezionati: function() {
        if (State.selectedProductIds.length === 0) {
            UI.mostraNotifica('Seleziona almeno un prodotto', 'warning');
            return;
        }
        
        // Usa mostraConferma se disponibile, altrimenti conf. nativo
        if (typeof UI !== 'undefined' && typeof UI.mostraConferma === 'function') {
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
        } else {
            // Conferma reset
            if (!confirm(`Resettare lo stato dei tag per ${State.selectedProductIds.length} prodotti selezionati?`)) {
                return;
            }
            
            API.resetTagProdotti(State.selectedProductIds).then(response => {
                UI.mostraNotifica(`Reset completato per ${response.data.count} prodotti`, 'success');
                
                // Ricarica prodotti e statistiche
                API.caricaProdotti();
                API.caricaStatistiche();
            });
        }
    },
    
    /**
     * Applica la classificazione manuale ai prodotti selezionati
     */
    applicaClassificazioneManuale: function() {
        // Verifica se è stato selezionato almeno un campo da aggiornare
        if (!$('#manual-update-categoria').prop('checked') && 
            !$('#manual-update-sottocategoria').prop('checked') && 
            !$('#manual-update-marca').prop('checked') && 
            !$('#manual-update-tags').prop('checked')) {
            UI.mostraNotifica('Seleziona almeno un campo da aggiornare', 'warning');
            return;
        }
        
        // Prepara dati da inviare
        const updateData = {
            action: 'manual_classify',
            product_ids: State.selectedProductIds,
            update_fields: {}
        };
        
        // Aggiungi campi da aggiornare
        if ($('#manual-update-categoria').prop('checked')) {
            updateData.update_fields.categoria_id = $('#manual-categoria').val();
        }
        
        if ($('#manual-update-sottocategoria').prop('checked')) {
            updateData.update_fields.sottocategoria_id = $('#manual-sottocategoria').val();
        }
        
        if ($('#manual-update-marca').prop('checked')) {
            updateData.update_fields.marca_id = $('#manual-marca').val();
        }
        
        if ($('#manual-update-tags').prop('checked')) {
            updateData.update_fields.tags = $('#manual-tags').val();
            updateData.update_fields.tags_mode = $('input[name="manual-tags-mode"]:checked').val();
        }
        
        // Mostra loader
        const $applyBtn = $('#btn-apply-manual');
        const originalText = $applyBtn.text();
        $applyBtn.prop('disabled', true).html('<span class="loader" style="width: 16px; height: 16px; border-width: 2px; margin-right: 8px;"></span> Aggiornamento...');
        
        // Invia richiesta al server
        API.applicaClassificazioneManuale(updateData).then(response => {
            // Chiudi modal
            $('#manual-classify-modal').hide();
            
            // Mostra notifica
            UI.mostraNotifica(response.message, 'success');
            
            // Ricarica prodotti e statistiche
            API.caricaProdotti();
            API.caricaStatistiche();
        }).catch(() => {
            $applyBtn.prop('disabled', false).text(originalText);
        });
    },
    
    /**
     * Ferma l'elaborazione in corso
     */
    stoppaElaborazione: function() {
        // Usa mostraConferma se disponibile, altrimenti conf. nativo
        if (typeof UI !== 'undefined' && typeof UI.mostraConferma === 'function') {
            UI.mostraConferma(
                'Sei sicuro di voler interrompere l\'elaborazione?',
                'Conferma interruzione',
                function() {
                    State.stopRequested = true;
                    UI.mostraNotifica('Interruzione in corso...', 'warning');
                },
                'warning'
            );
        } else {
            if (confirm('Sei sicuro di voler interrompere l\'elaborazione?')) {
                State.stopRequested = true;
                UI.mostraNotifica('Interruzione in corso...', 'warning');
            }
        }
    }
};