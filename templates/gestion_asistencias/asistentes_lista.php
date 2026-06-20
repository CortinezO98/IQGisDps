<?php
/**
 * templates/gestion_asistencias/asistentes_lista.php — CORREGIDO para DPS
 */

$modulo_plataforma = "Gestión Asistencias";
require_once("../../iniciador.php");
require_once(__DIR__ . '/includes/gas_funciones.php');
require_once(__DIR__ . '/includes/gas_permisos.php');

$url_fichero = pathinfo(__FILE__, PATHINFO_FILENAME);
$title    = "Gestión Asistencias";
$subtitle = "Asistentes";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $enlace_db->prepare(
    'SELECT gas_codigo, gas_nombre, gas_estado
     FROM gestion_asistencias_sesiones WHERE gas_id = ? LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$sesion = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$sesion) { header('Location: index.php'); exit; }

$stmt = $enlace_db->prepare(
    "SELECT r.*,
            CASE WHEN e.gae_id IS NOT NULL THEN 'Sí' ELSE 'No' END AS encuesta_respondida,
            e.gae_calificacion_promedio
     FROM gestion_asistencias_registros r
     LEFT JOIN gestion_asistencias_encuestas e ON e.gae_asistencia_id = r.gar_id
     WHERE r.gar_sesion_id = ? AND r.gar_estado = 'activo'
     ORDER BY r.gar_fecha_asistencia ASC"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$asistentes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
              <h4 class="mb-0"><i class="fas fa-users me-2 text-primary"></i>Asistentes</h4>
              <small class="text-muted">
                <code><?= htmlspecialchars($sesion['gas_codigo']) ?></code>
                — <?= htmlspecialchars($sesion['gas_nombre']) ?>
              </small>
            </div>
            <div class="d-flex gap-2">
              <a href="exportar_csv.php?id=<?= $id ?>&tipo=asistentes"
                 class="btn btn-sm btn-outline-success">
                <i class="fas fa-file-csv me-1"></i> Exportar CSV
              </a>
              <a href="sesion_detalle.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
              </a>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <span class="badge bg-primary"><?= count($asistentes) ?> asistente(s)</span>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover table-bordered table-striped font-size-11 mb-0">
                  <thead>
                    <tr>
                      <th class="px-1 py-2 text-white">#</th>
                      <th class="px-1 py-2 text-white">Tipo Doc.</th>
                      <th class="px-1 py-2 text-white">Documento</th>
                      <th class="px-1 py-2 text-white">Nombres</th>
                      <th class="px-1 py-2 text-white">Correo</th>
                      <th class="px-1 py-2 text-white">Celular</th>
                      <th class="px-1 py-2 text-white">Entidad</th>
                      <th class="px-1 py-2 text-white">Cargo</th>
                      <th class="px-1 py-2 text-white">Fecha Registro</th>
                      <th class="px-1 py-2 text-center text-white">Encuesta</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($asistentes)): ?>
                      <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                          No hay asistentes registrados.
                        </td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($asistentes as $i => $a): ?>
                        <tr>
                          <td class="p-1"><?= $i + 1 ?></td>
                          <td class="p-1"><?= htmlspecialchars($a['gar_tipo_documento']) ?></td>
                          <td class="p-1"><?= htmlspecialchars($a['gar_numero_documento']) ?></td>
                          <td class="p-1"><?= htmlspecialchars($a['gar_nombres'] . ' ' . $a['gar_apellidos']) ?></td>
                          <td class="p-1"><?= htmlspecialchars($a['gar_correo']) ?></td>
                          <td class="p-1"><?= htmlspecialchars($a['gar_celular'] ?? '—') ?></td>
                          <td class="p-1"><?= htmlspecialchars($a['gar_entidad'] ?? '—') ?></td>
                          <td class="p-1"><?= htmlspecialchars($a['gar_cargo'] ?? '—') ?></td>
                          <td class="p-1"><?= date('d/m/Y H:i', strtotime($a['gar_fecha_asistencia'])) ?></td>
                          <td class="p-1 text-center">
                            <?php if ($a['encuesta_respondida'] === 'Sí'): ?>
                              <span class="badge bg-success">
                                ✓ <?= number_format((float)$a['gae_calificacion_promedio'], 1) ?>
                              </span>
                            <?php else: ?>
                              <span class="badge bg-secondary">Pendiente</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div><!-- /content-wrapper -->
        <?php require_once(ROOT.'includes/_footer.php'); ?>
      </div>
    </div>
  </div>
</body>
</html>
