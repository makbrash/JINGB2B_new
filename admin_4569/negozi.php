<?php
/**
 * Anagrafica Negozi - Gestione negozi in visualizzazione card
 */

// Imposta titolo pagina e descrizione
$pageTitle = "Anagrafica Negozi";
$pageSubtitle = "Gestione e registrazione negozi";

// Includi file di configurazione e funzioni
require_once '../config/config.php';
require_once 'includes/functions.php';

// Impostazioni paginazione
$perPage = 50;

// Include header e altri componenti dell'interfaccia
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/navbar.php';
require_once 'includes/page-header.php';
?>

<!-- Toolbar di ricerca e filtri -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-8">
                <div class="input-group">
                    <input type="text" id="search-negozio" class="form-control" placeholder="Cerca negozio...">
                    <button class="btn btn-primary" type="button" id="btn-search">
                        <i class="ti ti-search"></i>
                        Cerca
                    </button>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-success w-100" id="btn-new-negozio" data-bs-toggle="modal" data-bs-target="#modal-negozio">
                    <i class="ti ti-plus"></i> Nuovo Negozio
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Contenitore cards negozi -->
<div class="row row-cards" id="negozio-cards-container">
    <!-- Le card dei negozi verranno caricate qui tramite AJAX -->
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2">Caricamento negozi in corso...</p>
    </div>
</div>

<!-- Paginazione -->
<div class="card mt-3">
    <div class="card-footer d-flex align-items-center">
        <p class="m-0 text-muted" id="pagination-info">
            Caricamento informazioni di paginazione...
        </p>
        <ul class="pagination m-0 ms-auto" id="pagination-container">
            <!-- Paginazione generata dinamicamente via JS -->
        </ul>
    </div>
</div>

<!-- Modal Form Negozio -->
<div class="modal modal-blur fade" id="modal-negozio" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Nuovo Negozio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-negozio">
                    <input type="hidden" id="negozio-id" name="id" value="0">
                    
                    <div class="row mb-3">
                        <div class="col-md-9">
                            <div class="mb-3">
                                <label class="form-label required">Nome Negozio</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descrizione</label>
                                <textarea class="form-control" id="descrizione" name="descrizione" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Immagine</label>
                                <div class="text-center mb-2">
                                    <img src="/img/logo panda.webp" id="preview-immagine" class="avatar avatar-xl" alt="Immagine">
                                </div>
                                <input type="file" class="form-control" id="immagine" name="immagine" accept="image/*">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="email@esempio.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">WhatsApp</label>
                            <input type="text" class="form-control" id="whatsapp" name="whatsapp" placeholder="+39...">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data Importazione</label>
                            <input type="date" class="form-control" id="data-import" name="data_import">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ordinamento</label>
                            <input type="number" class="form-control" id="ordinamento" name="ordinamento" value="0" min="0">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stato Negozio</label>
                            <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column">
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="checkbox" name="stanby" id="negozio-stanby" value="0" class="form-selectgroup-input" checked>
                                    <div class="form-selectgroup-label d-flex align-items-center p-3">
                                        <div class="me-3">
                                            <span class="form-selectgroup-check"></span>
                                        </div>
                                        <div>
                                            Negozio Attivo
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea class="form-control" id="note" name="note" rows="3" placeholder="Note interne"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Layout Catalogo (JSON)</label>
                        <textarea class="form-control" id="json-layout-catalogo" name="json_layout_catalogo" rows="4" placeholder="Configurazione JSON del layout catalogo"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                    Annulla
                </button>
                <button type="button" class="btn btn-primary ms-auto" id="btn-save-negozio">
                    <i class="ti ti-device-floppy"></i>
                    Salva Negozio
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Template per Card Negozio -->
<template id="template-negozio-card">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-status-top" data-status></div>
            <div class="card-body">
                <div class="row align-items-center mb-3">
                    <div class="col-auto">
                        <span class="avatar avatar-lg" data-immagine></span>
                    </div>
                    <div class="col">
                        <h3 class="mb-0" data-nome></h3>
                        <div class="text-muted" data-descrizione-breve></div>
                    </div>
                    <div class="col-auto">
                        <span class="badge" data-badge-status></span>
                    </div>
                </div>
                <div class="mb-2">
                    <i class="ti ti-calendar me-1"></i>
                    <span class="text-muted">Creato il: </span>
                    <strong data-data-creazione></strong>
                </div>
                <div class="mb-2">
                    <i class="ti ti-mail me-1"></i>
                    <a href="#" data-email></a>
                </div>
                <div class="mb-2">
                    <i class="ti ti-brand-whatsapp me-1"></i>
                    <a href="#" data-whatsapp></a>
                </div>
                <div class="mb-2">
                    <i class="ti ti-calendar-import me-1"></i>
                    <span class="text-muted">Data importazione: </span>
                    <span data-data-import></span>
                </div>
                <div class="mb-2">
                    <i class="ti ti-sort-ascending me-1"></i>
                    <span class="text-muted">Ordinamento: </span>
                    <span data-ordinamento></span>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <button class="btn btn-outline-primary btn-sm" data-btn-edit>
                        <i class="ti ti-edit"></i> Modifica
                    </button>
                    <div>
                        <button class="btn btn-outline-danger btn-sm" data-btn-toggle-status>
                            <i class="ti ti-ban"></i> <span data-toggle-label>Sospendi</span>
                        </button>
                        <div class="dropdown ms-1 d-inline-block">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="ti ti-dots-vertical"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="#" data-btn-view-notes>
                                    <i class="ti ti-notes me-2"></i> Visualizza Note
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="#" data-btn-delete>
                                    <i class="ti ti-trash me-2"></i> Elimina
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<?php
// Script specifici per la pagina
$pageScripts = [
    'assets/js/negozi.js'
];

// Includi il footer
require_once 'includes/footer.php';
?> 