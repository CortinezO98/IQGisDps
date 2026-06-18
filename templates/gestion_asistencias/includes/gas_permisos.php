<?php
/**
 * gas_permisos.php
 * Guard de acceso para el panel administrativo del módulo GAS.
 * Incluir al INICIO de cada template del panel admin.
 *
 * AJUSTA la lógica de sesión al sistema de permisos de tu DPS real.
 * Esta implementación es genérica y funcional para XAMPP.
 *
 * UBICACIÓN: templates/gestion_asistencias/includes/gas_permisos.php
 */

// Iniciar sesión PHP si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Verificar que el usuario esté autenticado en DPS ─────────────────────
// Ajusta el nombre de la variable de sesión según tu proyecto DPS.
// Ejemplo en DPS: $_SESSION['usuario_id'], $_SESSION['usr_id'], etc.
if (empty($_SESSION['usuario_id']) && empty($_SESSION['usr_id'])) {
    // Redirigir al login del proyecto DPS
    header('Location: ' . (defined('GAS_BASE_URL') ? GAS_BASE_URL : '') . '/templates/login.php');
    exit;
}

// ── Verificar permiso específico del módulo GAS ───────────────────────────
// Ajusta esto al sistema de permisos real de DPS.
// Ejemplo: si DPS guarda permisos en $_SESSION['permisos'] como array:
//   if (!in_array('gestion_asistencias', $_SESSION['permisos'])) { ... }
//
// Si DPS no tiene permisos granulares por módulo, con solo verificar
// que el usuario esté logueado es suficiente para empezar.
// Descomenta el bloque de abajo cuando conectes con el sistema real:

/*
if (!isset($_SESSION['permisos']) || !in_array('gestion_asistencias', $_SESSION['permisos'])) {
    header('Location: ' . GAS_BASE_URL . '/templates/assets/error-page/error-403.php');
    exit;
}
*/

// ── Obtener datos del usuario en sesión ──────────────────────────────────
// Define GAS_USUARIO_SESION para usarlo en los logs de auditoría
if (!defined('GAS_USUARIO_SESION')) {
    $gas_user_key = !empty($_SESSION['usuario_id']) ? 'usuario_id' : 'usr_id';
    define('GAS_USUARIO_SESION', (string)$_SESSION[$gas_user_key]);
}
