<?php
/**
 * templates/gas_publico/index.php
 * Enrutador público del módulo GAS.
 * Usa iniciador.php para $enlace_db. No valida sesión de usuario DPS.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../iniciador.php';
require_once __DIR__ . '/../gestion_asistencias/includes/gas_funciones.php';

$token = trim($_GET['t'] ?? '');

if (empty($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error_tipo = 'token_invalido';
    include __DIR__ . '/error.php'; exit;
}

$stmt = $enlace_db->prepare(
    'SELECT gas_id, gas_nombre, gas_estado, gas_facilitador,
            gas_tipo_sesion, gas_fecha_inicio, gas_fecha_fin, gas_token_publico
     FROM gestion_asistencias_sesiones
     WHERE gas_token_publico = ? LIMIT 1'
);
$stmt->bind_param('s', $token);
$stmt->execute();
$sesion = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sesion) {
    $error_tipo = 'token_invalido';
    include __DIR__ . '/error.php'; exit;
}

switch ($sesion['gas_estado']) {
    case 'activa':     include __DIR__ . '/asistencia_form.php';  break;
    case 'finalizada': include __DIR__ . '/encuesta_form.php';     break;
    case 'cerrada':    $error_tipo = 'sesion_cerrada';   include __DIR__ . '/error.php'; break;
    case 'anulada':    $error_tipo = 'sesion_anulada';   include __DIR__ . '/error.php'; break;
    default:           $error_tipo = 'sesion_no_disponible'; include __DIR__ . '/error.php'; break;
}
