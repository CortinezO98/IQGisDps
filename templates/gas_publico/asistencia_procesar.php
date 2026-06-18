<?php
/**
 * templates/gas_publico/asistencia_procesar.php
 * Procesador POST del formulario de asistencia.
 * Solo acepta POST. Valida, sanitiza e inserta.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../gas_config.php';
$conn = gas_conectar_bd();
require_once __DIR__ . '/../gestion_asistencias/includes/gas_funciones.php';

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Honeypot anti-bot: si el campo website viene relleno, es un bot
if (!empty($_POST['website'])) {
    // Silenciosamente redirigir a confirmación (sin guardar)
    include __DIR__ . '/asistencia_confirmacion.php';
    exit;
}

// ── Leer y validar el token ──────────────────────────────────────────────
$token = gas_sanitizar($_POST['token'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error_tipo = 'token_invalido';
    include __DIR__ . '/error.php';
    exit;
}

// ── Revalidar sesión en estado 'activa' ──────────────────────────────────
$stmt = $conn->prepare(
    'SELECT gas_id FROM gestion_asistencias_sesiones
     WHERE gas_token_publico = ? AND gas_estado = \'activa\'
     LIMIT 1'
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

// ── Sanitizar campos ─────────────────────────────────────────────────────
$tipo_doc   = gas_sanitizar($_POST['tipo_documento']   ?? '', 30);
$num_doc    = gas_sanitizar($_POST['numero_documento'] ?? '', 20);
$nombres    = gas_sanitizar($_POST['nombres']          ?? '', 150);
$apellidos  = gas_sanitizar($_POST['apellidos']        ?? '', 150);
$correo     = filter_var(trim($_POST['correo'] ?? ''), FILTER_SANITIZE_EMAIL);
$celular    = gas_sanitizar($_POST['celular']   ?? '', 20);
$entidad    = gas_sanitizar($_POST['entidad']   ?? '', 200);
$cargo      = gas_sanitizar($_POST['cargo']     ?? '', 100);

// ── Validaciones ─────────────────────────────────────────────────────────
$errores = [];
$tipos_validos = ['CC','CE','TI','PP','NIT','OTRO'];

if (empty($tipo_doc) || !in_array($tipo_doc, $tipos_validos, true))
    $errores[] = 'Selecciona un tipo de documento válido.';
if (empty($num_doc) || !preg_match('/^[0-9A-Za-z\-]{4,20}$/', $num_doc))
    $errores[] = 'El número de documento es requerido (4-20 caracteres, solo números o letras).';
if (empty($nombres))
    $errores[] = 'Los nombres son requeridos.';
if (empty($apellidos))
    $errores[] = 'Los apellidos son requeridos.';
if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL))
    $errores[] = 'El correo electrónico es inválido.';

if (!empty($errores)) {
    $_SESSION['gas_form_errores'] = $errores;
    header('Location: index.php?t=' . urlencode($token));
    exit;
}

// ── Insertar asistencia ──────────────────────────────────────────────────
$ip    = gas_obtener_ip();
$ua    = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
$ahora = date('Y-m-d H:i:s');

$stmt = $conn->prepare(
    'INSERT INTO gestion_asistencias_registros
     (gar_sesion_id, gar_tipo_documento, gar_numero_documento,
      gar_nombres, gar_apellidos, gar_correo, gar_celular,
      gar_entidad, gar_cargo, gar_fecha_asistencia,
      gar_ip, gar_user_agent, gar_registro_fecha)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
);
$stmt->bind_param(
    'issssssssss' . 'ss',  // 13 params: i + 12 strings
    $sesion_id, $tipo_doc, $num_doc,
    $nombres, $apellidos, $correo, $celular,
    $entidad, $cargo, $ahora,
    $ip, $ua, $ahora
);

if ($stmt->execute()) {
    $gar_id = $stmt->insert_id;
    $stmt->close();
    // Guardar gar_id en sesión para que encuesta_form lo pueda usar
    $_SESSION['gas_gar_id']  = $gar_id;
    $_SESSION['gas_token']   = $token;
    $_SESSION['gas_nombres'] = $nombres . ' ' . $apellidos;
    include __DIR__ . '/asistencia_confirmacion.php';
    exit;

} else {
    $err_num = $conn->errno;
    $stmt->close();

    if ($err_num === 1062) {
        // UNIQUE constraint: ya registró asistencia en esta sesión
        $error_tipo = 'asistencia_duplicada';
    } else {
        $error_tipo = 'error_generico';
    }
    include __DIR__ . '/error.php';
    exit;
}
