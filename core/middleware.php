<?php
/**
 * MIDDLEWARE - VERIFICACIONES CENTRALIZADAS
 * Protección de rutas y control de acceso
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/funciones.php';

/**
 * Middleware para páginas públicas (login)
 * Si ya está autenticado, redirige al dashboard correspondiente
 */
function middlewarePublico() {
    iniciarSesion();
    
    if (estaAutenticado()) {
        if (esAdmin()) {
            redirigir('/admin/dashboard.php');
        } else {
            redirigir('/empleado/entrada.php');
        }
    }
}

/**
 * Middleware para páginas de administrador
 * Requiere autenticación y rol admin
 */
function middlewareAdmin() {
    iniciarSesion();
    requerirAdmin();
}

/**
 * Middleware para páginas de empleado
 * Requiere autenticación y rol empleado
 */
function middlewareEmpleado() {
    iniciarSesion();
    requerirEmpleado();
}

/**
 * Middleware para API endpoints
 * Verifica autenticación y retorna respuesta JSON en caso de error
 */
function middlewareAPI() {
    iniciarSesion();
    
    header('Content-Type: application/json');
    
    if (!estaAutenticado()) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado']);
        exit();
    }
}

/**
 * Middleware para API endpoints de admin
 * Verifica autenticación, rol admin y retorna JSON en error
 */
function middlewareAPIAdmin() {
    iniciarSesion();
    
    header('Content-Type: application/json');
    
    if (!estaAutenticado()) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado']);
        exit();
    }
    
    if (!esAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado. Se requiere rol de administrador.']);
        exit();
    }
}

/**
 * Middleware para API endpoints de empleado
 * Verifica autenticación, rol empleado y retorna JSON en error
 */
function middlewareAPIEmpleado() {
    iniciarSesion();
    
    header('Content-Type: application/json');
    
    if (!estaAutenticado()) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado']);
        exit();
    }
    
    if (!esEmpleado()) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado. Se requiere rol de empleado.']);
        exit();
    }
}

/**
 * Verificar método HTTP
 * @param string $metodo GET, POST, PUT, DELETE
 */
function verificarMetodo($metodo) {
    if ($_SERVER['REQUEST_METHOD'] !== $metodo) {
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Método no permitido']);
        exit();
    }
}

/**
 * Verificar que sea petición AJAX
 */
function verificarAjax() {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Solo se permiten peticiones AJAX']);
        exit();
    }
}

/**
 * Prevenir acceso directo a archivos
 * Usado en archivos que solo deben ser incluidos
 */
function prevenirAccesoDirecto() {
    if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
        http_response_code(403);
        die('Acceso directo no permitido');
    }
}

/**
 * Headers de seguridad HTTP
 */
function aplicarHeadersSeguridad() {
    // Prevenir XSS
    header("X-XSS-Protection: 1; mode=block");
    
    // Prevenir clickjacking
    header("X-Frame-Options: SAMEORIGIN");
    
    // Prevenir MIME-sniffing
    header("X-Content-Type-Options: nosniff");
    
    // Referrer policy
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // No cache para páginas privadas
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
}