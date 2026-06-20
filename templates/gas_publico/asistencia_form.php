<?php
/**
 * templates/gas_publico/asistencia_form.php
 * Incluido desde index.php — $sesion, $token y $enlace_db disponibles.
 */

$errores_form = $_SESSION['gas_form_errores'] ?? [];
unset($_SESSION['gas_form_errores']);

$tipos_doc = [
    'CC'   => 'Cédula de Ciudadanía',
    'CE'   => 'Cédula de Extranjería',
    'TI'   => 'Tarjeta de Identidad',
    'PP'   => 'Pasaporte',
    'NIT'  => 'NIT',
    'OTRO' => 'Otro',
];

// Link público para el botón de copiar
$link_publico = GAS_PUBLIC_URL . '?t=' . urlencode($token);
?>
<!DOCTYPE html>
<html lang="<?php echo LANG; ?>">
<head>
  <?php require_once(ROOT.'includes/_head.php'); ?>
  <title>Registro de Asistencia — <?= htmlspecialchars($sesion['gas_nombre']) ?></title>
  <style>
    body { background: #f4f6fb; }

    .gas-pub-wrapper {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
    }

    .gas-pub-card {
      width: 100%;
      max-width: 640px;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(0,0,0,.10);
      background: #fff;
    }

    .gas-pub-header {
      background: var(--gas-header-bg, #1a3c6b);
      padding: 24px 28px 20px;
      color: #fff;
    }

    .gas-pub-header .gas-tag {
      display: inline-block;
      background: rgba(255,255,255,.18);
      border-radius: 20px;
      padding: 2px 12px;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: .5px;
      text-transform: uppercase;
      margin-bottom: 10px;
    }

    .gas-pub-header h5 {
      font-size: 1.15rem;
      font-weight: 700;
      margin: 0 0 6px;
      line-height: 1.3;
    }

    .gas-pub-header .gas-meta {
      font-size: 12px;
      opacity: .82;
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
    }

    .gas-pub-header .gas-meta span {
      display: flex;
      align-items: center;
      gap: 5px;
    }

    /* Link público */
    .gas-link-share {
      background: #f0f4f8;
      border: 1px solid #d0dce8;
      border-radius: 6px;
      padding: 8px 10px;
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 20px;
    }
    .gas-link-share input {
      flex: 1;
      border: none;
      background: transparent;
      font-size: 11px;
      color: #1a3c6b;
      font-family: 'Courier New', monospace;
      outline: none;
    }
    .gas-link-share .btn-copy {
      flex-shrink: 0;
      border: none;
      background: #1a3c6b;
      color: #fff;
      border-radius: 4px;
      padding: 4px 10px;
      font-size: 11px;
      cursor: pointer;
      transition: background .15s;
      white-space: nowrap;
    }
    .gas-link-share .btn-copy:hover { background: #2e75b6; }
    .gas-link-share .btn-copy.copied { background: #198754; }

    .gas-pub-body { padding: 24px 28px; }
    .gas-pub-footer {
      background: #f8f9fa;
      border-top: 1px solid #eee;
      padding: 10px 28px;
      font-size: 11px;
      color: #aaa;
      text-align: center;
    }

    .gas-section-label {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .6px;
      color: #6c757d;
      margin-bottom: 12px;
      padding-bottom: 6px;
      border-bottom: 1px solid #e9ecef;
    }

    .required { color: #dc3545; }

    .form-control, .form-select {
      font-size: 13px;
      border-radius: 5px;
    }
    .form-label { font-size: 12px; font-weight: 600; color: #495057; margin-bottom: 4px; }
    .form-text  { font-size: 11px; }

    .btn-gas-primary {
      background: #1a3c6b;
      color: #fff;
      border: none;
      border-radius: 5px;
      padding: 10px 28px;
      font-size: 13px;
      font-weight: 600;
      width: 100%;
      transition: background .15s;
    }
    .btn-gas-primary:hover { background: #2e75b6; color: #fff; }

    @media (max-width: 480px) {
      .gas-pub-header { padding: 18px 18px 14px; }
      .gas-pub-body   { padding: 18px 18px; }
      .gas-pub-footer { padding: 10px 18px; }
    }
  </style>
</head>
<body>
<div class="gas-pub-wrapper">
  <div class="gas-pub-card">

    <!-- HEADER -->
    <div class="gas-pub-header">
      <div class="gas-tag"><i class="fas fa-clipboard-list me-1"></i>Registro de Asistencia</div>
      <h5><?= htmlspecialchars($sesion['gas_nombre']) ?></h5>
      <div class="gas-meta">
        <span><i class="fas fa-user-tie"></i> <?= htmlspecialchars($sesion['gas_facilitador']) ?></span>
        <span><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y H:i', strtotime($sesion['gas_fecha_inicio'])) ?></span>
        <?php if (!empty($sesion['gas_fecha_fin'])): ?>
          <span><i class="fas fa-clock"></i> Fin: <?= date('d/m/Y H:i', strtotime($sesion['gas_fecha_fin'])) ?></span>
        <?php endif; ?>
        <span><i class="fas fa-circle text-success" style="font-size:8px;"></i> Sesión Activa</span>
      </div>
    </div>

    <!-- BODY -->
    <div class="gas-pub-body">

      <!-- Link para compartir -->
      <div class="gas-link-share">
        <i class="fas fa-link" style="color:#1a3c6b; font-size:12px; flex-shrink:0;"></i>
        <input type="text" id="gas_link_publico" value="<?= htmlspecialchars($link_publico) ?>" readonly>
        <button class="btn-copy" id="btn_copiar" onclick="copiarLink()">
          <i class="fas fa-copy me-1"></i>Copiar Link
        </button>
      </div>

      <!-- Alertas de validación -->
      <?php if (!empty($errores_form)): ?>
        <div class="alert alert-danger py-2 mb-3" style="font-size:12px;">
          <strong><i class="fas fa-exclamation-triangle me-1"></i>Corrige los siguientes campos:</strong>
          <ul class="mb-0 mt-1 ps-3">
            <?php foreach ($errores_form as $e): ?>
              <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Formulario -->
      <form method="POST" action="asistencia_procesar.php" novalidate>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <!-- Honeypot anti-bot -->
        <div style="display:none;"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

        <!-- Identificación -->
        <div class="gas-section-label"><i class="fas fa-id-card me-1"></i>Identificación</div>
        <div class="row g-2 mb-3">
          <div class="col-5">
            <label class="form-label">Tipo de Documento <span class="required">*</span></label>
            <select name="tipo_documento" class="form-select form-select-sm" required>
              <option value="">Selecciona...</option>
              <?php foreach ($tipos_doc as $val => $lbl): ?>
                <option value="<?= $val ?>"><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-7">
            <label class="form-label">Número de Documento <span class="required">*</span></label>
            <input type="text" name="numero_documento" class="form-control form-control-sm"
                   placeholder="Sin puntos ni espacios" maxlength="20"
                   pattern="[0-9A-Za-z\-]{4,20}" required>
          </div>
        </div>

        <!-- Datos personales -->
        <div class="gas-section-label"><i class="fas fa-user me-1"></i>Datos Personales</div>
        <div class="row g-2 mb-3">
          <div class="col-md-6">
            <label class="form-label">Nombres <span class="required">*</span></label>
            <input type="text" name="nombres" class="form-control form-control-sm"
                   maxlength="150" required placeholder="Ej: María Camila">
          </div>
          <div class="col-md-6">
            <label class="form-label">Apellidos <span class="required">*</span></label>
            <input type="text" name="apellidos" class="form-control form-control-sm"
                   maxlength="150" required placeholder="Ej: González Ruiz">
          </div>
          <div class="col-md-7">
            <label class="form-label">Correo Electrónico <span class="required">*</span></label>
            <input type="email" name="correo" class="form-control form-control-sm"
                   maxlength="200" required placeholder="correo@ejemplo.com">
          </div>
          <div class="col-md-5">
            <label class="form-label">Celular</label>
            <input type="tel" name="celular" class="form-control form-control-sm"
                   maxlength="20" placeholder="3001234567">
          </div>
        </div>

        <!-- Datos laborales -->
        <div class="gas-section-label"><i class="fas fa-building me-1"></i>Datos Laborales <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:10px;">(Opcional)</span></div>
        <div class="row g-2 mb-4">
          <div class="col-md-7">
            <label class="form-label">Entidad / Organización</label>
            <input type="text" name="entidad" class="form-control form-control-sm"
                   maxlength="200" placeholder="Nombre de tu entidad u organización">
          </div>
          <div class="col-md-5">
            <label class="form-label">Cargo</label>
            <input type="text" name="cargo" class="form-control form-control-sm"
                   maxlength="100" placeholder="Tu cargo o rol">
          </div>
        </div>

        <button type="submit" class="btn-gas-primary">
          <i class="fas fa-check-circle me-2"></i>Registrar mi Asistencia
        </button>

      </form>
    </div>

    <div class="gas-pub-footer">
      <i class="fas fa-shield-alt me-1"></i>
      La información suministrada es confidencial y usada únicamente con fines estadísticos y de seguimiento.
    </div>
  </div>

  <!-- Logo inferior -->
  <div class="mt-3 text-center">
    <img src="<?= LOGO_CLIENTE ?>" alt="logo" style="height:28px; opacity:.6;">
  </div>
</div>

<script>
function copiarLink() {
  const input  = document.getElementById('gas_link_publico');
  const btn    = document.getElementById('btn_copiar');
  const orig   = btn.innerHTML;

  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(input.value).then(ok, fallback);
  } else {
    fallback();
  }

  function ok() {
    btn.innerHTML = '<i class="fas fa-check me-1"></i>¡Copiado!';
    btn.classList.add('copied');
    setTimeout(() => { btn.innerHTML = orig; btn.classList.remove('copied'); }, 2500);
  }
  function fallback() {
    input.select();
    document.execCommand('copy');
    ok();
  }
}
</script>
</body>
</html>
