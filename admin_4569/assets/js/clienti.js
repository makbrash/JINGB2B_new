// JavaScript Document
/** clienti.js
 * Gestione Anagrafica Clienti
 * Script per la gestione delle operazioni AJAX e UI per l'anagrafica clienti
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
    searchTerm: '',
    filterPagamento: '',
    cachedCataloghi: null
};

// Inizializzazione al caricamento della pagina
document.addEventListener('DOMContentLoaded', function() {
    // Carica i dati iniziali
    loadClienti();
    loadCataloghi();
    
    // Inizializza la data di apertura con la data odierna
    document.getElementById('data-apertura').valueAsDate = new Date();
    
    // Gestione eventi search e filtri
    document.getElementById('btn-search').addEventListener('click', handleSearch);
    document.getElementById('search-cliente').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') handleSearch();
    });
    
    document.getElementById('filter-pagamento').addEventListener('change', function() {
        APP_STATE.filterPagamento = this.value;
        APP_STATE.currentPage = 1;
        loadClienti();
    });
    
    // Gestione eventi form
    document.getElementById('btn-new-cliente').addEventListener('click', resetForm);
    document.getElementById('btn-save-cliente').addEventListener('click', saveCliente);
    document.getElementById('toggle-password').addEventListener('click', togglePasswordVisibility);
    document.getElementById('generate-password').addEventListener('click', generateRandomPassword);
    
    // Gestione preview avatar
    document.getElementById('avatar').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-avatar').src = '/'+e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
});

/**
 * Carica la lista dei clienti via AJAX
 */
function loadClienti() {
    const container = document.getElementById('cliente-cards-container');
    container.innerHTML = `
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Caricamento clienti in corso...</p>
        </div>
    `;
    
    // Prepara i parametri per la richiesta
    const params = {
		apicall: 'cliente',
        action: 'list_clienti',
        page: APP_STATE.currentPage,
        per_page: CONFIG.perPage,
        search: APP_STATE.searchTerm,
        filter_pagamento: APP_STATE.filterPagamento
    };
    
    // Effettua la chiamata AJAX
    fetchAPI(params)
        .then(response => {
            if (response.success) {
                APP_STATE.totalItems = response.total;
                APP_STATE.totalPages = Math.ceil(APP_STATE.totalItems / CONFIG.perPage);
                
                // Aggiorna le informazioni di paginazione
                updatePaginationInfo();
                
                // Popola le card dei clienti
                renderClienteCards(response.data);
            } else {
                showError('Errore nel caricamento dei clienti', response.error);
            }
        })
        .catch(error => {
            showError('Errore di comunicazione con il server', error);
        });
}

/** clienti.js
 * Carica la lista dei cataloghi disponibili
 */
function loadCataloghi() {
    const container = document.getElementById('container-cataloghi');
    
    // Verifica se i cataloghi sono già in cache
    if (APP_STATE.cachedCataloghi) {
        renderCataloghi(APP_STATE.cachedCataloghi);
        return;
    }
    
    // Effettua la chiamata AJAX
    fetchAPI({ 
	    apicall: 'cliente',
	    action: 'list_cataloghi' 
		})
        .then(response => {
            if (response.success) {
                APP_STATE.cachedCataloghi = response.data;
                renderCataloghi(response.data);
            } else {
                container.innerHTML = `<div class="alert alert-warning">Impossibile caricare i cataloghi</div>`;
            }
        })
        .catch(error => {
            container.innerHTML = `<div class="alert alert-danger">Errore di comunicazione con il server</div>`;
            console.error('Errore nel caricamento cataloghi:', error);
        });
}

/**
 * Rendering delle card dei clienti
 * @param {Array} clienti - Array di oggetti cliente
 */
function renderClienteCards(clienti) {
    const container = document.getElementById('cliente-cards-container');
    container.innerHTML = '';
    
    if (clienti.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="empty">
                    <div class="empty-icon">
                        <i class="ti ti-users text-muted" style="font-size: 3rem;"></i>
                    </div>
                    <p class="empty-title">Nessun cliente trovato</p>
                    <p class="empty-subtitle text-muted">
                        Nessun cliente corrisponde ai criteri di ricerca.
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
    const template = document.getElementById('template-cliente-card');
    
    clienti.forEach(cliente => {
        const card = template.content.cloneNode(true);
        
        // Imposta lo stato del cliente (attivo/sospeso)
        const statusBar = card.querySelector('[data-status]');
        statusBar.className = cliente.attivo ? 'card-status-top bg-success' : 'card-status-top bg-danger';
        
        // Imposta avatar
        const avatarEl = card.querySelector('[data-avatar]');
        avatarEl.style.backgroundImage = `url('/${cliente.avatar || CONFIG.defaultAvatar}')`;
        
        // Imposta informazioni generali
        card.querySelector('[data-nome-negozio]').textContent = cliente.nome_negozio;
        card.querySelector('[data-nome-cognome]').textContent = `${cliente.nome_referente} ${cliente.cognome_referente}`;
        
        // Badge status
        const badgeStatus = card.querySelector('[data-badge-status]');
        if (cliente.attivo) {
            badgeStatus.className = 'badge bg-success';
            badgeStatus.textContent = 'Attivo';
        } else {
            badgeStatus.className = 'badge bg-danger';
            badgeStatus.textContent = 'Sospeso';
        }
        
        // Altre informazioni
        card.querySelector('[data-data-apertura]').textContent = formatDate(cliente.data_apertura);
        
        const emailEl = card.querySelector('[data-email]');
        emailEl.textContent = cliente.email;
        emailEl.href = `mailto:${cliente.email}`;
        
        card.querySelector('[data-indirizzo]').textContent = cliente.indirizzo || 'Non specificato';
        
        const whatsappEl = card.querySelector('[data-whatsapp]');
        if (cliente.whatsapp) {
            whatsappEl.textContent = cliente.whatsapp;
            whatsappEl.href = `https://wa.me/${cliente.whatsapp.replace(/[^0-9]/g, '')}`;
        } else {
            whatsappEl.textContent = 'Non specificato';
            whatsappEl.removeAttribute('href');
        }
        
        // Metodo di pagamento
        card.querySelector('[data-pagamento]').textContent = formatTipoPagamento(cliente.tipo_pagamento);
        
        // Cataloghi
        const cataloghiContainer = card.querySelector('[data-cataloghi]');
        if (cliente.cataloghi && cliente.cataloghi.length > 0) {
            const cataloghiHtml = cliente.cataloghi.map(cat => 
                `<span class="badge bg-primary me-1 mb-1">${cat.nome}</span>`
            ).join('');
            cataloghiContainer.innerHTML = cataloghiHtml;
        } else {
            cataloghiContainer.innerHTML = '<span class="text-muted">Nessun catalogo assegnato</span>';
        }
        
        // Pulsanti azioni
        const btnEdit = card.querySelector('[data-btn-edit]');
        btnEdit.addEventListener('click', () => editCliente(cliente.id));
        
        const btnToggleStatus = card.querySelector('[data-btn-toggle-status]');
        const toggleLabel = card.querySelector('[data-toggle-label]');
        toggleLabel.textContent = cliente.attivo ? 'Sospendi' : 'Attiva';
        btnToggleStatus.addEventListener('click', () => toggleClienteStatus(cliente.id, !cliente.attivo));
        
        const btnResetPassword = card.querySelector('[data-btn-reset-password]');
        btnResetPassword.addEventListener('click', () => resetPassword(cliente.id));
        
        const btnViewNotes = card.querySelector('[data-btn-view-notes]');
        btnViewNotes.addEventListener('click', () => viewNotes(cliente));
        
        const btnDelete = card.querySelector('[data-btn-delete]');
        btnDelete.addEventListener('click', () => deleteCliente(cliente.id, cliente.nome_negozio));
        
        // Aggiungi la card al container
        container.appendChild(card);
    });
}

/**
 * Rendering opzioni cataloghi nel form
 * @param {Array} cataloghi - Array di oggetti catalogo
 */
function renderCataloghi(cataloghi) {
    const container = document.getElementById('container-cataloghi');
    
    if (!cataloghi || cataloghi.length === 0) {
        container.innerHTML = '<div class="text-muted">Nessun catalogo disponibile</div>';
        return;
    }
    
    const cataloghiHtml = cataloghi.map(cat => `
        <label class="form-selectgroup-item">
            <input type="checkbox" name="cataloghi[]" value="${cat.id}" class="form-selectgroup-input">
            <span class="form-selectgroup-label">${cat.nome}</span>
        </label>
    `).join('');
    
    container.innerHTML = cataloghiHtml;
}

/**
 * Gestione ricerca clienti
 */
function handleSearch() {
    const searchInput = document.getElementById('search-cliente');
    APP_STATE.searchTerm = searchInput.value.trim();
    APP_STATE.currentPage = 1;
    loadClienti();
}

/**
 * Reset della ricerca
 */
function resetSearch() {
    document.getElementById('search-cliente').value = '';
    document.getElementById('filter-pagamento').value = '';
    APP_STATE.searchTerm = '';
    APP_STATE.filterPagamento = '';
    APP_STATE.currentPage = 1;
    loadClienti();
}

/**
 * Aggiornamento informazioni di paginazione
 */
function updatePaginationInfo() {
    const infoEl = document.getElementById('pagination-info');
    const start = (APP_STATE.currentPage - 1) * CONFIG.perPage + 1;
    const end = Math.min(APP_STATE.currentPage * CONFIG.perPage, APP_STATE.totalItems);
    
    infoEl.textContent = `Mostrando ${start} a ${end} di ${APP_STATE.totalItems} clienti`;
    
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
            loadClienti();
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
                loadClienti();
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
            loadClienti();
        });
    }
    paginationContainer.appendChild(nextLi);
}

/**
 * Reset form cliente
 */
function resetForm() {
    document.getElementById('form-cliente').reset();
    document.getElementById('cliente-id').value = '0';
    document.getElementById('preview-avatar').src = CONFIG.defaultAvatar;
    document.getElementById('modal-title').textContent = 'Nuovo Cliente';
    document.getElementById('data-apertura').valueAsDate = new Date();
    document.getElementById('cliente-attivo').checked = true;
    
    // Reset cataloghi
    if (APP_STATE.cachedCataloghi) {
        const checkboxes = document.querySelectorAll('[name="cataloghi[]"]');
        checkboxes.forEach(cb => cb.checked = false);
    }
}

/**
 * Editing di un cliente esistente
 * @param {number} clienteId - ID del cliente
 */
function editCliente(clienteId) {
    // Effettua la chiamata per recuperare i dati del cliente
    fetchAPI({ 
	    apicall: 'cliente',
        action: 'get_cliente', 
        id: clienteId 
    })
    .then(response => {
        if (response.success && response.data) {
            const cliente = response.data;
            
            // Popola il form con i dati del cliente
            document.getElementById('cliente-id').value = cliente.id;
            document.getElementById('nome-referente').value = cliente.nome_referente;
            document.getElementById('cognome-referente').value = cliente.cognome_referente;
            document.getElementById('nome-negozio').value = cliente.nome_negozio;
            document.getElementById('email').value = cliente.email;
            document.getElementById('whatsapp').value = cliente.whatsapp || '';
            document.getElementById('indirizzo').value = cliente.indirizzo || '';
            document.getElementById('password').value = ''; // Non mostriamo la password esistente
            document.getElementById('data-apertura').value = cliente.data_apertura;
            document.getElementById('tipo-pagamento').value = cliente.tipo_pagamento;
            document.getElementById('cliente-attivo').checked = cliente.attivo == 1;
            document.getElementById('note-interne').value = cliente.note_interne || '';
            
            // Imposta avatar
            if (cliente.avatar) {
                document.getElementById('preview-avatar').src = '/'+cliente.avatar;
            } else {
                document.getElementById('preview-avatar').src = CONFIG.defaultAvatar;
            }
            
            // Seleziona i cataloghi assegnati
            if (cliente.cataloghi && cliente.cataloghi.length > 0) {
                const checkboxes = document.querySelectorAll('[name="cataloghi[]"]');
                const cataloghiIds = cliente.cataloghi.map(c => c.id.toString());
                
                checkboxes.forEach(cb => {
                    cb.checked = cataloghiIds.includes(cb.value);
                });
            }
            
            // Aggiorna titolo modal
            document.getElementById('modal-title').textContent = 'Modifica Cliente';
            
            // Apri il modal
            const modal = new bootstrap.Modal(document.getElementById('modal-cliente'));
            modal.show();
        } else {
            showError('Errore', 'Impossibile caricare i dati del cliente');
        }
    })
    .catch(error => {
        showError('Errore di comunicazione', error);
    });
}

/**
 * Salvataggio dati cliente
 */
function saveCliente() {
    // Validazione form
    const form = document.getElementById('form-cliente');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Raccolta dati form
    const clienteId = document.getElementById('cliente-id').value;
    const formData = new FormData(form);
    const isNewCliente = clienteId === '0';
    
    // Conversione FormData in oggetto
    const clienteData = {};
    formData.forEach((value, key) => {
        // Gestisci gli array (es. cataloghi[])
        if (key.endsWith('[]')) {
            const baseKey = key.slice(0, -2);
            if (!clienteData[baseKey]) {
                clienteData[baseKey] = [];
            }
            clienteData[baseKey].push(value);
        } else {
            clienteData[key] = value;
        }
    });
    
    // Aggiungi stato attivo
    clienteData.attivo = document.getElementById('cliente-attivo').checked ? 1 : 0;
    
    // Prepara parametri API
    const params = {
		apicall: 'cliente',
        action: isNewCliente ? 'add_cliente' : 'update_cliente',
        data: clienteData
    };
    
    // Gestione upload avatar (se presente)
    const avatarFile = document.getElementById('avatar').files[0];
    if (avatarFile) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Aggiungi base64 dell'immagine
            params.data.avatar_data = e.target.result;
            sendSaveRequest(params);
        };
        reader.readAsDataURL(avatarFile);
    } else {
        sendSaveRequest(params);
    }
}

/**
 * Invia richiesta di salvataggio
 */
function sendSaveRequest(params) {
    // Mostra loading
    const saveBtn = document.getElementById('btn-save-cliente');
    const originalContent = saveBtn.innerHTML;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvataggio...';
    saveBtn.disabled = true;
    
    fetchAPI(params)
        .then(response => {
            if (response.success) {
                // Chiudi il modal
                bootstrap.Modal.getInstance(document.getElementById('modal-cliente')).hide();
                
                // Notifica successo
                showSuccess(params.action === 'add_cliente' ? 
                    'Cliente aggiunto con successo' : 
                    'Cliente aggiornato con successo');
                
                // Ricarica clienti
                loadClienti();
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
 * Cambio stato cliente (attivo/sospeso)
 */
function toggleClienteStatus(clienteId, newStatus) {
    if (!confirm(`Sei sicuro di voler ${newStatus ? 'attivare' : 'sospendere'} questo cliente?`)) {
        return;
    }
    
    fetchAPI({
		apicall: 'cliente',
        action: 'toggle_cliente_status',
        id: clienteId,
        attivo: newStatus ? 1 : 0
    })
    .then(response => {
        if (response.success) {
            showSuccess(`Cliente ${newStatus ? 'attivato' : 'sospeso'} con successo`);
            loadClienti();
        } else {
            showError('Errore nel cambio stato', response.error);
        }
    })
    .catch(error => {
        showError('Errore di comunicazione', error);
    });
}

/**
 * Reset password cliente
 */
function resetPassword(clienteId) {
    const newPassword = generateRandomString(10);
    
    if (!confirm(`Sei sicuro di voler resettare la password di questo cliente?\nLa nuova password sarà: ${newPassword}`)) {
        return;
    }
    
    fetchAPI({
		apicall: 'cliente',
        action: 'reset_password_cliente',
        id: clienteId,
        password: newPassword
    })
    .then(response => {
        if (response.success) {
            showSuccess(`Password resettata con successo: ${newPassword}`);
        } else {
            showError('Errore nel reset password', response.error);
        }
    })
    .catch(error => {
        showError('Errore di comunicazione', error);
    });
}

/**
 * Visualizza note cliente
 */
function viewNotes(cliente) {
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
    
    const notes = cliente.note_interne || 'Nessuna nota disponibile';
    
    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Note: ${cliente.nome_negozio}</h5>
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
 * Elimina cliente
 */
function deleteCliente(clienteId, nomeNegozio) {
    if (!confirm(`Sei sicuro di voler eliminare il cliente "${nomeNegozio}"?\nQuesta operazione non può essere annullata.`)) {
        return;
    }
    
    fetchAPI({
		apicall: 'cliente',
        action: 'delete_cliente',
        id: clienteId
    })
    .then(response => {
        if (response.success) {
            showSuccess('Cliente eliminato con successo');
            loadClienti();
        } else {
            showError('Errore nell\'eliminazione', response.error);
        }
    })
    .catch(error => {
        showError('Errore di comunicazione', error);
    });
}

/**
 * Toggle visibilità password
 */
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const icon = document.querySelector('#toggle-password i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.className = 'ti ti-eye-off';
    } else {
        passwordInput.type = 'password';
        icon.className = 'ti ti-eye';
    }
}

/**
 * Genera password casuale
 */
function generateRandomPassword() {
    const password = generateRandomString(10);
    document.getElementById('password').value = password;
    document.getElementById('password').type = 'text';
    document.querySelector('#toggle-password i').className = 'ti ti-eye-off';
}

/**
 * Genera stringa casuale
 * @param {number} length - Lunghezza della stringa
 * @return {string} Stringa casuale
 */
function generateRandomString(length) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
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
 * Formatta il tipo di pagamento
 * @param {string} tipo - Codice tipo pagamento
 * @return {string} Descrizione pagamento
 */
function formatTipoPagamento(tipo) {
    const tipi = {
        '30gg': 'Pagamento a 30 giorni',
        '60gg': 'Pagamento a 60 giorni',
        'anticipato': 'Pagamento anticipato',
        'contrassegno': 'Contrassegno'
    };
    
    return tipi[tipo] || tipo || 'Non specificato';
}

/**
 * Funzione per effettuare chiamate AJAX all'API
 * @param {Object} params - Parametri da inviare all'API
 * @return {Promise} Promise con la risposta
 */
function fetchAPI(params) {
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
        .then(resolve)
        .catch(reject);
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