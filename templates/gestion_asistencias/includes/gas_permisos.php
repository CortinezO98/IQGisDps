<?php
/**
 * gas_permisos.php — CORREGIDO para DPS
 *
 * Patrón EXACTO de DPS:
 *   $modulo_plataforma = "Gestión Asistencias";
 *   require_once("../../iniciador.php");   ← esto llama security.php que valida sesión y permisos
 *
 * NO se incluye este archivo directamente.
 * Cada template del módulo lo llama así:
 *
 *   $modulo_plataforma = "Gestión Asistencias";
 *   require_once("../../iniciador.php");
 *
 * Este archivo solo define GAS_USUARIO_SESION para que
 * los templates puedan usarlo en auditoría.
 */

// En este punto iniciador.php ya corrió → APP_SESSION está definido,
// la sesión está iniciada, y el usuario está autenticado.
if (!defined('GAS_USUARIO_SESION')) {
    define('GAS_USUARIO_SESION', (string)($_SESSION[APP_SESSION . '_session_usu_id'] ?? 'sistema'));
}
