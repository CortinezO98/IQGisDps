<?php
/**
 * templates/gestion_asistencias/sesion_crear.php
 * Formulario para crear una nueva sesión virtual.
 */

require_once __DIR__ . '/../../iniciador.php';
require_once __DIR__ . '/includes/gas_permisos.php';
require_once __DIR__ . '/includes/gas_funciones.php';

$errores  = [];
$exito    = false;
$datos    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar y recoger campos
    $datos['nombre']       = gas_sanitizar($_POST['nombre']       ?? '', 200);
    $datos['descripcion']  = gas_sanitizar($_POST['descripcion']  ?? '', 1000);
    $datos['tipo_sesion']  = gas_sanitizar($_POST['tipo_sesion']  ?? '', 100);
    $datos['facilitador']  = gas_sanitizar($_POST['facilitador']  ?? '', 200);
    $datos['fecha_inicio'] = gas_sanitizar($_POST['fecha_inicio'] ?? '', 20);
    $datos['fecha_fin']    = gas_sanitizar($_POST['fecha_fin']    ?? '', 20);

    // Validaciones
    if (empty($datos['nombre']))
        $errores[] = 'El nombre de la sesión es requerido.';
    if (empty($datos['tipo_sesion']) || !array_key_exists($datos['tipo_sesion'], GAS_TIPOS_SESION))
        $errores[] = 'Selecciona un tipo de sesión válido.';
    if (empty($datos['facilitador']))
        $errores[] = 'El nombre del facilitador es requerido.';
    if (empty($datos['fecha_inicio']) || !strtotime($datos['fecha_inicio']))
        $errores[] = 'La fecha de inicio es requerida y debe ser válida.';
    if (!empty($datos['fecha_fin']) && strtotime($datos['fecha_fin']) <= strtotime($datos['fecha_inicio']))
        $errores[] = 'La fecha de fin debe ser posterior a la fecha de inicio.';

    if (empty($errores)) {
        $token   = gas_generar_token();
        $codigo  = gas_generar_codigo($conn);
        $link    = gas_construir_link($token);
        $qr_path = gas_generar_qr($link, $token);
        $usuario = GAS_USUARIO_SESION;
        $ahora   = date('Y-m-d H:i:s');
        $fecha_fin_val = !empty($datos['fecha_fin']) ? $datos['fecha_fin'] : null;

        $stmt = $conn->prepare(
            'INSERT INTO gestion_asistencias_sesiones
             (gas_codigo, gas_nombre, gas_descripcion, gas_tipo_sesion,
              gas_facilitador, gas_fecha_inicio, gas_fecha_fin,
              gas_estado, gas_token_publico, gas_link_publico, gas_qr_path,
              gas_registro_usuario, gas_registro_fecha)
             VALUES (?,?,?,?,?,?,?,\'borrador\',?,?,?,?,?)'
        );
        $stmt->bind_param(
            'sssssssssss',
            $codigo, $datos['nombre'], $datos['descripcion'], $datos['tipo_sesion'],
            $datos['facilitador'], $datos['fecha_inicio'], $fecha_fin_val,
            $token, $link, $qr_path,
            $usuario, $ahora
        );

        if ($stmt->execute()) {
            $nuevo_id = $stmt->insert_id;
            $stmt->close();
            // Redirigir al detalle de la sesión recién creada
            header('Location: sesion_detalle.php?id=' . $nuevo_id . '&nuevo=1');
            exit;
        } else {
            $errores[] = 'Error al guardar la sesión. Por favor intenta de nuevo.';
            $stmt->close();
        }
    }
}

include __DIR__ . '/../../includes/_head.php';
include __DIR__ . '/../../includes/_navbar.php';
include __DIR__ . '/../../includes/_sidebar.php';
?>

<div class="main-content">
  <div class="page-content">
    <div class="container-fluid">

      <div class="row mb-3">
        <div class="col-12">
          <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0"><i class="fas fa-plus-circle me-2 text-primary"></i>Nueva Sesión Virtual</h4>
            <a href="index.php" class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
          </div>
        </div>
      </div>

      <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
          <strong><i class="fas fa-exclamation-triangle me-1"></i> Por favor corrige los siguientes errores:</strong>
          <ul class="mb-0 mt-2">
            <?php foreach ($errores as $e): ?>
              <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header bg-primary text-white">
          <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Datos de la Sesión</h6>
        </div>
        <div class="card-body">
          <form method="POST" novalidate>
            <div class="row g-3">

              <div class="col-md-8">
                <label class="form-label">Nombre de la Sesión <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control"
                       value="<?= htmlspecialchars($datos['nombre'] ?? '') ?>"
                       maxlength="200" required placeholder="Ej: Sesión de Inducción - Junio 2026">
              </div>

              <div class="col-md-4">
                <label class="form-label">Tipo de Sesión <span class="text-danger">*</span></label>
                <select name="tipo_sesion" class="form-select" required>
                  <option value="">-- Selecciona --</option>
                  <?php foreach (GAS_TIPOS_SESION as $key => $label): ?>
                    <option value="<?= $key ?>"
                      <?= ($datos['tipo_sesion'] ?? '') === $key ? 'selected' : '' ?>>
                      <?= htmlspecialchars($label) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">Facilitador <span class="text-danger">*</span></label>
                <input type="text" name="facilitador" class="form-control"
                       value="<?= htmlspecialchars($datos['facilitador'] ?? '') ?>"
                       maxlength="200" required placeholder="Nombre completo del facilitador">
              </div>

              <div class="col-md-3">
                <label class="form-label">Fecha y Hora de Inicio <span class="text-danger">*</span></label>
                <input type="datetime-local" name="fecha_inicio" class="form-control"
                       value="<?= htmlspecialchars($datos['fecha_inicio'] ?? '') ?>" required>
              </div>

              <div class="col-md-3">
                <label class="form-label">Fecha y Hora de Fin</label>
                <input type="datetime-local" name="fecha_fin" class="form-control"
                       value="<?= htmlspecialchars($datos['fecha_fin'] ?? '') ?>">
                <div class="form-text">Opcional. Puedes ajustarla después.</div>
              </div>

              <div class="col-12">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3"
                          maxlength="1000" placeholder="Descripción opcional de la sesión..."><?= htmlspecialchars($datos['descripcion'] ?? '') ?></textarea>
              </div>

            </div><!-- /row -->

            <hr class="my-3">

            <div class="d-flex justify-content-end gap-2">
              <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-times me-1"></i> Cancelar
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Crear Sesión
              </button>
            </div>
          </form>
        </div>
      </div>

    </div><!-- /container -->
  </div>
</div>

<?php include __DIR__ . '/../../includes/_footer.php'; ?>
