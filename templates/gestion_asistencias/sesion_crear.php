<?php
/**
 * templates/gestion_asistencias/sesion_crear.php
 * Patrón DPS: $respuesta_accion + alertButton() + SweetAlert 1
 */

$modulo_plataforma = "Gestión Asistencias";
require_once("../../iniciador.php");
require_once(__DIR__ . '/includes/gas_funciones.php');
require_once(__DIR__ . '/includes/gas_permisos.php');

$url_fichero      = pathinfo(__FILE__, PATHINFO_FILENAME);
$title            = "Gestión Asistencias";
$subtitle         = "Nueva Sesión";
$respuesta_accion = "";   // ← patrón DPS

$errores = [];
$datos   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['nombre']       = gas_sanitizar($_POST['nombre']       ?? '', 200);
    $datos['descripcion']  = gas_sanitizar($_POST['descripcion']  ?? '', 1000);
    $datos['tipo_sesion']  = gas_sanitizar($_POST['tipo_sesion']  ?? '', 100);
    $datos['facilitador']  = gas_sanitizar($_POST['facilitador']  ?? '', 200);
    $datos['fecha_inicio'] = gas_sanitizar($_POST['fecha_inicio'] ?? '', 20);
    $datos['fecha_fin']    = gas_sanitizar($_POST['fecha_fin']    ?? '', 20);

    $tipos_validos = array_keys(gas_tipos_sesion());

    if (empty($datos['nombre']))
        $errores[] = 'El nombre de la sesión es requerido.';
    if (empty($datos['tipo_sesion']) || !in_array($datos['tipo_sesion'], $tipos_validos))
        $errores[] = 'Selecciona un tipo de sesión válido.';
    if (empty($datos['facilitador']))
        $errores[] = 'El nombre del facilitador es requerido.';
    if (empty($datos['fecha_inicio']) || !strtotime($datos['fecha_inicio']))
        $errores[] = 'La fecha de inicio es requerida y debe ser válida.';
    if (!empty($datos['fecha_fin']) && strtotime($datos['fecha_fin']) <= strtotime($datos['fecha_inicio']))
        $errores[] = 'La fecha de fin debe ser posterior a la fecha de inicio.';

    if (empty($errores)) {
        $token         = gas_generar_token();
        $codigo        = gas_generar_codigo($enlace_db);
        $link          = gas_construir_link($token);
        $qr_path       = gas_generar_qr($link, $token);
        $usuario       = GAS_USUARIO_SESION;
        $ahora         = date('Y-m-d H:i:s');
        $fecha_fin_val = !empty($datos['fecha_fin']) ? $datos['fecha_fin'] : null;

        $stmt = $enlace_db->prepare(
            "INSERT INTO gestion_asistencias_sesiones
             (gas_codigo, gas_nombre, gas_descripcion, gas_tipo_sesion,
              gas_facilitador, gas_fecha_inicio, gas_fecha_fin,
              gas_estado, gas_token_publico, gas_link_publico, gas_qr_path,
              gas_registro_usuario, gas_registro_fecha)
             VALUES (?,?,?,?,?,?,?,'borrador',?,?,?,?,?)"
        );
        $stmt->bind_param(
            'ssssssssssss', // 12: codigo,nombre,desc,tipo,facil,f_ini,f_fin,token,link,qr,usuario,ahora
            $codigo,
            $datos['nombre'],
            $datos['descripcion'],
            $datos['tipo_sesion'],
            $datos['facilitador'],
            $datos['fecha_inicio'],
            $fecha_fin_val,
            $token,
            $link,
            $qr_path,
            $usuario,
            $ahora
        );

        if ($stmt->execute()) {
            $nuevo_id = $stmt->insert_id;
            $stmt->close();
            registro_log($enlace_db, 'Gestión Asistencias', 'crear', 'Sesión creada: ' . $codigo);

            // ── Patrón DPS: SweetAlert success con redirección al .then() ──
            $url_detalle = "sesion_detalle.php?id=" . $nuevo_id . "&nuevo=1";
            $respuesta_accion = "alertButton('success','Sesión creada','La sesión "
                . addslashes($codigo) . " fue creada exitosamente. Actívala para compartir el link.','"
                . $url_detalle . "');";

            // Limpiar campos para que el form quede vacío (igual que DPS)
            $datos = [];

        } else {
            $respuesta_accion = "alertButton('error','Error','No se pudo guardar la sesión. Intenta de nuevo.');";
            $stmt->close();
        }
    } else {
        // Errores de validación → SweetAlert warning con lista de errores
        $lista_errores = implode(' | ', $errores);
        $respuesta_accion = "alertButton('error','Campos requeridos','" . addslashes($lista_errores) . "');";
    }
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

          <!-- SweetAlert — patrón exacto DPS -->
          <?php if (!empty($respuesta_accion)) {
              echo "<script type='text/javascript'>" . $respuesta_accion . "</script>";
          } ?>

          <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
              <div>
                <h4 class="mb-0">
                  <i class="fas fa-plus-circle me-2 text-primary"></i>Nueva Sesión Virtual
                </h4>
                <small class="text-muted">Gestión Asistencias y Satisfacción</small>
              </div>
              <a href="index.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
              </a>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <form method="POST" novalidate>
                <div class="row g-3">

                  <div class="col-md-8">
                    <label class="form-label">
                      Nombre de la Sesión <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nombre" class="form-control"
                           value="<?= htmlspecialchars($datos['nombre'] ?? '') ?>"
                           maxlength="200" required
                           placeholder="Ej: Sesión de Inducción — Junio 2026">
                  </div>

                  <div class="col-md-4">
                    <label class="form-label">
                      Tipo de Sesión <span class="text-danger">*</span>
                    </label>
                    <select name="tipo_sesion" class="form-select" required>
                      <option value="">-- Selecciona --</option>
                      <?php foreach ($tipos_sesion as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>"
                                <?= ($datos['tipo_sesion'] ?? '') === $key ? 'selected' : '' ?>>
                          <?= htmlspecialchars($label) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">
                      Facilitador <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="facilitador" class="form-control"
                           value="<?= htmlspecialchars($datos['facilitador'] ?? '') ?>"
                           maxlength="200" required
                           placeholder="Nombre completo del facilitador">
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">
                      Fecha y Hora de Inicio <span class="text-danger">*</span>
                    </label>
                    <input type="datetime-local" name="fecha_inicio" class="form-control"
                           value="<?= htmlspecialchars($datos['fecha_inicio'] ?? '') ?>"
                           required>
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
                              maxlength="1000"
                              placeholder="Descripción opcional..."><?= htmlspecialchars($datos['descripcion'] ?? '') ?></textarea>
                  </div>

                </div><!-- /row -->

                <hr class="my-3">

                <div class="d-flex justify-content-end gap-2">
                  <button type="button" class="btn btn-outline-secondary"
                          onclick="alertButton('cancel','','','index.php')">
                    <i class="fas fa-times me-1"></i> Cancelar
                  </button>
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Crear Sesión
                  </button>
                </div>
              </form>
            </div>
          </div>

        </div><!-- /content-wrapper -->
        <?php require_once(ROOT.'includes/_footer.php'); ?>
      </div><!-- /main-panel -->
    </div>
  </div>
</body>
</html>