<?php
/**
 * Anagrafica Clienti - Gestione clienti in visualizzazione card
 */

// Imposta titolo pagina e descrizione
$pageTitle = "Anagrafica Clienti";
$pageSubtitle = "Gestione e registrazione clienti";

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
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" id="search-cliente" class="form-control" placeholder="Cerca cliente...">
                    <button class="btn btn-primary" type="button" id="btn-search">
                        <i class="ti ti-search"></i>
                        Cerca
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <select id="filter-pagamento" class="form-select">
                    <option value="">Tutti i metodi di pagamento</option>
                    <option value="vista">pagamento a vista sconto 2%</option>
                    <option value="30gg">rimessa diretta 30gg</option>
                    <option value="60gg">rimessa diretta 60gg</option>
                    <option value="anticipato">Pagamento anticipato</option>
                    <option value="contrassegno">Contrassegno</option>
                </select>
            </div>
            <div class="col-md-2 text-end">
                <button class="btn btn-success w-100" id="btn-new-cliente" data-bs-toggle="modal" data-bs-target="#modal-cliente">
                    <i class="ti ti-plus"></i> Nuovo Cliente
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Contenitore cards clienti -->
<div class="row row-cards" id="cliente-cards-container">
    <!-- Le card dei clienti verranno caricate qui tramite AJAX -->
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2">Caricamento clienti in corso...</p>
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

<!-- Modal Form Cliente -->
<div class="modal modal-blur fade" id="modal-cliente" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Nuovo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-cliente">
                    <input type="hidden" id="cliente-id" name="id" value="0">
                    
                    <div class="row mb-3">
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label required">Nome Referente</label>
                                    <input type="text" class="form-control" id="nome-referente" name="nome_referente" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label required">Cognome Referente</label>
                                    <input type="text" class="form-control" id="cognome-referente" name="cognome_referente" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Nome Negozio</label>
                                <input type="text" class="form-control" id="nome-negozio" name="nome_negozio" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Logo/Avatar</label>
                                <div class="text-center mb-2">
                                    <img src="/img/logo panda.webp" id="preview-avatar" class="avatar avatar-xl" alt="Avatar">
                                </div>
                                <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="email@esempio.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">WhatsApp</label>
                            <input type="text" class="form-control" id="whatsapp" name="whatsapp" placeholder="+39...">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Indirizzo</label>
                            <input type="text" class="form-control" id="indirizzo" name="indirizzo">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Provincia</label>
                            <select class="form-select" id="provincia" name="provincia">
                                <option value="">Seleziona provincia</option>
                                <option value="AR">Arezzo</option>
                                <option value="FI">Firenze</option>
                                <option value="GR">Grosseto</option>
                                <option value="LI">Livorno</option>
                                <option value="LU">Lucca</option>
                                <option value="MS">Massa-Carrara</option>
                                <option value="PI">Pisa</option>
                                <option value="PT">Pistoia</option>
                                <option value="PO">Prato</option>
                                <option value="SI">Siena</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">CAP</label>
                            <input type="text" class="form-control" id="cap" name="cap" maxlength="5" pattern="[0-9]{5}" placeholder="Inserisci CAP (5 cifre)">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Partita IVA</label>
                            <input type="text" class="form-control" id="partita-iva" name="partita_iva">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Codice SDI</label>
                            <input type="text" class="form-control" id="codice-sdi" name="codice_sdi" maxlength="7">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Orario di scarico merce</label>
                            <input type="text" class="form-control" id="orario-scarico" name="orario_scarico" placeholder="Es: 09:00-12:00, 14:00-17:00">
                        </div>
                        <div class="col-md-6">
                            <!-- Spazio vuoto per bilanciare il layout -->
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                                    <i class="ti ti-eye"></i>
                                </button>
                                <button class="btn btn-outline-secondary" type="button" id="generate-password">
                                    <i class="ti ti-refresh"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Data Apertura Rapporto</label>
                            <input type="date" class="form-control" id="data-apertura" name="data_apertura" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Tipo di Pagamento</label>
                            <select class="form-select" id="tipo-pagamento" name="tipo_pagamento" required>
                                <option value="">Seleziona metodo</option>
                                <option value="vista">pagamento a vista sconto 2%</option>
                                <option value="30gg">Pagamento 30gg</option>
                                <option value="60gg">Pagamento 60gg</option>
                                <option value="anticipato">Pagamento anticipato</option>
                                <option value="contrassegno">Contrassegno</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stato Cliente</label>
                            <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column">
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="checkbox" name="attivo" id="cliente-attivo" value="1" class="form-selectgroup-input" checked>
                                    <div class="form-selectgroup-label d-flex align-items-center p-3">
                                        <div class="me-3">
                                            <span class="form-selectgroup-check"></span>
                                        </div>
                                        <div>
                                            Cliente Attivo
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Privilegi Cataloghi</label>
                        <div id="container-cataloghi" class="form-selectgroup form-selectgroup-pills">
                            <!-- Cataloghi caricati via AJAX -->
                            <div class="text-center py-3">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                <span class="ms-2">Caricamento cataloghi...</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Note Interne</label>
                        <textarea class="form-control" id="note-interne" name="note_interne" rows="3" placeholder="Queste note saranno visibili solo all'amministrazione"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                    Annulla
                </button>
                <button type="button" class="btn btn-primary ms-auto" id="btn-save-cliente">
                    <i class="ti ti-device-floppy"></i>
                    Salva Cliente
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Template per Card Cliente -->
<template id="template-cliente-card">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-status-top" data-status></div>
            <div class="card-body">
                <div class="row align-items-center mb-3">
                    <div class="col-auto">
                        <span class="avatar avatar-lg" data-avatar></span>
                    </div>
                    <div class="col">
                        <h3 class="mb-0" data-nome-negozio></h3>
                        <div class="text-muted" data-referente>
                            <i class="ti ti-user me-1"></i> <span data-nome-cognome></span>
                        </div>
                    </div>
                    <div class="col-auto">
                        <span class="badge" data-badge-status></span>
                    </div>
                </div>
                <div class="mb-2">
                    <i class="ti ti-calendar me-1"></i>
                    <span class="text-muted">Dal: </span>
                    <strong data-data-apertura></strong>
                </div>
                <div class="mb-2">
                    <i class="ti ti-mail me-1"></i>
                    <a href="#" data-email></a>
                </div>
                <div class="mb-2">
                    <i class="ti ti-map-pin me-1"></i>
                    <span data-indirizzo></span>
                </div>
                <div class="mb-2">
                    <i class="ti ti-map me-1"></i>
                    <span data-provincia></span> - <span data-cap></span>
                </div>
                <div class="mb-2">
                    <i class="ti ti-receipt-tax me-1"></i>
                    <span class="text-muted">P.IVA: </span>
                    <span data-partita-iva></span>
                </div>
                <div class="mb-2">
                    <i class="ti ti-file-invoice me-1"></i>
                    <span class="text-muted">Codice SDI: </span>
                    <span data-codice-sdi></span>
                </div>
                <div class="mb-2">
                    <i class="ti ti-truck-delivery me-1"></i>
                    <span class="text-muted">Orario scarico: </span>
                    <span data-orario-scarico></span>
                </div>
                <div class="mb-2">
                    <i class="ti ti-brand-whatsapp me-1"></i>
                    <a href="#" data-whatsapp></a>
                </div>
                <div class="mb-3">
                    <i class="ti ti-cash me-1"></i>
                    <span class="text-muted">Pagamento: </span>
                    <span data-pagamento></span>
                </div>
                <div class="mb-3">
                    <i class="ti ti-tags me-1"></i>
                    <span class="text-muted">Cataloghi: </span>
                    <div class="mt-1" data-cataloghi></div>
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
                                <a class="dropdown-item" href="#" data-btn-reset-password>
                                    <i class="ti ti-key me-2"></i> Reset Password
                                </a>
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
    'assets/js/clienti.js'
];

// Includi il footer
require_once 'includes/footer.php';
?>