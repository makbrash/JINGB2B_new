<?php
/**
 * File di configurazione principale
 */

// Impostazioni di base
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Avvia la sessione se non è già attiva
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Costanti di configurazione
define('SITE_TITLE', 'Admin Panel');
define('SITE_DESCRIPTION', 'Pannello di amministrazione');

// Impostazione del percorso base
$base_path = str_replace('\\', '/', dirname(__FILE__, 2));
$base_url = '';

$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';

// Determina il percorso del sito
if (stripos($request_uri, $script_name) !== false) {
    $base_url = $script_name;
} else {
    $dir = dirname($script_name);
    $base_url = $dir === '\\' || $dir === '/' ? '' : $dir;
}

// Normalizza e termina il base_url con uno slash
$base_url = rtrim($base_url, '/');
if (!empty($base_url)) {
    $base_url .= '/';
}

// Definizione costante BASE_URL
define('BASE_URL', $base_url);

// Definisci percorsi a directory importanti
define('ROOT_PATH', $base_path . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('MODULES_PATH', ROOT_PATH . 'modules/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');

// Impostazioni timezone
date_default_timezone_set('Europe/Rome');

// Impostazioni del database
define('DB_HOST', 'localhost');
define('DB_NAME', 'admin_panel');
define('DB_USER', 'root');
define('DB_PASS', '');


define('TUO_TOKEN_API', 'ABCD123Makbrash11');



// Includi database.php
require_once __DIR__ . '/database.php';

// Funzione di autoloading per moduli
function requireModule($module) {
    $path = MODULES_PATH . $module . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
}

// Funzione per controllare se l'utente è loggato
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Funzione per reindirizzare
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Funzione per mostrare messaggi di notifica
function setMessage($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

// Funzione per recuperare messaggi di notifica
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'success';
        
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        
        return [
            'text' => $message,
            'type' => $type
        ];
    }
    
    return null;
}