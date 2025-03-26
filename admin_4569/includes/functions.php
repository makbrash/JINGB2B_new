<?php
/**
 * File di funzioni helper per il pannello di amministrazione
 */

/**
 * Genera un slug SEO-friendly da una stringa
 * 
 * @param string $string Stringa da convertire in slug
 * @return string Slug generato
 */
function generateSlug($string) {
    // Rimuovi caratteri non ASCII
    $string = transliterator_transliterate('Any-Latin; Latin-ASCII', $string);
    // Converti a minuscolo
    $string = strtolower($string);
    // Rimuovi caratteri speciali
    $string = preg_replace('/[^a-z0-9\-]/', '-', $string);
    // Rimuovi trattini multipli
    $string = preg_replace('/-+/', '-', $string);
    // Rimuovi trattini iniziali e finali
    $string = trim($string, '-');
    
    return $string;
}

/**
 * Formatta una data nel formato italiano
 * 
 * @param string $date Data in formato MySQL (YYYY-MM-DD)
 * @param bool $showTime Se true, include anche l'ora
 * @return string Data formattata
 */
function formatDate($date, $showTime = false) {
    if (empty($date)) {
        return '';
    }
    
    $format = 'd/m/Y';
    if ($showTime) {
        $format .= ' H:i';
    }
    
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Tronca un testo a una lunghezza specificata aggiungendo puntini di sospensione
 * 
 * @param string $text Testo da troncare
 * @param int $length Lunghezza massima
 * @param string $suffix Suffisso da aggiungere (default: '...')
 * @return string Testo troncato
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Genera HTML per un badge di stato
 * 
 * @param string $status Stato (active, pending, blocked, etc.)
 * @param string $text Testo da visualizzare (se vuoto, usa lo status capitalizzato)
 * @return string HTML del badge
 */
function getStatusBadge($status, $text = '') {
    $class = '';
    $label = !empty($text) ? $text : ucfirst($status);
    
    switch (strtolower($status)) {
        case 'active':
        case 'attivo':
            $class = 'bg-success text-white';
            break;
        case 'pending':
        case 'in attesa':
            $class = 'bg-warning text-dark';
            break;
        case 'blocked':
        case 'bloccato':
            $class = 'bg-danger text-white';
            break;
        case 'completed':
        case 'completato':
            $class = 'bg-info text-white';
            break;
        default:
            $class = 'bg-secondary text-white';
    }
    
    return '<span class="badge ' . $class . '">' . $label . '</span>';
}

/**
 * Formatta un numero come valuta
 * 
 * @param float $amount Importo da formattare
 * @param string $currency Simbolo valuta (default: €)
 * @return string Importo formattato
 */
function formatCurrency($amount, $currency = '€') {
    return $currency . ' ' . number_format($amount, 2, ',', '.');
}

/**
 * Genera una stringa random
 * 
 * @param int $length Lunghezza della stringa
 * @return string Stringa casuale
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

/**
 * Sanitizza input utente
 * 
 * @param string $input Input da sanitizzare
 * @return string Input sanitizzato
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}