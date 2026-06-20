<?php
/**
 * templates/gas_publico/asistencia_confirmacion.php
 * Pantalla de confirmación exitosa de asistencia.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../iniciador.php';

$nombre_asistente = htmlspecialchars($_SESSION['gas_nombres'] ?? 'Asistente');
$token_sesion     = $_SESSION['gas_token'] ?? '';
$link_publico     = !empty($token_sesion)
    ? GAS_PUBLIC_URL . '?t=' . urlencode($token_sesion)
    : '';
?>
<!DOCTYPE html>
<html lang="<?php echo LANG; ?>">
<head>
  <?php require_once(ROOT.'includes/_head.php'); ?>
  <title>¡Asistencia Registrada!</title>
  <style>
    body { background: #f4f6fb; }
    .gas-pub-wrapper { min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:24px 16px; }
    .gas-pub-card { width:100%; max-width:520px; border-radius:8px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,.10); background:#fff; }
    .gas-ok-header { background: linear-gradient(135deg, #155724, #198754); color:#fff; padding:32px 28px; text-align:center; }
    .gas-ok-header .gas-icon { font-size:3rem; margin-bottom:12px; opacity:.95; }
    .gas-ok-header h4 { font-size:1.2rem; font-weight:700; margin:0 0 4px; }
    .gas-ok-header p  { font-size:13px; opacity:.85; margin:0; }
    .gas-pub-body  { padding:24px 28px; }
    .gas-pub-footer{ background:#f8f9fa; border-top:1px solid #eee; padding:10px 28px; font-size:11px; color:#aaa; text-align:center; }
    .gas-link-share { background:#f0f4f8; border:1px solid #d0dce8; border-radius:6px; padding:8px 10px; display:flex; align-items:center; gap:8px; margin-top:16px; }
    .gas-link-share input { flex:1; border:none; background:transparent; font-size:11px; color:#1a3c6b; font-family:'Courier New',monospace; outline:none; }
    .gas-link-share .btn-copy { flex-shrink:0; border:none; background:#1a3c6b; color:#fff; border-radius:4px; padding:4px 10px; font-size:11px; cursor:pointer; transition:background .15s; white-space:nowrap; }
    .gas-link-share .btn-copy:hover { background:#2e75b6; }
    .gas-link-share .btn-copy.copied { background:#198754; }
    .info-box { background:#f0f4f8; border-left:3px solid #1a3c6b; border-radius:0 6px 6px 0; padding:12px 14px; font-size:12px; color:#1a3c6b; margin-top:16px; }
    .info-box strong { display:block; margin-bottom:3px; }
  </style>
</head>
<body>
<div class="gas-pub-wrapper">
  <div class="gas-pub-card">

    <div class="gas-ok-header">
      <div class="gas-icon"><i class="fas fa-check-circle"></i></div>
      <h4>¡Asistencia Registrada!</h4>
      <p>Tu participación ha sido confirmada exitosamente.</p>
    </div>

    <div class="gas-pub-body">
      <p style="font-size:14px; color:#333;">
        Hola, <strong><?= $nombre_asistente ?></strong>. Tu asistencia quedó registrada correctamente.
      </p>

      <div class="info-box">
        <strong><i class="fas fa-info-circle me-1"></i>¿Qué sigue?</strong>
        Cuando la sesión finalice, el organizador habilitará la encuesta de satisfacción.
        Podrás responderla usando el mismo link de esta sesión.
      </div>

      <?php if ($link_publico): ?>
        <div style="margin-top:12px; font-size:12px; color:#666;">
          <i class="fas fa-link me-1" style="color:#1a3c6b;"></i>
          <strong>Guarda el link para la encuesta:</strong>
        </div>
        <div class="gas-link-share">
          <i class="fas fa-link" style="color:#1a3c6b; font-size:12px; flex-shrink:0;"></i>
          <input type="text" id="gas_link_conf" value="<?= htmlspecialchars($link_publico) ?>" readonly>
          <button class="btn-copy" id="btn_copiar_conf" onclick="copiarLink()">
            <i class="fas fa-copy me-1"></i>Copiar
          </button>
        </div>
      <?php endif; ?>
    </div>

    <div class="gas-pub-footer">
      <i class="fas fa-shield-alt me-1"></i>
      Tu información es tratada de forma confidencial.
    </div>
  </div>

  <div class="mt-3 text-center">
    <img src="<?= LOGO_CLIENTE ?>" alt="logo" style="height:28px; opacity:.6;">
  </div>
</div>

<script>
function copiarLink() {
  const input = document.getElementById('gas_link_conf');
  const btn   = document.getElementById('btn_copiar_conf');
  const orig  = btn.innerHTML;
  function ok() {
    btn.innerHTML = '<i class="fas fa-check me-1"></i>¡Copiado!';
    btn.classList.add('copied');
    setTimeout(() => { btn.innerHTML = orig; btn.classList.remove('copied'); }, 2500);
  }
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(input.value).then(ok).catch(() => { input.select(); document.execCommand('copy'); ok(); });
  } else { input.select(); document.execCommand('copy'); ok(); }
}
</script>
</body>
</html>
