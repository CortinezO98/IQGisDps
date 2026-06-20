<?php
/**
 * templates/gestion_asistencias/encuestas_lista.php — CORREGIDO para DPS
 */

$modulo_plataforma = "Gestión Asistencias";
require_once("../../iniciador.php");
require_once(__DIR__ . '/includes/gas_funciones.php');
require_once(__DIR__ . '/includes/gas_permisos.php');

$url_fichero = pathinfo(__FILE__, PATHINFO_FILENAME);
$title    = "Gestión Asistencias";
$subtitle = "Encuestas";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $enlace_db->prepare(
    'SELECT gas_codigo, gas_nombre FROM gestion_asistencias_sesiones WHERE gas_id = ? LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$sesion = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$sesion) { header('Location: index.php'); exit; }

$stmt = $enlace_db->prepare(
    "SELECT e.*, r.gar_numero_documento, r.gar_nombres, r.gar_apellidos, r.gar_entidad
     FROM gestion_asistencias_encuestas e
     INNER JOIN gestion_asistencias_registros r ON r.gar_id = e.gae_asistencia_id
     WHERE e.gae_sesion_id = ? AND e.gae_estado = 'activo'
     ORDER BY e.gae_fecha_respuesta ASC"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$encuestas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
              <h4 class="mb-0"><i class="fas fa-star me-2 text-warning"></i>Encuestas de Satisfacción</h4>
              <small class="text-muted">
                <code><?= htmlspecialchars($sesion['gas_codigo']) ?></code>
                — <?= htmlspecialchars($sesion['gas_nombre']) ?>
              </small>
            </div>
            <div class="d-flex gap-2">
              <a href="exportar_csv.php?id=<?= $id ?>&tipo=encuestas"
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
              <span class="badge bg-warning text-dark"><?= count($encuestas) ?> encuesta(s)</span>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover table-bordered table-striped font-size-11 mb-0">
                  <thead>
                    <tr>
                      <th class="px-1 py-2 text-white">#</th>
                      <th class="px-1 py-2 text-white">Documento</th>
                      <th class="px-1 py-2 text-white">Asistente</th>
                      <th class="px-1 py-2 text-center text-white" title="Dominio del Tema">Tema</th>
                      <th class="px-1 py-2 text-center text-white" title="Claridad Explicación">Facilit.</th>
                      <th class="px-1 py-2 text-center text-white" title="Metodología">Metod.</th>
                      <th class="px-1 py-2 text-center text-white" title="Material">Mat.</th>
                      <th class="px-1 py-2 text-center text-white" title="General">Gral.</th>
                      <th class="px-1 py-2 text-center text-white">Promedio</th>
                      <th class="px-1 py-2 text-white">Observaciones</th>
                      <th class="px-1 py-2 text-white">Fecha</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($encuestas)): ?>
                      <tr>
                        <td colspan="11" class="text-center py-4 text-muted">
                          No hay encuestas respondidas.
                        </td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($encuestas as $i => $e): ?>
                        <?php $prom = (float)$e['gae_calificacion_promedio']; ?>
                        <tr>
                          <td class="p-1"><?= $i + 1 ?></td>
                          <td class="p-1"><?= htmlspecialchars($e['gar_numero_documento']) ?></td>
                          <td class="p-1"><?= htmlspecialchars($e['gar_nombres'] . ' ' . $e['gar_apellidos']) ?></td>
                          <td class="p-1 text-center"><?= (int)$e['gae_calificacion_tema'] ?></td>
                          <td class="p-1 text-center"><?= (int)$e['gae_calificacion_facilitador'] ?></td>
                          <td class="p-1 text-center"><?= (int)$e['gae_calificacion_metodologia'] ?></td>
                          <td class="p-1 text-center"><?= (int)$e['gae_calificacion_material'] ?></td>
                          <td class="p-1 text-center"><?= (int)$e['gae_calificacion_general'] ?></td>
                          <td class="p-1 text-center">
                            <span class="badge bg-<?= $prom >= 4 ? 'success' : ($prom >= 3 ? 'warning text-dark' : 'danger') ?>">
                              <?= number_format($prom, 1) ?>
                            </span>
                          </td>
                          <td class="p-1">
                            <?php if (!empty($e['gae_observacion'])): ?>
                              <span title="<?= htmlspecialchars($e['gae_observacion']) ?>">
                                <?= htmlspecialchars(mb_substr($e['gae_observacion'], 0, 50)) ?>...
                              </span>
                            <?php else: ?>
                              <span class="text-muted">—</span>
                            <?php endif; ?>
                          </td>
                          <td class="p-1"><?= date('d/m/Y H:i', strtotime($e['gae_fecha_respuesta'])) ?></td>
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
