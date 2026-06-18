<?php
/**
 * templates/gestion_asistencias/sesion_cambiar_estado.php
 * Procesador POST para cambio de estado de una sesión.
 * Solo acepta POST. Valida la transición y redirige.
 */

require_once __DIR__ . '/../../iniciador.php';
require_once __DIR__ . '/includes/gas_permisos.php';
require_once __DIR__ . '/includes/gas_funciones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$sesion_id   = (int)($_POST['sesion_id']   ?? 0);
$nuevo_estado = gas_sanitizar($_POST['nuevo_estado'] ?? '');
$redirect    = gas_sanitizar($_POST['redirect']     ?? 'index.php', 200);

// Validar ID
if ($sesion_id <= 0) {
    header('Location: index.php?error=id_invalido');
    exit;
}

// Validar estado nuevo
$estados_validos = ['activa', 'finalizada', 'cerrada', 'anulada'];
if (!in_array($nuevo_estado, $estados_validos, true)) {
    header('Location: ' . $redirect . '&error=estado_invalido');
    exit;
}

// Obtener estado actual
$stmt = $conn->prepare(
    'SELECT gas_estado FROM gestion_asistencias_sesiones WHERE gas_id = ? LIMIT 1'
);
$stmt->bind_param('i', $sesion_id);
$stmt->execute();
$stmt->bind_result($estado_actual);
$stmt->fetch();
$stmt->close();

if (!$estado_actual) {
    header('Location: index.php?error=no_encontrada');
    exit;
}

// Validar que la transición sea legal
if (!gas_validar_transicion($estado_actual, $nuevo_estado)) {
    header('Location: ' . $redirect . '&error=transicion_invalida');
    exit;
}

// Actualizar estado
$usuario = GAS_USUARIO_SESION;
$ahora   = date('Y-m-d H:i:s');

// Si se está finalizando, guardar fecha_fin si está vacía
if ($nuevo_estado === 'finalizada') {
    $stmt = $conn->prepare(
        'UPDATE gestion_asistencias_sesiones
         SET gas_estado = ?,
             gas_fecha_fin = CASE WHEN gas_fecha_fin IS NULL THEN ? ELSE gas_fecha_fin END,
             gas_modificacion_usuario = ?,
             gas_modificacion_fecha   = ?
         WHERE gas_id = ?'
    );
    $stmt->bind_param('ssssi', $nuevo_estado, $ahora, $usuario, $ahora, $sesion_id);
} else {
    $stmt = $conn->prepare(
        'UPDATE gestion_asistencias_sesiones
         SET gas_estado = ?,
             gas_modificacion_usuario = ?,
             gas_modificacion_fecha   = ?
         WHERE gas_id = ?'
    );
    $stmt->bind_param('sssi', $nuevo_estado, $usuario, $ahora, $sesion_id);
}

if ($stmt->execute()) {
    $stmt->close();
    header('Location: ' . $redirect . '&ok=estado_actualizado');
} else {
    $stmt->close();
    header('Location: ' . $redirect . '&error=db_error');
}
exit;
