<?php
/**
 * templates/gestion_asistencias/exportar_csv.php — CORREGIDO para DPS
 */

$modulo_plataforma = "Gestión Asistencias";
require_once("../../iniciador.php");
require_once(__DIR__ . '/includes/gas_funciones.php');
require_once(__DIR__ . '/includes/gas_permisos.php');

$id   = (int)($_GET['id']   ?? 0);
$tipo = gas_sanitizar($_GET['tipo'] ?? 'asistentes');
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $enlace_db->prepare(
    'SELECT gas_codigo, gas_nombre FROM gestion_asistencias_sesiones WHERE gas_id = ? LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$sesion = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$sesion) { header('Location: index.php'); exit; }

$filename = 'GAS_' . $sesion['gas_codigo'] . '_' . $tipo . '_' . date('Ymd') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
// BOM para Excel con tildes
fputs($out, "\xEF\xBB\xBF");

if ($tipo === 'asistentes') {
    fputcsv($out, [
        '#','Tipo Documento','Número Documento','Nombres','Apellidos',
        'Correo','Celular','Entidad','Cargo',
        'Fecha Asistencia','Encuesta Respondida','Promedio Encuesta'
    ], ';');

    $stmt = $enlace_db->prepare(
        "SELECT r.*,
                CASE WHEN e.gae_id IS NOT NULL THEN 'Sí' ELSE 'No' END AS encuesta_respondida,
                e.gae_calificacion_promedio
         FROM gestion_asistencias_registros r
         LEFT JOIN gestion_asistencias_encuestas e ON e.gae_asistencia_id = r.gar_id
         WHERE r.gar_sesion_id = ? AND r.gar_estado = 'activo'
         ORDER BY r.gar_fecha_asistencia ASC"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as $i => $r) {
        fputcsv($out, [
            $i + 1,
            $r['gar_tipo_documento'],
            $r['gar_numero_documento'],
            $r['gar_nombres'],
            $r['gar_apellidos'],
            $r['gar_correo'],
            $r['gar_celular']   ?? '',
            $r['gar_entidad']   ?? '',
            $r['gar_cargo']     ?? '',
            $r['gar_fecha_asistencia'],
            $r['encuesta_respondida'],
            $r['gae_calificacion_promedio'] ?? '',
        ], ';');
    }

} elseif ($tipo === 'encuestas') {
    fputcsv($out, [
        '#','Documento','Nombres','Apellidos','Entidad',
        'Tema (1-5)','Facilitador (1-5)','Metodología (1-5)',
        'Material (1-5)','General (1-5)','Promedio',
        'Observaciones','Fecha Respuesta'
    ], ';');

    $stmt = $enlace_db->prepare(
        "SELECT e.*, r.gar_numero_documento, r.gar_nombres, r.gar_apellidos, r.gar_entidad
         FROM gestion_asistencias_encuestas e
         INNER JOIN gestion_asistencias_registros r ON r.gar_id = e.gae_asistencia_id
         WHERE e.gae_sesion_id = ? AND e.gae_estado = 'activo'
         ORDER BY e.gae_fecha_respuesta ASC"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as $i => $e) {
        fputcsv($out, [
            $i + 1,
            $e['gar_numero_documento'],
            $e['gar_nombres'],
            $e['gar_apellidos'],
            $e['gar_entidad'] ?? '',
            $e['gae_calificacion_tema'],
            $e['gae_calificacion_facilitador'],
            $e['gae_calificacion_metodologia'],
            $e['gae_calificacion_material'],
            $e['gae_calificacion_general'],
            $e['gae_calificacion_promedio'],
            $e['gae_observacion'] ?? '',
            $e['gae_fecha_respuesta'],
        ], ';');
    }
}

fclose($out);
exit;
