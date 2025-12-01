<?php
/**
 * SISTEMA DE AUTENTICACIÓN Y AUTORIZACIÓN
 * Gestión de sesiones, login, logout y verificación de roles
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/funciones.php';

// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 si usas HTTPS
ini_set('session.cookie_samesite', 'Strict');

// Tiempo de expiración de sesión: 20 minutos
define('SESSION_TIMEOUT', 1200);

/**
 * Iniciar sesión segura
 */
function iniciarSesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        
        // Regenerar ID de sesión periódicamente
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 300) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

/**
 * Verificar timeout de sesión
 * @return bool True si la sesión sigue activa
 */
function verificarTimeout() {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            cerrarSesion();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Autenticar usuario
 * @param string $usuario Usuario
 * @param string $clave Contraseña en texto plano
 * @return array|false Datos del usuario o false si falla
 */
function autenticar($usuario, $clave) {
    $sql = "SELECT u.id, u.nombre, u.usuario, u.clave, u.rol_id, u.activo, r.nombre as rol_nombre 
            FROM usuarios u 
            INNER JOIN roles r ON u.rol_id = r.id 
            WHERE u.usuario = ? AND u.activo = 1";
    
    $user = obtenerFila($sql, [$usuario]);
    
    if (!$user) {
        return false;
    }
    
    // Verificar contraseña con password_verify
    if (!password_verify($clave, $user['clave'])) {
        return false;
    }
    
    // Registrar en auditoría
    registrarAuditoria($user['id'], "Inicio de sesión exitoso", "IP: " . $_SERVER['REMOTE_ADDR']);
    
    return $user;
}

/**
 * Iniciar sesión de usuario
 * @param array $usuario Datos del usuario autenticado
 */
function iniciarSesionUsuario($usuario) {
    // Regenerar ID de sesión por seguridad
    session_regenerate_id(true);
    
    // Guardar datos en sesión
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['usuario_usuario'] = $usuario['usuario'];
    $_SESSION['usuario_rol_id'] = $usuario['rol_id'];
    $_SESSION['usuario_rol_nombre'] = $usuario['rol_nombre'];
    $_SESSION['logged_in'] = true;
    $_SESSION['last_activity'] = time();
    $_SESSION['created'] = time();
}

/**
 * Verificar si el usuario está autenticado
 * @return bool
 */
function estaAutenticado() {
    iniciarSesion();
    
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }
    
    return verificarTimeout();
}

/**
 * Verificar si el usuario es administrador
 * @return bool
 */
function esAdmin() {
    return estaAutenticado() && $_SESSION['usuario_rol_id'] == 1;
}

/**
 * Verificar si el usuario es empleado
 * @return bool
 */
function esEmpleado() {
    return estaAutenticado() && $_SESSION['usuario_rol_id'] == 2;
}

/**
 * Obtener ID del usuario actual
 * @return int|null
 */
function obtenerIdUsuario() {
    return isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;
}

/**
 * Obtener nombre del usuario actual
 * @return string|null
 */
function obtenerNombreUsuario() {
    return isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : null;
}

/**
 * Obtener rol del usuario actual
 * @return string|null
 */
function obtenerRolUsuario() {
    return isset($_SESSION['usuario_rol_nombre']) ? $_SESSION['usuario_rol_nombre'] : null;
}

/**
 * Cerrar sesión
 */
function cerrarSesion() {
    iniciarSesion();
    
    $usuario_id = obtenerIdUsuario();
    if ($usuario_id) {
        registrarAuditoria($usuario_id, "Cierre de sesión", "IP: " . $_SERVER['REMOTE_ADDR']);
    }
    
    // Limpiar todas las variables de sesión
    $_SESSION = array();
    
    // Destruir cookie de sesión
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destruir sesión
    session_destroy();
}

/**
 * Requerir autenticación
 * Redirige al login si no está autenticado
 */
function requerirAutenticacion() {
    if (!estaAutenticado()) {
        redirigir('/public/login.php');
    }
}

/**
 * Requerir rol de administrador
 * Redirige si no es admin
 */
function requerirAdmin() {
    requerirAutenticacion();
    
    if (!esAdmin()) {
        mensajeError("No tienes permisos para acceder a esta sección.");
        redirigir('/empleado/entrada.php');
    }
}

/**
 * Requerir rol de empleado
 * Redirige si no es empleado
 */
function requerirEmpleado() {
    requerirAutenticacion();
    
    if (!esEmpleado()) {
        mensajeError("No tienes permisos para acceder a esta sección.");
        redirigir('/admin/dashboard.php');
    }
}

/**
 * Crear nuevo usuario
 * @param string $nombre
 * @param string $usuario
 * @param string $clave
 * @param int $rol_id
 * @return bool
 */
function crearUsuario($nombre, $usuario, $clave, $rol_id) {
    // Verificar que el usuario no exista
    $sql = "SELECT id FROM usuarios WHERE usuario = ?";
    $existe = obtenerFila($sql, [$usuario]);
    
    if ($existe) {
        return false;
    }
    
    // Hash de la contraseña
    $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
    
    // Insertar usuario
    $sql = "INSERT INTO usuarios (nombre, usuario, clave, rol_id, activo) VALUES (?, ?, ?, ?, 1)";
    
    try {
        ejecutarConsulta($sql, [$nombre, $usuario, $clave_hash, $rol_id]);
        
        $usuario_id = obtenerIdUsuario();
        registrarAuditoria($usuario_id, "Usuario creado: $usuario", "Rol ID: $rol_id");
        
        return true;
    } catch (Exception $e) {
        error_log("Error al crear usuario: " . $e->getMessage());
        return false;
    }
}

/**
 * Resetear contraseña de un usuario
 * @param int $usuario_id
 * @param string $nueva_clave
 * @return bool
 */
function resetearClave($usuario_id, $nueva_clave) {
    $clave_hash = password_hash($nueva_clave, PASSWORD_DEFAULT);
    
    $sql = "UPDATE usuarios SET clave = ? WHERE id = ?";
    
    try {
        ejecutarConsulta($sql, [$clave_hash, $usuario_id]);
        
        $admin_id = obtenerIdUsuario();
        registrarAuditoria($admin_id, "Contraseña reseteada", "Usuario ID: $usuario_id");
        
        return true;
    } catch (Exception $e) {
        error_log("Error al resetear contraseña: " . $e->getMessage());
        return false;
    }
}

/**
 * Cambiar estado de usuario (activar/desactivar)
 * @param int $usuario_id
 * @param bool $activo
 * @return bool
 */
function cambiarEstadoUsuario($usuario_id, $activo) {
    $sql = "UPDATE usuarios SET activo = ? WHERE id = ?";
    
    try {
        ejecutarConsulta($sql, [$activo ? 1 : 0, $usuario_id]);
        
        $admin_id = obtenerIdUsuario();
        $estado = $activo ? "activado" : "desactivado";
        registrarAuditoria($admin_id, "Usuario $estado", "Usuario ID: $usuario_id");
        
        return true;
    } catch (Exception $e) {
        error_log("Error al cambiar estado de usuario: " . $e->getMessage());
        return false;
    }
}

/**
 * Listar todos los usuarios
 * @return array
 */
function listarUsuarios() {
    $sql = "SELECT u.id, u.nombre, u.usuario, u.activo, u.fecha_creacion, r.nombre as rol 
            FROM usuarios u 
            INNER JOIN roles r ON u.rol_id = r.id 
            ORDER BY u.id ASC";
    
    return obtenerFilas($sql);
}