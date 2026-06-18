<?php
/**
 * templates/gas_publico/encuesta_confirmacion.php
 */
if (session_status() === PHP_SESSION_NONE) session_start();
$nombre   = htmlspecialchars($_SESSION['gas_enc_nombre']   ?? 'Asistente');
$promedio = number_format((float)($_SESSION['gas_enc_promedio'] ?? 0), 1);
unset($_SESSION['gas_enc_nombre'], $_SESSION['gas_enc_promedio']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>¡Encuesta Enviada!</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    body { background: #f0f4f8; display: flex; align-items: center; min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
    .gas-card { max-width: 540px; margin: auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.12); }
    .gas-ok { background: linear-gradient(135deg, #6f42c1, #0d6efd); color: white; padding: 40px 32px; text-align: center; }
    .gas-body { background: white; padding: 32px; text-align: center; }
    .gas-footer { background: #f8f9fa; padding: 12px 32px; font-size: .8rem; color: #888; text-align: center; }
    .stars { font-size: 2rem; letter-spacing: 4px; }
  </style>
</head>
<body>
<div class="container">
  <div class="gas-card">
    <div class="gas-ok">
      <div style="font-size:3.5rem; margin-bottom:12px;">🎉</div>
      <h4>¡Gracias por tu Evaluación!</h4>
      <p class="mb-0 opacity-75">Tu encuesta ha sido registrada exitosamente.</p>
    </div>
    <div class="gas-body">
      <p class="lead">Hola, <strong><?= $nombre ?></strong></p>
      <p class="text-muted">Tu calificación promedio de esta sesión fue:</p>
      <div class="display-4 fw-bold text-primary mb-2"><?= $promedio ?><small class="fs-5 text-muted">/5</small></div>
      <div class="stars">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <span style="color:<?= $i <= round((float)$promedio) ? '#ffc107' : '#dee2e6' ?>">★</span>
        <?php endfor; ?>
      </div>
      <p class="text-muted mt-3 small">Tu opinión contribuye a mejorar la calidad de nuestras sesiones. ¡Gracias por participar!</p>
    </div>
    <div class="gas-footer">Puedes cerrar esta ventana.</div>
  </div>
</div>
</body>
</html>
