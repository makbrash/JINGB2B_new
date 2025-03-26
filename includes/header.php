
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <title>JINGB2B Catalog</title>
  <!-- Manifest per la PWA -->
  <link rel="manifest" href="/manifest.json" />
  <meta name="theme-color" content="#ca674e" />
  <!-- (Facoltativo) Tag che indica "Add to Home Screen" su iOS -->
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <!-- Icona, se vuoi apparire su home iOS -->
  <!-- <link rel="apple-touch-icon" href="/assets/img/icon-192.png" /> -->
  <link rel="icon" sizes="192x192" href="favicon/android-chrome-192x192.png">
  <link rel="apple-touch-icon" sizes="512x512" href="favicon/android-chrome-512x512.png">

  <!-- Caricamento CSS -->
  <!-- Se preferisci, import style.css -->
  <link rel="stylesheet" href="/assets/css/style.css" />

  <!-- jQuery: da CDN o local -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Registrazione Service Worker -->
  <script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/service-worker.js')
        .then(reg => console.log("Service Worker registrato:", reg.scope))
        .catch(err => console.log("SW registration failed:", err));
    });
  }
  </script>
</head>