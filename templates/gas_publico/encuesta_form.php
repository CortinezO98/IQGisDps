<?php
/**
 * templates/gas_publico/encuesta_form.php
 * Formulario público de encuesta de satisfacción.
 * Se incluye desde index.php cuando gas_estado = 'finalizada'.
 * Variables disponibles: $sesion, $token
 */

if (session_status() === PHP_SESSION_NONE) session_start();

$errores_form = $_SESSION['gas_encuesta_errores'] ?? [];
unset($_SESSION['gas_encuesta_errores']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Encuesta de Satisfacción — <?= htmlspecialchars($sesion['gas_nombre']) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; }
    .gas-card { max-width: 680px; margin: 40px auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.12); }
    .gas-header { background: linear-gradient(135deg, #6f42c1, #0d6efd); color: white; padding: 28px 32px; }
    .gas-header h5 { font-size: 1.3rem; margin-bottom: 6px; }
    .gas-header .meta { opacity: .8; font-size: .9rem; }
    .gas-body { background: white; padding: 32px; }
    .gas-footer { background: #f8f9fa; padding: 12px 32px; font-size: .8rem; color: #888; text-align: center; }
    .star-badge { display: inline-block; background: rgba(255,255,255,.2); border-radius: 20px; padding: 3px 12px; font-size: .8rem; margin-top: 6px; }
    /* Calificación visual con botones */
    .rating-group { display: flex; gap: 8px; flex-wrap: wrap; }
    .rating-group input[type=radio] { display: none; }
    .rating-group label {
      width: 44px; height: 44px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-weight: bold; cursor: pointer;
      border: 2px solid #dee2e6; background: white;
      transition: all .2s; font-size: 1rem;
    }
    .rating-group input[type=radio]:checked + label {
      background: #0d6efd; color: white; border-color: #0d6efd;
    }
    .rating-group label:hover { background: #e8f0ff; border-color: #0d6efd; }
    .pregunta-card { border: 1px solid #e9ecef; border-radius: 8px; padding: 16px; margin-bottom: 12px; background: #fafbfc; }
    .pregunta-card .pregunta-num { color: #6f42c1; font-weight: bold; font-size: .85rem; }
    .required { color: #dc3545; }
  </style>
</head>
<body>

<div class="gas-card">
  <div class="gas-header">
    <div class="star-badge">⭐ Encuesta de Satisfacción</div>
    <h5 class="mt-2"><?= htmlspecialchars($sesion['gas_nombre']) ?></h5>
    <div class="meta">
      🎓 Facilitador: <strong><?= htmlspecialchars($sesion['gas_facilitador']) ?></strong>
    </div>
  </div>

  <div class="gas-body">
    <p class="text-muted mb-4 small">
      Tu opinión es muy importante para mejorar nuestras sesiones.
      Por favor ingresa tu número de documento para verificar tu asistencia
      y responde las preguntas. La escala es de <strong>1 (Deficiente)</strong>
      a <strong>5 (Excelente)</strong>.
    </p>

    <?php if (!empty($errores_form)): ?>
      <div class="alert alert-danger">
        <strong>Corrige los siguientes errores:</strong>
        <ul class="mb-0 mt-1">
          <?php foreach ($errores_form as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="encuesta_procesar.php" novalidate>
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

      <!-- Verificación de identidad -->
      <div class="bg-light border rounded p-3 mb-4">
        <h6 class="mb-3"><i class="bi bi-person-check"></i> Verificación de Identidad</h6>
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label form-label-sm">Tipo de Documento <span class="required">*</span></label>
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
          <div class="col-md-8">
            <label class="form-label form-label-sm">Número de Documento <span class="required">*</span></label>
            <input type="text" name="numero_documento" class="form-control form-control-sm"
                   maxlength="20" required placeholder="El mismo que usaste para registrar asistencia">
          </div>
        </div>
      </div>

      <!-- Preguntas de calificación -->
      <?php
      $preguntas = [
          'tema'          => '¿Cómo califica el dominio del tema por parte del facilitador?',
          'facilitador'   => '¿Cómo califica la claridad y calidad de la explicación?',
          'metodologia'   => '¿Cómo califica la metodología utilizada durante la sesión?',
          'material'      => '¿Cómo califica el material o contenido presentado?',
          'general'       => '¿Cuál es su calificación general de la sesión?',
      ];
      $labels = ['1' => '😞 Deficiente', '2' => '😐 Regular', '3' => '🙂 Aceptable', '4' => '😊 Bueno', '5' => '🤩 Excelente'];
      $num = 1;
      foreach ($preguntas as $key => $pregunta):
      ?>
        <div class="pregunta-card">
          <div class="pregunta-num">Pregunta <?= $num ?>/<?= count($preguntas) ?></div>
          <p class="fw-semibold mb-2"><?= htmlspecialchars($pregunta) ?> <span class="required">*</span></p>
          <div class="rating-group" id="rating_<?= $key ?>">
            <?php for ($v = 1; $v <= 5; $v++): ?>
              <input type="radio" name="cal_<?= $key ?>" id="r_<?= $key ?>_<?= $v ?>"
                     value="<?= $v ?>" required>
              <label for="r_<?= $key ?>_<?= $v ?>" title="<?= $labels[$v] ?>"><?= $v ?></label>
            <?php endfor; ?>
            <span class="ms-2 text-muted small align-self-center" id="lbl_<?= $key ?>"></span>
          </div>
        </div>
      <?php $num++; endforeach; ?>

      <!-- Observaciones -->
      <div class="pregunta-card">
        <p class="fw-semibold mb-2">¿Tiene alguna observación o comentario adicional?</p>
        <textarea name="observacion" class="form-control" rows="3" maxlength="1000"
                  placeholder="Escribe aquí tus comentarios (opcional)..."></textarea>
        <div class="form-text text-end" id="obs_counter">0/1000 caracteres</div>
      </div>

      <hr class="my-4">

      <div class="d-grid">
        <button type="submit" class="btn btn-primary btn-lg">
          ⭐ Enviar Encuesta de Satisfacción
        </button>
      </div>
    </form>
  </div>

  <div class="gas-footer">
    Tu información es tratada de forma confidencial según las normas de protección de datos.
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Mostrar label al seleccionar calificación
const labels = {1:'Deficiente',2:'Regular',3:'Aceptable',4:'Bueno',5:'Excelente'};
document.querySelectorAll('.rating-group input[type=radio]').forEach(radio => {
    radio.addEventListener('change', function() {
        const key = this.name.replace('cal_','');
        document.getElementById('lbl_' + key).textContent = labels[this.value] || '';
    });
});

// Contador de observaciones
const obs = document.querySelector('[name=observacion]');
const counter = document.getElementById('obs_counter');
if (obs && counter) {
    obs.addEventListener('input', () => {
        counter.textContent = obs.value.length + '/1000 caracteres';
    });
}
</script>
</body>
</html>
