<?php
/**
 * templates/gestion_asistencias/reportes.php — CORREGIDO para DPS
 */

$modulo_plataforma = "Gestión Asistencias";
require_once("../../iniciador.php");
require_once(__DIR__ . '/includes/gas_funciones.php');
require_once(__DIR__ . '/includes/gas_permisos.php');

$url_fichero = pathinfo(__FILE__, PATHINFO_FILENAME);
$title    = "Gestión Asistencias";
$subtitle = "Reportes y Métricas";

// Filtros
$f_estado      = gas_sanitizar($_GET['estado']      ?? '');
$f_tipo        = gas_sanitizar($_GET['tipo']        ?? '');
$f_facilitador = gas_sanitizar($_GET['facilitador'] ?? '');
$f_fecha_ini   = gas_sanitizar($_GET['fecha_ini']   ?? '');
$f_fecha_fin   = gas_sanitizar($_GET['fecha_fin']   ?? '');

// Resumen global (sin filtros)
$resumen = $enlace_db->query(
    "SELECT
        COUNT(*)                         AS total_sesiones,
        SUM(gas_estado = 'activa')       AS sesiones_activas,
        SUM(gas_estado = 'finalizada')   AS sesiones_finalizadas,
        SUM(gas_estado = 'cerrada')      AS sesiones_cerradas,
        SUM(gas_estado = 'anulada')      AS sesiones_anuladas,
        SUM(gas_estado = 'borrador')     AS sesiones_borrador
     FROM gestion_asistencias_sesiones"
)->fetch_assoc();

// Filtros dinámicos
$where  = ['1=1'];
$params = [];
$types  = '';

if ($f_estado      !== '') { $where[] = 's.gas_estado = ?';           $params[] = $f_estado;                    $types .= 's'; }
if ($f_tipo        !== '') { $where[] = 's.gas_tipo_sesion = ?';      $params[] = $f_tipo;                      $types .= 's'; }
if ($f_facilitador !== '') { $where[] = 's.gas_facilitador LIKE ?';   $params[] = '%' . $f_facilitador . '%';  $types .= 's'; }
if ($f_fecha_ini   !== '') { $where[] = 'DATE(s.gas_fecha_inicio) >= ?'; $params[] = $f_fecha_ini;             $types .= 's'; }
if ($f_fecha_fin   !== '') { $where[] = 'DATE(s.gas_fecha_inicio) <= ?'; $params[] = $f_fecha_fin;             $types .= 's'; }

$where_sql = implode(' AND ', $where);

$sql_detalle = "
    SELECT
        s.gas_id, s.gas_codigo, s.gas_nombre, s.gas_tipo_sesion,
        s.gas_facilitador, s.gas_fecha_inicio, s.gas_estado,
        COUNT(DISTINCT r.gar_id)  AS total_asistentes,
        COUNT(DISTINCT e.gae_id)  AS total_encuestas,
        ROUND(COUNT(DISTINCT e.gae_id) * 100.0 / NULLIF(COUNT(DISTINCT r.gar_id), 0), 1) AS pct_encuesta,
        ROUND(AVG(e.gae_calificacion_promedio), 2)     AS prom_general,
        ROUND(AVG(e.gae_calificacion_facilitador), 2)  AS prom_facilitador,
        ROUND(AVG(e.gae_calificacion_metodologia), 2)  AS prom_metodologia,
        ROUND(AVG(e.gae_calificacion_material), 2)     AS prom_material
    FROM gestion_asistencias_sesiones s
    LEFT JOIN gestion_asistencias_registros r
           ON r.gar_sesion_id = s.gas_id AND r.gar_estado = 'activo'
    LEFT JOIN gestion_asistencias_encuestas e
           ON e.gae_sesion_id = s.gas_id AND e.gae_estado = 'activo'
    WHERE $where_sql
    GROUP BY s.gas_id
    ORDER BY s.gas_fecha_inicio DESC
";

$stmt = $enlace_db->prepare($sql_detalle);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$sesiones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
              <h4 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Reportes y Métricas</h4>
              <small class="text-muted">Gestión Asistencias y Satisfacción</small>
            </div>
            <a href="index.php" class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
          </div>

          <!-- Resumen global -->
          <div class="row g-2 mb-3">
            <?php
            $cards = [
              ['Total', $resumen['total_sesiones'],     'bg-primary text-white'],
              ['Activas', $resumen['sesiones_activas'], 'bg-success text-white'],
              ['Finalizadas', $resumen['sesiones_finalizadas'], 'bg-warning text-dark'],
              ['Cerradas', $resumen['sesiones_cerradas'],    'bg-dark text-white'],
              ['Anuladas', $resumen['sesiones_anuladas'],    'bg-danger text-white'],
              ['Borrador', $resumen['sesiones_borrador'],    'bg-secondary text-white'],
            ];
            foreach ($cards as [$lbl, $val, $cls]):
            ?>
              <div class="col-md-2 col-4">
                <div class="card <?= $cls ?> text-center border-0">
                  <div class="card-body py-2">
                    <div class="h3 mb-0"><?= (int)$val ?></div>
                    <small><?= $lbl ?></small>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Filtros -->
          <div class="card mb-3">
            <div class="card-body py-2">
              <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                  <label class="form-label form-label-sm mb-1">Estado</label>
                  <select name="estado" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach (['borrador','activa','finalizada','cerrada','anulada'] as $e): ?>
                      <option value="<?= $e ?>" <?= $f_estado === $e ? 'selected' : '' ?>><?= ucfirst($e) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label form-label-sm mb-1">Tipo</label>
                  <select name="tipo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($tipos_sesion as $k => $l): ?>
                      <option value="<?= htmlspecialchars($k) ?>" <?= $f_tipo === $k ? 'selected' : '' ?>>
                        <?= htmlspecialchars($l) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label form-label-sm mb-1">Facilitador</label>
                  <input type="text" name="facilitador" class="form-control form-control-sm"
                         value="<?= htmlspecialchars($f_facilitador) ?>">
                </div>
                <div class="col-md-2">
                  <label class="form-label form-label-sm mb-1">Desde</label>
                  <input type="date" name="fecha_ini" class="form-control form-control-sm"
                         value="<?= htmlspecialchars($f_fecha_ini) ?>">
                </div>
                <div class="col-md-2">
                  <label class="form-label form-label-sm mb-1">Hasta</label>
                  <input type="date" name="fecha_fin" class="form-control form-control-sm"
                         value="<?= htmlspecialchars($f_fecha_fin) ?>">
                </div>
                <div class="col-md-2 d-flex gap-1">
                  <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
                  <a href="reportes.php" class="btn btn-sm btn-outline-secondary">Limpiar</a>
                </div>
              </form>
            </div>
          </div>

          <!-- Tabla métricas -->
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <strong>Métricas por Sesión</strong>
              <span class="badge bg-secondary"><?= count($sesiones) ?> sesión(es)</span>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover table-bordered table-striped font-size-11 mb-0">
                  <thead>
                    <tr>
                      <th class="px-1 py-2">Código</th>
                      <th class="px-1 py-2">Sesión</th>
                      <th class="px-1 py-2">Facilitador</th>
                      <th class="px-1 py-2">Fecha</th>
                      <th class="px-1 py-2 text-center">Estado</th>
                      <th class="px-1 py-2 text-center">Asist.</th>
                      <th class="px-1 py-2 text-center">Encuestas</th>
                      <th class="px-1 py-2 text-center">% Resp.</th>
                      <th class="px-1 py-2 text-center">P. Gral.</th>
                      <th class="px-1 py-2 text-center">Facilit.</th>
                      <th class="px-1 py-2 text-center">Metod.</th>
                      <th class="px-1 py-2 text-center">Mat.</th>
                      <th class="px-1 py-2 text-center">Export.</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($sesiones)): ?>
                      <tr>
                        <td colspan="13" class="text-center py-4 text-muted">No hay sesiones.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($sesiones as $s): ?>
                        <tr>
                          <td class="p-1"><code><?= htmlspecialchars($s['gas_codigo']) ?></code></td>
                          <td class="p-1">
                            <a href="sesion_detalle.php?id=<?= (int)$s['gas_id'] ?>">
                              <?= htmlspecialchars(mb_substr($s['gas_nombre'], 0, 30)) ?>
                            </a>
                          </td>
                          <td class="p-1"><?= htmlspecialchars(mb_substr($s['gas_facilitador'], 0, 20)) ?></td>
                          <td class="p-1"><?= date('d/m/Y', strtotime($s['gas_fecha_inicio'])) ?></td>
                          <td class="p-1 text-center">
                            <span class="badge bg-<?= gas_badge_estado($s['gas_estado']) ?>">
                              <?= gas_label_estado($s['gas_estado']) ?>
                            </span>
                          </td>
                          <td class="p-1 text-center"><span class="badge bg-info"><?= (int)$s['total_asistentes'] ?></span></td>
                          <td class="p-1 text-center"><span class="badge bg-success"><?= (int)$s['total_encuestas'] ?></span></td>
                          <td class="p-1 text-center">
                            <?php if ((int)$s['total_asistentes'] > 0): ?>
                              <?= number_format((float)($s['pct_encuesta'] ?? 0), 0) ?>%
                            <?php else: ?>—<?php endif; ?>
                          </td>
                          <td class="p-1 text-center fw-bold"><?= $s['prom_general'] ? number_format((float)$s['prom_general'], 1) : '—' ?></td>
                          <td class="p-1 text-center"><?= $s['prom_facilitador'] ? number_format((float)$s['prom_facilitador'], 1) : '—' ?></td>
                          <td class="p-1 text-center"><?= $s['prom_metodologia'] ? number_format((float)$s['prom_metodologia'], 1) : '—' ?></td>
                          <td class="p-1 text-center"><?= $s['prom_material'] ? number_format((float)$s['prom_material'], 1) : '—' ?></td>
                          <td class="p-1 text-center">
                            <a href="exportar_csv.php?id=<?= (int)$s['gas_id'] ?>&tipo=asistentes"
                               class="btn btn-info btn-icon px-1 py-1" title="CSV Asistentes">
                              <i class="fas fa-users font-size-11"></i>
                            </a>
                            <?php if ((int)$s['total_encuestas'] > 0): ?>
                              <a href="exportar_csv.php?id=<?= (int)$s['gas_id'] ?>&tipo=encuestas"
                                 class="btn btn-success btn-icon px-1 py-1" title="CSV Encuestas">
                                <i class="fas fa-star font-size-11"></i>
                              </a>
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
