<?php
/**
 * templates/gestion_asistencias/sesion_detalle.php
 */

$modulo_plataforma = "Gestión Asistencias";
require_once("../../iniciador.php");
require_once(__DIR__ . '/includes/gas_funciones.php');
require_once(__DIR__ . '/includes/gas_permisos.php');

$url_fichero      = pathinfo(__FILE__, PATHINFO_FILENAME);
$title            = "Gestión Asistencias";
$subtitle         = "Detalle de Sesión";
$respuesta_accion = "";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $enlace_db->prepare('SELECT * FROM gestion_asistencias_sesiones WHERE gas_id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$sesion = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$sesion) { header('Location: index.php'); exit; }

$stmt = $enlace_db->prepare(
    "SELECT
        COUNT(DISTINCT r.gar_id)                      AS total_asistentes,
        COUNT(DISTINCT e.gae_id)                      AS total_encuestas,
        ROUND(AVG(e.gae_calificacion_promedio), 2)    AS prom_general,
        ROUND(AVG(e.gae_calificacion_facilitador), 2) AS prom_facilitador,
        ROUND(AVG(e.gae_calificacion_metodologia), 2) AS prom_metodologia,
        ROUND(AVG(e.gae_calificacion_material), 2)    AS prom_material
     FROM gestion_asistencias_sesiones s
     LEFT JOIN gestion_asistencias_registros r
            ON r.gar_sesion_id = s.gas_id AND r.gar_estado = 'activo'
     LEFT JOIN gestion_asistencias_encuestas e
            ON e.gae_sesion_id = s.gas_id AND e.gae_estado = 'activo'
     WHERE s.gas_id = ? GROUP BY s.gas_id"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$metricas = $stmt->get_result()->fetch_assoc() ?? [];
$stmt->close();

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

// ── Link público ──────────────────────────────────────────────────────────────
$link_publico = !empty($sesion['gas_link_publico'])
    ? $sesion['gas_link_publico']
    : gas_construir_link($sesion['gas_token_publico']);

// ── Ruta QR ───────────────────────────────────────────────────────────────────
// gas_qr_path = 'assets/gas/qr/TOKEN.png'
// Físico:       templates/assets/gas/qr/TOKEN.png
// Desde aquí:   templates/gestion_asistencias/ → subir 1 nivel → templates/
$qr_path_rel = $sesion['gas_qr_path'] ?? '';
$qr_fisico   = !empty($qr_path_rel) ? __DIR__ . '/../' . $qr_path_rel : '';
$qr_url      = !empty($qr_path_rel) ? URL . '/' . $qr_path_rel         : '';
$tiene_qr    = !empty($qr_fisico) && file_exists($qr_fisico);

// Auto-generar QR si no existe (ej: sesión creada antes de instalar endroid)
if (!$tiene_qr && !empty($sesion['gas_token_publico'])) {
    $nuevo_path = gas_generar_qr($link_publico, $sesion['gas_token_publico']);
    if (!empty($nuevo_path)) {
        $stmt = $enlace_db->prepare(
            'UPDATE gestion_asistencias_sesiones SET gas_qr_path = ?, gas_modificacion_fecha = NOW() WHERE gas_id = ?'
        );
        $stmt->bind_param('si', $nuevo_path, $id);
        $stmt->execute();
        $stmt->close();
        $sesion['gas_qr_path'] = $nuevo_path;
        $qr_fisico = __DIR__ . '/../' . $nuevo_path;
        $qr_url    = URL . '/' . $nuevo_path;
        $tiene_qr  = file_exists($qr_fisico);
    }
}

// ── Alertas ───────────────────────────────────────────────────────────────────
if (isset($_GET['ok'])) {
    $msgs = ['estado_actualizado' => 'Estado actualizado correctamente.', 'editada' => 'Sesión actualizada.'];
    $respuesta_accion = "alertButton('success','Operación exitosa','" . addslashes($msgs[$_GET['ok']] ?? 'OK') . "');";
}
if (isset($_GET['error'])) {
    $errs = ['transicion_invalida' => 'Cambio de estado no permitido.', 'db_error' => 'Error de base de datos.'];
    $respuesta_accion = "alertButton('error','Error','" . addslashes($errs[$_GET['error']] ?? 'Error') . "');";
}

$nuevo        = isset($_GET['nuevo']);
$estado       = $sesion['gas_estado'];
$tipos_sesion = gas_tipos_sesion();

$wa_texto = urlencode(
    "Hola, te invito a registrar tu asistencia:\n" .
    "*" . $sesion['gas_nombre'] . "*\n" .
    "Facilitador: " . $sesion['gas_facilitador'] . "\n" .
    "Fecha: " . date('d/m/Y H:i', strtotime($sesion['gas_fecha_inicio'])) . "\n\n" .
    $link_publico
);
?>
<!DOCTYPE html>
<html lang="<?php echo LANG; ?>">
<head>
  <?php require_once(ROOT.'includes/_head.php'); ?>
  <style>
    .gas-share-box { background:#f0f4f8; border:1px solid #d0dce8; border-radius:8px; padding:14px 16px; }
    .gas-share-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#6c757d; margin-bottom:8px; display:block; }
    .gas-link-row { display:flex; gap:6px; align-items:center; }
    .gas-link-row input { flex:1; font-size:12px; font-family:'Courier New',monospace; color:#1a3c6b; background:#fff; border:1px solid #c8d8e8; border-radius:5px; padding:5px 10px; outline:none; }
    .gas-link-row input:focus { border-color:#2e75b6; }
    .gas-share-btns { display:flex; gap:6px; margin-top:8px; flex-wrap:wrap; }
    .gas-sbtn { font-size:11px; padding:5px 12px; border-radius:5px; border:none; cursor:pointer; font-weight:600; display:inline-flex; align-items:center; gap:5px; transition:all .15s; white-space:nowrap; text-decoration:none !important; }
    .gas-sbtn.blue { background:#1a3c6b; color:#fff; }
    .gas-sbtn.blue:hover, .gas-sbtn.blue.copied { background:#198754; color:#fff; }
    .gas-sbtn.green { background:#25D366; color:#fff; }
    .gas-sbtn.green:hover { background:#1ebe5a; color:#fff; }
    .gas-sbtn.outline { background:#fff; color:#1a3c6b; border:1px solid #c8d8e8; }
    .gas-sbtn.outline:hover { background:#e8f0f8; }

    /* QR */
    .gas-qr-wrap { text-align:center; padding:12px; }
    .gas-qr-wrap img { max-width:180px; width:100%; border:3px solid #e8f0f8; border-radius:10px; padding:8px; background:#fff; box-shadow:0 2px 10px rgba(26,60,107,.12); cursor:zoom-in; transition:transform .15s, box-shadow .15s; display:block; margin:0 auto 8px; }
    .gas-qr-wrap img:hover { transform:scale(1.04); box-shadow:0 4px 18px rgba(26,60,107,.2); }
    .gas-qr-hint { font-size:10px; color:#adb5bd; margin-bottom:8px; }
    .gas-qr-placeholder { width:150px; height:150px; border:2px dashed #dee2e6; border-radius:10px; display:flex; flex-direction:column; align-items:center; justify-content:center; margin:0 auto 8px; color:#adb5bd; }

    /* Lightbox */
    #gas-lightbox {
      display:none;
      position:fixed; inset:0; z-index:9999;
      background:rgba(0,0,0,.82);
      align-items:center; justify-content:center;
      flex-direction:column; gap:16px;
    }
    #gas-lightbox.show { display:flex; }
    #gas-lightbox img { max-width:320px; width:90%; border-radius:12px; padding:12px; background:#fff; box-shadow:0 8px 40px rgba(0,0,0,.4); }
    #gas-lightbox .gas-lb-link { font-size:12px; font-family:'Courier New',monospace; color:#fff; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2); border-radius:6px; padding:8px 16px; max-width:400px; text-align:center; word-break:break-all; }
    #gas-lightbox .gas-lb-actions { display:flex; gap:10px; flex-wrap:wrap; justify-content:center; }
    #gas-lightbox .gas-lb-close { position:fixed; top:18px; right:22px; background:rgba(255,255,255,.15); border:none; color:#fff; width:36px; height:36px; border-radius:50%; font-size:18px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background .15s; }
    #gas-lightbox .gas-lb-close:hover { background:rgba(255,255,255,.3); }
  </style>
</head>
<body class="sidebar-dark sidebar-icon-only">
<div class="container-scroller">
  <?php require_once(ROOT.'includes/_navbar.php'); ?>
  <div class="container-fluid page-body-wrapper">
    <?php require_once(ROOT.'includes/_sidebar.php'); ?>
    <div class="main-panel">
      <div class="content-wrapper">

        <?php if (!empty($respuesta_accion)) echo "<script>{$respuesta_accion}</script>"; ?>

        <?php if ($nuevo): ?>
        <script>
          document.addEventListener('DOMContentLoaded', function() {
            alertButton('success','¡Sesión creada!',
              'La sesión <?= addslashes(htmlspecialchars($sesion["gas_codigo"])) ?> fue creada exitosamente. Actívala para compartir el link con los asistentes.');
          });
        </script>
        <?php endif; ?>

        <!-- Cabecera -->
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div>
            <h4 class="mb-1 font-size-16"><?= htmlspecialchars($sesion['gas_nombre']) ?></h4>
            <small class="text-muted">
              <code class="font-size-11"><?= htmlspecialchars($sesion['gas_codigo']) ?></code>
              &nbsp;·&nbsp;
              <?= htmlspecialchars($tipos_sesion[$sesion['gas_tipo_sesion']] ?? $sesion['gas_tipo_sesion']) ?>
              &nbsp;·&nbsp;
              <span class="badge bg-<?= gas_badge_estado($estado) ?>"><?= gas_label_estado($estado) ?></span>
            </small>
          </div>
          <a href="index.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
          </a>
        </div>

        <div class="row g-3">

          <!-- ── Columna izquierda ──────────────────────────────────────── -->
          <div class="col-md-7">

            <!-- Info -->
            <div class="card mb-3">
              <div class="card-header py-2">
                <h6 class="mb-0 font-size-12">
                  <i class="fas fa-info-circle me-1 text-primary"></i>Información de la Sesión
                </h6>
              </div>
              <div class="card-body py-2">
                <dl class="row mb-0 font-size-12">
                  <dt class="col-sm-4 text-muted fw-normal">Facilitador</dt>
                  <dd class="col-sm-8 mb-1"><?= htmlspecialchars($sesion['gas_facilitador']) ?></dd>
                  <dt class="col-sm-4 text-muted fw-normal">Fecha Inicio</dt>
                  <dd class="col-sm-8 mb-1"><?= date('d/m/Y H:i', strtotime($sesion['gas_fecha_inicio'])) ?></dd>
                  <?php if (!empty($sesion['gas_fecha_fin'])): ?>
                  <dt class="col-sm-4 text-muted fw-normal">Fecha Fin</dt>
                  <dd class="col-sm-8 mb-1"><?= date('d/m/Y H:i', strtotime($sesion['gas_fecha_fin'])) ?></dd>
                  <?php endif; ?>
                  <?php if (!empty($sesion['gas_descripcion'])): ?>
                  <dt class="col-sm-4 text-muted fw-normal">Descripción</dt>
                  <dd class="col-sm-8 mb-1"><?= nl2br(htmlspecialchars($sesion['gas_descripcion'])) ?></dd>
                  <?php endif; ?>
                </dl>
              </div>
            </div>

            <!-- Compartir -->
            <div class="card mb-3">
              <div class="card-header py-2">
                <h6 class="mb-0 font-size-12">
                  <i class="fas fa-share-alt me-1 text-primary"></i>Compartir con Asistentes
                </h6>
              </div>
              <div class="card-body py-3">
                <div class="gas-share-box mb-2">
                  <span class="gas-share-label"><i class="fas fa-link me-1"></i>Link público de la sesión</span>
                  <div class="gas-link-row">
                    <input type="text" id="gas_link" value="<?= htmlspecialchars($link_publico) ?>" readonly>
                  </div>
                  <div class="gas-share-btns">
                    <button class="gas-sbtn blue" id="btn_copiar" onclick="copiarLink()">
                      <i class="fas fa-copy"></i> Copiar Link
                    </button>
                    <a class="gas-sbtn green"
                       href="https://wa.me/?text=<?= $wa_texto ?>" target="_blank" rel="noopener">
                      <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    <a class="gas-sbtn outline"
                       href="<?= htmlspecialchars($link_publico) ?>" target="_blank" rel="noopener">
                      <i class="fas fa-external-link-alt"></i> Abrir
                    </a>
                    <?php if ($tiene_qr): ?>
                    <button class="gas-sbtn outline" onclick="abrirLightbox()">
                      <i class="fas fa-qrcode"></i> Ver QR
                    </button>
                    <?php endif; ?>
                  </div>
                </div>

                <?php
                $estado_info = [
                    'borrador'   => ['warning', 'clock',        'Activa la sesión para que el link funcione.'],
                    'activa'     => ['success', 'check-circle', 'El link muestra el formulario de asistencia.'],
                    'finalizada' => ['info',    'star',          'El link ahora muestra la encuesta de satisfacción.'],
                    'cerrada'    => ['secondary','lock',         'La sesión está cerrada.'],
                    'anulada'    => ['danger',  'ban',           'La sesión está anulada.'],
                ];
                [$c, $ic, $msg] = $estado_info[$estado] ?? ['secondary','info',''];
                ?>
                <div class="alert alert-<?= $c ?> py-2 mb-0 font-size-11">
                  <i class="fas fa-<?= $ic ?> me-1"></i><?= $msg ?>
                </div>
              </div>
            </div>

            <!-- Acciones -->
            <div class="card">
              <div class="card-header py-2">
                <h6 class="mb-0 font-size-12">
                  <i class="fas fa-sliders-h me-1 text-primary"></i>Acciones
                </h6>
              </div>
              <div class="card-body py-2">
                <div class="d-flex flex-wrap gap-2">
                  <?php if (gas_validar_transicion($estado, 'activa')): ?>
                    <button class="btn btn-success btn-sm font-size-11"
                            onclick="cambiarEstado(<?= $id ?>,'activa','Activar')">
                      <i class="fas fa-play me-1"></i>Activar
                    </button>
                  <?php endif; ?>
                  <?php if (gas_validar_transicion($estado, 'finalizada')): ?>
                    <button class="btn btn-warning btn-sm font-size-11"
                            onclick="cambiarEstado(<?= $id ?>,'finalizada','Finalizar')">
                      <i class="fas fa-stop me-1"></i>Finalizar
                    </button>
                  <?php endif; ?>
                  <?php if (gas_validar_transicion($estado, 'cerrada')): ?>
                    <button class="btn btn-dark btn-sm font-size-11"
                            onclick="cambiarEstado(<?= $id ?>,'cerrada','Cerrar')">
                      <i class="fas fa-lock me-1"></i>Cerrar
                    </button>
                  <?php endif; ?>
                  <?php if (gas_validar_transicion($estado, 'anulada')): ?>
                    <button class="btn btn-danger btn-sm font-size-11"
                            onclick="cambiarEstado(<?= $id ?>,'anulada','Anular')">
                      <i class="fas fa-ban me-1"></i>Anular
                    </button>
                  <?php endif; ?>
                  <?php if (in_array($estado, ['borrador','activa'])): ?>
                    <a href="sesion_editar.php?id=<?= $id ?>"
                       class="btn btn-outline-secondary btn-sm font-size-11">
                      <i class="fas fa-edit me-1"></i>Editar
                    </a>
                  <?php endif; ?>
                  <a href="asistentes_lista.php?id=<?= $id ?>"
                     class="btn btn-outline-info btn-sm font-size-11">
                    <i class="fas fa-users me-1"></i>Asistentes
                    <?php if ((int)($metricas['total_asistentes'] ?? 0) > 0): ?>
                      <span class="badge bg-info ms-1"><?= (int)$metricas['total_asistentes'] ?></span>
                    <?php endif; ?>
                  </a>
                  <?php if ((int)($metricas['total_encuestas'] ?? 0) > 0): ?>
                    <a href="encuestas_lista.php?id=<?= $id ?>"
                       class="btn btn-outline-success btn-sm font-size-11">
                      <i class="fas fa-star me-1"></i>Encuestas
                      <span class="badge bg-success ms-1"><?= (int)$metricas['total_encuestas'] ?></span>
                    </a>
                  <?php endif; ?>
                  <a href="reportes.php" class="btn btn-outline-secondary btn-sm font-size-11">
                    <i class="fas fa-chart-bar me-1"></i>Reportes
                  </a>
                </div>
              </div>
            </div>

          </div><!-- /col-md-7 -->

          <!-- ── Columna derecha: QR + Métricas ──────────────────────── -->
          <div class="col-md-5">

            <!-- QR -->
            <div class="card mb-3">
              <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 font-size-12">
                  <i class="fas fa-qrcode me-1 text-primary"></i>Código QR
                </h6>
                <?php if ($tiene_qr): ?>
                  <a href="<?= htmlspecialchars($qr_url) ?>"
                     download="QR_<?= htmlspecialchars($sesion['gas_codigo']) ?>.png"
                     class="btn btn-sm btn-outline-primary py-0 font-size-11">
                    <i class="fas fa-download me-1"></i>Descargar
                  </a>
                <?php endif; ?>
              </div>
              <div class="gas-qr-wrap">
                <?php if ($tiene_qr): ?>
                  <img src="<?= htmlspecialchars($qr_url) ?>"
                       alt="QR <?= htmlspecialchars($sesion['gas_codigo']) ?>"
                       onclick="abrirLightbox()"
                       title="Clic para ampliar">
                  <div class="gas-qr-hint">
                    <i class="fas fa-search-plus me-1"></i>Clic para ampliar
                  </div>
                  <a class="gas-sbtn green"
                     href="https://wa.me/?text=<?= $wa_texto ?>"
                     target="_blank" rel="noopener"
                     style="display:inline-flex; font-size:11px; margin-top:4px;">
                    <i class="fab fa-whatsapp"></i> Compartir por WhatsApp
                  </a>
                <?php else: ?>
                  <div class="gas-qr-placeholder">
                    <i class="fas fa-qrcode fa-3x mb-2" style="opacity:.2;"></i>
                    <span style="font-size:11px;">Sin QR</span>
                  </div>
                  <small class="text-muted d-block font-size-11">
                    Instala <code>endroid/qr-code</code> y vuelve a crear una sesión.
                  </small>
                <?php endif; ?>
              </div>
            </div>

            <!-- Métricas -->
            <div class="card">
              <div class="card-header py-2">
                <h6 class="mb-0 font-size-12">
                  <i class="fas fa-chart-pie me-1 text-primary"></i>Métricas
                </h6>
              </div>
              <div class="card-body py-2">
                <div class="row text-center g-2 mb-2">
                  <div class="col-6">
                    <div class="bg-light rounded p-2">
                      <div class="h4 mb-0 text-primary"><?= (int)($metricas['total_asistentes'] ?? 0) ?></div>
                      <small class="text-muted font-size-11">Asistentes</small>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="bg-light rounded p-2">
                      <div class="h4 mb-0 text-success"><?= (int)($metricas['total_encuestas'] ?? 0) ?></div>
                      <small class="text-muted font-size-11">Encuestas</small>
                    </div>
                  </div>
                </div>
                <?php if ((int)($metricas['total_asistentes'] ?? 0) > 0): ?>
                  <?php $pct = round((int)($metricas['total_encuestas'] ?? 0) * 100 / (int)$metricas['total_asistentes']); ?>
                  <div class="mb-2">
                    <div class="d-flex justify-content-between font-size-11">
                      <span class="text-muted">Participación encuesta</span>
                      <span class="fw-bold text-success"><?= $pct ?>%</span>
                    </div>
                    <div class="progress mt-1" style="height:6px;">
                      <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                    </div>
                  </div>
                <?php endif; ?>
                <?php if (!empty($metricas['prom_general'])): ?>
                  <table class="table table-sm table-borderless mb-0 font-size-11">
                    <tr><td class="text-muted py-1">Promedio General</td>
                        <td class="text-end py-1 fw-bold text-primary"><?= number_format((float)$metricas['prom_general'],1) ?>/5</td></tr>
                    <tr><td class="text-muted py-1">Facilitador</td>
                        <td class="text-end py-1"><?= number_format((float)$metricas['prom_facilitador'],1) ?>/5</td></tr>
                    <tr><td class="text-muted py-1">Metodología</td>
                        <td class="text-end py-1"><?= number_format((float)$metricas['prom_metodologia'],1) ?>/5</td></tr>
                    <tr><td class="text-muted py-1">Material</td>
                        <td class="text-end py-1"><?= number_format((float)$metricas['prom_material'],1) ?>/5</td></tr>
                  </table>
                <?php else: ?>
                  <p class="text-muted text-center mb-0 font-size-11">Sin encuestas aún.</p>
                <?php endif; ?>
              </div>
            </div>

          </div><!-- /col-md-5 -->

          <!-- Últimos asistentes -->
          <?php if (!empty($ultimos)): ?>
          <div class="col-12">
            <div class="card">
              <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 font-size-12">
                  <i class="fas fa-users me-1 text-primary"></i>Últimos Asistentes
                </h6>
                <a href="asistentes_lista.php?id=<?= $id ?>"
                   class="btn btn-sm btn-outline-primary py-0 font-size-11">
                  Ver todos <i class="fas fa-arrow-right ms-1"></i>
                </a>
              </div>
              <div class="card-body p-0">
                <table class="table table-sm table-hover table-bordered font-size-11 mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="px-2 py-1">Nombre</th>
                      <th class="px-2 py-1">Correo</th>
                      <th class="px-2 py-1">Entidad</th>
                      <th class="px-2 py-1">Registrado</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($ultimos as $a): ?>
                    <tr>
                      <td class="px-2 py-1"><?= htmlspecialchars($a['gar_nombres'].' '.$a['gar_apellidos']) ?></td>
                      <td class="px-2 py-1"><?= htmlspecialchars($a['gar_correo']) ?></td>
                      <td class="px-2 py-1"><?= htmlspecialchars($a['gar_entidad'] ?? '—') ?></td>
                      <td class="px-2 py-1"><?= date('d/m/Y H:i', strtotime($a['gar_fecha_asistencia'])) ?></td>
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

      <!-- footer — patrón DPS -->
      <?php require_once(ROOT.'includes/_footer.php'); ?>
    </div><!-- /main-panel -->
  </div><!-- /page-body-wrapper -->

  <!-- ── _js.php AQUÍ — patrón exacto DPS, carga jQuery + Bootstrap JS ── -->
  <?php require_once(ROOT.'includes/_js.php'); ?>

</div><!-- /container-scroller -->

<!-- ── Lightbox QR (puro JS, sin depender del modal de Bootstrap) ─────── -->
<?php if ($tiene_qr): ?>
<div id="gas-lightbox" onclick="cerrarLightbox(event)">
  <button class="gas-lb-close" onclick="cerrarLightbox(null,true)" title="Cerrar">
    <i class="fas fa-times"></i>
  </button>
  <img src="<?= htmlspecialchars($qr_url) ?>"
       alt="QR <?= htmlspecialchars($sesion['gas_codigo']) ?>"
       onclick="event.stopPropagation()">
  <div class="gas-lb-link"><?= htmlspecialchars($link_publico) ?></div>
  <div class="gas-lb-actions">
    <button class="gas-sbtn blue" onclick="copiarLink(); event.stopPropagation();">
      <i class="fas fa-copy"></i> Copiar Link
    </button>
    <a class="gas-sbtn green"
       href="https://wa.me/?text=<?= $wa_texto ?>"
       target="_blank" rel="noopener"
       onclick="event.stopPropagation()">
      <i class="fab fa-whatsapp"></i> WhatsApp
    </a>
    <a class="gas-sbtn outline"
       href="<?= htmlspecialchars($qr_url) ?>"
       download="QR_<?= htmlspecialchars($sesion['gas_codigo']) ?>.png"
       onclick="event.stopPropagation()"
       style="color:#fff; border-color:rgba(255,255,255,.3);">
      <i class="fas fa-download"></i> Descargar
    </a>
  </div>
</div>
<?php endif; ?>

<!-- Formulario oculto cambio de estado -->
<form id="form_estado" method="POST" action="sesion_cambiar_estado.php" style="display:none;">
  <input type="hidden" name="sesion_id"    id="inp_sesion_id">
  <input type="hidden" name="nuevo_estado" id="inp_nuevo_estado">
  <input type="hidden" name="redirect"     value="sesion_detalle.php?id=<?= $id ?>">
</form>

<script>
/* ── Lightbox ───────────────────────────────────────────────────────── */
function abrirLightbox() {
    document.getElementById('gas-lightbox').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function cerrarLightbox(e, forzar) {
    if (forzar || (e && e.target === document.getElementById('gas-lightbox'))) {
        document.getElementById('gas-lightbox').classList.remove('show');
        document.body.style.overflow = '';
    }
}
// Cerrar con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarLightbox(null, true);
});

/* ── Copiar link ─────────────────────────────────────────────────────── */
function copiarLink() {
    const val = document.getElementById('gas_link').value;
    const btn = document.getElementById('btn_copiar');
    const orig = btn ? btn.innerHTML : '';

    function onOk() {
        if (btn) {
            btn.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
            btn.classList.add('copied');
            setTimeout(() => { btn.innerHTML = orig; btn.classList.remove('copied'); }, 2500);
        }
        swal({ title:'¡Link copiado!', text:'El link fue copiado al portapapeles.', icon:'success', timer:1600, buttons:false });
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(val).then(onOk).catch(() => {
            document.getElementById('gas_link').select();
            document.execCommand('copy');
            onOk();
        });
    } else {
        document.getElementById('gas_link').select();
        document.execCommand('copy');
        onOk();
    }
}

/* ── Cambio de estado con SweetAlert ────────────────────────────────── */
function cambiarEstado(sid, estado, accion) {
    swal({
        title: '¿' + accion + ' esta sesión?',
        text:  'El estado cambiará a "' + estado + '". Esta acción no se puede deshacer fácilmente.',
        icon:  'warning',
        buttons: { cancel: 'Cancelar', confirm: { text: accion, className: 'btn-danger' } },
        dangerMode: true,
    }).then(function(ok) {
        if (!ok) return;
        document.getElementById('inp_sesion_id').value    = sid;
        document.getElementById('inp_nuevo_estado').value = estado;
        document.getElementById('form_estado').submit();
    });
}
</script>

</body>
</html>