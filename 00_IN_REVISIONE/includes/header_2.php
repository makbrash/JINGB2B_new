<?php
// includes/header.php
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>JINGB2B - Catalogo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Foglio di stile principale -->
    <link rel="stylesheet" href="/assets/css/style.css" />
</head>
<body>
    <header id="main-header">
        <!-- Logo -->
        <img class="logo" src="/assets/images/logo.png" alt="Logo JINGB2B" />

        <nav>
            <!-- Select con negozi -->
            <select class="shop-select">
                <option value="negozio1">Negozio 1</option>
                <option value="negozio2">Negozio 2</option>
            </select>

            <!-- Filtro con dropdown -->
            <div class="filter-container">
                <a href="#" class="filter-link">Filtra</a>
                <ul class="filter-dropdown">
                    <li><a href="#">Visualizza preferiti</a></li>
                    <li><a href="#">Ordina per marche</a></li>
                    <li><a href="#">Offerte</a></li>
                </ul>
            </div>
        </nav>
    </header>
