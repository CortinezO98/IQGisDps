<?php
/**
 * gas_funciones.php — CORREGIDO para DPS
 *
 * CAMBIOS vs versión original:
 *  1. Eliminado el bloque "if (!defined('GAS_BASE_URL')) require gas_config.php"
 *     → Las constantes GAS_BASE_URL / GAS_PUBLIC_URL las define config.php de DPS
 *  2. gas_generar_codigo() ahora recibe $enlace_db en lugar de $conn
 *     → DPS usa $enlace_db como nombre de la conexión MySQLi
 *  3. gas_generar_qr() ajusta ruta al QR relativa a la raíz del proyecto DPS
 */

/**
 * Genera un token público seguro de 64 caracteres hexadecimales.
 */
function gas_generar_token(): string {
    return bin2hex(random_bytes(32));
}

/**
 * Genera el código legible de la sesión: SES-YYYY-NNN
 * IMPORTANTE: usa $enlace_db (nombre real en DPS, no $conn)
 *
 * @param mysqli $enlace_db Conexión activa a la BD (variable de DPS)
 */
function gas_generar_codigo(mysqli $enlace_db): string {
    $year = (int)date('Y');
    $stmt = $enlace_db->prepare(
        'SELECT COUNT(*) FROM gestion_asistencias_sesiones
         WHERE YEAR(gas_registro_fecha) = ?'
    );
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return sprintf('SES-%d-%03d', $year, $count + 1);
}

/**
 * Obtiene la IP real del cliente.
 */
function gas_obtener_ip(): string {
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR',
    ];
    foreach ($headers as $h) {
        if (!empty($_SERVER[$h])) {
            $ips = explode(',', $_SERVER[$h]);
            $ip  = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return 'desconocida';
}

/**
 * Sanitiza un string de entrada para evitar XSS.
 * NOTA: validar_input() de DPS usa htmlspecialchars y corrompe IDs numéricos.
 *       Para campos de texto libre usa esta función.
 *       Para IDs numéricos usa siempre (int).
 *
 * @param string $valor
 * @param int    $maxLen Longitud máxima (0 = sin límite)
 */
function gas_sanitizar(string $valor, int $maxLen = 0): string {
    $clean = htmlspecialchars(strip_tags(trim($valor)), ENT_QUOTES, 'UTF-8');
    if ($maxLen > 0 && mb_strlen($clean) > $maxLen) {
        $clean = mb_substr($clean, 0, $maxLen);
    }
    return $clean;
}

/**
 * Calcula el promedio de las 5 calificaciones de la encuesta.
 */
function gas_calcular_promedio(int $t, int $f, int $m, int $ma, int $g): float {
    return round(($t + $f + $m + $ma + $g) / 5, 2);
}

/**
 * Genera y guarda la imagen QR de la sesión usando phpqrcode.
 * Si la librería no está disponible, retorna '' (no bloquea el flujo).
 *
 * @param string $link  URL pública completa de la sesión
 * @param string $token Token de la sesión (nombre del archivo)
 * @return string Ruta relativa al QR generado, o '' si no disponible
 */
function gas_generar_qr(string $link, string $token): string {
    // Ruta desde templates/gestion_asistencias/includes/ → raíz del proyecto
    $qrlib = __DIR__ . '/../../../app/functions/phpqrcode/qrlib.php';
    if (!file_exists($qrlib)) {
        return '';
    }
    require_once $qrlib;

    // Carpeta QR en templates/assets/gas/qr/
    $dir = __DIR__ . '/../../assets/gas/qr/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $archivo = $dir . $token . '.png';
    QRcode::png($link, $archivo, QR_ECLEVEL_M, 8, 2);

    return 'assets/gas/qr/' . $token . '.png';
}

/**
 * Construye el link público completo para una sesión.
 * Usa GAS_PUBLIC_URL definida en config.php de DPS.
 */
function gas_construir_link(string $token): string {
    return GAS_PUBLIC_URL . '?t=' . urlencode($token);
}

/**
 * Valida que una transición de estado sea legal.
 */
function gas_validar_transicion(string $estadoActual, string $estadoNuevo): bool {
    $transiciones = [
        'borrador'   => ['activa', 'anulada'],
        'activa'     => ['finalizada', 'anulada'],
        'finalizada' => ['cerrada'],
        'cerrada'    => [],
        'anulada'    => [],
    ];
    return in_array($estadoNuevo, $transiciones[$estadoActual] ?? [], true);
}

/**
 * Retorna la clase CSS Bootstrap para el badge de cada estado.
 */
function gas_badge_estado(string $estado): string {
    $badges = [
        'borrador'   => 'secondary',
        'activa'     => 'success',
        'finalizada' => 'warning',
        'cerrada'    => 'dark',
        'anulada'    => 'danger',
    ];
    return $badges[$estado] ?? 'light';
}

/**
 * Retorna el label amigable de un estado.
 */
function gas_label_estado(string $estado): string {
    $labels = [
        'borrador'   => 'Borrador',
        'activa'     => 'Activa',
        'finalizada' => 'Finalizada',
        'cerrada'    => 'Cerrada',
        'anulada'    => 'Anulada',
    ];
    return $labels[$estado] ?? $estado;
}

/**
 * Retorna los tipos de sesión disponibles.
 * Si GAS_TIPOS_SESION está definido en config.php de DPS, usa ese.
 * Si no, usa el array por defecto.
 */
function gas_tipos_sesion(): array {
    if (defined('GAS_TIPOS_SESION')) {
        return GAS_TIPOS_SESION;
    }
    return [
        'Formacion'       => 'Formación y Capacitación',
        'Sensibilizacion' => 'Sensibilización',
        'Seguimiento'     => 'Seguimiento y Retroalimentación',
        'Informativo'     => 'Informativo',
        'Induccion'       => 'Inducción',
        'Otro'            => 'Otro',
    ];
}
