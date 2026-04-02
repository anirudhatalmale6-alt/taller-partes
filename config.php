<?php
session_start();
date_default_timezone_set('Europe/Madrid');

define('DB_FILE', __DIR__ . '/taller.db');
define('APP_NAME', 'Taller - Partes de Trabajo');

function db() {
    static $pdo = null;
    if ($pdo === null) {
        $isNew = !file_exists(DB_FILE);
        $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("PRAGMA journal_mode=WAL");
        $pdo->exec("PRAGMA foreign_keys=ON");
        if ($isNew) {
            initDatabase($pdo);
        }
    }
    return $pdo;
}

function initDatabase($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS operadores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(100) NOT NULL,
            pin CHAR(4) NOT NULL UNIQUE,
            activo INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS partes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cliente_nombre VARCHAR(150) NOT NULL,
            cliente_apellidos VARCHAR(150) NOT NULL DEFAULT '',
            vehiculo_marca VARCHAR(80) NOT NULL DEFAULT '',
            vehiculo_modelo VARCHAR(80) NOT NULL DEFAULT '',
            matricula VARCHAR(20) NOT NULL DEFAULT '',
            telefono VARCHAR(20) NOT NULL DEFAULT '',
            operador_id INTEGER DEFAULT NULL,
            estado TEXT NOT NULL DEFAULT 'abierto',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (operador_id) REFERENCES operadores(id) ON DELETE SET NULL
        );
        CREATE TABLE IF NOT EXISTS tareas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            parte_id INTEGER NOT NULL,
            descripcion TEXT NOT NULL,
            tiempo_estimado REAL NOT NULL DEFAULT 0,
            tiempo_real REAL NOT NULL DEFAULT 0,
            observaciones TEXT,
            cerrada INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parte_id) REFERENCES partes(id) ON DELETE CASCADE
        );
        CREATE TABLE IF NOT EXISTS registros_tiempo (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tarea_id INTEGER NOT NULL,
            operador_id INTEGER NOT NULL,
            minutos REAL NOT NULL DEFAULT 0,
            nota VARCHAR(255) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (tarea_id) REFERENCES tareas(id) ON DELETE CASCADE,
            FOREIGN KEY (operador_id) REFERENCES operadores(id) ON DELETE CASCADE
        );
        CREATE TABLE IF NOT EXISTS articulos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            parte_id INTEGER NOT NULL,
            descripcion VARCHAR(255) NOT NULL,
            precio_coste REAL NOT NULL DEFAULT 0,
            precio_venta REAL NOT NULL DEFAULT 0,
            cantidad INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parte_id) REFERENCES partes(id) ON DELETE CASCADE
        );
        INSERT INTO operadores (nombre, pin) VALUES ('Operario Demo', '1234');
    ");
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
