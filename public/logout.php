<?php
/**
 * LOGOUT - CERRAR SESIÓN
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';

// Iniciar sesión para poder cerrarla
iniciarSesion();

// Cerrar sesión (registra en auditoría internamente)
cerrarSesion();

// Redirigir al login con mensaje
redirigir('/public/login.php?logout=1');