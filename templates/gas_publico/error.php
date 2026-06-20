<?php
/**
 * templates/gas_publico/error.php
 * Pantalla de errores controlados del módulo GAS.
 * Variable requerida: $error_tipo (string)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../iniciador.php';

$configs = [
    'token_invalido' => [
        'icon'  => 'fas fa-unlink',
        'color' => '#dc3545',
        'grad'  => 'linear-gradient(135deg, #721c24, #dc3545)',
        'titulo'=> 'Link Inválido',
        'msg'   => 'El link que usaste no es válido o ha expirado. Verifica el link que te compartieron e intenta de nuevo.',
    ],
    'sesion_cerrada' => [
        'icon'  => 'fas fa-lock',
        'color' => '#343a40',
        'grad'  => 'linear-gradient(135deg, #1a1a2e, #343a40)',
        'titulo'=> 'Sesión Cerrada',
        'msg'   => 'Esta sesión ya fue cerrada y no acepta más registros ni encuestas.',
    ],
    'sesion_anulada' => [
        'icon'  => 'fas fa-ban',
        'color' => '#dc3545',
        'grad'  => 'linear-gradient(135deg, #721c24, #c0392b)',
        'titulo'=> 'Sesión Anulada',
        'msg'   => 'Esta sesión fue anulada por el organizador. Por favor contacta al organizador para más información.',
    ],
    'sesion_no_disponible' => [
        'icon'  => 'fas fa-hourglass-half',
        'color' => '#6c757d',
        'grad'  => 'linear-gradient(135deg, #495057, #6c757d)',
        'titulo'=> 'Sesión No Disponible',
        'msg'   => 'Esta sesión aún no está activa o no está disponible en este momento. Intenta más tarde o contacta al organizador.',
    ],
    'asistencia_duplicada' => [
        'icon'  => 'fas fa-user-check',
        'color' => '#0d6efd',
        'grad'  => 'linear-gradient(135deg, #084298, #0d6efd)',
        'titulo'=> 'Ya Estás Registrado',
        'msg'   => 'Tu documento ya fue registrado en esta sesión. No es posible registrar asistencia dos veces.',
    ],
    'no_registrado' => [
        'icon'  => 'fas fa-user-times',
        'color' => '#fd7e14',
        'grad'  => 'linear-gradient(135deg, #7d3701, #fd7e14)',
        'titulo'=> 'Asistencia No Encontrada',
        'msg'   => 'No encontramos un registro de asistencia con tu documento para esta sesión. Asegúrate de haber registrado tu asistencia previamente.',
    ],
    'encuesta_duplicada' => [
        'icon'  => 'fas fa-clipboard-check',
        'color' => '#6f42c1',
        'grad'  => 'linear-gradient(135deg, #3b0764, #6f42c1)',
        'titulo'=> 'Encuesta Ya Respondida',
        'msg'   => 'Ya respondiste la encuesta de satisfacción para esta sesión. Solo se permite una respuesta por asistente.',
    ],
    'error_generico' => [
        'icon'  => 'fas fa-exclamation-circle',
        'color' => '#6c757d',
        'grad'  => 'linear-gradient(135deg, #495057, #6c757d)',
        'titulo'=> 'Error Inesperado',
        'msg'   => 'Ocurrió un error al procesar tu solicitud. Por favor intenta de nuevo más tarde.',
    ],
];

$tipo = $error_tipo ?? 'error_generico';
$cfg  = $configs[$tipo] ?? $configs['error_generico'];
?>
<!DOCTYPE html>
<html lang="<?php echo LANG; ?>">
<head>
  <?php require_once(ROOT.'includes/_head.php'); ?>
  <title><?= htmlspecialchars($cfg['titulo']) ?></title>
  <style>
    body { background:#f4f6fb; }
    .gas-pub-wrapper { min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:24px 16px; }
    .gas-pub-card { width:100%; max-width:500px; border-radius:8px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,.10); background:#fff; }
    .gas-err-header { padding:36px 28px; text-align:center; color:#fff; background: <?= $cfg['grad'] ?>; }
    .gas-err-header .gas-icon { font-size:3rem; margin-bottom:14px; opacity:.95; }
    .gas-err-header h4 { font-size:1.2rem; font-weight:700; margin:0; }
    .gas-err-body  { padding:28px; text-align:center; }
    .gas-err-body p { font-size:13px; color:#555; line-height:1.6; }
    .gas-err-footer{ background:#f8f9fa; border-top:1px solid #eee; padding:10px 28px; font-size:11px; color:#aaa; text-align:center; }
    .btn-gas-secondary { background:#f0f4f8; color:#1a3c6b; border:1px solid #d0dce8; border-radius:5px; padding:8px 22px; font-size:12px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-block; transition:background .15s; margin-top:4px; }
    .btn-gas-secondary:hover { background:#dde6f0; color:#1a3c6b; }
  </style>
</head>
<body>
<div class="gas-pub-wrapper">
  <div class="gas-pub-card">

    <div class="gas-err-header">
      <div class="gas-icon"><i class="<?= $cfg['icon'] ?>"></i></div>
      <h4><?= htmlspecialchars($cfg['titulo']) ?></h4>
    </div>

    <div class="gas-err-body">
      <p><?= htmlspecialchars($cfg['msg']) ?></p>
      <div class="d-flex justify-content-center gap-2 mt-3">
        <a href="javascript:history.back()" class="btn-gas-secondary">
          <i class="fas fa-arrow-left me-1"></i>Regresar
        </a>
        <a href="javascript:window.close()" class="btn-gas-secondary">
          <i class="fas fa-times me-1"></i>Cerrar
        </a>
      </div>
    </div>

    <div class="gas-err-footer">
      <i class="fas fa-headset me-1"></i>
      Si necesitas ayuda, contacta al organizador de la sesión.
    </div>
  </div>

  <div class="mt-3 text-center">
    <img src="<?= LOGO_CLIENTE ?>" alt="logo" style="height:28px; opacity:.6;">
  </div>
</div>
</body>
</html>
