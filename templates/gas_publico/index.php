<?php
/**
 * templates/gas_publico/index.php
 * ─────────────────────────────────────────────────────────────────────────
 * ENRUTADOR PÚBLICO del módulo GAS.
 * Acceso: http://127.0.0.1/IQGisDps/templates/gas_publico/index.php?t=TOKEN
 *
 * NO requiere sesión de usuario DPS.
 * Usa iniciador.php SOLO para obtener $enlace_db con las credenciales
 * correctas de config.php — sin validación de sesión de usuario.
 * ─────────────────────────────────────────────────────────────────────────
 */

// Sesión propia del módulo público (para pasar gar_id entre páginas)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Cargar config y conexión BD usando el mismo iniciador.php de DPS ────
// iniciador.php → config.php (define constantes) + security_index.php
//               → functions.php (define funciones) + db.php (crea $enlace_db)
// NO llama security.php ni valida sesión de usuario → seguro para vistas públicas
require_once __DIR__ . '/../../iniciador.php';

// Funciones del módulo GAS
require_once __DIR__ . '/../gestion_asistencias/includes/gas_funciones.php';

// ── Leer y validar el token ──────────────────────────────────────────────
$token = trim($_GET['t'] ?? '');

if (empty($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error_tipo = 'token_invalido';
    include __DIR__ . '/error.php';
    exit;
}

// ── Buscar sesión por token — usa $enlace_db (variable de DPS) ──────────
$stmt = $enlace_db->prepare(
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

// ── Enrutar según estado de la sesión ───────────────────────────────────
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
