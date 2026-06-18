<?php
/**
 * templates/gestion_asistencias/index.php
 * Listado de sesiones virtuales — Panel Administrativo GAS
 */

require_once __DIR__ . '/../../iniciador.php';           // Conexión $conn del DPS real
require_once __DIR__ . '/includes/gas_permisos.php';
require_once __DIR__ . '/includes/gas_funciones.php';

// ── Filtros GET ────────────────────────────────────────────────────────────
$filtro_estado      = gas_sanitizar($_GET['estado']      ?? '');
$filtro_tipo        = gas_sanitizar($_GET['tipo']        ?? '');
$filtro_facilitador = gas_sanitizar($_GET['facilitador'] ?? '');
$filtro_fecha_ini   = gas_sanitizar($_GET['fecha_ini']   ?? '');
$filtro_fecha_fin   = gas_sanitizar($_GET['fecha_fin']   ?? '');

// ── Paginación ────────────────────────────────────────────────────────────
$por_pagina   = 15;
$pagina_actual = max(1, (int)($_GET['pag'] ?? 1));
$offset        = ($pagina_actual - 1) * $por_pagina;

// ── Query con filtros dinámicos ───────────────────────────────────────────
$where  = ['1=1'];
$params = [];
$types  = '';

if ($filtro_estado !== '') {
    $where[]  = 's.gas_estado = ?';
    $params[] = $filtro_estado;
    $types   .= 's';
}
if ($filtro_tipo !== '') {
    $where[]  = 's.gas_tipo_sesion = ?';
    $params[] = $filtro_tipo;
    $types   .= 's';
}
if ($filtro_facilitador !== '') {
    $where[]  = 's.gas_facilitador LIKE ?';
    $params[] = '%' . $filtro_facilitador . '%';
    $types   .= 's';
}
if ($filtro_fecha_ini !== '') {
    $where[]  = 'DATE(s.gas_fecha_inicio) >= ?';
    $params[] = $filtro_fecha_ini;
    $types   .= 's';
}
if ($filtro_fecha_fin !== '') {
    $where[]  = 'DATE(s.gas_fecha_inicio) <= ?';
    $params[] = $filtro_fecha_fin;
    $types   .= 's';
}

$where_sql = implode(' AND ', $where);

// Contar total para paginación
$sql_count = "SELECT COUNT(*) FROM gestion_asistencias_sesiones s WHERE $where_sql";
$stmt = $conn->prepare($sql_count);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($total_registros);
$stmt->fetch();
$stmt->close();
$total_paginas = max(1, ceil($total_registros / $por_pagina));

// Query principal
$sql = "
    SELECT s.gas_id, s.gas_codigo, s.gas_nombre, s.gas_tipo_sesion,
           s.gas_facilitador, s.gas_fecha_inicio, s.gas_estado,
           s.gas_token_publico,
           COUNT(DISTINCT r.gar_id) AS total_asistentes,
           COUNT(DISTINCT e.gae_id) AS total_encuestas
    FROM gestion_asistencias_sesiones s
    LEFT JOIN gestion_asistencias_registros r
           ON r.gar_sesion_id = s.gas_id AND r.gar_estado = 'activo'
    LEFT JOIN gestion_asistencias_encuestas e
           ON e.gae_sesion_id = s.gas_id AND e.gae_estado = 'activo'
    WHERE $where_sql
    GROUP BY s.gas_id
    ORDER BY s.gas_registro_fecha DESC
    LIMIT ? OFFSET ?
";

$params_pag  = array_merge($params, [$por_pagina, $offset]);
$types_pag   = $types . 'ii';
$stmt = $conn->prepare($sql);
$stmt->bind_param($types_pag, ...$params_pag);
$stmt->execute();
$result   = $stmt->get_result();
$sesiones = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/../../includes/_head.php';
include __DIR__ . '/../../includes/_navbar.php';
include __DIR__ . '/../../includes/_sidebar.php';
?>

<div class="main-content">
  <div class="page-content">
    <div class="container-fluid">

      <!-- Cabecera -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0"><i class="fas fa-calendar-check me-2 text-primary"></i>Gestión de Asistencias y Satisfacción</h4>
            <a href="sesion_crear.php" class="btn btn-primary btn-sm">
              <i class="fas fa-plus me-1"></i> Nueva Sesión
            </a>
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
                  <option value="<?= $e ?>" <?= $filtro_estado === $e ? 'selected' : '' ?>>
                    <?= ucfirst($e) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label form-label-sm mb-1">Tipo de Sesión</label>
              <select name="tipo" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach (GAS_TIPOS_SESION as $key => $label): ?>
                  <option value="<?= $key ?>" <?= $filtro_tipo === $key ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label form-label-sm mb-1">Facilitador</label>
              <input type="text" name="facilitador" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($filtro_facilitador) ?>" placeholder="Buscar...">
            </div>
            <div class="col-md-2">
              <label class="form-label form-label-sm mb-1">Desde</label>
              <input type="date" name="fecha_ini" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($filtro_fecha_ini) ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label form-label-sm mb-1">Hasta</label>
              <input type="date" name="fecha_fin" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($filtro_fecha_fin) ?>">
            </div>
            <div class="col-md-1 d-flex gap-1">
              <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
              <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
            </div>
          </form>
        </div>
      </div>

      <!-- Tabla -->
      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
              <thead class="table-dark">
                <tr>
                  <th>Código</th>
                  <th>Nombre</th>
                  <th>Tipo</th>
                  <th>Facilitador</th>
                  <th>Fecha Inicio</th>
                  <th class="text-center">Estado</th>
                  <th class="text-center">Asistentes</th>
                  <th class="text-center">Encuestas</th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($sesiones)): ?>
                  <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                      <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                      No se encontraron sesiones con los filtros aplicados.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($sesiones as $s): ?>
                    <tr>
                      <td><code><?= htmlspecialchars($s['gas_codigo']) ?></code></td>
                      <td><?= htmlspecialchars($s['gas_nombre']) ?></td>
                      <td><?= htmlspecialchars(GAS_TIPOS_SESION[$s['gas_tipo_sesion']] ?? $s['gas_tipo_sesion']) ?></td>
                      <td><?= htmlspecialchars($s['gas_facilitador']) ?></td>
                      <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($s['gas_fecha_inicio']))) ?></td>
                      <td class="text-center">
                        <span class="badge bg-<?= gas_badge_estado($s['gas_estado']) ?>">
                          <?= gas_label_estado($s['gas_estado']) ?>
                        </span>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-info"><?= (int)$s['total_asistentes'] ?></span>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-<?= $s['total_encuestas'] > 0 ? 'success' : 'light text-dark' ?>">
                          <?= (int)$s['total_encuestas'] ?>
                        </span>
                      </td>
                      <td class="text-center">
                        <div class="btn-group btn-group-sm">
                          <a href="sesion_detalle.php?id=<?= (int)$s['gas_id'] ?>"
                             class="btn btn-outline-primary" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                          </a>
                          <?php if (in_array($s['gas_estado'], ['borrador','activa'])): ?>
                          <a href="sesion_editar.php?id=<?= (int)$s['gas_id'] ?>"
                             class="btn btn-outline-secondary" title="Editar">
                            <i class="fas fa-edit"></i>
                          </a>
                          <?php endif; ?>
                          <button type="button" class="btn btn-outline-info btn-copy-link"
                                  data-token="<?= htmlspecialchars($s['gas_token_publico']) ?>"
                                  title="Copiar link público">
                            <i class="fas fa-link"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
        <div class="card-footer d-flex justify-content-between align-items-center py-2">
          <small class="text-muted">
            Mostrando <?= min($offset + 1, $total_registros) ?>–<?= min($offset + $por_pagina, $total_registros) ?>
            de <?= $total_registros ?> sesiones
          </small>
          <nav>
            <ul class="pagination pagination-sm mb-0">
              <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                <li class="page-item <?= $p === $pagina_actual ? 'active' : '' ?>">
                  <a class="page-link"
                     href="?pag=<?= $p ?>&estado=<?= urlencode($filtro_estado) ?>&tipo=<?= urlencode($filtro_tipo) ?>&facilitador=<?= urlencode($filtro_facilitador) ?>&fecha_ini=<?= urlencode($filtro_fecha_ini) ?>&fecha_fin=<?= urlencode($filtro_fecha_fin) ?>">
                    <?= $p ?>
                  </a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /container -->
  </div><!-- /page-content -->
</div><!-- /main-content -->

<script>
// Copiar link al portapapeles
document.querySelectorAll('.btn-copy-link').forEach(btn => {
    btn.addEventListener('click', function() {
        const token  = this.dataset.token;
        const link   = '<?= GAS_PUBLIC_URL ?>?t=' + token;
        navigator.clipboard.writeText(link).then(() => {
            const orig = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i>';
            this.classList.replace('btn-outline-info', 'btn-success');
            setTimeout(() => {
                this.innerHTML = orig;
                this.classList.replace('btn-success', 'btn-outline-info');
            }, 2000);
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/_footer.php'; ?>
