<?php
/**
 * gas_config.php
 * Configuración central del módulo GAS.
 * AJUSTA estos valores según tu entorno XAMPP.
 *
 * Incluye este archivo al inicio de cualquier script del módulo
 * que NO use el iniciador.php de DPS (por ejemplo, en pruebas standalone).
 *
 * En el proyecto real de DPS este archivo NO se usa directamente:
 * la conexión $conn ya viene del iniciador.php del proyecto.
 */

// ─── CONFIGURACIÓN DE BASE DE DATOS (XAMPP) ───────────────────────────────
define('GAS_DB_HOST',   'localhost');
define('GAS_DB_USER',   'root');       // Cambia si tienes otro usuario en XAMPP
define('GAS_DB_PASS',   '');           // Por defecto XAMPP no tiene contraseña
define('GAS_DB_NAME',   'dps_db');     // ← CAMBIA ESTE VALOR por el nombre de tu BD
define('GAS_DB_CHARSET','utf8mb4');

// ─── URL BASE DEL PROYECTO EN XAMPP ──────────────────────────────────────
// Ejemplo: http://localhost/iqgisdps
define('GAS_BASE_URL', 'http://localhost/iqgisdps');

// Ruta al enrutador público
define('GAS_PUBLIC_URL', GAS_BASE_URL . '/templates/gas_publico/index.php');

// ─── TIPOS DE SESIÓN ──────────────────────────────────────────────────────
define('GAS_TIPOS_SESION', [
    'Formacion'       => 'Formación y Capacitación',
    'Sensibilizacion' => 'Sensibilización',
    'Seguimiento'     => 'Seguimiento y Retroalimentación',
    'Informativo'     => 'Informativo',
    'Induccion'       => 'Inducción',
    'Otro'            => 'Otro',
]);

// ─── FUNCIÓN DE CONEXIÓN STANDALONE (solo para pruebas fuera de DPS) ────
// En DPS real la variable $conn ya existe. No llames esta función en DPS.
function gas_conectar_bd(): mysqli {
    $conn = new mysqli(GAS_DB_HOST, GAS_DB_USER, GAS_DB_PASS, GAS_DB_NAME);
    if ($conn->connect_error) {
        die('Error de conexión a BD: ' . $conn->connect_error);
    }
    $conn->set_charset(GAS_DB_CHARSET);
    return $conn;
}
