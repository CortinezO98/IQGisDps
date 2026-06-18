<?php
    // Calidad - Calculadora Muestral | Eliminar Aleatoriedad (muestras del día)
    $modulo_plataforma = "Calidad-Calculadora Muestral";
    require_once("../../iniciador.php");
    $url_fichero = pathinfo(__FILE__, PATHINFO_FILENAME);

    /*VARIABLES*/
    $title    = "Calidad";
    $subtitle = "Calculadora Muestral | Configuración - Eliminar Aleatoriedad";
    $permisos_usuario = $_SESSION[APP_SESSION . '_session_modulos'][$modulo_plataforma] ?? null;
    $id_registro      = validar_input(base64_decode($_GET['reg']   ?? ''));
    $fecha_dia        = validar_input(base64_decode($_GET['fecha'] ?? ''));
    $mes_calculadora  = validar_input($_GET['date'] ?? '');
    $url_salir        = "cmuestral_configurar?reg=" . base64_encode($id_registro) . "&date=" . urlencode($mes_calculadora);
    $respuesta_accion = '';

    // Solo Administrador y Gestor pueden eliminar la aleatoriedad
    if ($permisos_usuario !== "Administrador" && $permisos_usuario !== "Gestor") {
        header("Location: {$url_salir}");
        exit;
    }

    if ($id_registro === '' || $mes_calculadora === '' || $fecha_dia === '') {
        header("Location: {$url_salir}");
        exit;
    }

    // Contar muestras existentes para informar al usuario
    $q = $enlace_db->prepare("SELECT COUNT(*) FROM `gestion_calidad_cmuestral_muestras` WHERE `cmm_calculadora`=? AND `cmm_mes`=? AND `cmm_fecha`=?");
    $q->bind_param("sss", $id_registro, $mes_calculadora, $fecha_dia);
    $q->execute();
    $total_muestras = $q->get_result()->fetch_row()[0];
    $q->close();

    // Contar transacciones que quedan (para que el usuario sepa que se conservan)
    $q2 = $enlace_db->prepare("SELECT COUNT(*) FROM `gestion_calidad_cmuestral_transacciones` WHERE `gcmt_calculadora`=? AND `gcmt_mes`=? AND `gcmt_fecha`=?");
    $q2->bind_param("sss", $id_registro, $mes_calculadora, $fecha_dia);
    $q2->execute();
    $total_transacciones = $q2->get_result()->fetch_row()[0];
    $q2->close();

    $flag_session = APP_SESSION . 'aleatoriedad_eliminada_' . $id_registro . '_' . str_replace(['-', ' '], '_', $fecha_dia);

    if (isset($_POST["eliminar_aleatoriedad"])) {
        if (($_SESSION[$flag_session] ?? 0) != 1) {

            // Eliminar SOLO las muestras (aleatoriedad) del día — las transacciones se conservan
            $s = $enlace_db->prepare("DELETE FROM `gestion_calidad_cmuestral_muestras` WHERE `cmm_calculadora`=? AND `cmm_mes`=? AND `cmm_fecha`=?");
            $s->bind_param("sss", $id_registro, $mes_calculadora, $fecha_dia);

            if ($s->execute()) {
                $s->close();

                // Restaurar estado de las transacciones a 'seleccionable' para que se puedan re-sortear
                $upd = $enlace_db->prepare("UPDATE `gestion_calidad_cmuestral_transacciones` SET `gcmt_estado`='seleccionable' WHERE `gcmt_calculadora`=? AND `gcmt_mes`=? AND `gcmt_fecha`=? AND `gcmt_estado`='auditoria'");
                $upd->bind_param("sss", $id_registro, $mes_calculadora, $fecha_dia);
                $upd->execute();
                $upd->close();

                // Registrar en log
                $log_s = $enlace_db->prepare("INSERT INTO `administrador_log` (`clog_log_modulo`, `clog_log_tipo`, `clog_log_accion`, `clog_log_detalle`, `clog_registro_usuario`) VALUES (?,?,?,?,?)");
                if ($log_s !== false) {
                    $log_modulo  = $modulo_plataforma;
                    $log_tipo    = "eliminar";
                    $log_accion  = "Eliminar aleatoriedad";
                    $log_detalle = "Aleatoriedad eliminada para calculadora ID={$id_registro}, mes={$mes_calculadora}, fecha={$fecha_dia}";
                    $log_usuario = $_SESSION[APP_SESSION . '_session_usu_id'] ?? '';
                    $log_s->bind_param("sssss", $log_modulo, $log_tipo, $log_accion, $log_detalle, $log_usuario);
                    $log_s->execute();
                    $log_s->close();
                }

                $_SESSION[$flag_session] = 1;
                // Limpiar flag de cargue para que se pueda volver a cargar base
                unset($_SESSION[APP_SESSION . 'registro_cargue_base_transacciones']);

                $respuesta_accion = "alertButton('success', 'Aleatoriedad eliminada', 'Las muestras aleatorias del día fueron eliminadas. Puede cargar una nueva base o re-sortear.');";
            } else {
                error_log("Error eliminar aleatoriedad: " . $s->error);
                $s->close();
                $respuesta_accion = "alertButton('error', 'Error', 'No se pudo eliminar la aleatoriedad. Intente nuevamente o contacte a soporte.');";
            }
        } else {
            $respuesta_accion = "alertButton('success', 'Aleatoriedad eliminada', 'La aleatoriedad ya fue eliminada para esta fecha.');";
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
          <form name="eliminar_aleatoriedad" action="" method="POST">
          <div class="row justify-content-center">
            <?php if (!empty($respuesta_accion)) { echo "<script type='text/javascript'>".$respuesta_accion."</script>"; } ?>
            <div class="col-lg-6 d-flex flex-column">
              <div class="row flex-grow">
                <div class="col-12 grid-margin stretch-card">
                  <div class="card card-rounded">
                    <div class="card-body">

                      <?php if (!$ya_eliminada): ?>

                        <!-- Resumen de la operación -->
                        <div class="col-md-12 mb-3">
                          <div class="row">
                            <div class="col-md-6">
                              <p class="font-size-11 mb-1"><b>Mes:</b> <?php echo htmlspecialchars($mes_calculadora); ?></p>
                              <p class="font-size-11 mb-0"><b>Fecha:</b> <?php echo htmlspecialchars($fecha_dia); ?></p>
                            </div>
                          </div>
                        </div>

                        <hr class="my-2">

                        <!-- Tabla de impacto -->
                        <div class="col-md-12 mb-3">
                          <p class="font-size-11 fw-bold mb-1">
                            <span class="fas fa-info-circle text-primary me-1"></span>
                            Detalle de la operación:
                          </p>
                          <table class="table table-sm table-bordered font-size-11 mb-0">
                            <tbody>
                              <tr class="table-danger">
                                <td class="py-1 px-2">
                                  <span class="fas fa-random text-danger me-1"></span>
                                  Muestras aleatorias a eliminar
                                </td>
                                <td class="py-1 px-2 text-center fw-bold text-danger"><?php echo number_format($total_muestras); ?></td>
                              </tr>
                              <tr class="table-success">
                                <td class="py-1 px-2">
                                  <span class="fas fa-list text-success me-1"></span>
                                  Transacciones cargadas (se conservan)
                                </td>
                                <td class="py-1 px-2 text-center fw-bold text-success"><?php echo number_format($total_transacciones); ?></td>
                              </tr>
                            </tbody>
                          </table>
                        </div>

                        <!-- Aviso -->
                        <div class="col-md-12 mb-3">
                          <p class="alert alert-warning p-2 font-size-11 mb-0">
                            <span class="fas fa-exclamation-triangle me-1"></span>
                            Se eliminarán <strong>únicamente las muestras aleatorias</strong> generadas para esta fecha. La base de transacciones cargada <strong>se conservará intacta</strong>, permitiéndole cargar una nueva base o re-sortear la aleatoriedad.
                          </p>
                        </div>

                        <!-- Botones -->
                        <div class="col-md-12">
                          <div class="form-group mb-0">
                            <button class="btn btn-warning float-end ms-1 font-size-12"
                                    type="submit"
                                    name="eliminar_aleatoriedad">
                              <span class="fas fa-random me-1"></span> Sí, eliminar aleatoriedad
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
                          <p class="mt-2 fw-bold font-size-13">Aleatoriedad eliminada</p>
                          <p class="text-muted font-size-11">
                            Las muestras aleatorias fueron removidas. Puede regresar y cargar una nueva base para el día <strong><?php echo htmlspecialchars($fecha_dia); ?></strong>.
                          </p>
                          <a href="<?php echo $url_salir; ?>" class="btn btn-dark mt-2 font-size-12">
                            <span class="fas fa-arrow-left me-1"></span> Volver a configuración
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
