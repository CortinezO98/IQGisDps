<?php
/**
 * templates/gestion_asistencias/reportes.php
 * Dashboard de métricas y reportes del módulo GAS.
 */

require_once __DIR__ . '/../../iniciador.php';
require_once __DIR__ . '/includes/gas_permisos.php';
require_once __DIR__ . '/includes/gas_funciones.php';

// ── Filtros ────────────────────────────────────────────────────────────────
$f_estado      = gas_sanitizar($_GET['estado']      ?? '');
$f_tipo        = gas_sanitizar($_GET['tipo']        ?? '');
$f_facilitador = gas_sanitizar($_GET['facilitador'] ?? '');
$f_fecha_ini   = gas_sanitizar($_GET['fecha_ini']   ?? '');
$f_fecha_fin   = gas_sanitizar($_GET['fecha_fin']   ?? '');

// ── Resumen global ─────────────────────────────────────────────────────────
$sql_resumen = "
    SELECT
        COUNT(*)                                              AS total_sesiones,
        SUM(gas_estado = 'activa')                           AS sesiones_activas,
        SUM(gas_estado = 'finalizada')                       AS sesiones_finalizadas,
        SUM(gas_estado = 'cerrada')                          AS sesiones_cerradas,
        SUM(gas_estado = 'anulada')                          AS sesiones_anuladas,
        SUM(gas_estado = 'borrador')                         AS sesiones_borrador
    FROM gestion_asistencias_sesiones
";
$resumen = $conn->query($sql_resumen)->fetch_assoc();

// ── Métricas por sesión con filtros ──────────────────────────────────────
$where  = ['1=1'];
$params = [];
$types  = '';

if ($f_estado !== '') {
    $where[]  = 's.gas_estado = ?'; $params[] = $f_estado; $types .= 's';
}
if ($f_tipo !== '') {
    $where[]  = 's.gas_tipo_sesion = ?'; $params[] = $f_tipo; $types .= 's';
}
if ($f_facilitador !== '') {
    $where[]  = 's.gas_facilitador LIKE ?'; $params[] = '%' . $f_facilitador . '%'; $types .= 's';
}
if ($f_fecha_ini !== '') {
    $where[]  = 'DATE(s.gas_fecha_inicio) >= ?'; $params[] = $f_fecha_ini; $types .= 's';
}
if ($f_fecha_fin !== '') {
    $where[]  = 'DATE(s.gas_fecha_inicio) <= ?'; $params[] = $f_fecha_fin; $types .= 's';
}

$where_sql = implode(' AND ', $where);

$sql_detalle = "
    SELECT
        s.gas_id, s.gas_codigo, s.gas_nombre, s.gas_tipo_sesion,
        s.gas_facilitador, s.gas_fecha_inicio, s.gas_estado,
        COUNT(DISTINCT r.gar_id)                                         AS total_asistentes,
        COUNT(DISTINCT e.gae_id)                                         AS total_encuestas,
        ROUND(
            COUNT(DISTINCT e.gae_id) * 100.0 / NULLIF(COUNT(DISTINCT r.gar_id), 0)
        , 1)                                                             AS pct_encuesta,
        ROUND(AVG(e.gae_calificacion_promedio), 2)                       AS prom_general,
        ROUND(AVG(e.gae_calificacion_facilitador), 2)                    AS prom_facilitador,
        ROUND(AVG(e.gae_calificacion_metodologia), 2)                    AS prom_metodologia,
        ROUND(AVG(e.gae_calificacion_material), 2)                       AS prom_material
    FROM gestion_asistencias_sesiones s
    LEFT JOIN gestion_asistencias_registros r
           ON r.gar_sesion_id = s.gas_id AND r.gar_estado = 'activo'
    LEFT JOIN gestion_asistencias_encuestas e
           ON e.gae_sesion_id = s.gas_id AND e.gae_estado = 'activo'
    WHERE $where_sql
    GROUP BY s.gas_id
    ORDER BY s.gas_fecha_inicio DESC
";

$stmt = $conn->prepare($sql_detalle);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$sesiones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
          <h4><i class="fas fa-chart-bar me-2 text-primary"></i>Reportes y Métricas GAS</h4>
          <a href="index.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
          </a>
        </div>
      </div>

      <!-- Resumen global -->
      <div class="row g-3 mb-4">
        <div class="col-md-2">
          <div class="card text-center border-0 bg-primary text-white">
            <div class="card-body py-3">
              <div class="h2 mb-0"><?= (int)$resumen['total_sesiones'] ?></div>
              <small>Total Sesiones</small>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <div class="card text-center border-0 bg-success text-white">
            <div class="card-body py-3">
              <div class="h2 mb-0"><?= (int)$resumen['sesiones_activas'] ?></div>
              <small>Activas</small>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <div class="card text-center border-0 bg-warning text-dark">
            <div class="card-body py-3">
              <div class="h2 mb-0"><?= (int)$resumen['sesiones_finalizadas'] ?></div>
              <small>Finalizadas</small>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <div class="card text-center border-0 bg-dark text-white">
            <div class="card-body py-3">
              <div class="h2 mb-0"><?= (int)$resumen['sesiones_cerradas'] ?></div>
              <small>Cerradas</small>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <div class="card text-center border-0 bg-danger text-white">
            <div class="card-body py-3">
              <div class="h2 mb-0"><?= (int)$resumen['sesiones_anuladas'] ?></div>
              <small>Anuladas</small>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <div class="card text-center border-0 bg-secondary text-white">
            <div class="card-body py-3">
              <div class="h2 mb-0"><?= (int)$resumen['sesiones_borrador'] ?></div>
              <small>Borrador</small>
            </div>
          </div>
        </div>
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
                  <option value="<?= $e ?>" <?= $f_estado === $e ? 'selected':'' ?>><?= ucfirst($e) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label form-label-sm mb-1">Tipo</label>
              <select name="tipo" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach (GAS_TIPOS_SESION as $k => $l): ?>
                  <option value="<?= $k ?>" <?= $f_tipo === $k ? 'selected':'' ?>><?= $l ?></option>
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

      <!-- Tabla de métricas por sesión -->
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Métricas por Sesión</h6>
          <span class="badge bg-secondary"><?= count($sesiones) ?> sesiones</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-dark">
                <tr>
                  <th>Código</th>
                  <th>Sesión</th>
                  <th>Facilitador</th>
                  <th>Fecha</th>
                  <th class="text-center">Estado</th>
                  <th class="text-center">Asist.</th>
                  <th class="text-center">Encuestas</th>
                  <th class="text-center">% Resp.</th>
                  <th class="text-center">Prom. Gral.</th>
                  <th class="text-center">Facilit.</th>
                  <th class="text-center">Metod.</th>
                  <th class="text-center">Material</th>
                  <th class="text-center">Exportar</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($sesiones)): ?>
                  <tr><td colspan="13" class="text-center py-4 text-muted">No hay sesiones con los filtros aplicados.</td></tr>
                <?php else: ?>
                  <?php foreach ($sesiones as $s): ?>
                    <tr>
                      <td><code><?= htmlspecialchars($s['gas_codigo']) ?></code></td>
                      <td>
                        <a href="sesion_detalle.php?id=<?= (int)$s['gas_id'] ?>" class="text-decoration-none">
                          <?= htmlspecialchars(mb_substr($s['gas_nombre'], 0, 35)) ?>
                        </a>
                      </td>
                      <td><?= htmlspecialchars(mb_substr($s['gas_facilitador'], 0, 25)) ?></td>
                      <td><?= date('d/m/Y', strtotime($s['gas_fecha_inicio'])) ?></td>
                      <td class="text-center">
                        <span class="badge bg-<?= gas_badge_estado($s['gas_estado']) ?>">
                          <?= gas_label_estado($s['gas_estado']) ?>
                        </span>
                      </td>
                      <td class="text-center"><span class="badge bg-info"><?= (int)$s['total_asistentes'] ?></span></td>
                      <td class="text-center"><span class="badge bg-success"><?= (int)$s['total_encuestas'] ?></span></td>
                      <td class="text-center">
                        <?php if ((int)$s['total_asistentes'] > 0): ?>
                          <div class="progress" style="height:8px; min-width:60px;">
                            <div class="progress-bar bg-<?= ($s['pct_encuesta'] ?? 0) >= 80 ? 'success' : (($s['pct_encuesta'] ?? 0) >= 50 ? 'warning' : 'danger') ?>"
                                 style="width:<?= min(100, (float)($s['pct_encuesta'] ?? 0)) ?>%"></div>
                          </div>
                          <small><?= number_format((float)($s['pct_encuesta'] ?? 0), 0) ?>%</small>
                        <?php else: ?>—<?php endif; ?>
                      </td>
                      <td class="text-center fw-bold <?= ($s['prom_general'] ?? 0) >= 4 ? 'text-success' : (($s['prom_general'] ?? 0) >= 3 ? 'text-warning' : 'text-muted') ?>">
                        <?= $s['prom_general'] ? number_format((float)$s['prom_general'], 1) : '—' ?>
                      </td>
                      <td class="text-center"><?= $s['prom_facilitador'] ? number_format((float)$s['prom_facilitador'], 1) : '—' ?></td>
                      <td class="text-center"><?= $s['prom_metodologia'] ? number_format((float)$s['prom_metodologia'], 1) : '—' ?></td>
                      <td class="text-center"><?= $s['prom_material'] ? number_format((float)$s['prom_material'], 1) : '—' ?></td>
                      <td class="text-center">
                        <div class="btn-group btn-group-sm">
                          <a href="exportar_csv.php?id=<?= (int)$s['gas_id'] ?>&tipo=asistentes"
                             class="btn btn-outline-info" title="Exportar asistentes CSV">
                            <i class="fas fa-users"></i>
                          </a>
                          <?php if ((int)$s['total_encuestas'] > 0): ?>
                          <a href="exportar_csv.php?id=<?= (int)$s['gas_id'] ?>&tipo=encuestas"
                             class="btn btn-outline-success" title="Exportar encuestas CSV">
                            <i class="fas fa-star"></i>
                          </a>
                          <?php endif; ?>
                        </div>
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
