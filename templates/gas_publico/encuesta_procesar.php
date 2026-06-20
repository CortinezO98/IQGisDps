<?php
/**
 * templates/gas_publico/encuesta_procesar.php
 * Procesador POST de la encuesta de satisfacción.
 * CORRECCIÓN: usa iniciador.php para obtener $enlace_db en lugar de gas_conectar_bd()
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Usar iniciador.php de DPS → obtiene $enlace_db con credenciales correctas
require_once __DIR__ . '/../../iniciador.php';
require_once __DIR__ . '/../gestion_asistencias/includes/gas_funciones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// ── Validar token ─────────────────────────────────────────────────────────
$token = gas_sanitizar($_POST['token'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error_tipo = 'token_invalido';
    include __DIR__ . '/error.php';
    exit;
}

// ── Revalidar sesión en estado 'finalizada' ───────────────────────────────
$stmt = $enlace_db->prepare(
    "SELECT gas_id, gas_nombre, gas_facilitador
     FROM gestion_asistencias_sesiones
     WHERE gas_token_publico = ? AND gas_estado = 'finalizada'
     LIMIT 1"
);
$stmt->bind_param('s', $token);
$stmt->execute();
$sesion = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sesion) {
    $error_tipo = 'sesion_no_disponible';
    include __DIR__ . '/error.php';
    exit;
}
$sesion_id = (int)$sesion['gas_id'];

// ── Verificar documento del asistente ────────────────────────────────────
$tipo_doc = gas_sanitizar($_POST['tipo_documento']   ?? '', 30);
$num_doc  = gas_sanitizar($_POST['numero_documento'] ?? '', 20);

if (empty($tipo_doc) || empty($num_doc)) {
    $_SESSION['gas_encuesta_errores'] = ['Ingresa tu tipo y número de documento para verificar tu asistencia.'];
    header('Location: index.php?t=' . urlencode($token));
    exit;
}

$stmt = $enlace_db->prepare(
    "SELECT gar_id, gar_nombres, gar_apellidos
     FROM gestion_asistencias_registros
     WHERE gar_sesion_id = ?
       AND gar_tipo_documento   = ?
       AND gar_numero_documento = ?
       AND gar_estado = 'activo'
     LIMIT 1"
);
$stmt->bind_param('iss', $sesion_id, $tipo_doc, $num_doc);
$stmt->execute();
$asistencia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$asistencia) {
    $error_tipo = 'no_registrado';
    include __DIR__ . '/error.php';
    exit;
}
$asistencia_id = (int)$asistencia['gar_id'];

// ── Recoger y validar calificaciones (1-5) ───────────────────────────────
$campos_cal = ['tema', 'facilitador', 'metodologia', 'material', 'general'];
$errores    = [];
$cal        = [];

foreach ($campos_cal as $c) {
    $v = (int)($_POST['cal_' . $c] ?? 0);
    if ($v < 1 || $v > 5) {
        $errores[] = 'La calificación "' . $c . '" es requerida (1-5).';
    } else {
        $cal[$c] = $v;
    }
}

if (!empty($errores)) {
    $_SESSION['gas_encuesta_errores'] = $errores;
    header('Location: index.php?t=' . urlencode($token));
    exit;
}

$observacion = gas_sanitizar($_POST['observacion'] ?? '', 1000);
$promedio    = gas_calcular_promedio(
    $cal['tema'], $cal['facilitador'], $cal['metodologia'],
    $cal['material'], $cal['general']
);

$ip    = gas_obtener_ip();
$ua    = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
$ahora = date('Y-m-d H:i:s');

// ── Insertar encuesta ─────────────────────────────────────────────────────
// 12 parámetros: ii + iiiii + s + d + s + s + s
// gae_sesion_id(i), gae_asistencia_id(i),
// cal_tema(i), cal_faci(i), cal_meto(i), cal_mate(i), cal_gene(i),
// observacion(s), promedio(d), fecha(s), ip(s), ua(s)
$stmt = $enlace_db->prepare(
    'INSERT INTO gestion_asistencias_encuestas
     (gae_sesion_id, gae_asistencia_id,
      gae_calificacion_tema, gae_calificacion_facilitador,
      gae_calificacion_metodologia, gae_calificacion_material,
      gae_calificacion_general, gae_observacion,
      gae_calificacion_promedio, gae_fecha_respuesta,
      gae_ip, gae_user_agent)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
);
$stmt->bind_param(
    'iiiiiiisdsss',  // 12: sesion(i)+asistencia(i)+5_cal(iiiii)+obs(s)+prom(d)+fecha(s)+ip(s)+ua(s)
    $sesion_id, $asistencia_id,
    $cal['tema'], $cal['facilitador'],
    $cal['metodologia'], $cal['material'],
    $cal['general'], $observacion,
    $promedio, $ahora, $ip, $ua
);

if ($stmt->execute()) {
    $stmt->close();
    $_SESSION['gas_enc_nombre']   = $asistencia['gar_nombres'] . ' ' . $asistencia['gar_apellidos'];
    $_SESSION['gas_enc_promedio'] = $promedio;
    include __DIR__ . '/encuesta_confirmacion.php';
    exit;

} else {
    $err_num = $enlace_db->errno;
    $stmt->close();
    $error_tipo = ($err_num === 1062) ? 'encuesta_duplicada' : 'error_generico';
    include __DIR__ . '/error.php';
    exit;
}
