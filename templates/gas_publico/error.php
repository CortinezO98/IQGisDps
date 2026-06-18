<?php
/**
 * templates/gas_publico/error.php
 * Página de errores controlados del módulo GAS.
 * Se incluye desde index.php, procesar.php, etc.
 * Variable requerida: $error_tipo (string)
 */

$mensajes = [
    'token_invalido' => [
        'titulo'  => 'Link Inválido',
        'icono'   => '🔗',
        'msg'     => 'El link que usaste no es válido o ha expirado. Verifica el link que te compartieron.',
        'color'   => '#dc3545',
    ],
    'sesion_cerrada' => [
        'titulo'  => 'Sesión Cerrada',
        'icono'   => '🔒',
        'msg'     => 'Esta sesión ya fue cerrada y no acepta más registros.',
        'color'   => '#343a40',
    ],
    'sesion_anulada' => [
        'titulo'  => 'Sesión Anulada',
        'icono'   => '🚫',
        'msg'     => 'Esta sesión fue anulada. Por favor contacta al organizador para más información.',
        'color'   => '#dc3545',
    ],
    'sesion_no_disponible' => [
        'titulo'  => 'Sesión No Disponible',
        'icono'   => '⏳',
        'msg'     => 'Esta sesión aún no está activa o ya no está disponible para registros.',
        'color'   => '#6c757d',
    ],
    'asistencia_duplicada' => [
        'titulo'  => 'Ya Estás Registrado',
        'icono'   => '✅',
        'msg'     => 'Tu documento ya fue registrado en esta sesión. No es posible registrar asistencia dos veces.',
        'color'   => '#0d6efd',
    ],
    'no_registrado' => [
        'titulo'  => 'Asistencia No Encontrada',
        'icono'   => '❓',
        'msg'     => 'No encontramos un registro de asistencia con tu documento para esta sesión. Asegúrate de haber registrado tu asistencia previamente.',
        'color'   => '#fd7e14',
    ],
    'encuesta_duplicada' => [
        'titulo'  => 'Encuesta Ya Respondida',
        'icono'   => '📋',
        'msg'     => 'Ya respondiste la encuesta de satisfacción para esta sesión. Solo se permite una respuesta por asistente.',
        'color'   => '#6f42c1',
    ],
    'calificacion_invalida' => [
        'titulo'  => 'Calificación Inválida',
        'icono'   => '⚠️',
        'msg'     => 'Una o más calificaciones están fuera del rango permitido (1-5). Por favor intenta de nuevo.',
        'color'   => '#ffc107',
    ],
    'error_generico' => [
        'titulo'  => 'Error Inesperado',
        'icono'   => '⚙️',
        'msg'     => 'Ocurrió un error al procesar tu solicitud. Por favor intenta de nuevo más tarde.',
        'color'   => '#6c757d',
    ],
];

$tipo = $error_tipo ?? 'error_generico';
$info = $mensajes[$tipo] ?? $mensajes['error_generico'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($info['titulo']) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    body { background: #f0f4f8; display: flex; align-items: center; min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
    .gas-card { max-width: 520px; margin: auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.12); }
    .gas-header { padding: 40px 32px; text-align: center; color: white; }
    .gas-body { background: white; padding: 32px; text-align: center; }
    .gas-footer { background: #f8f9fa; padding: 12px 32px; font-size: .8rem; color: #888; text-align: center; }
  </style>
</head>
<body>
<div class="container">
  <div class="gas-card">
    <div class="gas-header" style="background: <?= htmlspecialchars($info['color']) ?>;">
      <div style="font-size: 3rem; margin-bottom: 12px;"><?= $info['icono'] ?></div>
      <h4><?= htmlspecialchars($info['titulo']) ?></h4>
    </div>
    <div class="gas-body">
      <p class="text-muted"><?= htmlspecialchars($info['msg']) ?></p>
      <p class="mt-4">
        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm me-2">
          ← Regresar
        </a>
        <a href="javascript:window.close()" class="btn btn-outline-secondary btn-sm">
          Cerrar
        </a>
      </p>
    </div>
    <div class="gas-footer">Si necesitas ayuda, contacta al organizador de la sesión.</div>
  </div>
</div>
</body>
</html>
