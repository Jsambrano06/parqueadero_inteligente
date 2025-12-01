<?php
/**
 * API - CREAR EMPLEADO
 * Crear nuevo usuario con rol empleado
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/funciones.php';
require_once __DIR__ . '/../core/csrf.php';
require_once __DIR__ . '/../core/middleware.php';

// Proteger API - solo admin
middlewareAPIAdmin();
verificarMetodo('POST');
verificarAjax();

header('Content-Type: application/json');

try {
    // Verificar CSRF
    verificarCSRFAjax();
    
    $usuario_id = obtenerIdUsuario();
    
    // Validar datos
    if (!isset($_POST['nombre']) || !isset($_POST['usuario']) || !isset($_POST['clave'])) {
        throw new Exception('Datos incompletos');
    }
    
    $nombre = trim($_POST['nombre']);
    $usuario = trim($_POST['usuario']);
    $clave = $_POST['clave'];
    
    // Validaciones
    if (empty($nombre) || empty($usuario) || empty($clave)) {
        throw new Exception('Todos los campos son obligatorios');
    }
    
    if (strlen($nombre) > 100) {
        throw new Exception('El nombre no puede exceder 100 caracteres');
    }
    
    if (strlen($usuario) > 50) {
        throw new Exception('El usuario no puede exceder 50 caracteres');
    }
    
    if (strlen($clave) < 6) {
        throw new Exception('La contraseña debe tener al menos 6 caracteres');
    }
    
    // Validar que el usuario no exista
    $sql = "SELECT id FROM usuarios WHERE usuario = ?";
    $existe = obtenerFila($sql, [$usuario]);
    
    if ($existe) {
        throw new Exception('El usuario ya existe en el sistema');
    }
    
    // Hash de la contraseña
    $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
    
    // Insertar empleado (rol_id = 2)
    $sql = "INSERT INTO usuarios (nombre, usuario, clave, rol_id, activo) 
            VALUES (?, ?, ?, 2, 1)";
    
    ejecutarConsulta($sql, [$nombre, $usuario, $clave_hash]);
    
    $empleado_id = ultimoId();
    
    // Registrar en auditoría
    registrarAuditoria($usuario_id, "Empleado creado: $usuario", "ID: $empleado_id, Nombre: $nombre");
    
    echo json_encode([
        'success' => true,
        'message' => 'Empleado creado correctamente',
        'empleado_id' => $empleado_id
    ]);
    
} catch (Exception $e) {
    error_log("Error en crear_empleado.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}