<?php
/**
 * templates/gas_publico/index.php
 * ─────────────────────────────────────────────────────────
 * ENRUTADOR PÚBLICO del módulo GAS.
 * Este es el único archivo al que acceden los asistentes.
 * URL de acceso: http://localhost/iqgisdps/templates/gas_publico/index.php?t=TOKEN
 *
 * NO requiere sesión DPS. Es completamente público.
 * ─────────────────────────────────────────────────────────
 */

// Iniciar sesión PHP para pasar el gar_id entre procesar y confirmación
if (session_status() === PHP_SESSION_NONE) session_start();

// Cargar configuración (conexión BD y constantes)
require_once __DIR__ . '/../../gas_config.php';
$conn = gas_conectar_bd();

// Funciones auxiliares
require_once __DIR__ . '/../gestion_asistencias/includes/gas_funciones.php';

// ── Leer y validar el token ──────────────────────────────────────────────
$token = trim($_GET['t'] ?? '');

if (empty($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error_tipo = 'token_invalido';
    include __DIR__ . '/error.php';
    exit;
}

// ── Buscar sesión por token ──────────────────────────────────────────────
$stmt = $conn->prepare(
    'SELECT gas_id, gas_nombre, gas_estado, gas_facilitador,
            gas_tipo_sesion, gas_fecha_inicio, gas_fecha_fin, gas_token_publico
     FROM gestion_asistencias_sesiones
     WHERE gas_token_publico = ?
     LIMIT 1'
);
$stmt->bind_param('s', $token);
$stmt->execute();
$sesion = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sesion) {
    $error_tipo = 'token_invalido';
    include __DIR__ . '/error.php';
    exit;
}

// ── Enrutar según estado ─────────────────────────────────────────────────
switch ($sesion['gas_estado']) {

    case 'activa':
        include __DIR__ . '/asistencia_form.php';
        break;

    case 'finalizada':
        include __DIR__ . '/encuesta_form.php';
        break;

    case 'cerrada':
        $error_tipo = 'sesion_cerrada';
        include __DIR__ . '/error.php';
        break;

    case 'anulada':
        $error_tipo = 'sesion_anulada';
        include __DIR__ . '/error.php';
        break;

    case 'borrador':
    default:
        $error_tipo = 'sesion_no_disponible';
        include __DIR__ . '/error.php';
        break;
}
