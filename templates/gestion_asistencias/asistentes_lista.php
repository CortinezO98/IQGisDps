<?php
/**
 * templates/gestion_asistencias/asistentes_lista.php
 * Listado completo de asistentes de una sesión con exportación.
 */

require_once __DIR__ . '/../../iniciador.php';
require_once __DIR__ . '/includes/gas_permisos.php';
require_once __DIR__ . '/includes/gas_funciones.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $conn->prepare(
    'SELECT gas_codigo, gas_nombre, gas_estado
     FROM gestion_asistencias_sesiones WHERE gas_id = ? LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$sesion = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$sesion) { header('Location: index.php'); exit; }

$stmt = $conn->prepare(
    'SELECT r.*,
            CASE WHEN e.gae_id IS NOT NULL THEN \'Sí\' ELSE \'No\' END AS encuesta_respondida,
            e.gae_calificacion_promedio
     FROM gestion_asistencias_registros r
     LEFT JOIN gestion_asistencias_encuestas e ON e.gae_asistencia_id = r.gar_id
     WHERE r.gar_sesion_id = ? AND r.gar_estado = \'activo\'
     ORDER BY r.gar_fecha_asistencia ASC'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$asistentes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
            <h4><i class="fas fa-users me-2 text-primary"></i>Asistentes</h4>
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
      </div>

      <div class="card">
        <div class="card-header">
          <span class="badge bg-primary"><?= count($asistentes) ?> asistente(s) registrado(s)</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-dark">
                <tr>
                  <th>#</th>
                  <th>Tipo Doc.</th>
                  <th>Documento</th>
                  <th>Nombres</th>
                  <th>Correo</th>
                  <th>Celular</th>
                  <th>Entidad</th>
                  <th>Cargo</th>
                  <th>Fecha Registro</th>
                  <th class="text-center">Encuesta</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($asistentes)): ?>
                  <tr>
                    <td colspan="10" class="text-center py-4 text-muted">
                      <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                      No hay asistentes registrados en esta sesión.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($asistentes as $i => $a): ?>
                    <tr>
                      <td><?= $i + 1 ?></td>
                      <td><?= htmlspecialchars($a['gar_tipo_documento']) ?></td>
                      <td><?= htmlspecialchars($a['gar_numero_documento']) ?></td>
                      <td><?= htmlspecialchars($a['gar_nombres'] . ' ' . $a['gar_apellidos']) ?></td>
                      <td><?= htmlspecialchars($a['gar_correo']) ?></td>
                      <td><?= htmlspecialchars($a['gar_celular'] ?? '—') ?></td>
                      <td><?= htmlspecialchars($a['gar_entidad'] ?? '—') ?></td>
                      <td><?= htmlspecialchars($a['gar_cargo'] ?? '—') ?></td>
                      <td><?= date('d/m/Y H:i', strtotime($a['gar_fecha_asistencia'])) ?></td>
                      <td class="text-center">
                        <?php if ($a['encuesta_respondida'] === 'Sí'): ?>
                          <span class="badge bg-success" title="Promedio: <?= number_format((float)$a['gae_calificacion_promedio'], 1) ?>">
                            ✓ <?= number_format((float)$a['gae_calificacion_promedio'], 1) ?>
                          </span>
                        <?php else: ?>
                          <span class="badge bg-light text-muted">Pendiente</span>
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
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/_footer.php'; ?>
