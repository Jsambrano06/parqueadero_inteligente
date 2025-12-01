<?php
/**
 * PROTECCIÓN CSRF (Cross-Site Request Forgery)
 * Sistema obligatorio de seguridad
 * Sistema de Parqueadero Inteligente
 */

/**
 * Generar token CSRF único para la sesión
 * @return string Token generado
 */
function generarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Obtener token CSRF actual
 * @return string|null
 */
function obtenerTokenCSRF() {
    return isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : null;
}

/**
 * Validar token CSRF
 * @param string $token Token recibido del formulario
 * @return bool True si el token es válido
 */
function validarTokenCSRF($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Comparación segura contra timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Campo oculto HTML con token CSRF
 * @return string HTML del input hidden
 */
function campoCSRF() {
    $token = generarTokenCSRF();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verificar y validar token CSRF desde POST
 * Detiene la ejecución si el token no es válido
 * @return void
 */
function verificarCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        
        if (!validarTokenCSRF($token)) {
            error_log("Intento de ataque CSRF detectado desde IP: " . $_SERVER['REMOTE_ADDR']);
            http_response_code(403);
            die("Error de seguridad: Token CSRF inválido.");
        }
    }
}

/**
 * Verificar CSRF para peticiones AJAX (JSON)
 * Lee el token desde el header X-CSRF-Token
 * @return bool
 */
function verificarCSRFAjax() {
    $headers = getallheaders();
    $token = isset($headers['X-CSRF-Token']) ? $headers['X-CSRF-Token'] : '';
    
    if (!validarTokenCSRF($token)) {
        error_log("Intento de ataque CSRF (AJAX) detectado desde IP: " . $_SERVER['REMOTE_ADDR']);
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Token CSRF inválido']);
        exit();
    }
    
    return true;
}

/**
 * Regenerar token CSRF
 * Útil después de operaciones críticas
 * @return string Nuevo token
 */
function regenerarTokenCSRF() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}