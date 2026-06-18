<?php
/**
 * templates/gestion_asistencias/encuestas_lista.php
 * Listado de encuestas respondidas de una sesión.
 */

require_once __DIR__ . '/../../iniciador.php';
require_once __DIR__ . '/includes/gas_permisos.php';
require_once __DIR__ . '/includes/gas_funciones.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $conn->prepare('SELECT gas_codigo, gas_nombre FROM gestion_asistencias_sesiones WHERE gas_id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$sesion = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$sesion) { header('Location: index.php'); exit; }

$stmt = $conn->prepare(
    'SELECT e.*,
            r.gar_nombres, r.gar_apellidos, r.gar_numero_documento, r.gar_entidad
     FROM gestion_asistencias_encuestas e
     INNER JOIN gestion_asistencias_registros r ON r.gar_id = e.gae_asistencia_id
     WHERE e.gae_sesion_id = ? AND e.gae_estado = \'activo\'
     ORDER BY e.gae_fecha_respuesta ASC'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$encuestas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/../../includes/_head.php';
include __DIR__ . '/../../includes/_navbar.php';
include __DIR__ . '/../../includes/_sidebar.php';
?>

<div class="main-content">
  <div class="page-content">
    <div class="container-fluid">
      <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
          <div>
            <h4><i class="fas fa-star me-2 text-warning"></i>Encuestas de Satisfacción</h4>
            <small class="text-muted"><code><?= htmlspecialchars($sesion['gas_codigo']) ?></code> — <?= htmlspecialchars($sesion['gas_nombre']) ?></small>
          </div>
          <div class="d-flex gap-2">
            <a href="exportar_csv.php?id=<?= $id ?>&tipo=encuestas" class="btn btn-sm btn-outline-success">
              <i class="fas fa-file-csv me-1"></i> Exportar CSV
            </a>
            <a href="sesion_detalle.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <span class="badge bg-warning text-dark"><?= count($encuestas) ?> encuesta(s) respondida(s)</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-dark">
                <tr>
                  <th>#</th>
                  <th>Asistente</th>
                  <th>Documento</th>
                  <th class="text-center" title="Dominio del Tema">Tema</th>
                  <th class="text-center" title="Claridad Facilitador">Facilit.</th>
                  <th class="text-center" title="Metodología">Metod.</th>
                  <th class="text-center" title="Material">Mat.</th>
                  <th class="text-center" title="General">Gral.</th>
                  <th class="text-center">Promedio</th>
                  <th>Observaciones</th>
                  <th>Fecha</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($encuestas)): ?>
                  <tr><td colspan="11" class="text-center py-4 text-muted">No hay encuestas respondidas.</td></tr>
                <?php else: ?>
                  <?php foreach ($encuestas as $i => $e): ?>
                    <tr>
                      <td><?= $i + 1 ?></td>
                      <td><?= htmlspecialchars($e['gar_nombres'] . ' ' . $e['gar_apellidos']) ?></td>
                      <td><?= htmlspecialchars($e['gar_numero_documento']) ?></td>
                      <td class="text-center"><?= (int)$e['gae_calificacion_tema'] ?></td>
                      <td class="text-center"><?= (int)$e['gae_calificacion_facilitador'] ?></td>
                      <td class="text-center"><?= (int)$e['gae_calificacion_metodologia'] ?></td>
                      <td class="text-center"><?= (int)$e['gae_calificacion_material'] ?></td>
                      <td class="text-center"><?= (int)$e['gae_calificacion_general'] ?></td>
                      <td class="text-center">
                        <?php $prom = (float)$e['gae_calificacion_promedio']; ?>
                        <span class="badge bg-<?= $prom >= 4 ? 'success' : ($prom >= 3 ? 'warning text-dark' : 'danger') ?>">
                          <?= number_format($prom, 1) ?>
                        </span>
                      </td>
                      <td>
                        <?php if (!empty($e['gae_observacion'])): ?>
                          <small class="text-muted" title="<?= htmlspecialchars($e['gae_observacion']) ?>">
                            <?= htmlspecialchars(mb_substr($e['gae_observacion'], 0, 60)) ?>...
                          </small>
                        <?php else: ?>
                          <small class="text-muted">—</small>
                        <?php endif; ?>
                      </td>
                      <td><?= date('d/m/Y H:i', strtotime($e['gae_fecha_respuesta'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/_footer.php'; ?>
