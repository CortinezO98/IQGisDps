<?php
/**
 * templates/gestion_asistencias/index.php — CORREGIDO para DPS
 *
 * CORRECCIONES vs versión original:
 *  1. $modulo_plataforma ANTES del require iniciador.php (patrón DPS)
 *  2. require_once("../../iniciador.php") — igual que todos los módulos DPS
 *  3. gas_permisos.php solo define GAS_USUARIO_SESION (no valida sesión de nuevo)
 *  4. $enlace_db en lugar de $conn (nombre real en DPS)
 *  5. ROOT para includes (_head, _navbar, _sidebar, _footer)
 *  6. gas_tipos_sesion() en lugar de la constante GAS_TIPOS_SESION directamente
 */

// ── Patrón EXACTO de DPS para permisos ─────────────────────────────────────
$modulo_plataforma = "Gestión Asistencias";
require_once("../../iniciador.php");
// → iniciador.php carga config.php (define ROOT, URL, APP_SESSION, GAS_BASE_URL, etc.)
// → carga security_index.php → functions.php
// → carga db.php → crea $enlace_db
// → security.php (vía iniciador) valida sesión y permiso del módulo

require_once(__DIR__ . '/includes/gas_funciones.php');
require_once(__DIR__ . '/includes/gas_permisos.php'); // define GAS_USUARIO_SESION

// ── Variables de página (patrón DPS) ───────────────────────────────────────
$url_fichero = pathinfo(__FILE__, PATHINFO_FILENAME);
$title       = "Gestión Asistencias";
$subtitle    = "Sesiones";

// ── Filtros GET ─────────────────────────────────────────────────────────────
$filtro_estado      = gas_sanitizar($_GET['estado']      ?? '');
$filtro_tipo        = gas_sanitizar($_GET['tipo']        ?? '');
$filtro_facilitador = gas_sanitizar($_GET['facilitador'] ?? '');
$filtro_fecha_ini   = gas_sanitizar($_GET['fecha_ini']   ?? '');
$filtro_fecha_fin   = gas_sanitizar($_GET['fecha_fin']   ?? '');

// ── Paginación ──────────────────────────────────────────────────────────────
$por_pagina    = 15;
$pagina_actual = max(1, (int)($_GET['pag'] ?? 1));
$offset        = ($pagina_actual - 1) * $por_pagina;

// ── Construcción dinámica de WHERE ──────────────────────────────────────────
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

// ── Contar total ─────────────────────────────────────────────────────────────
$sql_count = "SELECT COUNT(*) FROM gestion_asistencias_sesiones s WHERE $where_sql";
$stmt = $enlace_db->prepare($sql_count);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($total_registros);
$stmt->fetch();
$stmt->close();
$total_paginas = max(1, ceil($total_registros / $por_pagina));

// ── Query principal ──────────────────────────────────────────────────────────
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
$params_pag = array_merge($params, [$por_pagina, $offset]);
$types_pag  = $types . 'ii';
$stmt = $enlace_db->prepare($sql);
$stmt->bind_param($types_pag, ...$params_pag);
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

          <!-- Cabecera -->
          <div class="row mb-3">
            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div>
                  <h4 class="mb-0"><i class="fas fa-calendar-check me-2 text-primary"></i>Gestión de Asistencias</h4>
                  <small class="text-muted">Sesiones virtuales registradas</small>
                </div>
                <a href="sesion_crear.php" class="btn btn-primary btn-sm">
                  <i class="fas fa-plus me-1"></i> Nueva Sesión
                </a>
              </div>
            </div>
          </div>

          <!-- Alertas -->
          <?php if (isset($_GET['ok'])): ?>
            <div class="alert alert-success alert-dismissible fade show py-2">
              <?php
                $msgs = [
                  'estado_actualizado' => 'Estado de la sesión actualizado correctamente.',
                  'editada'            => 'Sesión actualizada correctamente.',
                ];
                echo htmlspecialchars($msgs[$_GET['ok']] ?? 'Operación realizada correctamente.');
              ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>
          <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show py-2">
              <?php
                $errs = [
                  'transicion_invalida' => 'Cambio de estado no permitido.',
                  'estado_invalido'     => 'Estado destino no válido.',
                  'db_error'            => 'Error de base de datos. Intenta de nuevo.',
                ];
                echo htmlspecialchars($errs[$_GET['error']] ?? 'Ocurrió un error.');
              ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

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
                  <label class="form-label form-label-sm mb-1">Tipo</label>
                  <select name="tipo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($tipos_sesion as $key => $label): ?>
                      <option value="<?= htmlspecialchars($key) ?>"
                              <?= $filtro_tipo === $key ? 'selected' : '' ?>>
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
                  <button type="submit" class="btn btn-sm btn-outline-primary" title="Filtrar">
                    <i class="fas fa-search"></i>
                  </button>
                  <a href="index.php" class="btn btn-sm btn-outline-secondary" title="Limpiar">
                    <i class="fas fa-times"></i>
                  </a>
                </div>
              </form>
            </div>
          </div>

          <!-- Tabla -->
          <div class="card">
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover table-bordered table-striped font-size-11 mb-0">
                  <thead>
                    <tr>
                      <th class="px-1 py-2">Código</th>
                      <th class="px-1 py-2">Nombre</th>
                      <th class="px-1 py-2">Tipo</th>
                      <th class="px-1 py-2">Facilitador</th>
                      <th class="px-1 py-2">Fecha Inicio</th>
                      <th class="px-1 py-2 text-center">Estado</th>
                      <th class="px-1 py-2 text-center">Asist.</th>
                      <th class="px-1 py-2 text-center">Encuestas</th>
                      <th class="px-1 py-2 text-center">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($sesiones)): ?>
                      <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                          <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                          No hay sesiones registradas con los filtros aplicados.
                        </td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($sesiones as $s): ?>
                        <tr>
                          <td class="p-1"><code><?= htmlspecialchars($s['gas_codigo']) ?></code></td>
                          <td class="p-1"><?= htmlspecialchars($s['gas_nombre']) ?></td>
                          <td class="p-1"><?= htmlspecialchars($tipos_sesion[$s['gas_tipo_sesion']] ?? $s['gas_tipo_sesion']) ?></td>
                          <td class="p-1"><?= htmlspecialchars($s['gas_facilitador']) ?></td>
                          <td class="p-1"><?= date('d/m/Y H:i', strtotime($s['gas_fecha_inicio'])) ?></td>
                          <td class="p-1 text-center">
                            <span class="badge bg-<?= gas_badge_estado($s['gas_estado']) ?>">
                              <?= gas_label_estado($s['gas_estado']) ?>
                            </span>
                          </td>
                          <td class="p-1 text-center">
                            <span class="badge bg-info"><?= (int)$s['total_asistentes'] ?></span>
                          </td>
                          <td class="p-1 text-center">
                            <span class="badge bg-<?= (int)$s['total_encuestas'] > 0 ? 'success' : 'secondary' ?>">
                              <?= (int)$s['total_encuestas'] ?>
                            </span>
                          </td>
                          <td class="p-1 text-center">
                            <a href="sesion_detalle.php?id=<?= (int)$s['gas_id'] ?>"
                               class="btn btn-warning btn-icon px-1 py-1" title="Ver detalle">
                              <i class="fas fa-eye font-size-11"></i>
                            </a>
                            <?php if (in_array($s['gas_estado'], ['borrador','activa'])): ?>
                              <a href="sesion_editar.php?id=<?= (int)$s['gas_id'] ?>"
                                 class="btn btn-primary btn-icon px-1 py-1" title="Editar">
                                <i class="fas fa-pen font-size-11"></i>
                              </a>
                            <?php endif; ?>
                            <button class="btn btn-info btn-icon px-1 py-1 btn-copy-link"
                                    data-token="<?= htmlspecialchars($s['gas_token_publico']) ?>"
                                    title="Copiar link público">
                              <i class="fas fa-link font-size-11"></i>
                            </button>
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
                  <?= min($offset + 1, $total_registros) ?>–<?= min($offset + $por_pagina, $total_registros) ?>
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

        </div><!-- /content-wrapper -->
        <?php require_once(ROOT.'includes/_footer.php'); ?>
      </div><!-- /main-panel -->
    </div><!-- /page-body-wrapper -->
  </div><!-- /container-scroller -->
</body>
</html>

<script>
document.querySelectorAll('.btn-copy-link').forEach(btn => {
    btn.addEventListener('click', function() {
        const link = '<?= GAS_PUBLIC_URL ?>?t=' + this.dataset.token;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(link).then(() => {
                const orig = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check font-size-11"></i>';
                setTimeout(() => { this.innerHTML = orig; }, 2000);
            });
        } else {
            prompt('Copia este link:', link);
        }
    });
});
</script>
