<?php
/**
 * templates/gestion_asistencias/sesion_cambiar_estado.php — CORREGIDO para DPS
 */

$modulo_plataforma = "Gestión Asistencias";
require_once("../../iniciador.php");
require_once(__DIR__ . '/includes/gas_funciones.php');
require_once(__DIR__ . '/includes/gas_permisos.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$sesion_id    = (int)($_POST['sesion_id']    ?? 0);
$nuevo_estado = gas_sanitizar($_POST['nuevo_estado'] ?? '');
$redirect     = gas_sanitizar($_POST['redirect']     ?? 'index.php', 200);

if ($sesion_id <= 0) {
    header('Location: index.php?error=id_invalido');
    exit;
}

$estados_validos = ['activa', 'finalizada', 'cerrada', 'anulada'];
if (!in_array($nuevo_estado, $estados_validos, true)) {
    header('Location: ' . $redirect . '&error=estado_invalido');
    exit;
}

// Obtener estado actual
$stmt = $enlace_db->prepare(
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

if (!gas_validar_transicion($estado_actual, $nuevo_estado)) {
    header('Location: ' . $redirect . '&error=transicion_invalida');
    exit;
}

$usuario = GAS_USUARIO_SESION;
$ahora   = date('Y-m-d H:i:s');

if ($nuevo_estado === 'finalizada') {
    $stmt = $enlace_db->prepare(
        "UPDATE gestion_asistencias_sesiones
         SET gas_estado = ?,
             gas_fecha_fin = CASE WHEN gas_fecha_fin IS NULL THEN ? ELSE gas_fecha_fin END,
             gas_modificacion_usuario = ?,
             gas_modificacion_fecha   = ?
         WHERE gas_id = ?"
    );
    $stmt->bind_param('ssssi', $nuevo_estado, $ahora, $usuario, $ahora, $sesion_id);
} else {
    $stmt = $enlace_db->prepare(
        "UPDATE gestion_asistencias_sesiones
         SET gas_estado = ?,
             gas_modificacion_usuario = ?,
             gas_modificacion_fecha   = ?
         WHERE gas_id = ?"
    );
    $stmt->bind_param('sssi', $nuevo_estado, $usuario, $ahora, $sesion_id);
}

if ($stmt->execute()) {
    $stmt->close();
    registro_log($enlace_db, 'Gestión Asistencias', 'editar',
        'Estado sesión ' . $sesion_id . ' → ' . $nuevo_estado);
    header('Location: ' . $redirect . '&ok=estado_actualizado');
} else {
    $stmt->close();
    header('Location: ' . $redirect . '&error=db_error');
}
exit;
