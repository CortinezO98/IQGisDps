<?php
/**
 * templates/gas_publico/asistencia_form.php
 * Formulario público de registro de asistencia.
 * Se incluye desde index.php. La variable $sesion y $token ya están disponibles.
 * La variable $errores_form puede venir de asistencia_procesar.php si redirige de vuelta.
 */

$errores_form = $_SESSION['gas_form_errores'] ?? [];
unset($_SESSION['gas_form_errores']);

$tipos_doc = ['CC' => 'Cédula de Ciudadanía', 'CE' => 'Cédula de Extranjería',
              'TI' => 'Tarjeta de Identidad', 'PP' => 'Pasaporte',
              'NIT' => 'NIT', 'OTRO' => 'Otro'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro de Asistencia — <?= htmlspecialchars($sesion['gas_nombre']) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; }
    .gas-card { max-width: 680px; margin: 40px auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.12); }
    .gas-header { background: linear-gradient(135deg, #1a3c6b, #2e75b6); color: white; padding: 28px 32px; }
    .gas-header h5 { font-size: 1.3rem; margin-bottom: 6px; }
    .gas-header .meta { opacity: .8; font-size: .9rem; }
    .gas-body { background: white; padding: 32px; }
    .gas-footer { background: #f8f9fa; padding: 12px 32px; font-size: .8rem; color: #888; text-align: center; }
    .required { color: #dc3545; }
    .star-badge { display: inline-block; background: rgba(255,255,255,.2); border-radius: 20px; padding: 3px 12px; font-size: .8rem; margin-top: 6px; }
  </style>
</head>
<body>

<div class="gas-card">
  <div class="gas-header">
    <div class="star-badge">📋 Registro de Asistencia</div>
    <h5 class="mt-2"><?= htmlspecialchars($sesion['gas_nombre']) ?></h5>
    <div class="meta">
      🎓 Facilitador: <strong><?= htmlspecialchars($sesion['gas_facilitador']) ?></strong>
      &nbsp;·&nbsp;
      📅 <?= date('d/m/Y H:i', strtotime($sesion['gas_fecha_inicio'])) ?>
    </div>
  </div>

  <div class="gas-body">
    <p class="text-muted mb-4 small">
      <i class="bi bi-info-circle"></i>
      Diligencia el formulario para registrar tu asistencia a esta sesión.
      Todos los campos marcados con <span class="required">*</span> son obligatorios.
    </p>

    <?php if (!empty($errores_form)): ?>
      <div class="alert alert-danger mb-3">
        <strong>Por favor corrige los siguientes errores:</strong>
        <ul class="mb-0 mt-1">
          <?php foreach ($errores_form as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="asistencia_procesar.php" novalidate>
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Tipo de Documento <span class="required">*</span></label>
          <select name="tipo_documento" class="form-select" required>
            <option value="">Selecciona...</option>
            <?php foreach ($tipos_doc as $val => $lbl): ?>
              <option value="<?= $val ?>"><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-8">
          <label class="form-label">Número de Documento <span class="required">*</span></label>
          <input type="text" name="numero_documento" class="form-control"
                 placeholder="Sin puntos ni espacios" maxlength="20"
                 pattern="[0-9A-Za-z\-]{4,20}" required>
          <div class="form-text">Solo números o letras, sin puntos ni espacios.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Nombres <span class="required">*</span></label>
          <input type="text" name="nombres" class="form-control"
                 maxlength="150" required placeholder="Ej: María Camila">
        </div>

        <div class="col-md-6">
          <label class="form-label">Apellidos <span class="required">*</span></label>
          <input type="text" name="apellidos" class="form-control"
                 maxlength="150" required placeholder="Ej: González Ruiz">
        </div>

        <div class="col-md-7">
          <label class="form-label">Correo Electrónico <span class="required">*</span></label>
          <input type="email" name="correo" class="form-control"
                 maxlength="200" required placeholder="correo@ejemplo.com">
        </div>

        <div class="col-md-5">
          <label class="form-label">Celular</label>
          <input type="tel" name="celular" class="form-control"
                 maxlength="20" placeholder="Ej: 3001234567">
        </div>

        <div class="col-md-8">
          <label class="form-label">Entidad / Organización</label>
          <input type="text" name="entidad" class="form-control"
                 maxlength="200" placeholder="Nombre de tu entidad u organización">
        </div>

        <div class="col-md-4">
          <label class="form-label">Cargo</label>
          <input type="text" name="cargo" class="form-control"
                 maxlength="100" placeholder="Tu cargo o rol">
        </div>
      </div>

      <!-- Honeypot anti-bot -->
      <div style="display:none;">
        <input type="text" name="website" tabindex="-1" autocomplete="off">
      </div>

      <hr class="my-4">

      <div class="d-grid">
        <button type="submit" class="btn btn-primary btn-lg">
          ✅ Registrar mi Asistencia
        </button>
      </div>
    </form>
  </div>

  <div class="gas-footer">
    Este registro es confidencial y se usa únicamente para fines estadísticos y de seguimiento.
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
