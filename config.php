<?php
session_start();
date_default_timezone_set('Europe/Madrid');

// --- CONFIGURACION BASE DE DATOS ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'taller');
define('DB_USER', 'root');
define('DB_PASS', '');
// ------------------------------------

define('APP_NAME', 'Taller - Partes de Trabajo');

function db() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    }
    return $pdo;
}

function sanitize($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function flash($key, $msg) {
    $_SESSION['flash'][$key] = $msg;
}

function get_flash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function is_operador() {
    return !empty($_SESSION['operador_id']);
}

function operador_id() {
    return $_SESSION['operador_id'] ?? 0;
}

function operador_nombre() {
    return $_SESSION['operador_nombre'] ?? '';
}

function format_tiempo($minutos) {
    $minutos = (float)$minutos;
    $h = floor($minutos / 60);
    $m = round($minutos % 60);
    if ($h > 0) return "{$h}h {$m}m";
    return "{$m}m";
}

function format_euro($amount) {
    return number_format((float)$amount, 2, ',', '.') . ' &euro;';
}
