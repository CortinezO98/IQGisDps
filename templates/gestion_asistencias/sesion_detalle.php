<?php
/**
 * templates/gestion_asistencias/sesion_detalle.php — CORREGIDO para DPS
 */

$modulo_plataforma = "Gestión Asistencias";
require_once("../../iniciador.php");
require_once(__DIR__ . '/includes/gas_funciones.php');
require_once(__DIR__ . '/includes/gas_permisos.php');

$url_fichero = pathinfo(__FILE__, PATHINFO_FILENAME);
$title    = "Gestión Asistencias";
$subtitle = "Detalle de Sesión";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

// Cargar sesión
$stmt = $enlace_db->prepare(
    'SELECT * FROM gestion_asistencias_sesiones WHERE gas_id = ? LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$sesion = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$sesion) { header('Location: index.php'); exit; }

// Métricas
$stmt = $enlace_db->prepare(
    "SELECT
        COUNT(DISTINCT r.gar_id)                             AS total_asistentes,
        COUNT(DISTINCT e.gae_id)                             AS total_encuestas,
        ROUND(AVG(e.gae_calificacion_promedio), 2)           AS prom_general,
        ROUND(AVG(e.gae_calificacion_facilitador), 2)        AS prom_facilitador,
        ROUND(AVG(e.gae_calificacion_metodologia), 2)        AS prom_metodologia,
        ROUND(AVG(e.gae_calificacion_material), 2)           AS prom_material
     FROM gestion_asistencias_sesiones s
     LEFT JOIN gestion_asistencias_registros r
            ON r.gar_sesion_id = s.gas_id AND r.gar_estado = 'activo'
     LEFT JOIN gestion_asistencias_encuestas e
            ON e.gae_sesion_id = s.gas_id AND e.gae_estado = 'activo'
     WHERE s.gas_id = ?
     GROUP BY s.gas_id"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$metricas = $stmt->get_result()->fetch_assoc() ?? [];
$stmt->close();

// Últimos 5 asistentes
$stmt = $enlace_db->prepare(
    "SELECT gar_nombres, gar_apellidos, gar_correo, gar_entidad, gar_fecha_asistencia
     FROM gestion_asistencias_registros
     WHERE gar_sesion_id = ? AND gar_estado = 'activo'
     ORDER BY gar_fecha_asistencia DESC LIMIT 5"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$ultimos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$nuevo        = isset($_GET['nuevo']);
$link_publico = $sesion['gas_link_publico'] ?: gas_construir_link($sesion['gas_token_publico']);
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

          <?php if ($nuevo): ?>
            <div class="alert alert-success alert-dismissible fade show">
              <i class="fas fa-check-circle me-2"></i>
              <strong>¡Sesión creada!</strong>
              Código: <strong><?= htmlspecialchars($sesion['gas_codigo']) ?></strong>.
              Actívala para compartir el link con los asistentes.
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>
          <?php if (isset($_GET['ok'])): ?>
            <div class="alert alert-success alert-dismissible fade show py-2">
              Estado actualizado correctamente.
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <!-- Cabecera -->
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h4 class="mb-0"><?= htmlspecialchars($sesion['gas_nombre']) ?></h4>
              <small class="text-muted">
                <code><?= htmlspecialchars($sesion['gas_codigo']) ?></code>
                &nbsp;·&nbsp;
                <?= htmlspecialchars($tipos_sesion[$sesion['gas_tipo_sesion']] ?? $sesion['gas_tipo_sesion']) ?>
                &nbsp;·&nbsp;
                <span class="badge bg-<?= gas_badge_estado($sesion['gas_estado']) ?>">
                  <?= gas_label_estado($sesion['gas_estado']) ?>
                </span>
              </small>
            </div>
            <a href="index.php" class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
          </div>

          <div class="row g-3">

            <!-- Info + acciones + link -->
            <div class="col-md-8">
              <div class="card h-100">
                <div class="card-header"><strong>Información de la Sesión</strong></div>
                <div class="card-body">
                  <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Facilitador</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($sesion['gas_facilitador']) ?></dd>
                    <dt class="col-sm-4 text-muted">Fecha Inicio</dt>
                    <dd class="col-sm-8"><?= date('d/m/Y H:i', strtotime($sesion['gas_fecha_inicio'])) ?></dd>
                    <?php if ($sesion['gas_fecha_fin']): ?>
                      <dt class="col-sm-4 text-muted">Fecha Fin</dt>
                      <dd class="col-sm-8"><?= date('d/m/Y H:i', strtotime($sesion['gas_fecha_fin'])) ?></dd>
                    <?php endif; ?>
                    <?php if ($sesion['gas_descripcion']): ?>
                      <dt class="col-sm-4 text-muted">Descripción</dt>
                      <dd class="col-sm-8"><?= nl2br(htmlspecialchars($sesion['gas_descripcion'])) ?></dd>
                    <?php endif; ?>
                  </dl>

                  <hr>

                  <!-- Link público -->
                  <label class="form-label fw-bold">Link Público para Asistentes</label>
                  <div class="input-group mb-3">
                    <input type="text" id="gas_link" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($link_publico) ?>" readonly>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copiarLink()" title="Copiar">
                      <i class="fas fa-copy"></i>
                    </button>
                    <a href="<?= htmlspecialchars($link_publico) ?>" target="_blank"
                       class="btn btn-sm btn-outline-primary" title="Abrir en nueva pestaña">
                      <i class="fas fa-external-link-alt"></i>
                    </a>
                  </div>

                  <!-- Botones de cambio de estado -->
                  <hr>
                  <div class="d-flex flex-wrap gap-2">
                    <?php $estado = $sesion['gas_estado']; ?>

                    <?php if (gas_validar_transicion($estado, 'activa')): ?>
                      <button class="btn btn-success btn-sm"
                              onclick="cambiarEstado(<?= $id ?>, 'activa', 'Activar')">
                        <i class="fas fa-play me-1"></i> Activar Sesión
                      </button>
                    <?php endif; ?>

                    <?php if (gas_validar_transicion($estado, 'finalizada')): ?>
                      <button class="btn btn-warning btn-sm"
                              onclick="cambiarEstado(<?= $id ?>, 'finalizada', 'Finalizar')">
                        <i class="fas fa-stop me-1"></i> Finalizar Sesión
                      </button>
                    <?php endif; ?>

                    <?php if (gas_validar_transicion($estado, 'cerrada')): ?>
                      <button class="btn btn-dark btn-sm"
                              onclick="cambiarEstado(<?= $id ?>, 'cerrada', 'Cerrar')">
                        <i class="fas fa-lock me-1"></i> Cerrar Sesión
                      </button>
                    <?php endif; ?>

                    <?php if (gas_validar_transicion($estado, 'anulada')): ?>
                      <button class="btn btn-danger btn-sm"
                              onclick="cambiarEstado(<?= $id ?>, 'anulada', 'Anular')">
                        <i class="fas fa-ban me-1"></i> Anular Sesión
                      </button>
                    <?php endif; ?>

                    <?php if (in_array($estado, ['borrador','activa'])): ?>
                      <a href="sesion_editar.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-pen me-1"></i> Editar
                      </a>
                    <?php endif; ?>

                    <a href="asistentes_lista.php?id=<?= $id ?>" class="btn btn-outline-info btn-sm">
                      <i class="fas fa-users me-1"></i> Asistentes
                    </a>

                    <?php if ((int)($metricas['total_encuestas'] ?? 0) > 0): ?>
                      <a href="encuestas_lista.php?id=<?= $id ?>" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-star me-1"></i> Encuestas
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>

            <!-- QR + Métricas -->
            <div class="col-md-4">
              <div class="card mb-3 text-center">
                <div class="card-header"><strong>Código QR</strong></div>
                <div class="card-body">
                  <?php
                    $qr_file = ROOT . $sesion['gas_qr_path'];
                    if (!empty($sesion['gas_qr_path']) && file_exists($qr_file)):
                  ?>
                    <img src="<?= URL ?>/<?= htmlspecialchars($sesion['gas_qr_path']) ?>"
                         alt="QR Sesión" class="img-fluid mb-2" style="max-width:160px;">
                    <br>
                    <a href="<?= URL ?>/<?= htmlspecialchars($sesion['gas_qr_path']) ?>"
                       download="qr_<?= htmlspecialchars($sesion['gas_codigo']) ?>.png"
                       class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-download me-1"></i> Descargar QR
                    </a>
                  <?php else: ?>
                    <div class="text-muted py-3">
                      <i class="fas fa-qrcode fa-3x mb-2 d-block opacity-25"></i>
                      <small>QR no disponible.<br>Instala phpqrcode para generarlo.</small>
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <div class="card">
                <div class="card-header"><strong>Métricas de la Sesión</strong></div>
                <div class="card-body">
                  <div class="row text-center g-2">
                    <div class="col-6">
                      <div class="bg-light rounded p-2">
                        <div class="h4 mb-0 text-primary"><?= (int)($metricas['total_asistentes'] ?? 0) ?></div>
                        <small class="text-muted">Asistentes</small>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="bg-light rounded p-2">
                        <div class="h4 mb-0 text-success"><?= (int)($metricas['total_encuestas'] ?? 0) ?></div>
                        <small class="text-muted">Encuestas</small>
                      </div>
                    </div>
                    <?php if ((int)($metricas['total_asistentes'] ?? 0) > 0): ?>
                      <?php $pct = round((int)($metricas['total_encuestas'] ?? 0) * 100 / (int)$metricas['total_asistentes']); ?>
                      <div class="col-12 mt-1">
                        <small class="text-muted">Participación en encuesta</small>
                        <div class="progress mt-1" style="height:7px;">
                          <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                        </div>
                        <small class="text-success fw-bold"><?= $pct ?>%</small>
                      </div>
                    <?php endif; ?>
                    <?php if (!empty($metricas['prom_general'])): ?>
                      <div class="col-12 mt-2">
                        <table class="table table-sm table-borderless mb-0 text-start font-size-11">
                          <tr><td class="text-muted">Promedio General</td><td class="fw-bold text-end"><?= number_format((float)$metricas['prom_general'], 1) ?>/5</td></tr>
                          <tr><td class="text-muted">Facilitador</td><td class="text-end"><?= number_format((float)$metricas['prom_facilitador'], 1) ?>/5</td></tr>
                          <tr><td class="text-muted">Metodología</td><td class="text-end"><?= number_format((float)$metricas['prom_metodologia'], 1) ?>/5</td></tr>
                          <tr><td class="text-muted">Material</td><td class="text-end"><?= number_format((float)$metricas['prom_material'], 1) ?>/5</td></tr>
                        </table>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>

            <!-- Últimos asistentes -->
            <?php if (!empty($ultimos)): ?>
              <div class="col-12">
                <div class="card">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Últimos Asistentes</strong>
                    <a href="asistentes_lista.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary">
                      Ver todos <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                  </div>
                  <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0 font-size-11">
                      <thead class="table-light">
                        <tr><th>Nombre</th><th>Correo</th><th>Entidad</th><th>Registrado</th></tr>
                      </thead>
                      <tbody>
                        <?php foreach ($ultimos as $a): ?>
                          <tr>
                            <td><?= htmlspecialchars($a['gar_nombres'] . ' ' . $a['gar_apellidos']) ?></td>
                            <td><?= htmlspecialchars($a['gar_correo']) ?></td>
                            <td><?= htmlspecialchars($a['gar_entidad'] ?? '—') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($a['gar_fecha_asistencia'])) ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            <?php endif; ?>

          </div><!-- /row -->
        </div><!-- /content-wrapper -->
        <?php require_once(ROOT.'includes/_footer.php'); ?>
      </div><!-- /main-panel -->
    </div>
  </div>

  <!-- Formulario oculto para cambio de estado -->
  <form id="form_estado" method="POST" action="sesion_cambiar_estado.php" style="display:none;">
    <input type="hidden" name="sesion_id"    id="inp_sesion_id">
    <input type="hidden" name="nuevo_estado" id="inp_nuevo_estado">
    <input type="hidden" name="redirect"     value="sesion_detalle.php?id=<?= $id ?>">
  </form>

  <script>
  function copiarLink() {
    const val = document.getElementById('gas_link').value;
    if (navigator.clipboard) {
      navigator.clipboard.writeText(val).then(() => alert('¡Link copiado!'));
    } else {
      document.getElementById('gas_link').select();
      document.execCommand('copy');
      alert('Link copiado.');
    }
  }
  function cambiarEstado(sid, estado, accion) {
    if (!confirm('¿Confirmas que deseas ' + accion.toLowerCase() + ' esta sesión?')) return;
    document.getElementById('inp_sesion_id').value   = sid;
    document.getElementById('inp_nuevo_estado').value = estado;
    document.getElementById('form_estado').submit();
  }
  </script>
</body>
</html>
