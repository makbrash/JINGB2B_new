<?php
// includes/header.php
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>JINGB2B - Catalogo</title>
    <!-- Impostazioni per la visualizzazione mobile -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- Meta per PWA -->
    <meta name="theme-color" content="#ca674e">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="JINGB2B">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="JINGB2B">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- Manifest e icone -->
    <link rel="manifest" href="manifest.json">
    <link rel="icon" sizes="192x192" href="favicon/android-chrome-192x192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="favicon/android-chrome-512x512.png">
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
