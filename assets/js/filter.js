// Variabili globali
let currentFilters = {
    categoria_id: "",
    sottocategoria_id: "", 
    searchText: ""
};

let catalogLoaded = false;
let categoriesLoaded = false;
let hierarchyData = [];


// Funzione di inizializzazione principale

$(document).ready(function() {
    console.log("Filter.js caricato");
    // Inizializza toggle tra ricerca e filtri
    initToggleButtons();
    // Ricerca testuale
    $(document).on('input', '#searchFilter', function() {
        console.log("Ricerca: " + $(this).val());
        currentFilters.searchText = $(this).val();
        applyFilters();
    });
    // Reset filtri
    $(document).on('click', '#resetFilters', function() {
        console.log("Reset filtri");
        resetAllFilters();
    });
});

// Inizializza i pulsanti di toggle
function initToggleButtons() {
    $(document).on('click', '#toggleSearch', function() {
        console.log("Toggle ricerca");
        $('.toggle-button').removeClass('active');
        $(this).addClass('active');
        $('.filter-container').removeClass('active');
        $('#searchContainer').addClass('active');
    });
    
    $(document).on('click', '#toggleFilter', function() {
        console.log("Toggle filtri");
        $('.toggle-button').removeClass('active');
        $(this).addClass('active');
        $('.filter-container').removeClass('active');
        $('#filterContainer').addClass('active');
    });
}

// Carica e inizializza categorie
function loadCategoriesHierarchy() {
    console.log("Caricamento categorie");
    
    // Offline: carica da localStorage
    if (!navigator.onLine) {
        const saved = localStorage.getItem('jingb2b_categories');
        if (saved) {
            try {
                hierarchyData = JSON.parse(saved);
                initCategoryDropdown(hierarchyData);
                categoriesLoaded = true;
                return;
            } catch (e) {
                console.error("Errore localStorage:", e);
            }
        }
        console.log("Offline - impossibile caricare categorie");
        return;
    }
    
    // Online: carica dal server
    $.ajax({
        url: "/api/proxy_request.php",
        type: "POST",
        dataType: "json",
        contentType: "application/json",
        data: JSON.stringify({ action: "getCategoriesHierarchy" }),
        success: function(response) {
            console.log("Risposta categorie:", response);
            if (response.success) {
                hierarchyData = response.data;
                localStorage.setItem('jingb2b_categories', JSON.stringify(hierarchyData));
                initCategoryDropdown(hierarchyData);
                categoriesLoaded = true;
            }
        },
        error: function(xhr) {
            console.error("Errore rete:", xhr.statusText);
        }
    });
}

// Inizializza dropdown categorie
function initCategoryDropdown(categories) {
    console.log("Inizializzazione dropdown categorie");
    
    let options = [];
    
    // Prepara dati per Select2
    categories.forEach(cat => {
        options.push({
            id: 'cat_' + cat.id,
            text: cat.titolo,
            category_id: cat.id
        });
        
        if (cat.subcategories && cat.subcategories.length > 0) {
            cat.subcategories.forEach(sub => {
                options.push({
                    id: 'subcat_' + sub.id,
                    text: sub.titolo.charAt(0).toUpperCase() + sub.titolo.slice(1).toLowerCase(),
                    category_id: cat.id,
                    subcategory_id: sub.id
                });
            });
        }
    });
    
    // Inizializza Select2
    $('#categoriaFilter').select2({
        data: options,
        width: '100%',
        placeholder: "Seleziona categoria...",
        templateResult: formatCategoryOption
    }).on('change', function() {
        const selected = $(this).select2('data')[0];
        console.log("Categoria selezionata:", selected);
        
        if (!selected) {
            currentFilters.categoria_id = "";
            currentFilters.sottocategoria_id = "";
        } else if (selected.id.startsWith('cat_')) {
            currentFilters.categoria_id = selected.category_id;
            currentFilters.sottocategoria_id = "";
        } else {
            currentFilters.categoria_id = selected.category_id;
            currentFilters.sottocategoria_id = selected.subcategory_id;
        }
        
        applyFilters();
    });
    
    // Seleziona primo elemento
    if (options.length > 0) {
        $('#categoriaFilter').val(options[1].id).trigger('change');
    }
}

// Formattazione opzioni Select2
function formatCategoryOption(option) {
    if (!option.id) return option.text;
    
    if (option.id.startsWith('cat_')) {
        return $('<strong>' + option.text + '</strong>');
    } else {
        return $('<span style="padding-left:15px">' + option.text + '</span>');
    }
}

// Applica filtri ai prodotti
function applyFilters() {
    console.log("Applicazione filtri:", currentFilters);
    
    if (!db) {
        console.error("DB non inizializzato");
        return;
    }
    
    getAllProductsFromDB().then(products => {
        console.log("Totale prodotti:", products.length);
        
        // Filtra prodotti
        const filtered = products.filter(product => {
            // Filtro categoria
            if (currentFilters.categoria_id && 
                String(product.categoria_id) !== String(currentFilters.categoria_id)) {
                return false;
            }
            
            // Filtro sottocategoria
            if (currentFilters.sottocategoria_id && 
                String(product.sottocategoria_id) !== String(currentFilters.sottocategoria_id)) {
                return false;
            }
            
            // Filtro ricerca
            if (currentFilters.searchText) {
                const search = currentFilters.searchText.toLowerCase();
                const title = (product.titolo || "").toString().toLowerCase();
                const ean = (product.ean || "").toString().toLowerCase();
                const tags = (product.tags || "").toString().toLowerCase();
                
                if (!(title.includes(search) || ean.includes(search) || tags.includes(search))) {
                    return false;
                }
            }
            
            return true;
        });
        
        console.log("Prodotti filtrati:", filtered.length);
        
        // Aggiorna visualizzazione
        renderCatalog(filtered);
        updateResultCounter(filtered.length, products.length);
    });
}

// Aggiorna contatore risultati
function updateResultCounter(count, total) {
    $('.filter-counter').text(`${count} prodotti`);
}

// Reset tutti i filtri
function resetAllFilters() {
    $('#searchFilter').val('');
    currentFilters.searchText = "";
    
    if (hierarchyData.length > 0) {
        $('#categoriaFilter').val('cat_' + hierarchyData[0].id).trigger('change');
    } else {
        currentFilters.categoria_id = "";
        currentFilters.sottocategoria_id = "";
        applyFilters();
    }
}