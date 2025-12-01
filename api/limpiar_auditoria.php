<?php
/**
 * API - LIMPIAR AUDITORÍA ANTIGUA
 * Eliminar registros de auditoría con más de 6 meses
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
    
    // Calcular fecha límite (6 meses atrás)
    $fecha_limite = date('Y-m-d H:i:s', strtotime('-6 months'));
    
    // Contar registros a eliminar
    $sql = "SELECT COUNT(*) as total FROM auditoria WHERE fecha < ?";
    $count = obtenerFila($sql, [$fecha_limite]);
    $total_eliminar = $count['total'];
    
    if ($total_eliminar == 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No hay registros antiguos para eliminar',
            'registros_eliminados' => 0
        ]);
        exit;
    }
    
    // Eliminar registros antiguos
    $sql = "DELETE FROM auditoria WHERE fecha < ?";
    ejecutarConsulta($sql, [$fecha_limite]);
    
    // Registrar la limpieza
    registrarAuditoria($usuario_id, "Limpieza de auditoría", "Eliminados $total_eliminar registros anteriores a $fecha_limite");
    
    echo json_encode([
        'success' => true,
        'message' => 'Auditoría limpiada correctamente',
        'registros_eliminados' => $total_eliminar
    ]);
    
} catch (Exception $e) {
    error_log("Error en limpiar_auditoria.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}