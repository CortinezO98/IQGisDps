<?php
/**
 * gas_funciones.php
 * Funciones auxiliares reutilizables del módulo GAS.
 * No hace echo, no incluye HTML, no tiene lógica de negocio directa.
 *
 * UBICACIÓN en DPS: templates/gestion_asistencias/includes/gas_funciones.php
 */

// Cargar config si no viene del iniciador DPS
if (!defined('GAS_BASE_URL')) {
    require_once __DIR__ . '/../../../gas_config.php';
}

/**
 * Genera un token público seguro de 64 caracteres hexadecimales.
 * Usa random_bytes() — disponible en PHP 7+.
 */
function gas_generar_token(): string {
    return bin2hex(random_bytes(32));
}

/**
 * Genera el código legible de la sesión: SES-YYYY-NNN
 * Ejemplo: SES-2026-001
 *
 * @param mysqli $conn Conexión activa a la BD
 */
function gas_generar_codigo(mysqli $conn): string {
    $year = (int)date('Y');
    $stmt = $conn->prepare(
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
 * Obtiene la IP real del cliente, considerando proxies y CDN.
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
 * Los prepared statements ya evitan SQL injection.
 * Esta función es una capa adicional de higiene visual.
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
 * Genera y guarda la imagen QR de la sesión usando la librería phpqrcode.
 * Si la librería no está disponible, retorna cadena vacía (no bloquea el flujo).
 *
 * @param string $link  URL pública completa de la sesión
 * @param string $token Token de la sesión (usado como nombre de archivo)
 * @return string Ruta relativa al QR generado, o '' si no se pudo generar
 */
function gas_generar_qr(string $link, string $token): string {
    $qrlib = __DIR__ . '/../../../app/functions/phpqrcode/qrlib.php';
    if (!file_exists($qrlib)) {
        return '';
    }
    require_once $qrlib;

    // Directorio de QRs — relativo a la raíz del proyecto
    $dir = __DIR__ . '/../../../assets/gas/qr/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $archivo = $dir . $token . '.png';
    // Parámetros: texto, archivo, nivel de corrección, tamaño pixel, margen
    QRcode::png($link, $archivo, QR_ECLEVEL_M, 8, 2);

    return 'assets/gas/qr/' . $token . '.png';
}

/**
 * Construye el link público completo para una sesión.
 *
 * @param string $token Token de la sesión
 * @return string URL completa
 */
function gas_construir_link(string $token): string {
    return GAS_PUBLIC_URL . '?t=' . urlencode($token);
}

/**
 * Valida que una transición de estado sea legal.
 * Estados: borrador → activa → finalizada → cerrada
 *          borrador|activa → anulada
 *
 * @param string $estadoActual
 * @param string $estadoNuevo
 * @return bool
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
 * Retorna la clase CSS Bootstrap correspondiente a cada estado.
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
