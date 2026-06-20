<?php
/**
 * templates/gestion_asistencias/sesion_editar.php — CORREGIDO para DPS
 */

$modulo_plataforma = "Gestión Asistencias";
require_once("../../iniciador.php");
require_once(__DIR__ . '/includes/gas_funciones.php');
require_once(__DIR__ . '/includes/gas_permisos.php');

$url_fichero = pathinfo(__FILE__, PATHINFO_FILENAME);
$title    = "Gestión Asistencias";
$subtitle = "Editar Sesión";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $enlace_db->prepare(
    'SELECT * FROM gestion_asistencias_sesiones WHERE gas_id = ? LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$sesion = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sesion || !in_array($sesion['gas_estado'], ['borrador', 'activa'])) {
    header('Location: sesion_detalle.php?id=' . $id . '&error=no_editable');
    exit;
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre       = gas_sanitizar($_POST['nombre']       ?? '', 200);
    $descripcion  = gas_sanitizar($_POST['descripcion']  ?? '', 1000);
    $tipo_sesion  = gas_sanitizar($_POST['tipo_sesion']  ?? '', 100);
    $facilitador  = gas_sanitizar($_POST['facilitador']  ?? '', 200);
    $fecha_inicio = gas_sanitizar($_POST['fecha_inicio'] ?? '', 20);
    $fecha_fin    = gas_sanitizar($_POST['fecha_fin']    ?? '', 20);

    if (empty($nombre))      $errores[] = 'El nombre es requerido.';
    if (empty($facilitador)) $errores[] = 'El facilitador es requerido.';
    if (empty($fecha_inicio)) $errores[] = 'La fecha de inicio es requerida.';

    if (empty($errores)) {
        $usuario       = GAS_USUARIO_SESION;
        $ahora         = date('Y-m-d H:i:s');
        $fecha_fin_val = !empty($fecha_fin) ? $fecha_fin : null;

        $stmt = $enlace_db->prepare(
            "UPDATE gestion_asistencias_sesiones
             SET gas_nombre = ?, gas_descripcion = ?, gas_tipo_sesion = ?,
                 gas_facilitador = ?, gas_fecha_inicio = ?, gas_fecha_fin = ?,
                 gas_modificacion_usuario = ?, gas_modificacion_fecha = ?
             WHERE gas_id = ?"
        );
        $stmt->bind_param(
            'ssssssssi',
            $nombre, $descripcion, $tipo_sesion,
            $facilitador, $fecha_inicio, $fecha_fin_val,
            $usuario, $ahora, $id
        );

        if ($stmt->execute()) {
            $stmt->close();
            registro_log($enlace_db, 'Gestión Asistencias', 'editar', 'Sesión editada: ' . $id);
            header('Location: sesion_detalle.php?id=' . $id . '&ok=editada');
            exit;
        } else {
            $errores[] = 'Error al actualizar. Intenta de nuevo.';
            $stmt->close();
        }
    }

    // Reflejar valores del POST en la vista
    $sesion['gas_nombre']       = $nombre;
    $sesion['gas_descripcion']  = $descripcion;
    $sesion['gas_tipo_sesion']  = $tipo_sesion;
    $sesion['gas_facilitador']  = $facilitador;
    $sesion['gas_fecha_inicio'] = $fecha_inicio;
    $sesion['gas_fecha_fin']    = $fecha_fin;
}

$tipos_sesion = gas_tipos_sesion();
?>
<!DOCTYPE html>
<html lang="<?php echo LANG; ?>">
<head>
  <?php require_once(ROOT.'includes/_head.php'); ?>
</head>
<body class="sidebar-dark sidebar-icon-only">
  <div class="container-scroller">
    <?php require_once(ROOT.'includes/_navbar.php'); ?>
    <div class="container-fluid page-body-wrapper">
      <?php require_once(ROOT.'includes/_sidebar.php'); ?>
      <div class="main-panel">
        <div class="content-wrapper">

          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h4 class="mb-0"><i class="fas fa-edit me-2 text-primary"></i>Editar Sesión</h4>
              <small class="text-muted"><?= htmlspecialchars($sesion['gas_codigo']) ?></small>
            </div>
            <a href="sesion_detalle.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
          </div>

          <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($errores as $e): ?>
                  <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <div class="card">
            <div class="card-body">
              <form method="POST">
                <div class="row g-3">
                  <div class="col-md-8">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control"
                           value="<?= htmlspecialchars($sesion['gas_nombre']) ?>"
                           maxlength="200" required>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Tipo <span class="text-danger">*</span></label>
                    <select name="tipo_sesion" class="form-select" required>
                      <?php foreach ($tipos_sesion as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>"
                                <?= $sesion['gas_tipo_sesion'] === $key ? 'selected' : '' ?>>
                          <?= htmlspecialchars($label) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Facilitador <span class="text-danger">*</span></label>
                    <input type="text" name="facilitador" class="form-control"
                           value="<?= htmlspecialchars($sesion['gas_facilitador']) ?>"
                           maxlength="200" required>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Fecha Inicio <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="fecha_inicio" class="form-control"
                           value="<?= htmlspecialchars(str_replace(' ', 'T', $sesion['gas_fecha_inicio'])) ?>"
                           required>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Fecha Fin</label>
                    <input type="datetime-local" name="fecha_fin" class="form-control"
                           value="<?= htmlspecialchars(str_replace(' ', 'T', $sesion['gas_fecha_fin'] ?? '')) ?>">
                  </div>
                  <div class="col-12">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="3"
                              maxlength="1000"><?= htmlspecialchars($sesion['gas_descripcion'] ?? '') ?></textarea>
                  </div>
                </div>
                <hr>
                <div class="d-flex justify-content-end gap-2">
                  <a href="sesion_detalle.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancelar</a>
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar Cambios
                  </button>
                </div>
              </form>
            </div>
          </div>

        </div><!-- /content-wrapper -->
        <?php require_once(ROOT.'includes/_footer.php'); ?>
      </div>
    </div>
  </div>
</body>
</html>
