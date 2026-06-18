<?php
    // Calidad - Calculadora Muestral | Eliminar Calculadora
    $modulo_plataforma = "Calidad-Calculadora Muestral";
    require_once("../../iniciador.php");
    $url_fichero = pathinfo(__FILE__, PATHINFO_FILENAME);

    /*VARIABLES*/
    $title    = "Calidad";
    $subtitle = "Calculadora Muestral | Eliminar Calculadora";
    $permisos_usuario = $_SESSION[APP_SESSION . '_session_modulos'][$modulo_plataforma] ?? null;

    // Solo Administradores pueden acceder
    if ($permisos_usuario !== "Administrador") {
        header("Location: cmuestral?pagina=1&id=null");
        exit;
    }

    $id_registro     = validar_input(base64_decode($_GET['reg'] ?? ''));
    $pagina          = validar_input($_GET['pagina'] ?? '1');
    $filtro          = validar_input($_GET['id'] ?? 'null');
    $url_salir       = "cmuestral?pagina={$pagina}&id={$filtro}";
    $respuesta_accion = '';
    $flag_session    = APP_SESSION . 'calculadora_eliminada_' . $id_registro;

    if ($id_registro === '') {
        header("Location: {$url_salir}");
        exit;
    }

    // Consultar datos de la calculadora para mostrar en la confirmación
    $consulta_string = "SELECT `cm_id`, `cm_nombre`, `cm_registro_fecha` FROM `gestion_calidad_cmuestral` WHERE `cm_id`=?";
    $consulta_registros = $enlace_db->prepare($consulta_string);
    $consulta_registros->bind_param("s", $id_registro);
    $consulta_registros->execute();
    $resultado_calculadora = $consulta_registros->get_result()->fetch_all(MYSQLI_NUM);
    $consulta_registros->close();

    if (empty($resultado_calculadora)) {
        header("Location: {$url_salir}");
        exit;
    }

    // Contar registros relacionados para informar al usuario
    $conteo = [];

    $q = $enlace_db->prepare("SELECT COUNT(*) FROM `gestion_calidad_cmuestral_segmento` WHERE `cms_calculadora`=?");
    $q->bind_param("s", $id_registro); $q->execute();
    $conteo['segmentos'] = $q->get_result()->fetch_row()[0]; $q->close();

    $q = $enlace_db->prepare("SELECT COUNT(*) FROM `gestion_calidad_cmuestral_mensual` WHERE `cmm_calculadora`=?");
    $q->bind_param("s", $id_registro); $q->execute();
    $conteo['periodos'] = $q->get_result()->fetch_row()[0]; $q->close();

    $q = $enlace_db->prepare("SELECT COUNT(*) FROM `gestion_calidad_cmuestral_transacciones` WHERE `gcmt_calculadora`=?");
    $q->bind_param("s", $id_registro); $q->execute();
    $conteo['transacciones'] = $q->get_result()->fetch_row()[0]; $q->close();

    $q = $enlace_db->prepare("SELECT COUNT(*) FROM `gestion_calidad_cmuestral_muestras` WHERE `cmm_calculadora`=?");
    $q->bind_param("s", $id_registro); $q->execute();
    $conteo['muestras'] = $q->get_result()->fetch_row()[0]; $q->close();

    // Procesamiento del formulario de eliminación
    if (isset($_POST["eliminar_registro"])) {
        $confirmacion = validar_input($_POST['confirmacion'] ?? '');

        if (($_SESSION[$flag_session] ?? 0) != 1) {
            if ($confirmacion !== $resultado_calculadora[0][1]) {
                $respuesta_accion = "alertButton('error', 'Error', 'El nombre ingresado no coincide. Por favor escriba el nombre exacto de la calculadora para confirmar.');";
            } else {
                $enlace_db->begin_transaction();
                $ok = true;

                // 1. Eliminar muestras
                $s = $enlace_db->prepare("DELETE FROM `gestion_calidad_cmuestral_muestras` WHERE `cmm_calculadora`=?");
                $s->bind_param("s", $id_registro);
                if (!$s->execute()) { $ok = false; error_log("Error eliminar muestras: " . $s->error); }
                $s->close();

                // 2. Eliminar transacciones
                if ($ok) {
                    $s = $enlace_db->prepare("DELETE FROM `gestion_calidad_cmuestral_transacciones` WHERE `gcmt_calculadora`=?");
                    $s->bind_param("s", $id_registro);
                    if (!$s->execute()) { $ok = false; error_log("Error eliminar transacciones: " . $s->error); }
                    $s->close();
                }

                // 3. Eliminar configuración mensual/semanas
                if ($ok) {
                    $s = $enlace_db->prepare("DELETE FROM `gestion_calidad_cmuestral_mensual` WHERE `cmm_calculadora`=?");
                    $s->bind_param("s", $id_registro);
                    if (!$s->execute()) { $ok = false; error_log("Error eliminar mensual: " . $s->error); }
                    $s->close();
                }

                // 4. Eliminar segmentos
                if ($ok) {
                    $s = $enlace_db->prepare("DELETE FROM `gestion_calidad_cmuestral_segmento` WHERE `cms_calculadora`=?");
                    $s->bind_param("s", $id_registro);
                    if (!$s->execute()) { $ok = false; error_log("Error eliminar segmentos: " . $s->error); }
                    $s->close();
                }

                // 5. Eliminar la calculadora principal
                if ($ok) {
                    $s = $enlace_db->prepare("DELETE FROM `gestion_calidad_cmuestral` WHERE `cm_id`=?");
                    $s->bind_param("s", $id_registro);
                    if (!$s->execute()) { $ok = false; error_log("Error eliminar calculadora: " . $s->error); }
                    $s->close();
                }

                if ($ok) {
                    $enlace_db->commit();

                    // Registrar en log
                    $log_s = $enlace_db->prepare("INSERT INTO `administrador_log` (`clog_log_modulo`, `clog_log_tipo`, `clog_log_accion`, `clog_log_detalle`, `clog_registro_usuario`) VALUES (?,?,?,?,?)");
                    if ($log_s !== false) {
                        $log_modulo  = $modulo_plataforma;
                        $log_tipo    = "eliminar";
                        $log_accion  = "Eliminar calculadora";
                        $log_detalle = "Calculadora eliminada: [" . $resultado_calculadora[0][1] . "] ID=" . $id_registro;
                        $log_usuario = $_SESSION[APP_SESSION . '_session_usu_id'] ?? '';
                        $log_s->bind_param("sssss", $log_modulo, $log_tipo, $log_accion, $log_detalle, $log_usuario);
                        $log_s->execute();
                        $log_s->close();
                    }

                    $_SESSION[$flag_session] = 1;
                    $respuesta_accion = "alertButton('success', 'Calculadora eliminada', 'La calculadora y todos sus registros asociados han sido eliminados.');";
                } else {
                    $enlace_db->rollback();
                    $respuesta_accion = "alertButton('error', 'Error', 'Ocurrió un error al eliminar. Por favor intente nuevamente o contacte a soporte.');";
                }
            }
        } else {
            $respuesta_accion = "alertButton('success', 'Calculadora eliminada', 'La calculadora ya fue eliminada.');";
        }
    }

    $ya_eliminada = ($_SESSION[$flag_session] ?? 0) == 1;
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
          <form name="eliminar_calculadora" action="" method="POST">
          <div class="row justify-content-center">
            <?php if (!empty($respuesta_accion)) { echo "<script type='text/javascript'>".$respuesta_accion."</script>"; } ?>
            <div class="col-lg-6 d-flex flex-column">
              <div class="row flex-grow">
                <div class="col-12 grid-margin stretch-card">
                  <div class="card card-rounded">
                    <div class="card-body">

                      <?php if (!$ya_eliminada): ?>
                        <!-- Nombre de la calculadora -->
                        <div class="col-md-12 mb-2">
                          <p class="fw-bold font-size-13 mb-1">
                            <span class="fas fa-calculator me-1"></span>
                            <?php echo htmlspecialchars($resultado_calculadora[0][1]); ?>
                          </p>
                          <p class="text-muted font-size-11 mb-0">
                            Registrada el <?php echo $resultado_calculadora[0][2]; ?>
                          </p>
                        </div>

                        <hr class="my-2">

                        <!-- Resumen de registros a eliminar -->
                        <div class="col-md-12 mb-3">
                          <p class="font-size-11 fw-bold mb-1">
                            <span class="fas fa-exclamation-triangle text-danger me-1"></span>
                            Se eliminarán permanentemente los siguientes registros:
                          </p>
                          <table class="table table-sm table-bordered font-size-11 mb-0">
                            <tbody>
                              <tr>
                                <td class="py-1 px-2"><span class="fas fa-layer-group me-1 text-secondary"></span> Segmentos</td>
                                <td class="py-1 px-2 text-center fw-bold"><?php echo $conteo['segmentos']; ?></td>
                              </tr>
                              <tr>
                                <td class="py-1 px-2"><span class="fas fa-calendar-alt me-1 text-secondary"></span> Períodos / semanas configurados</td>
                                <td class="py-1 px-2 text-center fw-bold"><?php echo $conteo['periodos']; ?></td>
                              </tr>
                              <tr>
                                <td class="py-1 px-2"><span class="fas fa-list me-1 text-secondary"></span> Transacciones cargadas</td>
                                <td class="py-1 px-2 text-center fw-bold"><?php echo number_format($conteo['transacciones']); ?></td>
                              </tr>
                              <tr>
                                <td class="py-1 px-2"><span class="fas fa-check-double me-1 text-secondary"></span> Muestras seleccionadas</td>
                                <td class="py-1 px-2 text-center fw-bold"><?php echo number_format($conteo['muestras']); ?></td>
                              </tr>
                            </tbody>
                          </table>
                        </div>

                        <!-- Aviso de acción irreversible -->
                        <div class="col-md-12 mb-3">
                          <p class="alert alert-danger p-2 font-size-11 mb-0">
                            <span class="fas fa-exclamation-circle me-1"></span>
                            <strong>Esta acción es irreversible.</strong> Para confirmar, escriba exactamente el nombre de la calculadora en el campo de abajo.
                          </p>
                        </div>

                        <!-- Campo de confirmación por nombre -->
                        <div class="col-md-12 mb-3">
                          <label for="confirmacion" class="m-0 font-size-11 fw-bold">
                            Escriba: <span class="text-danger"><?php echo htmlspecialchars($resultado_calculadora[0][1]); ?></span>
                          </label>
                          <input type="text"
                                 class="form-control form-control-sm mt-1"
                                 name="confirmacion"
                                 id="confirmacion"
                                 placeholder="Nombre exacto de la calculadora"
                                 autocomplete="off"
                                 required>
                        </div>

                        <!-- Botones -->
                        <div class="col-md-12">
                          <div class="form-group mb-0">
                            <button class="btn btn-danger float-end ms-1 font-size-12"
                                    type="submit"
                                    name="eliminar_registro"
                                    onclick="return confirm('¿Está completamente seguro? Esta acción no se puede deshacer.');">
                              <span class="fas fa-trash me-1"></span> Eliminar calculadora
                            </button>
                            <a href="<?php echo $url_salir; ?>"
                               class="btn btn-secondary float-end font-size-12">
                              <span class="fas fa-arrow-left me-1"></span> Cancelar
                            </a>
                          </div>
                        </div>

                      <?php else: ?>
                        <!-- Estado: ya eliminada -->
                        <div class="col-md-12 text-center py-3">
                          <span class="fas fa-check-circle text-success" style="font-size: 2.5rem;"></span>
                          <p class="mt-2 fw-bold font-size-13">Calculadora eliminada exitosamente</p>
                          <p class="text-muted font-size-11">Todos los registros asociados han sido removidos.</p>
                          <a href="<?php echo $url_salir; ?>" class="btn btn-dark mt-2 font-size-12">
                            <span class="fas fa-arrow-left me-1"></span> Volver al listado
                          </a>
                        </div>
                      <?php endif; ?>

                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <?php require_once(ROOT.'includes/_js.php'); ?>
</body>
</html>
