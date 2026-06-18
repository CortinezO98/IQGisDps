<?php
/**
 * templates/gas_publico/asistencia_confirmacion.php
 * Pantalla de confirmación tras registrar asistencia exitosamente.
 */

if (session_status() === PHP_SESSION_NONE) session_start();
$nombre_asistente = htmlspecialchars($_SESSION['gas_nombres'] ?? 'Asistente');
$token_sesion     = htmlspecialchars($_SESSION['gas_token']   ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>¡Asistencia Registrada!</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; display: flex; align-items: center; min-height: 100vh; }
    .gas-card { max-width: 560px; margin: auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.12); }
    .gas-ok { background: linear-gradient(135deg, #198754, #20c997); color: white; padding: 40px 32px; text-align: center; }
    .gas-ok .icon { font-size: 3.5rem; margin-bottom: 12px; }
    .gas-body { background: white; padding: 32px; text-align: center; }
    .gas-footer { background: #f8f9fa; padding: 12px 32px; font-size: .8rem; color: #888; text-align: center; }
  </style>
</head>
<body>
<div class="container">
  <div class="gas-card">
    <div class="gas-ok">
      <div class="icon">✅</div>
      <h4 class="mb-1">¡Asistencia Registrada!</h4>
      <p class="mb-0 opacity-75">Tu participación ha sido confirmada.</p>
    </div>
    <div class="gas-body">
      <p class="lead">Hola, <strong><?= $nombre_asistente ?></strong></p>
      <p class="text-muted">
        Tu asistencia a esta sesión ha sido registrada exitosamente.
        Cuando la sesión finalice, podrás usar el mismo link para responder
        la <strong>encuesta de satisfacción</strong>.
      </p>
      <?php if ($token_sesion): ?>
        <div class="bg-light rounded p-3 mt-3">
          <p class="mb-1 small text-muted">Guarda el link de la sesión:</p>
          <code class="small"><?= GAS_PUBLIC_URL ?>?t=<?= $token_sesion ?></code>
        </div>
      <?php endif; ?>
    </div>
    <div class="gas-footer">
      Puedes cerrar esta ventana. Te recordamos guardar el link para la encuesta final.
    </div>
  </div>
</div>
</body>
</html>
