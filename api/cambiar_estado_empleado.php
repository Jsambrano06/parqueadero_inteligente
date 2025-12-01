<?php
/**
 * API - CAMBIAR ESTADO DE EMPLEADO
 * Activar o desactivar empleado
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
    
    $admin_id = obtenerIdUsuario();
    
    // Validar datos
    if (!isset($_POST['empleado_id']) || !isset($_POST['activo'])) {
        throw new Exception('Datos incompletos');
    }
    
    $empleado_id = (int)$_POST['empleado_id'];
    $activo = (int)$_POST['activo'];
    
    // Validar que activo sea 0 o 1
    if ($activo !== 0 && $activo !== 1) {
        throw new Exception('Estado inválido');
    }
    
    // Verificar que el empleado existe y es empleado (rol_id = 2)
    $sql = "SELECT id, nombre, usuario, rol_id FROM usuarios WHERE id = ? AND rol_id = 2";
    $empleado = obtenerFila($sql, [$empleado_id]);
    
    if (!$empleado) {
        throw new Exception('Empleado no encontrado');
    }
    
    // Actualizar estado
    $sql = "UPDATE usuarios SET activo = ? WHERE id = ?";
    ejecutarConsulta($sql, [$activo, $empleado_id]);
    
    // Registrar en auditoría
    $estado_texto = $activo ? 'activado' : 'desactivado';
    registrarAuditoria($admin_id, "Empleado $estado_texto", "Empleado: {$empleado['usuario']} (ID: $empleado_id)");
    
    echo json_encode([
        'success' => true,
        'message' => "Empleado $estado_texto correctamente"
    ]);
    
} catch (Exception $e) {
    error_log("Error en cambiar_estado_empleado.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}