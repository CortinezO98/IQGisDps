<?php
/**
 * templates/gas_publico/encuesta_confirmacion.php
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../iniciador.php';

$nombre   = htmlspecialchars($_SESSION['gas_enc_nombre']   ?? 'Asistente');
$promedio = (float)($_SESSION['gas_enc_promedio'] ?? 0);
unset($_SESSION['gas_enc_nombre'], $_SESSION['gas_enc_promedio']);
$estrellas_llenas = round($promedio);
?>
<!DOCTYPE html>
<html lang="<?php echo LANG; ?>">
<head>
  <?php require_once(ROOT.'includes/_head.php'); ?>
  <title>¡Encuesta Enviada!</title>
  <style>
    body { background:#f4f6fb; }
    .gas-pub-wrapper { min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:24px 16px; }
    .gas-pub-card { width:100%; max-width:500px; border-radius:8px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,.10); background:#fff; }
    .gas-ok-header { background:linear-gradient(135deg, #4a1a8c, #2e75b6); color:#fff; padding:32px 28px; text-align:center; }
    .gas-ok-header .gas-icon { font-size:3rem; margin-bottom:12px; }
    .gas-ok-header h4 { font-size:1.2rem; font-weight:700; margin:0 0 4px; }
    .gas-ok-header p  { font-size:13px; opacity:.85; margin:0; }
    .gas-pub-body  { padding:28px; text-align:center; }
    .gas-pub-footer{ background:#f8f9fa; border-top:1px solid #eee; padding:10px 28px; font-size:11px; color:#aaa; text-align:center; }
    .gas-score { margin:16px 0 8px; }
    .gas-score .num { font-size:3.2rem; font-weight:800; color:#1a3c6b; line-height:1; }
    .gas-score .den { font-size:1rem; color:#aaa; font-weight:400; }
    .gas-stars { font-size:1.6rem; letter-spacing:4px; margin:6px 0 16px; }
    .gas-star-on  { color:#ffc107; }
    .gas-star-off { color:#dee2e6; }
    .gas-thanks { font-size:13px; color:#666; }
  </style>
</head>
<body>
<div class="gas-pub-wrapper">
  <div class="gas-pub-card">

    <div class="gas-ok-header">
      <div class="gas-icon"><i class="fas fa-award"></i></div>
      <h4>¡Gracias por tu Evaluación!</h4>
      <p>Tu encuesta de satisfacción fue registrada correctamente.</p>
    </div>

    <div class="gas-pub-body">
      <p style="font-size:14px; color:#333; margin-bottom:8px;">
        Hola, <strong><?= $nombre ?></strong>.
      </p>

      <div class="gas-score">
        <span class="num"><?= number_format($promedio, 1) ?></span>
        <span class="den"> / 5</span>
      </div>

      <div class="gas-stars">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <i class="fas fa-star <?= $i <= $estrellas_llenas ? 'gas-star-on' : 'gas-star-off' ?>"></i>
        <?php endfor; ?>
      </div>

      <p class="gas-thanks">
        Tu calificación contribuye a mejorar la calidad de nuestras sesiones.
        ¡Gracias por participar!
      </p>
    </div>

    <div class="gas-pub-footer">
      <i class="fas fa-shield-alt me-1"></i>Tu información es tratada de forma confidencial.
    </div>
  </div>

  <div class="mt-3 text-center">
    <img src="<?= LOGO_CLIENTE ?>" alt="logo" style="height:28px; opacity:.6;">
  </div>
</div>
</body>
</html>
