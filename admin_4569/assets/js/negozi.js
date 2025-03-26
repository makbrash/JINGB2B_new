// JavaScript Document
/** negozi.js
 * Gestione Anagrafica Negozi
 * Script per la gestione delle operazioni AJAX e UI per l'anagrafica negozi
 */

// Configurazione globale
const CONFIG = {
    perPage: 50,
    apiUrl: '/api/proxy_request.php',
    defaultAvatar: '/img/logo panda.webp'
};

// Stato dell'applicazione
let APP_STATE = {
    currentPage: 1,
    totalItems: 0,
    totalPages: 0,
    searchTerm: ''
};

// Inizializzazione al caricamento della pagina
document.addEventListener('DOMContentLoaded', function() {
    // Aggiungiamo un controllo per verificare se la pagina è già stata caricata
    checkAppState();
    
    // Carica i dati iniziali
    loadNegozi();
    
    // Gestione eventi search
    document.getElementById('btn-search').addEventListener('click', handleSearch);
    document.getElementById('search-negozio').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') handleSearch();
    });
    
    // Gestione eventi form
    document.getElementById('btn-new-negozio').addEventListener('click', resetForm);
    document.getElementById('btn-save-negozio').addEventListener('click', saveNegozio);
    
    // Gestione preview immagine
    document.getElementById('immagine').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-immagine').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
});

/**
 * Verifica lo stato dell'applicazione e risolve eventuali problemi
 */
function checkAppState() {
    console.log('Verifica stato applicazione...');
    
    // Verifica se il container delle card esiste
    const container = document.getElementById('negozio-cards-container');
    if (!container) {
        console.error('Container cards non trovato!');
        return;
    }
    
    // Imposta un timer di controllo per verificare il caricamento dei dati
    setTimeout(() => {
        // Se dopo 5 secondi il container è vuoto o contiene solo il loader, riprova a caricare
        const content = container.innerHTML.trim();
        if (content === '' || content.includes('spinner-border')) {
            console.warn('Possibile problema di caricamento rilevato, riprovo...');
            loadNegozi();
        }
    }, 5000);
    
    // Aggiungi un gestore di eventi per ricaricare i dati quando la finestra torna in focus
    window.addEventListener('focus', () => {
        console.log('Finestra tornata in focus, ricarico i dati...');
        loadNegozi();
    });
}

/**
 * Carica la lista dei negozi via AJAX
 */
function loadNegozi() {
    console.log('Caricamento negozi...');
    
    const container = document.getElementById('negozio-cards-container');
    container.innerHTML = `
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Caricamento negozi in corso...</p>
        </div>
    `;
    
    // Prepara i parametri per la richiesta
    const params = {
        apicall: 'negozio',
        action: 'list_negozi',
        page: APP_STATE.currentPage,
        per_page: CONFIG.perPage,
        search: APP_STATE.searchTerm
    };
    
    // Effettua la chiamata AJAX
    fetchAPI(params)
        .then(response => {
            console.log('Risposta loadNegozi ricevuta:', response);
            
            if (response.success) {
                APP_STATE.totalItems = response.total;
                APP_STATE.totalPages = Math.ceil(APP_STATE.totalItems / CONFIG.perPage);
                
                // Aggiorna le informazioni di paginazione
                updatePaginationInfo();
                
                // Popola le card dei negozi
                renderNegozioCards(response.data);
                console.log('Rendering negozi completato, totale:', response.data.length);
            } else {
                showError('Errore nel caricamento dei negozi', response.error);
            }
        })
        .catch(error => {
            console.error('Errore in loadNegozi:', error);
            showError('Errore di comunicazione con il server', error);
        });
}

/**
 * Rendering delle card dei negozi
 * @param {Array} negozi - Array di oggetti negozio
 */
function renderNegozioCards(negozi) {
    console.log('Rendering negozi:', negozi);
    
    const container = document.getElementById('negozio-cards-container');
    if (!container) {
        console.error('Container delle card non trovato!');
        return;
    }
    
    // Pulisci il container
    container.innerHTML = '';
    
    // Verifica che negozi sia un array valido
    if (!Array.isArray(negozi)) {
        console.error('Errore: i dati dei negozi non sono in formato array', negozi);
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="alert alert-danger">
                    <i class="ti ti-alert-circle me-2"></i>
                    Errore nel formato dei dati. Ricarica la pagina.
                </div>
            </div>
        `;
        return;
    }
    
    if (negozi.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="empty">
                    <div class="empty-icon">
                        <i class="ti ti-building-store text-muted" style="font-size: 3rem;"></i>
                    </div>
                    <p class="empty-title">Nessun negozio trovato</p>
                    <p class="empty-subtitle text-muted">
                        Nessun negozio corrisponde ai criteri di ricerca.
                    </p>
                    <div class="empty-action">
                        <button class="btn btn-primary" onclick="resetSearch()">
                            <i class="ti ti-refresh"></i> Reimposta ricerca
                        </button>
                    </div>
                </div>
            </div>
        `;
        return;
    }
    
    // Usa il template per creare le card
    const template = document.getElementById('template-negozio-card');
    
    negozi.forEach(negozio => {
        const card = template.content.cloneNode(true);
        
        // Imposta lo stato del negozio (attivo/sospeso)
        const statusBar = card.querySelector('[data-status]');
        statusBar.className = negozio.stanby == 0 ? 'card-status-top bg-success' : 'card-status-top bg-danger';
        
        // Imposta immagine
        const imageEl = card.querySelector('[data-immagine]');
        if (negozio.immagine) {
            imageEl.style.backgroundImage = `url('/${negozio.immagine}')`;
        } else {
            imageEl.style.backgroundImage = `url('${CONFIG.defaultAvatar}')`;
        }
        
        // Imposta informazioni generali
        card.querySelector('[data-nome]').textContent = negozio.nome;
        
        // Descrizione breve (max 100 caratteri)
        const descBrief = negozio.descrizione && negozio.descrizione.length > 100 
            ? negozio.descrizione.substring(0, 97) + '...' 
            : negozio.descrizione || 'Nessuna descrizione';
        card.querySelector('[data-descrizione-breve]').textContent = descBrief;
        
        // Badge status
        const badgeStatus = card.querySelector('[data-badge-status]');
        if (negozio.stanby == 0) {
            badgeStatus.className = 'badge bg-success';
            badgeStatus.textContent = 'Attivo';
        } else {
            badgeStatus.className = 'badge bg-danger';
            badgeStatus.textContent = 'Sospeso';
        }
        
        // Altre informazioni
        card.querySelector('[data-data-creazione]').textContent = formatDate(negozio.data_creazione);
        
        const emailEl = card.querySelector('[data-email]');
        if (negozio.email) {
            emailEl.textContent = negozio.email;
            emailEl.href = `mailto:${negozio.email}`;
        } else {
            emailEl.textContent = 'Non specificato';
            emailEl.removeAttribute('href');
        }
        
        const whatsappEl = card.querySelector('[data-whatsapp]');
        if (negozio.whatsapp) {
            whatsappEl.textContent = negozio.whatsapp;
            whatsappEl.href = `https://wa.me/${negozio.whatsapp.replace(/[^0-9]/g, '')}`;
        } else {
            whatsappEl.textContent = 'Non specificato';
            whatsappEl.removeAttribute('href');
        }
        
        // Gestione campo data_import (potrebbe essere disabilitato nel backend)
        const dataImportEl = card.querySelector('[data-data-import]');
        if (dataImportEl) {
            if (negozio.data_import) {
                dataImportEl.textContent = formatDate(negozio.data_import);
            } else {
                dataImportEl.textContent = 'Non specificato';
            }
        }
        
        card.querySelector('[data-ordinamento]').textContent = negozio.ordinamento || '0';
        
        // Pulsanti azioni
        const btnEdit = card.querySelector('[data-btn-edit]');
        btnEdit.addEventListener('click', () => editNegozio(negozio.id));
        
        const btnToggleStatus = card.querySelector('[data-btn-toggle-status]');
        const toggleLabel = card.querySelector('[data-toggle-label]');
        
        // Imposta colore e testo in base allo stato attuale
        if (negozio.stanby == 0) {
            // Se attivo, mostra pulsante rosso per sospendere
            btnToggleStatus.className = 'btn btn-outline-danger btn-sm';
            toggleLabel.textContent = 'Sospendi';
        } else {
            // Se sospeso, mostra pulsante verde per attivare
            btnToggleStatus.className = 'btn btn-outline-success btn-sm';
            toggleLabel.textContent = 'Attiva';
        }
        
        // Se stanby = 0 (attivo), newStatus deve essere true
        // Se stanby = 1 (sospeso), newStatus deve essere false
        btnToggleStatus.addEventListener('click', () => toggleNegozioStatus(negozio.id, negozio.stanby == 0));
        
        const btnViewNotes = card.querySelector('[data-btn-view-notes]');
        btnViewNotes.addEventListener('click', () => viewNotes(negozio));
        
        const btnDelete = card.querySelector('[data-btn-delete]');
        btnDelete.addEventListener('click', () => deleteNegozio(negozio.id, negozio.nome));
        
        // Aggiungi la card al container
        container.appendChild(card);
    });
}

/**
 * Gestione ricerca negozi
 */
function handleSearch() {
    const searchInput = document.getElementById('search-negozio');
    APP_STATE.searchTerm = searchInput.value.trim();
    APP_STATE.currentPage = 1;
    loadNegozi();
}

/**
 * Reset della ricerca
 */
function resetSearch() {
    document.getElementById('search-negozio').value = '';
    APP_STATE.searchTerm = '';
    APP_STATE.currentPage = 1;
    loadNegozi();
}

/**
 * Aggiornamento informazioni di paginazione
 */
function updatePaginationInfo() {
    const infoEl = document.getElementById('pagination-info');
    const start = (APP_STATE.currentPage - 1) * CONFIG.perPage + 1;
    const end = Math.min(APP_STATE.currentPage * CONFIG.perPage, APP_STATE.totalItems);
    
    infoEl.textContent = `Mostrando ${start} a ${end} di ${APP_STATE.totalItems} negozi`;
    
    // Aggiorna i controlli di paginazione
    const paginationContainer = document.getElementById('pagination-container');
    paginationContainer.innerHTML = '';
    
    // Pulsante precedente
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${APP_STATE.currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `
        <a class="page-link" href="#" aria-label="Previous">
            <i class="ti ti-chevron-left"></i>
            prev
        </a>
    `;
    if (APP_STATE.currentPage > 1) {
        prevLi.querySelector('a').addEventListener('click', (e) => {
            e.preventDefault();
            APP_STATE.currentPage--;
            loadNegozi();
        });
    }
    paginationContainer.appendChild(prevLi);
    
    // Pagine
    const maxPages = Math.min(5, APP_STATE.totalPages);
    let startPage = Math.max(1, APP_STATE.currentPage - 2);
    let endPage = Math.min(APP_STATE.totalPages, startPage + maxPages - 1);
    
    if (endPage - startPage + 1 < maxPages) {
        startPage = Math.max(1, endPage - maxPages + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const pageLi = document.createElement('li');
        pageLi.className = `page-item ${i === APP_STATE.currentPage ? 'active' : ''}`;
        pageLi.innerHTML = `<a class="page-link" href="#">${i}</a>`;
        
        if (i !== APP_STATE.currentPage) {
            pageLi.querySelector('a').addEventListener('click', (e) => {
                e.preventDefault();
                APP_STATE.currentPage = i;
                loadNegozi();
            });
        }
        
        paginationContainer.appendChild(pageLi);
    }
    
    // Pulsante successivo
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${APP_STATE.currentPage === APP_STATE.totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `
        <a class="page-link" href="#" aria-label="Next">
            next
            <i class="ti ti-chevron-right"></i>
        </a>
    `;
    if (APP_STATE.currentPage < APP_STATE.totalPages) {
        nextLi.querySelector('a').addEventListener('click', (e) => {
            e.preventDefault();
            APP_STATE.currentPage++;
            loadNegozi();
        });
    }
    paginationContainer.appendChild(nextLi);
}

/**
 * Reset form negozio
 */
function resetForm() {
    document.getElementById('form-negozio').reset();
    document.getElementById('negozio-id').value = '0';
    document.getElementById('preview-immagine').src = CONFIG.defaultAvatar;
    document.getElementById('modal-title').textContent = 'Nuovo Negozio';
    document.getElementById('negozio-stanby').checked = true;
}

/**
 * Editing di un negozio esistente
 * @param {number} negozioId - ID del negozio
 */
function editNegozio(negozioId) {
    // Effettua la chiamata per recuperare i dati del negozio
    fetchAPI({ 
        apicall: 'negozio',
        action: 'get_negozio', 
        id: negozioId 
    })
    .then(response => {
        if (response.success && response.data) {
            const negozio = response.data;
            
            // Popola il form con i dati del negozio
            document.getElementById('negozio-id').value = negozio.id;
            document.getElementById('nome').value = negozio.nome;
            document.getElementById('descrizione').value = negozio.descrizione || '';
            document.getElementById('email').value = negozio.email || '';
            document.getElementById('whatsapp').value = negozio.whatsapp || '';
            
            // Gestione campo data_import (potrebbe essere disabilitato nel backend)
            const dataImportEl = document.getElementById('data-import');
            if (dataImportEl) {
                if (negozio.data_import) {
                    dataImportEl.value = negozio.data_import;
                } else {
                    dataImportEl.value = '';
                }
            }
            
            document.getElementById('ordinamento').value = negozio.ordinamento || '0';
            document.getElementById('negozio-stanby').checked = negozio.stanby == 0;
            document.getElementById('note').value = negozio.note || '';
            document.getElementById('json-layout-catalogo').value = negozio.json_layout_catalogo || '';
            
            // Imposta immagine
            if (negozio.immagine) {
                document.getElementById('preview-immagine').src = '/' + negozio.immagine;
            } else {
                document.getElementById('preview-immagine').src = CONFIG.defaultAvatar;
            }
            
            // Aggiorna titolo modal
            document.getElementById('modal-title').textContent = 'Modifica Negozio';
            
            // Apri il modal
            const modal = new bootstrap.Modal(document.getElementById('modal-negozio'));
            modal.show();
        } else {
            showError('Errore', 'Impossibile caricare i dati del negozio');
        }
    })
    .catch(error => {
        showError('Errore di comunicazione', error);
    });
}

/**
 * Salvataggio dati negozio
 */
function saveNegozio() {
    // Validazione form
    const form = document.getElementById('form-negozio');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Raccolta dati form
    const negozioId = document.getElementById('negozio-id').value;
    const formData = new FormData(form);
    const isNewNegozio = negozioId === '0';
    
    // Conversione FormData in oggetto
    const negozioData = {};
    formData.forEach((value, key) => {
        negozioData[key] = value;
    });
    
    // Gestione checkbox stanby (0 = attivo, 1 = sospeso)
    negozioData.stanby = document.getElementById('negozio-stanby').checked ? 0 : 1;
    
    // Prepara parametri API
    const params = {
        apicall: 'negozio',
        action: isNewNegozio ? 'add_negozio' : 'update_negozio',
        data: negozioData
    };
    
    // Gestione upload immagine (se presente)
    const immagineFile = document.getElementById('immagine').files[0];
    if (immagineFile) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Aggiungi base64 dell'immagine
            params.data.immagine_data = e.target.result;
            sendSaveRequest(params);
        };
        reader.readAsDataURL(immagineFile);
    } else {
        sendSaveRequest(params);
    }
}

/**
 * Invia richiesta di salvataggio
 */
function sendSaveRequest(params) {
    // Mostra loading
    const saveBtn = document.getElementById('btn-save-negozio');
    const originalContent = saveBtn.innerHTML;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvataggio...';
    saveBtn.disabled = true;
    
    // Chiudi il modal prima di fare la chiamata per evitare problemi di UI
    const modalElement = document.getElementById('modal-negozio');
    const modal = bootstrap.Modal.getInstance(modalElement);
    
    fetchAPI(params)
        .then(response => {
            if (response.success) {
                // Chiudi il modal se ancora aperto
                if (modal) {
                    modal.hide();
                    
                    // Attendi che la transizione del modal sia completata
                    modalElement.addEventListener('hidden.bs.modal', function onModalHidden() {
                        modalElement.removeEventListener('hidden.bs.modal', onModalHidden);
                        
                        // Notifica successo
                        showSuccess(params.action === 'add_negozio' ? 
                            'Negozio aggiunto con successo' : 
                            'Negozio aggiornato con successo');
                        
                        // Ricarica negozi dopo la chiusura del modal
                        setTimeout(() => loadNegozi(), 100);
                    }, { once: true });
                } else {
                    // Se il modal è già chiuso, procedi direttamente
                    showSuccess(params.action === 'add_negozio' ? 
                        'Negozio aggiunto con successo' : 
                        'Negozio aggiornato con successo');
                    
                    // Ricarica negozi
                    setTimeout(() => loadNegozi(), 100);
                }
            } else {
                showError('Errore nel salvataggio', response.error);
            }
        })
        .catch(error => {
            showError('Errore di comunicazione', error);
        })
        .finally(() => {
            // Ripristina pulsante
            saveBtn.innerHTML = originalContent;
            saveBtn.disabled = false;
        });
}

/**
 * Cambio stato negozio (attivo/sospeso)
 */
function toggleNegozioStatus(negozioId, newStatus) {
    // Trova il pulsante e la card per l'aggiornamento immediato
    const negozioCards = document.querySelectorAll('.col-md-6.col-lg-4');
    let targetBtn = null;
    let targetCard = null;
    let statusBar = null;
    let statusBadge = null;
    
    // Cerca il pulsante e altri elementi da aggiornare
    negozioCards.forEach(card => {
        const toggleBtn = card.querySelector('[data-btn-toggle-status]');
        if (toggleBtn && toggleBtn.onclick && toggleBtn.onclick.toString().includes(negozioId)) {
            targetBtn = toggleBtn;
            targetCard = card;
            statusBar = card.querySelector('[data-status]');
            statusBadge = card.querySelector('[data-badge-status]');
            
            // Applica un overlay di caricamento
            card.style.position = 'relative';
            const loadingOverlay = document.createElement('div');
            loadingOverlay.className = 'updating-overlay';
            loadingOverlay.innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
            loadingOverlay.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.7);display:flex;justify-content:center;align-items:center;z-index:10;';
            card.appendChild(loadingOverlay);
        }
    });
    
    // Valore corretto per stanby: 0 = attivo, 1 = sospeso
    // Se newStatus = true, vogliamo attivarlo (stanby = 0)
    // Se newStatus = false, vogliamo sospenderlo (stanby = 1)
    fetchAPI({
        apicall: 'negozio',
        action: 'toggle_negozio_status',
        id: negozioId,
        stanby: newStatus ? 0 : 1
    })
    .then(response => {
        if (response.success) {
            showSuccess(`Negozio ${newStatus ? 'attivato' : 'sospeso'} con successo`);
            
            // Aggiorna immediatamente l'interfaccia
            if (targetBtn && targetCard) {
                // Rimuovi overlay di caricamento
                const overlay = targetCard.querySelector('.updating-overlay');
                if (overlay) overlay.remove();
                
                // Aggiorna il colore e il testo del pulsante
                const toggleLabel = targetBtn.querySelector('[data-toggle-label]');
                if (toggleLabel) {
                    toggleLabel.textContent = newStatus ? 'Sospendi' : 'Attiva';
                }
                
                // Cambia lo stile del pulsante in base allo stato
                if (newStatus) {
                    // Negozio attivo - pulsante rosso per sospendere
                    targetBtn.className = 'btn btn-outline-danger btn-sm';
                } else {
                    // Negozio sospeso - pulsante verde per attivare
                    targetBtn.className = 'btn btn-outline-success btn-sm';
                }
                
                // Aggiorna la barra di stato superiore
                if (statusBar) {
                    statusBar.className = newStatus ? 'card-status-top bg-success' : 'card-status-top bg-danger';
                }
                
                // Aggiorna il badge di stato
                if (statusBadge) {
                    statusBadge.className = newStatus ? 'badge bg-success' : 'badge bg-danger';
                    statusBadge.textContent = newStatus ? 'Attivo' : 'Sospeso';
                }
                
                // Aggiorna la funzione onclick del pulsante per invertire l'azione
                targetBtn.onclick = () => toggleNegozioStatus(negozioId, !newStatus);
            } else {
                // Se non siamo riusciti a trovare gli elementi, ricarica tutti i dati
                loadNegozi();
            }
        } else {
            showError('Errore nel cambio stato', response.error);
            
            // Rimuovi l'overlay se c'è stato un errore
            if (targetCard) {
                const overlay = targetCard.querySelector('.updating-overlay');
                if (overlay) overlay.remove();
            }
        }
    })
    .catch(error => {
        showError('Errore di comunicazione', error);
        
        // Rimuovi l'overlay in caso di errore
        if (targetCard) {
            const overlay = targetCard.querySelector('.updating-overlay');
            if (overlay) overlay.remove();
        }
    });
}

/**
 * Visualizza note negozio
 */
function viewNotes(negozio) {
    // Crea un modal per visualizzare le note
    const modalId = 'modal-notes';
    let modal = document.getElementById(modalId);
    
    // Se il modal esiste già, rimuovilo
    if (modal) {
        modal.remove();
    }
    
    // Crea il nuovo modal
    modal = document.createElement('div');
    modal.id = modalId;
    modal.className = 'modal modal-blur fade';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('role', 'dialog');
    
    const notes = negozio.note || 'Nessuna nota disponibile';
    
    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Note: ${negozio.nome}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>${notes}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Mostra il modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

/**
 * Elimina negozio
 */
function deleteNegozio(negozioId, nomeNegozio) {
    if (!confirm(`Sei sicuro di voler eliminare il negozio "${nomeNegozio}"?\nQuesta operazione non può essere annullata.`)) {
        return;
    }
    
    console.log('Eliminazione negozio:', negozioId, nomeNegozio);
    
    // Mostra indicatore di caricamento
    const negozioCards = document.querySelectorAll('.col-md-6.col-lg-4');
    let targetCard = null;
    
    // Trova la card del negozio che stiamo eliminando e aggiungi un overlay di caricamento
    negozioCards.forEach(card => {
        const deleteBtn = card.querySelector('[data-btn-delete]');
        if (deleteBtn && deleteBtn.onclick && deleteBtn.onclick.toString().includes(negozioId)) {
            targetCard = card;
            card.style.position = 'relative';
            const loadingOverlay = document.createElement('div');
            loadingOverlay.className = 'eliminating-overlay';
            loadingOverlay.innerHTML = '<div class="spinner-border text-danger" role="status"></div>';
            loadingOverlay.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.8);display:flex;justify-content:center;align-items:center;z-index:10;';
            card.appendChild(loadingOverlay);
        }
    });
    
    fetchAPI({
        apicall: 'negozio',
        action: 'delete_negozio',
        id: negozioId
    })
    .then(response => {
        if (response.success) {
            showSuccess('Negozio eliminato con successo');
            
            // Se abbiamo trovato la card, rimuoviamola con animazione
            if (targetCard) {
                targetCard.style.transition = 'all 0.5s ease';
                targetCard.style.opacity = '0';
                targetCard.style.transform = 'scale(0.8)';
                
                setTimeout(() => {
                    // Rimuovi la card dal DOM
                    targetCard.remove();
                    
                    // Ricarica tutti i negozi per aggiornare la paginazione
                    setTimeout(() => loadNegozi(), 100);
                }, 500);
            } else {
                // Se non abbiamo trovato la card, ricarica tutto
                loadNegozi();
            }
        } else {
            showError('Errore nell\'eliminazione', response.error);
            
            // Rimuovi l'overlay se c'è stato un errore
            if (targetCard) {
                const overlay = targetCard.querySelector('.eliminating-overlay');
                if (overlay) overlay.remove();
            }
        }
    })
    .catch(error => {
        showError('Errore di comunicazione', error);
        
        // Rimuovi l'overlay in caso di errore
        if (targetCard) {
            const overlay = targetCard.querySelector('.eliminating-overlay');
            if (overlay) overlay.remove();
        }
    });
}

/**
 * Formatta una data in formato leggibile
 * @param {string} dateString - Data in formato YYYY-MM-DD
 * @return {string} Data formattata
 */
function formatDate(dateString) {
    if (!dateString) return 'N/D';
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT', { 
        day: '2-digit',
        month: '2-digit', 
        year: 'numeric' 
    });
}

/**
 * Funzione per effettuare chiamate AJAX all'API
 * @param {Object} params - Parametri da inviare all'API
 * @return {Promise} Promise con la risposta
 */
function fetchAPI(params) {
    console.log('Chiamata API:', params); // Logging della chiamata
    
    return new Promise((resolve, reject) => {
        fetch(CONFIG.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(params)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Errore HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Risposta API:', data); // Logging della risposta
            resolve(data);
        })
        .catch(error => {
            console.error('Errore API:', error); // Logging degli errori
            reject(error);
        });
    });
}

/**
 * Mostra messaggio di successo
 * @param {string} message - Messaggio da mostrare
 */
function showSuccess(message) {
    Toastify({
        text: message,
        duration: 3000,
        gravity: "top",
        position: "right",
        backgroundColor: "#10b981",
    }).showToast();
}

/**
 * Mostra messaggio di errore
 * @param {string} title - Titolo errore
 * @param {string} message - Messaggio errore
 */
function showError(title, message) {
    console.error(`${title}: ${message}`);
    
    Toastify({
        text: `${title}: ${message}`,
        duration: 5000,
        gravity: "top",
        position: "right",
        backgroundColor: "#ef4444",
        close: true
    }).showToast();
} 