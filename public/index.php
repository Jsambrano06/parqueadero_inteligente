<?php
/**
 * PÁGINA PRINCIPAL
 * Redirige según estado de autenticación
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';

iniciarSesion();

// Si está autenticado, redirigir según rol
if (estaAutenticado()) {
    if (esAdmin()) {
        redirigir('/admin/dashboard.php');
    } else {
        redirigir('/empleado/entrada.php');
    }
} else {
    // Si no está autenticado, redirigir al login
    redirigir('/public/login.php');
}