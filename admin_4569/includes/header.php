<?php
// Includi file di configurazione
// Verifica sessione e autenticazione

/*if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: login.php');
    exit;
}
*/
// Variabili principali utente (in un'applicazione reale prenderesti questi dati dal DB)
$username = $_SESSION['username'] ?? "Marco Vitaletti";
$userEmail = $_SESSION['email'] ?? "marco@example.com";
$userAvatar = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($userEmail))) . "?s=200&d=mp";
$userRole = $_SESSION['role'] ?? "Amministratore";

// Contatori notifiche e messaggi (in un'applicazione reale prenderesti questi dati dal DB)
$notificationCount = 5;
$messageCount = 3;

// Titolo pagina predefinito se non impostato
$pageTitle = $pageTitle ?? "Dashboard Admin";
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    
    <!-- CSS files via CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    
    <link rel="icon" sizes="192x192" href="/favicon/android-chrome-192x192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="/favicon/android-chrome-512x512.png">
    
    <!-- Google Font - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
    
    <style>
      :root {
        --tblr-font-sans-serif: "Inter";
      }
    </style>
</head>
<body class='layout-fluid' <?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'data-bs-theme="dark"' : ''; ?>>
<div class="page"><?php
// Il sidebar.php sarà incluso qui dopo questo file
?>