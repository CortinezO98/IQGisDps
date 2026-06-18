<?php
  //Validación de permisos del usuario para el módulo
  $modulo_plataforma="Calidad-Calculadora Muestral";
  @set_time_limit(300);
  @ini_set('memory_limit', '256M');
  require_once("../../iniciador.php");
  require_once("../../app/functions/validar_festivos.php");
  $url_fichero=pathinfo(__FILE__, PATHINFO_FILENAME);
  error_reporting(E_ALL);
  ini_set('display_errors', '1');

  /*VARIABLES*/
  $title = "Calidad";
  $subtitle = "Calculadora Muestral | Configuración - Cargar Transacciones";
  $id_registro = (int)trim(base64_decode($_GET['reg'] ?? ''));
  $fecha_dia=validar_input(base64_decode($_GET['fecha'] ?? ''));
  $mes_calculadora=validar_input($_GET['date'] ?? '');
  $url_salir="cmuestral_configurar?reg=".base64_encode($id_registro)."&date=".$mes_calculadora;

  $consulta_string_usuarios="SELECT `usu_id`, `usu_nombres_apellidos`, `usu_fecha_ingreso_piloto`, `usu_fecha_incorporacion` FROM `administrador_usuario` WHERE `usu_estado`='Activo' ORDER BY `usu_nombres_apellidos` ASC";
  $consulta_registros_usuarios = $enlace_db->prepare($consulta_string_usuarios);
  $consulta_registros_usuarios->execute();
  $resultado_registros_usuarios = $consulta_registros_usuarios->get_result()->fetch_all(MYSQLI_NUM);

  for ($i=0; $i < count($resultado_registros_usuarios); $i++) { 
      $usuarios_detalle[$resultado_registros_usuarios[$i][1]]['nombre']=$resultado_registros_usuarios[$i][1];
      $usuarios_detalle[$resultado_registros_usuarios[$i][0]]['fecha_piloto']=$resultado_registros_usuarios[$i][2];
      $usuarios_detalle[$resultado_registros_usuarios[$i][0]]['fecha_ingreso']=$resultado_registros_usuarios[$i][3];
  }

  $consulta_string_segmento="SELECT `cms_id`, `cms_calculadora`, `cms_nombre_segmento`, `cms_peso` FROM `gestion_calidad_cmuestral_segmento` WHERE `cms_calculadora`=? ORDER BY `cms_nombre_segmento` ASC";
  $consulta_registros_segmento = $enlace_db->prepare($consulta_string_segmento);
  $consulta_registros_segmento->bind_param("s", $id_registro);
  $consulta_registros_segmento->execute();
  $resultado_registros_segmento = $consulta_registros_segmento->get_result()->fetch_all(MYSQLI_NUM);

  // FIX: traer la semana que corresponde al día actual (por rango inicio-fin)
  // Esto permite saber el rango exacto de la semana para no repetir agentes en ella.
  $consulta_string_semana="SELECT `cmm_id`, `cmm_calculadora`, `cmm_mes`, `cmm_segmento`, `cmm_total_mes`, `cmm_muestra_calculada`, `cmm_muestra_auditoria`, `cmm_numero_agentes`, `cmm_muestras_agente_mes`, `cmm_muestras_agente_semana`, `cmm_semana_dias`, `cmm_semana_peso`, `cmm_semana_porcentaje`, `cmm_semana_muestras`, `cmm_semana_inicio`, `cmm_semana_fin`, `cmm_muestra_realizada`, `cmm_muestra_recalculada` FROM `gestion_calidad_cmuestral_mensual` WHERE `cmm_calculadora`=? AND `cmm_mes`=? AND `cmm_fecha_dia`=?";

  // Índices del SELECT:
  // [0]=cmm_id, [1]=cmm_calculadora, [2]=cmm_mes, [3]=cmm_segmento, [4]=cmm_total_mes
  // [5]=cmm_muestra_calculada, [6]=cmm_muestra_auditoria, [7]=cmm_numero_agentes
  // [8]=cmm_muestras_agente_mes, [9]=cmm_muestras_agente_semana, [10]=cmm_semana_dias
  // [11]=cmm_semana_peso, [12]=cmm_semana_porcentaje, [13]=cmm_semana_muestras
  // [14]=cmm_semana_inicio, [15]=cmm_semana_fin, [16]=cmm_muestra_realizada, [17]=cmm_muestra_recalculada

  // Buscar la semana cuyo rango contiene la fecha del día actual
  $consulta_string_semana="SELECT `cmm_id`, `cmm_calculadora`, `cmm_mes`, `cmm_segmento`, `cmm_total_mes`, `cmm_muestra_calculada`, `cmm_muestra_auditoria`, `cmm_numero_agentes`, `cmm_muestras_agente_mes`, `cmm_muestras_agente_semana`, `cmm_semana_dias`, `cmm_semana_peso`, `cmm_semana_porcentaje`, `cmm_semana_muestras`, `cmm_semana_inicio`, `cmm_semana_fin`, `cmm_muestra_realizada`, `cmm_muestra_recalculada` FROM `gestion_calidad_cmuestral_mensual` WHERE `cmm_calculadora`=? AND `cmm_mes`=? AND ? BETWEEN `cmm_semana_inicio` AND `cmm_semana_fin` LIMIT 1";
  $consulta_registros_semana = $enlace_db->prepare($consulta_string_semana);
  $consulta_registros_semana->bind_param("sss", $id_registro, $mes_calculadora, $fecha_dia);
  $consulta_registros_semana->execute();
  $resultado_registros_semana = $consulta_registros_semana->get_result()->fetch_all(MYSQLI_NUM);

  error_log("cmuestral_semana: id=" . $id_registro . " mes=" . $mes_calculadora . " fecha=" . $fecha_dia . " filas_encontradas=" . count($resultado_registros_semana));
  if (!empty($resultado_registros_semana)) {
      error_log("cmuestral_semana: segmento=" . ($resultado_registros_semana[0][3] ?? 'NULL') . " inicio=" . ($resultado_registros_semana[0][14] ?? 'NULL') . " fin=" . ($resultado_registros_semana[0][15] ?? 'NULL'));
  }

  // Si no encontró semana por rango, traer todas (fallback al comportamiento original)
  if (empty($resultado_registros_semana)) {
    $consulta_string_semana_fb="SELECT `cmm_id`, `cmm_calculadora`, `cmm_mes`, `cmm_segmento`, `cmm_total_mes`, `cmm_muestra_calculada`, `cmm_muestra_auditoria`, `cmm_numero_agentes`, `cmm_muestras_agente_mes`, `cmm_muestras_agente_semana`, `cmm_semana_dias`, `cmm_semana_peso`, `cmm_semana_porcentaje`, `cmm_semana_muestras`, `cmm_semana_inicio`, `cmm_semana_fin`, `cmm_muestra_realizada`, `cmm_muestra_recalculada` FROM `gestion_calidad_cmuestral_mensual` WHERE `cmm_calculadora`=? AND `cmm_mes`=?";
    $consulta_registros_semana_fb = $enlace_db->prepare($consulta_string_semana_fb);
    $consulta_registros_semana_fb->bind_param("ss", $id_registro, $mes_calculadora);
    $consulta_registros_semana_fb->execute();
    $resultado_registros_semana = $consulta_registros_semana_fb->get_result()->fetch_all(MYSQLI_NUM);
    error_log("cmuestral_semana: FALLBACK filas=" . count($resultado_registros_semana) . " segmento=" . ($resultado_registros_semana[0][3] ?? 'NULL') . " inicio=" . ($resultado_registros_semana[0][14] ?? 'NULL') . " fin=" . ($resultado_registros_semana[0][15] ?? 'NULL'));
  }

  $array_usuarios_seleccionables=array();

  if(isset($_POST["guardar_registro"])){
      $base_transacciones=validar_input($_POST['base_transacciones'] ?? '');
      $muestras = max(1, min(500, (int)($_POST['muestras'] ?? 0)));

      error_log("cmuestral_cargar: muestras_post=" . ($_POST['muestras'] ?? 'VACIO') . " muestras_final=" . $muestras . " fecha=" . $fecha_dia);

      // Consulta de muestras: filtrar SOLO por la semana actual (cmm_mes exacto).
      // Cada semana es independiente — los agentes auditados en S1 pueden volver
      // a salir en S2, S3, etc. Solo se excluyen agentes ya auditados en
      // otros días de la MISMA semana (mismo cmm_mes).
      $consulta_string_muestras="SELECT `cmm_id`, `cmm_calculadora`, `cmm_mes`, `cmm_fecha`, `cmm_segmento`, `cmm_usuario`, `cmm_monitor`, `cmm_muestra_auditoria`, `cmm_muestra_fecha_hora` FROM `gestion_calidad_cmuestral_muestras` WHERE `cmm_calculadora`=? AND `cmm_mes`=? AND `cmm_fecha` != ?";
      $consulta_registros_muestras = $enlace_db->prepare($consulta_string_muestras);
      $consulta_registros_muestras->bind_param("sss", $id_registro, $mes_calculadora, $fecha_dia);
      $consulta_registros_muestras->execute();
      $resultado_registros_muestras = $consulta_registros_muestras->get_result()->fetch_all(MYSQLI_NUM);

      $total_semana=round(count($resultado_registros_usuarios))-count($resultado_registros_muestras);
      $total_diario=round($total_semana/$resultado_registros_semana[0][11]);
      $usuarios_auditado=array();
      for ($i=0; $i < count($resultado_registros_muestras); $i++) { 
        $usuarios_auditado[]=$resultado_registros_muestras[$i][5];
      }

      $id_usuario=$_SESSION[APP_SESSION.'_session_usu_id'];

      if(($_SESSION[APP_SESSION.'registro_cargue_base_transacciones'] ?? 0)!=1){
          if (($_FILES['documento']["error"] ?? 1) > 0) {
              $respuesta_accion = "alertButton('error', 'Error', 'Problemas al cargar el documento');";
          } else {
              $nombre_directorio="storage_temporal/";
              $nombre_archivo=$_FILES['documento']['name'];
              if (move_uploaded_file($_FILES['documento']['tmp_name'], $nombre_directorio.$nombre_archivo)) {
                  $nombre_archivo = $nombre_directorio.$nombre_archivo;

                  if (file_exists($nombre_archivo)){
                      clearstatcache();

                      // Lector nativo XLSX: ZipArchive + XMLReader
                      // 10-50x más rápido que PhpSpreadsheet para archivos grandes.
                      // Un .xlsx es un ZIP que contiene xl/worksheets/sheet1.xml
                      // y xl/sharedStrings.xml con los textos.

                      $filas_xlsx = [];
                      $zip = new ZipArchive();

                      if ($zip->open($nombre_archivo) !== true) {
                          $respuesta_accion = "alertButton('error', 'Error', 'No se pudo abrir el archivo XLSX');";
                      } else {
                          // 1. Leer strings compartidos (sharedStrings.xml)
                          $shared_strings = [];
                          $ss_xml = $zip->getFromName('xl/sharedStrings.xml');
                          if ($ss_xml !== false) {
                              $ss_reader = new XMLReader();
                              $ss_reader->XML($ss_xml);
                              $current_string = '';
                              $in_t = false;
                              while ($ss_reader->read()) {
                                  if ($ss_reader->nodeType === XMLReader::ELEMENT && $ss_reader->localName === 't') {
                                      $in_t = true;
                                      $current_string = '';
                                  } elseif ($ss_reader->nodeType === XMLReader::TEXT && $in_t) {
                                      $current_string .= $ss_reader->value;
                                  } elseif ($ss_reader->nodeType === XMLReader::END_ELEMENT && $ss_reader->localName === 't') {
                                      $in_t = false;
                                  } elseif ($ss_reader->nodeType === XMLReader::END_ELEMENT && $ss_reader->localName === 'si') {
                                      $shared_strings[] = $current_string;
                                      $current_string = '';
                                  }
                              }
                              $ss_reader->close();
                          }

                          // 2. Leer la primera hoja (sheet1.xml)
                          // Detectar nombre real de la hoja desde workbook.xml.rels
                          $sheet_path = 'xl/worksheets/sheet1.xml';
                          $sheet_xml = $zip->getFromName($sheet_path);
                          $zip->close();

                          if ($sheet_xml === false) {
                              $respuesta_accion = "alertButton('error', 'Error', 'No se pudo leer la hoja del archivo XLSX');";
                          } else {
                              $xml_reader = new XMLReader();
                              $xml_reader->XML($sheet_xml);

                              $fila_actual   = [];
                              $col_actual    = '';
                              $tipo_celda    = '';
                              $valor_celda   = '';
                              $num_fila      = 0;
                              $in_v          = false;

                              while ($xml_reader->read()) {
                                  if ($xml_reader->nodeType === XMLReader::ELEMENT) {
                                      if ($xml_reader->localName === 'row') {
                                          $num_fila = (int)$xml_reader->getAttribute('r');
                                          $fila_actual = [];
                                      } elseif ($xml_reader->localName === 'c') {
                                          $ref = $xml_reader->getAttribute('r'); // ej: A2, B2
                                          // Extraer solo la letra de columna
                                          preg_match('/^([A-Z]+)/', $ref, $m);
                                          $col_actual = $m[1] ?? '';
                                          $tipo_celda = $xml_reader->getAttribute('t') ?? '';
                                          $valor_celda = '';
                                          $in_v = false;
                                      } elseif ($xml_reader->localName === 'v') {
                                          $in_v = true;
                                          $valor_celda = '';
                                      }
                                  } elseif ($xml_reader->nodeType === XMLReader::TEXT && $in_v) {
                                      $valor_celda .= $xml_reader->value;
                                  } elseif ($xml_reader->nodeType === XMLReader::END_ELEMENT) {
                                      if ($xml_reader->localName === 'v') {
                                          $in_v = false;
                                          // Resolver valor
                                          if ($tipo_celda === 's') {
                                              // String compartido
                                              $val = $shared_strings[(int)$valor_celda] ?? '';
                                          } elseif ($tipo_celda === 'inlineStr') {
                                              $val = $valor_celda;
                                          } else {
                                              // Número o fecha — guardar raw
                                              $val = $valor_celda;
                                          }
                                          // Solo columnas A-E
                                          if (in_array($col_actual, ['A','B','C','D','E'])) {
                                              $fila_actual[$col_actual] = $val;
                                          }
                                      } elseif ($xml_reader->localName === 'row' && $num_fila >= 2) {
                                          // Solo guardar filas de datos (desde fila 2)
                                          if (!empty($fila_actual)) {
                                              $filas_xlsx[] = $fila_actual;
                                          }
                                      }
                                  }
                              }
                              $xml_reader->close();
                              unset($sheet_xml, $ss_xml, $shared_strings);
                          }
                      }

                      // Función para convertir número serial de Excel a fecha Y-m-d H:i:s
                      $excel_date = function($val) {
                          if (is_numeric($val) && $val > 1) {
                              // Excel epoch: 1900-01-01 = día 1
                              $unix = ($val - 25569) * 86400;
                              return date('Y-m-d H:i:s', (int)$unix);
                          }
                          $t = strtotime($val);
                          return $t ? date('Y-m-d H:i:s', $t) : $val;
                      };

                      $numeroMayorDeFila = count($filas_xlsx) + 1;
                      error_log("cmuestral_cargar: total_filas=" . count($filas_xlsx) . " memoria=" . round(memory_get_usage()/1024/1024,1) . "MB t=" . microtime(true));

                      $control_item=0;
                      $control_errores=0;

                      foreach ($filas_xlsx as $fila_raw) {
                          if ($base_transacciones=='Unificada') {
                              $columna_a = trim((string)($fila_raw['A'] ?? ''));
                              $columna_b = trim((string)($fila_raw['B'] ?? ''));
                              $columna_c = trim((string)($fila_raw['C'] ?? ''));
                              $columna_d = trim((string)($fila_raw['D'] ?? ''));
                              $columna_e = trim((string)($fila_raw['E'] ?? ''));

                              $columna_a = trim(validar_input($columna_a));
                              $columna_b = trim(validar_input($columna_b));
                              $columna_c = trim(validar_input($columna_c));
                              $columna_d = trim(validar_input($columna_d));
                              $columna_e = trim(validar_input($columna_e));

                              if ($columna_a!='' AND $columna_b!='' AND $columna_c!='' AND $columna_d!='' AND $columna_e!='') {
                                $array_data_base[$control_item]['id_transaccion']=$columna_a;
                                $array_data_base[$control_item]['id_agente']=$columna_b;
                                $array_data_base[$control_item]['nombre_agente']=$columna_c;
                                $array_data_base[$control_item]['fecha']=$excel_date($columna_d);
                                $array_data_base[$control_item]['canal']=$columna_e;
                                
                                $temp_fecha_piloto=$usuarios_detalle[$array_data_base[$control_item]['id_agente']]['fecha_piloto'] ?? '';

                                if ($temp_fecha_piloto!="") {
                                    $temp_usuario_estado=1;
                                    $limite_fecha_piloto = date("Y-m-d", strtotime("+ 30 day", strtotime($temp_fecha_piloto)));
                                    if (date('Y-m-d')>$limite_fecha_piloto) {
                                        $fecha_piloto_estado=1;
                                    } else {
                                        $fecha_piloto_estado=0;
                                    }
                                } else {
                                    $temp_usuario_estado=1;
                                    $fecha_piloto_estado=1;
                                }
                                
                                if ($temp_usuario_estado AND $fecha_piloto_estado) {
                                    $array_data_base[$control_item]['estado']='seleccionable';
                                    if (!isset($array_base_seleccionables[$array_data_base[$control_item]['id_agente']])) {
                                        $array_base_seleccionables[$array_data_base[$control_item]['id_agente']]=array();
                                    }
                                    $array_base_seleccionables[$array_data_base[$control_item]['id_agente']][]=$control_item;
                                    $array_usuarios_seleccionables[]=$array_data_base[$control_item]['id_agente'];
                                } elseif(!$temp_usuario_estado){
                                    $array_data_base[$control_item]['estado']='excluido_usuario';
                                } elseif(!$fecha_piloto_estado){
                                    $array_data_base[$control_item]['estado']='excluido_fecha_area';
                                } else {
                                    $array_data_base[$control_item]['estado']='no_seleccionable';
                                }

                                $control_item++;
                              }
                          }
                      }
                      unset($filas_xlsx);

                      $array_usuarios_seleccionables=array_values(array_unique($array_usuarios_seleccionables));
                      error_log("cmuestral_cargar: filas_procesadas=" . $control_item . " agentes_unicos=" . count($array_usuarios_seleccionables) . " usuarios_auditado=" . count($usuarios_auditado) . " muestras=" . $muestras . " t=" . microtime(true));
                      shuffle($array_usuarios_seleccionables);

                      // Sorteo: seleccionar exactamente $muestras agentes distintos
                      // que NO estén ya en $usuarios_auditado (auditados en otros días del mes)
                      $array_usuarios_auditar=array();
                      $control_muestras=0;
                      foreach ($array_usuarios_seleccionables as $agente_id) {
                          if ($control_muestras >= $muestras) break;
                          if (in_array($agente_id, $usuarios_auditado)) continue;
                          if (!isset($array_base_seleccionables[$agente_id])) continue;
                          if (count($array_base_seleccionables[$agente_id]) === 0) continue;

                          shuffle($array_base_seleccionables[$agente_id]);
                          $array_base_auditar[$agente_id][] = $array_base_seleccionables[$agente_id][0];
                          $array_usuarios_auditar[] = $agente_id;
                          $control_muestras++;
                      }

                      error_log("cmuestral_cargar: muestras_sorteadas=" . $control_muestras);

                        // FIX: gcmt_segmento debe ser el ID numérico del segmento, no 'Unificada'
                        $gcmt_segmento_insert = '0';
                        if (!empty($resultado_registros_semana) && isset($resultado_registros_semana[0][3])) {
                            $gcmt_segmento_insert = (string)(int)$resultado_registros_semana[0][3];
                        }

                        $sentencia_insert_data = $enlace_db->prepare("INSERT INTO `gestion_calidad_cmuestral_transacciones`(`gcmt_calculadora`, `gcmt_mes`, `gcmt_fecha`, `gcmt_segmento`, `gcmt_transaccion_id`, `gcmt_campo_1`, `gcmt_campo_2`, `gcmt_campo_3`, `gcmt_campo_4`, `gcmt_campo_5`, `gcmt_campo_6`, `gcmt_campo_7`, `gcmt_campo_8`, `gcmt_campo_9`, `gcmt_campo_10`, `gcmt_estado`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                        $sentencia_insert_data->bind_param('ssssssssssssssss', $id_registro, $mes_calculadora, $fecha_dia, $gcmt_segmento_insert, $gcmt_transaccion_id, $gcmt_campo_1, $gcmt_campo_2, $gcmt_campo_3, $gcmt_campo_4, $gcmt_campo_5, $gcmt_campo_6, $gcmt_campo_7, $gcmt_campo_8, $gcmt_campo_9, $gcmt_campo_10, $gcmt_estado);
                        
                        $control_insert=0;
                        $control_fail=0;
                        $string_fail="";

                        $enlace_db->begin_transaction();
                        for ($i=0; $i < count($array_data_base); $i++) { 
                            $gcmt_transaccion_id=$array_data_base[$i]['id_transaccion'];
                            $gcmt_campo_1=$array_data_base[$i]['id_agente'];
                            $gcmt_campo_2=$array_data_base[$i]['fecha'];
                            $gcmt_campo_3=$array_data_base[$i]['canal'];
                            $gcmt_campo_4='';
                            $gcmt_campo_5='';
                            $gcmt_campo_6='';
                            $gcmt_campo_7='';
                            $gcmt_campo_8='';
                            $gcmt_campo_9='';
                            $gcmt_campo_10='';
                            $gcmt_estado=$array_data_base[$i]['estado'];
                            
                            if ($sentencia_insert_data->execute()) {
                                $control_insert++;
                            } else {
                                $control_fail++;
                                $string_fail.=$gcmt_transaccion_id."\r\n";
                            }
                        }
                        $enlace_db->commit();
                        $sentencia_insert_data->close();
                        error_log("cmuestral_cargar: insert_ok=" . $control_insert . " insert_fail=" . $control_fail . " t=" . microtime(true));
                        $total_insertados = $control_insert + $control_fail;
                        $total_registros  = count($array_data_base);

                        // Guardar datos de muestras ANTES de liberar $array_data_base
                        $muestras_a_insertar = [];
                        for ($i=0; $i < count($array_usuarios_auditar); $i++) {
                            $usr = $array_usuarios_auditar[$i];
                            $idx = $array_base_auditar[$usr][0];
                            $muestras_a_insertar[] = [
                                'usuario'   => $usr,
                                'auditoria' => $array_data_base[$idx]['id_transaccion'],
                                'fecha'     => $array_data_base[$idx]['fecha'],
                            ];
                        }
                        unset($array_data_base);

                        if ($total_insertados == $total_registros) {
                            $consulta_string_log = "INSERT INTO `administrador_log`(`clog_log_modulo`, `clog_log_tipo`, `clog_log_accion`, `clog_log_detalle`, `clog_registro_usuario`) VALUES (?,?,?,?,?)";
                            $log_modulo=$modulo_plataforma;
                            $log_tipo="crear";
                            $log_accion="Crear registro";
                            $log_detalle="Cargue base transacciones [".$base_transacciones."]";
                            $log_usuario=$_SESSION[APP_SESSION.'_session_usu_id'];
                            $consulta_registros_log = $enlace_db->prepare($consulta_string_log);
                            $consulta_registros_log->bind_param("sssss", $log_modulo, $log_tipo, $log_accion, $log_detalle, $log_usuario);
                            $consulta_registros_log->execute();

                            $sentencia_insert_muestras = $enlace_db->prepare("INSERT IGNORE INTO `gestion_calidad_cmuestral_muestras`(`cmm_calculadora`, `cmm_mes`, `cmm_fecha`, `cmm_segmento`, `cmm_usuario`, `cmm_monitor`, `cmm_muestra_auditoria`, `cmm_muestra_fecha_hora`) VALUES (?,?,?,?,?,?,?,?)");
                            $cmm_segmento_insert = $gcmt_segmento_insert;
                            $sentencia_insert_muestras->bind_param('ssssssss', $id_registro, $mes_calculadora, $fecha_dia, $cmm_segmento_insert, $cmm_usuario, $cmm_monitor, $cmm_muestra_auditoria, $cmm_muestra_fecha_hora);

                            foreach ($muestras_a_insertar as $muestra) {
                              $cmm_usuario          = $muestra['usuario'];
                              $cmm_monitor          = '';
                              $cmm_muestra_auditoria  = $muestra['auditoria'];
                              $cmm_muestra_fecha_hora = $muestra['fecha'];
                              if ($cmm_usuario != "" && $cmm_muestra_auditoria != "") {
                                $sentencia_insert_muestras->execute();
                              }
                            }

                            $respuesta_accion = "alertButton('success', 'Registro creado', 'Base cargada exitosamente | Cargado: ".$control_insert." | Error: ".$control_fail."');";
                            $_SESSION[APP_SESSION.'registro_cargue_base_transacciones']=1;

                            $nombre_temporal_control="storage_temporal/CARGAR_FAIL".date('YmdHis').".txt";
                            $archivo_fail = fopen($nombre_temporal_control,'a');
                            fputs($archivo_fail,$string_fail);
                            fclose($archivo_fail);
                        } else {
                            $respuesta_accion = "alertButton('error', 'Error', 'Problemas al cargar base, por favor intente nuevamente');";
                        }
                  } else {
                    $respuesta_accion = "alertButton('error', 'Error', 'Problemas al cargar base, por favor intente nuevamente');";
                  }
              } else {
                $respuesta_accion = "alertButton('error', 'Error', 'Problemas al cargar base, por favor intente nuevamente');";
              }
          }
      } else {
          $respuesta_accion = "alertButton('success', 'Registro creado', 'Registro creado exitosamente');";
      }
  }
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
          <form name="guardar_registro" action="" method="POST" enctype="multipart/form-data">
          <div class="row justify-content-center">
            <?php if (!empty($respuesta_accion)) {echo "<script type='text/javascript'>".$respuesta_accion."</script>";} ?>
            <div class="col-lg-7 d-flex flex-column">
              <div class="row flex-grow">
                <div class="col-12 grid-margin stretch-card">
                  <div class="card card-rounded">
                    <div class="card-body">
                      <?php if(isset($control_errores) && $control_errores>0): ?>
                          <div class="col-md-12">
                              <p class="alert alert-danger p-1 font-size-11">Por favor verifique los siguientes errores:</p>
                              <?php for ($i=0; $i < count($control_errores_detalle); $i++): ?>
                              <p class="alert alert-warning p-1 font-size-11 my-0"><?php echo $control_errores_detalle[$i]; ?></p>
                              <?php endfor; ?>
                          </div>
                      <?php endif; ?>
                      
                      <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                              <label for="mes" class="m-0">Mes</label>
                              <input type="text" class="form-control form-control-sm" name="mes" id="mes" value="<?php echo $mes_calculadora; ?>" readonly required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                              <label for="fecha" class="m-0">Fecha</label>
                              <input type="text" class="form-control form-control-sm" name="fecha" id="fecha" value="<?php echo $fecha_dia; ?>" readonly required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="base_transacciones" class="m-0">Base transacciones</label>
                                <select class="form-control form-control-sm" name="base_transacciones" id="base_transacciones" <?php if(($_SESSION[APP_SESSION.'registro_cargue_base_transacciones'] ?? 0)==1) { echo 'disabled'; } ?> required>
                                    <option value="">Seleccione</option>
                                    <option value="Unificada" <?php if(isset($_POST["guardar_registro"]) AND ($base_transacciones ?? '')=='Unificada'){ echo "selected"; } ?>>Unificada</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                              <label for="muestras" class="m-0">Muestras</label>
                              <input type="number" class="form-control form-control-sm" name="muestras" id="muestras" min="1" max="500" value="<?php echo isset($_POST["muestras"]) ? (int)$_POST["muestras"] : ''; ?>" <?php if(($_SESSION[APP_SESSION.'registro_cargue_base_transacciones'] ?? 0)==1) { echo 'disabled'; } ?> required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="documento" class="my-0">Documento base</label>
                                <input class="form-control form-control-sm custom-file-input" name="documento" id="inputGroupFile01" type="file" <?php if(($_SESSION[APP_SESSION.'registro_cargue_base_transacciones'] ?? 0)==1) { echo 'disabled'; } ?> accept=".xlsx, .XLSX" required>
                            </div>
                        </div>
                      </div>
                      <div class="col-md-12">
                          <div class="form-group">
                              <?php if(($_SESSION[APP_SESSION.'registro_cargue_base_transacciones'] ?? 0)==1): ?>
                                  <a href="<?php echo $url_salir; ?>" class="btn btn-dark float-end">Finalizar</a>
                              <?php else: ?>
                                  <button class="btn btn-success float-end ms-1" type="submit" name="guardar_registro" id="guardar_registro_btn">Cargar transacciones</button>
                                  <button class="btn btn-danger float-end" type="button" onclick="alertButton('cancel', null, null, '<?php echo $url_salir; ?>');">Cancelar</button>
                              <?php endif; ?>
                          </div>
                      </div>
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
  <script type="text/javascript">
      $("#inputGroupFile01").change(function(){
          var valor_opcion = document.getElementById("inputGroupFile01").files[0].name;
          if (valor_opcion!="") {
              document.getElementById('inputGroupFile01label').innerHTML=valor_opcion.substring(0, 25)+"...";
              $("#inputGroupFile01label").addClass("color-verde");
          }
      });
  </script>
</body>
</html>
