<?php
/**
 * templates/gas_publico/encuesta_form.php
 * Formulario de encuesta de satisfacción — incluido desde index.php.
 * Variables disponibles: $sesion, $token, $enlace_db
 */
if (session_status() === PHP_SESSION_NONE) session_start();

$errores_form = $_SESSION['gas_encuesta_errores'] ?? [];
unset($_SESSION['gas_encuesta_errores']);

$link_publico = GAS_PUBLIC_URL . '?t=' . urlencode($token);

$preguntas = [
    'tema'         => '¿Cómo califica el dominio del tema por parte del facilitador?',
    'facilitador'  => '¿Cómo califica la claridad y calidad de la explicación?',
    'metodologia'  => '¿Cómo califica la metodología utilizada durante la sesión?',
    'material'     => '¿Cómo califica el material o contenido presentado?',
    'general'      => '¿Cuál es su calificación general de la sesión?',
];
$escala = [1 => 'Deficiente', 2 => 'Regular', 3 => 'Aceptable', 4 => 'Bueno', 5 => 'Excelente'];
?>
<!DOCTYPE html>
<html lang="<?php echo LANG; ?>">
<head>
  <?php require_once(ROOT.'includes/_head.php'); ?>
  <title>Encuesta de Satisfacción — <?= htmlspecialchars($sesion['gas_nombre']) ?></title>
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
      max-width: 660px;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(0,0,0,.10);
      background: #fff;
    }

    /* Header morado para encuesta */
    .gas-pub-header {
      background: linear-gradient(135deg, #4a1a8c, #2e75b6);
      padding: 22px 28px 18px;
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
    .gas-pub-header h5 { font-size:1.1rem; font-weight:700; margin:0 0 6px; }
    .gas-pub-header .gas-meta { font-size:12px; opacity:.82; display:flex; flex-wrap:wrap; gap:12px; }
    .gas-pub-header .gas-meta span { display:flex; align-items:center; gap:5px; }

    .gas-pub-body  { padding: 22px 28px; }
    .gas-pub-footer{ background:#f8f9fa; border-top:1px solid #eee; padding:10px 28px; font-size:11px; color:#aaa; text-align:center; }

    .gas-section-label {
      font-size:11px; font-weight:700; text-transform:uppercase;
      letter-spacing:.6px; color:#6c757d; margin-bottom:12px;
      padding-bottom:6px; border-bottom:1px solid #e9ecef;
    }

    /* Verificación de identidad */
    .gas-id-box {
      background: #f0f4f8;
      border: 1px solid #d0dce8;
      border-radius: 6px;
      padding: 14px 16px;
      margin-bottom: 20px;
    }
    .gas-id-box .gas-id-title {
      font-size: 12px; font-weight: 700; color: #1a3c6b; margin-bottom: 10px;
    }

    /* Tarjeta de pregunta */
    .gas-pregunta {
      border: 1px solid #e9ecef;
      border-radius: 7px;
      padding: 14px 16px;
      margin-bottom: 10px;
      transition: border-color .2s, background .2s;
      background: #fafbfc;
    }
    .gas-pregunta:focus-within {
      border-color: #2e75b6;
      background: #f0f6ff;
    }
    .gas-pregunta.has-error {
      border-color: #dc3545;
      background: #fff5f5;
    }
    .gas-pregunta-num {
      font-size: 10px; font-weight: 700; color: #4a1a8c;
      text-transform: uppercase; letter-spacing:.5px; margin-bottom:4px;
    }
    .gas-pregunta-texto {
      font-size: 13px; font-weight: 600; color: #2c3e50; margin-bottom: 12px;
    }

    /* Botones de calificación (1-5) */
    .gas-rating { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
    .gas-rating input[type="radio"] { display:none; }
    .gas-rating label {
      width: 40px; height: 40px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 14px;
      cursor: pointer;
      border: 2px solid #dee2e6;
      background: #fff;
      color: #6c757d;
      transition: all .18s;
      user-select: none;
    }
    .gas-rating label:hover { border-color: #2e75b6; background: #e8f0ff; color: #2e75b6; }
    .gas-rating input[type="radio"]:checked + label {
      background: #1a3c6b; color: #fff; border-color: #1a3c6b;
      box-shadow: 0 2px 8px rgba(26,60,107,.3);
      transform: scale(1.08);
    }
    .gas-rating-desc {
      font-size: 11px; color: #2e75b6; font-weight: 600;
      margin-left: 8px; min-width: 70px;
    }

    /* Observaciones */
    .gas-obs-box { margin-bottom: 16px; }
    .gas-obs-counter { font-size:11px; text-align:right; color:#adb5bd; margin-top:3px; }

    /* Botón principal */
    .btn-gas-primary {
      background: #1a3c6b; color: #fff; border: none;
      border-radius: 5px; padding: 11px 28px;
      font-size: 13px; font-weight: 600; width: 100%;
      transition: background .15s; cursor: pointer;
    }
    .btn-gas-primary:hover { background: #2e75b6; }

    /* Link copiar */
    .gas-link-share { background:#f0f4f8; border:1px solid #d0dce8; border-radius:6px; padding:7px 10px; display:flex; align-items:center; gap:8px; margin-bottom:18px; }
    .gas-link-share input { flex:1; border:none; background:transparent; font-size:11px; color:#1a3c6b; font-family:'Courier New',monospace; outline:none; }
    .gas-link-share .btn-copy { flex-shrink:0; border:none; background:#1a3c6b; color:#fff; border-radius:4px; padding:4px 10px; font-size:11px; cursor:pointer; transition:background .15s; white-space:nowrap; }
    .gas-link-share .btn-copy:hover { background:#2e75b6; }
    .gas-link-share .btn-copy.copied { background:#198754; }

    .form-control, .form-select { font-size:13px; border-radius:5px; }
    .form-label { font-size:12px; font-weight:600; color:#495057; margin-bottom:4px; }
    .required { color:#dc3545; }

    @media (max-width: 480px) {
      .gas-pub-header, .gas-pub-body, .gas-pub-footer { padding-left:18px; padding-right:18px; }
      .gas-rating label { width:36px; height:36px; font-size:13px; }
    }
  </style>
</head>
<body>
<div class="gas-pub-wrapper">
  <div class="gas-pub-card">

    <!-- HEADER -->
    <div class="gas-pub-header">
      <div class="gas-tag"><i class="fas fa-star me-1"></i>Encuesta de Satisfacción</div>
      <h5><?= htmlspecialchars($sesion['gas_nombre']) ?></h5>
      <div class="gas-meta">
        <span><i class="fas fa-user-tie"></i> <?= htmlspecialchars($sesion['gas_facilitador']) ?></span>
        <span><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($sesion['gas_fecha_inicio'])) ?></span>
        <span><i class="fas fa-check-circle" style="color:#6de089;"></i> Sesión Finalizada</span>
      </div>
    </div>

    <!-- BODY -->
    <div class="gas-pub-body">

      <!-- Link para compartir -->
      <div class="gas-link-share">
        <i class="fas fa-link" style="color:#1a3c6b; font-size:12px; flex-shrink:0;"></i>
        <input type="text" id="gas_link_enc" value="<?= htmlspecialchars($link_publico) ?>" readonly>
        <button class="btn-copy" id="btn_copiar_enc" onclick="copiarLink()">
          <i class="fas fa-copy me-1"></i>Copiar Link
        </button>
      </div>

      <!-- Alertas -->
      <?php if (!empty($errores_form)): ?>
        <div class="alert alert-danger py-2 mb-3" style="font-size:12px;" id="gas-error-top">
          <strong><i class="fas fa-exclamation-triangle me-1"></i>Corrige los siguientes errores:</strong>
          <ul class="mb-0 mt-1 ps-3">
            <?php foreach ($errores_form as $e): ?>
              <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <p style="font-size:12px; color:#666; margin-bottom:18px;">
        Tu opinión es muy importante. Ingresa tu número de documento para verificar tu asistencia
        y califica la sesión en una escala de <strong>1 (Deficiente)</strong> a <strong>5 (Excelente)</strong>.
      </p>

      <form method="POST" action="encuesta_procesar.php" novalidate id="form_encuesta">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <!-- Verificación de identidad -->
        <div class="gas-id-box">
          <div class="gas-id-title"><i class="fas fa-id-card me-1"></i>Verificación de Identidad</div>
          <div class="row g-2">
            <div class="col-5">
              <label class="form-label">Tipo de Documento <span class="required">*</span></label>
              <select name="tipo_documento" class="form-select form-select-sm" required>
                <option value="">Selecciona...</option>
                <option value="CC">Cédula de Ciudadanía</option>
                <option value="CE">Cédula de Extranjería</option>
                <option value="TI">Tarjeta de Identidad</option>
                <option value="PP">Pasaporte</option>
                <option value="NIT">NIT</option>
                <option value="OTRO">Otro</option>
              </select>
            </div>
            <div class="col-7">
              <label class="form-label">Número de Documento <span class="required">*</span></label>
              <input type="text" name="numero_documento" class="form-control form-control-sm"
                     maxlength="20" required
                     placeholder="El mismo con el que registraste asistencia">
            </div>
          </div>
        </div>

        <!-- Preguntas de calificación -->
        <div class="gas-section-label"><i class="fas fa-star-half-alt me-1"></i>Calificación de la Sesión</div>

        <?php $num = 1; foreach ($preguntas as $key => $texto): ?>
          <div class="gas-pregunta" id="preg_<?= $key ?>">
            <div class="gas-pregunta-num">Pregunta <?= $num ?> de <?= count($preguntas) ?></div>
            <div class="gas-pregunta-texto"><?= htmlspecialchars($texto) ?> <span class="required">*</span></div>
            <div class="gas-rating" id="rating_<?= $key ?>">
              <?php for ($v = 1; $v <= 5; $v++): ?>
                <input type="radio" name="cal_<?= $key ?>"
                       id="r_<?= $key ?>_<?= $v ?>" value="<?= $v ?>" required>
                <label for="r_<?= $key ?>_<?= $v ?>"
                       title="<?= $escala[$v] ?>"><?= $v ?></label>
              <?php endfor; ?>
              <span class="gas-rating-desc" id="desc_<?= $key ?>"></span>
            </div>
          </div>
        <?php $num++; endforeach; ?>

        <!-- Observaciones -->
        <div class="gas-section-label mt-3"><i class="fas fa-comment-dots me-1"></i>Comentarios Adicionales</div>
        <div class="gas-obs-box">
          <textarea name="observacion" class="form-control" rows="3"
                    maxlength="1000" id="obs_textarea"
                    placeholder="Escribe aquí tus comentarios, sugerencias o felicitaciones (opcional)..."></textarea>
          <div class="gas-obs-counter" id="obs_counter">0 / 1000 caracteres</div>
        </div>

        <button type="submit" class="btn-gas-primary" id="btn_enviar">
          <i class="fas fa-paper-plane me-2"></i>Enviar Encuesta de Satisfacción
        </button>
      </form>
    </div>

    <div class="gas-pub-footer">
      <i class="fas fa-shield-alt me-1"></i>
      Tu información es tratada de forma confidencial según las normas de protección de datos.
    </div>
  </div>

  <div class="mt-3 text-center">
    <img src="<?= LOGO_CLIENTE ?>" alt="logo" style="height:28px; opacity:.6;">
  </div>
</div>

<script>
// Descriptores de calificación
const desc = {1:'Deficiente', 2:'Regular', 3:'Aceptable', 4:'Bueno', 5:'Excelente'};

// Actualizar descriptor al seleccionar
document.querySelectorAll('.gas-rating input[type=radio]').forEach(r => {
  r.addEventListener('change', function() {
    const key = this.name.replace('cal_', '');
    const d   = document.getElementById('desc_' + key);
    if (d) d.textContent = desc[this.value] || '';
    // Limpiar error visual de la tarjeta
    const card = document.getElementById('preg_' + key);
    if (card) card.classList.remove('has-error');
  });
});

// Contador de observaciones
const obs     = document.getElementById('obs_textarea');
const counter = document.getElementById('obs_counter');
if (obs && counter) {
  obs.addEventListener('input', () => {
    counter.textContent = obs.value.length + ' / 1000 caracteres';
    counter.style.color = obs.value.length >= 900 ? '#fd7e14' : '#adb5bd';
  });
}

// Validación de calificaciones antes de enviar
document.getElementById('form_encuesta').addEventListener('submit', function(e) {
  const keys = ['tema','facilitador','metodologia','material','general'];
  let faltantes = 0;

  keys.forEach(k => {
    const sel  = document.querySelector('input[name="cal_'+k+'"]:checked');
    const card = document.getElementById('preg_' + k);
    if (!sel) {
      faltantes++;
      if (card) card.classList.add('has-error');
    }
  });

  if (faltantes > 0) {
    e.preventDefault();
    // Scroll a primera tarjeta con error
    const primera = document.querySelector('.gas-pregunta.has-error');
    if (primera) primera.scrollIntoView({ behavior:'smooth', block:'center' });
    return;
  }

  // Spinner en el botón
  const btn = document.getElementById('btn_enviar');
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
  btn.disabled = true;
});

// Copiar link
function copiarLink() {
  const input = document.getElementById('gas_link_enc');
  const btn   = document.getElementById('btn_copiar_enc');
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
