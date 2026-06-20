<?php
/**
 * gas_funciones.php — Funciones auxiliares del módulo GAS
 * Compatible con DPS: usa $enlace_db (no $conn)
 * QR: endroid/qr-code v4.x (API con setters, no fluent create())
 */

function gas_generar_token(): string {
    return bin2hex(random_bytes(32));
}

function gas_generar_codigo(mysqli $enlace_db): string {
    $year = (int)date('Y');
    $stmt = $enlace_db->prepare(
        'SELECT COUNT(*) FROM gestion_asistencias_sesiones WHERE YEAR(gas_registro_fecha) = ?'
    );
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return sprintf('SES-%d-%03d', $year, $count + 1);
}

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
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return 'desconocida';
}

function gas_sanitizar(string $valor, int $maxLen = 0): string {
    $clean = htmlspecialchars(strip_tags(trim($valor)), ENT_QUOTES, 'UTF-8');
    if ($maxLen > 0 && mb_strlen($clean) > $maxLen) {
        $clean = mb_substr($clean, 0, $maxLen);
    }
    return $clean;
}

function gas_calcular_promedio(int $t, int $f, int $m, int $ma, int $g): float {
    return round(($t + $f + $m + $ma + $g) / 5, 2);
}

/**
 * Genera y guarda el código QR usando endroid/qr-code v4.x
 *
 * API v4 usa setters (NO el método estático ::create() que es de v5+)
 *
 * Instalación:
 *   cd templates\assets\plugins\QrCode
 *   composer require endroid/qr-code:^4.0
 */
function gas_generar_qr(string $link, string $token): string {
    $autoload = __DIR__ . '/../../../templates/assets/plugins/QrCode/vendor/autoload.php';

    // Fallback para diferentes profundidades de llamada
    if (!file_exists($autoload)) {
        $base     = dirname(__DIR__, 3);
        $autoload = $base . '/templates/assets/plugins/QrCode/vendor/autoload.php';
    }
    if (!file_exists($autoload)) {
        // Librería no instalada — el módulo sigue funcionando sin QR
        return '';
    }

    require_once $autoload;

    $dir = dirname(__DIR__, 2) . '/assets/gas/qr/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $archivo = $dir . $token . '.png';

    try {
        // ── API CORRECTA para endroid/qr-code v4.x ──────────────────────────
        // v4 usa constructores + setters individuales
        // v5+ usa QrCode::create() — NO disponible en v4
        $qrCode = new \Endroid\QrCode\QrCode($link);
        $qrCode->setSize(300);
        $qrCode->setMargin(10);
        $qrCode->setEncoding(
            new \Endroid\QrCode\Encoding\Encoding('UTF-8')
        );
        $qrCode->setErrorCorrectionLevel(
            new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium()
        );
        $qrCode->setForegroundColor(
            new \Endroid\QrCode\Color\Color(26, 60, 107)  // azul DPS #1a3c6b
        );
        $qrCode->setBackgroundColor(
            new \Endroid\QrCode\Color\Color(255, 255, 255)
        );

        $writer = new \Endroid\QrCode\Writer\PngWriter();
        $result = $writer->write($qrCode);
        $result->saveToFile($archivo);

        // Ruta relativa desde templates/ para mostrar en el HTML
        return 'assets/gas/qr/' . $token . '.png';

    } catch (\Throwable $e) {
        // No rompe el flujo — la sesión se guarda aunque falle el QR
        error_log('GAS QR error: ' . $e->getMessage());
        return '';
    }
}

function gas_construir_link(string $token): string {
    return GAS_PUBLIC_URL . '?t=' . urlencode($token);
}

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

function gas_badge_estado(string $estado): string {
    return [
        'borrador'   => 'secondary',
        'activa'     => 'success',
        'finalizada' => 'warning',
        'cerrada'    => 'dark',
        'anulada'    => 'danger',
    ][$estado] ?? 'light';
}

function gas_label_estado(string $estado): string {
    return [
        'borrador'   => 'Borrador',
        'activa'     => 'Activa',
        'finalizada' => 'Finalizada',
        'cerrada'    => 'Cerrada',
        'anulada'    => 'Anulada',
    ][$estado] ?? $estado;
}

function gas_tipos_sesion(): array {
    if (defined('GAS_TIPOS_SESION')) return GAS_TIPOS_SESION;
    return [
        'Formacion'       => 'Formación y Capacitación',
        'Sensibilizacion' => 'Sensibilización',
        'Seguimiento'     => 'Seguimiento y Retroalimentación',
        'Informativo'     => 'Informativo',
        'Induccion'       => 'Inducción',
        'Otro'            => 'Otro',
    ];
}