// Aggiornamento del modulo statistics.js per implementare Chart.js

const Statistics = {
    // Riferimenti ai grafici attivi
    charts: {
        categories: null,
        brands: null,
        tags: null,
        subcategories: null
    },
    
    /**
     * Inizializza i tab delle statistiche
     */
    inizializzaTab: function() {
        // Mantieni il codice originale per i tab
        // ma aggiungi contenitori canvas per i grafici
        
        if (!$('#stat-tabs').length) {
            $('#category-stats').before(`
                <div id="stat-tabs" class="stat-tabs">
                    <button class="stat-tab active" data-stat="category">Distribuzione categorie</button>
                    <button class="stat-tab" data-stat="marca">Marche principali</button>
                    <button class="stat-tab" data-stat="tag">Tag più utilizzati</button>
                    <button class="stat-tab" data-stat="subcategory">Sottocategorie</button>
                </div>
            `);
            
            // Inizialmente nascondi tutti tranne la categoria
            $('#brand-stats, #tag-cloud, #subcategory-stats').hide();
            
            // Aggiungi contenitore per sottocategorie se non esiste
            if (!$('#subcategory-stats').length) {
                $('#category-stats').after('<div id="subcategory-stats" style="display: none;"></div>');
            }
            
            // Aggiungi contenitori canvas per i grafici
            $('#category-stats').html('<canvas id="categories-chart"></canvas><div id="categories-legend" class="chart-legend"></div>');
            $('#brand-stats').html('<canvas id="brands-chart"></canvas><div id="brands-legend" class="chart-legend"></div>');
            $('#subcategory-stats').html('<canvas id="subcategories-chart"></canvas><div id="subcategories-legend" class="chart-legend"></div>');
            
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
            this.aggiungiStiliTab();
        }
    },
    
    /**
     * Aggiunge gli stili CSS per i tab
     */
    aggiungiStiliTab: function() {
        // Mantieni il codice originale
    },
    
    /**
     * Mostra le statistiche sui prodotti usando Chart.js
     * @param {Object} stats - Oggetto contenente i dati delle statistiche
     */
    mostraStatistiche: function(stats) {
        // Aggiorna dati statistiche principali
        $('#stat-total').text(stats.totale_prodotti);
        $('#stat-processed').text(stats.prodotti_elaborati);
        $('#stat-pending').text(stats.prodotti_da_elaborare);
        $('#stat-error').text(stats.prodotti_in_errore);
        $('#stat-unique-tags').text(stats.tag_unici);
        
        // Aggiorna tag cloud
        this.mostraTagCloud(stats.tag_popolari);
        
        // Crea grafici con Chart.js
        this.creaGraficoCategorie(stats.prodotti_per_categoria, stats.totale_prodotti);
        this.creaGraficoMarche(stats.prodotti_per_marca, stats.totale_prodotti);
        
        // Aggiorna statistiche sottocategorie
        this.mostraStatisticheSottocategorie(stats);
        
        // Assicurati che i tab siano inizializzati
        this.inizializzaTab();
    },
    
    /**
     * Mostra il tag cloud
     * @param {Array} tags - Lista di tag popolari
     */
    mostraTagCloud: function(tags) {
        let tagCloudHtml = '';
        if (tags && tags.length > 0) {
            tags.forEach(function(tag) {
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
            Products.cercaProdotti();
        });
    },
    
    /**
     * Crea un grafico a barre per le categorie con Chart.js
     * @param {Array} categorie - Lista di categorie con conteggi
     * @param {number} totale - Numero totale di prodotti
     */
    creaGraficoCategorie: function(categorie, totale) {
        if (!categorie || categorie.length === 0) {
            $('#category-stats').html('<em>Nessuna categoria disponibile</em>');
            return;
        }
        
        // Ordina le categorie per conteggio decrescente
        const sortedCategories = [...categorie].sort((a, b) => b.count - a.count);
        
        // Limita a top 10 per leggibilità
        const topCategories = sortedCategories.slice(0, 10);
        
        // Prepara i dati per il grafico
        const labels = topCategories.map(c => c.nome);
        const data = topCategories.map(c => c.count);
        const percentages = topCategories.map(c => ((c.count / totale) * 100).toFixed(1) + '%');
        const backgroundColor = this.generaPaletteColori(topCategories.length, 0.7);
        const borderColor = this.generaPaletteColori(topCategories.length, 1);
        
        // Cancella il grafico precedente se esiste
        if (this.charts.categories) {
            this.charts.categories.destroy();
        }
        
        // Crea il canvas se non esiste
        if ($('#categories-chart').length === 0) {
            $('#category-stats').html('<canvas id="categories-chart"></canvas><div id="categories-legend" class="chart-legend"></div>');
        }
        
        // Crea nuovo grafico a barre orizzontali
        const ctx = document.getElementById('categories-chart').getContext('2d');
        this.charts.categories = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Prodotti per categoria',
                    data: data,
                    backgroundColor: backgroundColor,
                    borderColor: borderColor,
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.raw} prodotti (${percentages[context.dataIndex]})`;
                            }
                        }
                    }
                },
                onClick: (e, elements) => {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const categoriaId = topCategories[index].id;
                        $('#categoria-filter').val(categoriaId).trigger('change');
                        API.caricaProdotti();
                    }
                }
            }
        });
        
        // Crea una legenda personalizzata cliccabile
        let legendHtml = '<div class="chart-legend-items">';
        topCategories.forEach((cat, index) => {
            const percentage = ((cat.count / totale) * 100).toFixed(1);
            legendHtml += `
                <div class="chart-legend-item" data-categoria-id="${cat.id}">
                    <span class="chart-legend-color" style="background-color:${backgroundColor[index]}"></span>
                    <span class="chart-legend-label">${cat.nome}</span>
                    <span class="chart-legend-value">${cat.count} (${percentage}%)</span>
                </div>
            `;
        });
        legendHtml += '</div>';
        
        $('#categories-legend').html(legendHtml);
        
        // Aggiungi event listener alla legenda
        $('.chart-legend-item').on('click', function() {
            const categoriaId = $(this).data('categoria-id');
            $('#categoria-filter').val(categoriaId).trigger('change');
            API.caricaProdotti();
        });
    },
    
    /**
     * Crea un grafico a barre per le marche con Chart.js
     * @param {Array} marche - Lista di marche con conteggi
     * @param {number} totale - Numero totale di prodotti
     */
    creaGraficoMarche: function(marche, totale) {
        if (!marche || marche.length === 0) {
            $('#brand-stats').html('<em>Nessuna marca disponibile</em>');
            return;
        }
        
        // Filtra per marche con conteggio > 0 e ordina
        const filteredBrands = marche.filter(m => m.count > 0).sort((a, b) => b.count - a.count);
        
        // Limita a top 10 per leggibilità
        const topBrands = filteredBrands.slice(0, 10);
        
        // Prepara i dati per il grafico
        const labels = topBrands.map(b => b.nome);
        const data = topBrands.map(b => b.count);
        const percentages = topBrands.map(b => ((b.count / totale) * 100).toFixed(1) + '%');
        const backgroundColor = this.generaPaletteColori(topBrands.length, 0.7, 120); // Colore differente da categorie
        const borderColor = this.generaPaletteColori(topBrands.length, 1, 120);
        
        // Cancella il grafico precedente se esiste
        if (this.charts.brands) {
            this.charts.brands.destroy();
        }
        
        // Crea il canvas se non esiste
        if ($('#brands-chart').length === 0) {
            $('#brand-stats').html('<canvas id="brands-chart"></canvas><div id="brands-legend" class="chart-legend"></div>');
        }
        
        // Crea nuovo grafico a barre orizzontali
        const ctx = document.getElementById('brands-chart').getContext('2d');
        this.charts.brands = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Prodotti per marca',
                    data: data,
                    backgroundColor: backgroundColor,
                    borderColor: borderColor,
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.raw} prodotti (${percentages[context.dataIndex]})`;
                            }
                        }
                    }
                },
                onClick: (e, elements) => {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const marcaId = topBrands[index].id;
                        $('#marca-filter').val(marcaId).trigger('change');
                        API.caricaProdotti();
                    }
                }
            }
        });
        
        // Crea una legenda personalizzata cliccabile
        let legendHtml = '<div class="chart-legend-items">';
        topBrands.forEach((marca, index) => {
            const percentage = ((marca.count / totale) * 100).toFixed(1);
            legendHtml += `
                <div class="chart-legend-item" data-marca-id="${marca.id}">
                    <span class="chart-legend-color" style="background-color:${backgroundColor[index]}"></span>
                    <span class="chart-legend-label">${marca.nome}</span>
                    <span class="chart-legend-value">${marca.count} (${percentage}%)</span>
                </div>
            `;
        });
        legendHtml += '</div>';
        
        $('#brands-legend').html(legendHtml);
        
        // Aggiungi event listener alla legenda
        $('.chart-legend-item[data-marca-id]').on('click', function() {
            const marcaId = $(this).data('marca-id');
            $('#marca-filter').val(marcaId).trigger('change');
            API.caricaProdotti();
        });
    },
    
    /**
     * Mostra le statistiche sulle sottocategorie
     * @param {Object} stats - Oggetto contenente tutti i dati delle statistiche
     */
    mostraStatisticheSottocategorie: function(stats) {
        // Se non abbiamo i dati necessari, facciamo una richiesta apposita
        if (!stats.prodotti_per_sottocategoria) {
            API.call({
                data: {
                    action: 'get_subcategory_stats'
                }
            }).then(response => {
                if (response.data) {
                    this.creaGraficoSottocategorie(response.data.prodotti_per_sottocategoria);
                }
            });
        } else {
            this.creaGraficoSottocategorie(stats.prodotti_per_sottocategoria);
        }
    },
    
    /**
     * Crea un grafico a ciambella per le sottocategorie con Chart.js
     * @param {Array} sottocategorie - Lista di sottocategorie con conteggi
     */
    creaGraficoSottocategorie: function(sottocategorie) {
        if (!sottocategorie || sottocategorie.length === 0) {
            $('#subcategory-stats').html('<em>Nessuna sottocategoria disponibile</em>');
            return;
        }
        
        // Ordina le sottocategorie per conteggio decrescente
        const sortedSubcategories = [...sottocategorie].sort((a, b) => b.count - a.count);
        
        // Limita a top 12 per leggibilità
        const topSubcategories = sortedSubcategories.slice(0, 12);
        
        // Calcola il totale per le percentuali
        let totaleProdotti = 0;
        topSubcategories.forEach(function(subcat) {
            totaleProdotti += subcat.count;
        });
        
        // Prepara i dati per il grafico
        const labels = topSubcategories.map(s => s.nome);
        const data = topSubcategories.map(s => s.count);
        const percentages = topSubcategories.map(s => ((s.count / totaleProdotti) * 100).toFixed(1) + '%');
        const backgroundColor = this.generaPaletteColori(topSubcategories.length, 0.7, 240); // Colore differente
        const borderColor = this.generaPaletteColori(topSubcategories.length, 1, 240);
        
        // Cancella il grafico precedente se esiste
        if (this.charts.subcategories) {
            this.charts.subcategories.destroy();
        }
        
        // Crea il canvas se non esiste
        if ($('#subcategories-chart').length === 0) {
            $('#subcategory-stats').html('<canvas id="subcategories-chart"></canvas><div id="subcategories-legend" class="chart-legend"></div>');
        }

        
        // Crea nuovo grafico a ciambella
        const ctx = document.getElementById('subcategories-chart').getContext('2d');
        this.charts.subcategories = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: backgroundColor,
                    borderColor: borderColor,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw} prodotti (${percentages[context.dataIndex]})`;
                            }
                        }
                    }
                },
                onClick: (e, elements) => {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const categoriaId = topSubcategories[index].categoria_id;
                        const sottocategoriaId = topSubcategories[index].id;
                        
                        // Imposta prima la categoria
                        $('#categoria-filter').val(categoriaId).trigger('change');
                        
                        // Poi la sottocategoria con un ritardo
                        setTimeout(function() {
                            $('#sottocategoria-filter').val(sottocategoriaId);
                            API.caricaProdotti();
                        }, 100);
                    }
                }
            }
        });
        
        // Crea una legenda personalizzata cliccabile
        let legendHtml = '<div class="chart-legend-items">';
        topSubcategories.forEach((subcat, index) => {
            const percentage = ((subcat.count / totaleProdotti) * 100).toFixed(1);
            legendHtml += `
                <div class="chart-legend-item" data-sottocategoria-id="${subcat.id}" data-categoria-id="${subcat.categoria_id}">
                    <span class="chart-legend-color" style="background-color:${backgroundColor[index]}"></span>
                    <span class="chart-legend-label">${subcat.nome}</span>
                    <span class="chart-legend-value">${subcat.count} (${percentage}%)</span>
                </div>
            `;
        });
        legendHtml += '</div>';
        
        $('#subcategories-legend').html(legendHtml);
        
        // Aggiungi event listener alla legenda
        $('.chart-legend-item[data-sottocategoria-id]').on('click', function() {
            const categoriaId = $(this).data('categoria-id');
            const sottocategoriaId = $(this).data('sottocategoria-id');
            
            // Imposta prima la categoria
            $('#categoria-filter').val(categoriaId).trigger('change');
            
            // Poi la sottocategoria con un ritardo
            setTimeout(function() {
                $('#sottocategoria-filter').val(sottocategoriaId);
                API.caricaProdotti();
            }, 100);
        });
    },
    
    /**
     * Genera una palette di colori per i grafici
     * @param {number} count - Numero di colori da generare
     * @param {number} alpha - Trasparenza (0-1)
     * @param {number} hueOffset - Offset per il colore iniziale (0-360)
     * @returns {Array} Array di colori in formato rgba
     */
    generaPaletteColori: function(count, alpha = 0.7, hueOffset = 0) {
        const colors = [];
        for (let i = 0; i < count; i++) {
            // Calcola un colore HSL ben distribuito e converti in RGB
            const hue = (hueOffset + i * (360 / count)) % 360;
            const saturation = 70; // %
            const lightness = 50; // %
            
            colors.push(`hsla(${hue}, ${saturation}%, ${lightness}%, ${alpha})`);
        }
        return colors;
    }
};

