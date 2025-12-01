<?php
/**
 * API - ELIMINAR PUESTO
 * Eliminar un puesto si no está ocupado
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

    if (!isset($_POST['puesto_id'])) {
        throw new Exception('Datos incompletos');
    }

    $puesto_id = (int)$_POST['puesto_id'];

    // Verificar existencia
    $sql = "SELECT id, codigo, estado FROM puestos WHERE id = ?";
    $puesto = obtenerFila($sql, [$puesto_id]);

    if (!$puesto) {
        throw new Exception('Puesto no encontrado');
    }

    // No permitir eliminar si está ocupado
    if ($puesto['estado'] === 'ocupado') {
        throw new Exception('No se puede eliminar un puesto ocupado');
    }

    // Eliminar puesto
    $sql = "DELETE FROM puestos WHERE id = ?";
    ejecutarConsulta($sql, [$puesto_id]);

    // Registrar auditoría y log
    registrarAuditoria($usuario_id, "Puesto eliminado: {$puesto['codigo']}", "ID: $puesto_id");
    registrarLogMapa($usuario_id, "Puesto eliminado: {$puesto['codigo']}");

    echo json_encode([
        'success' => true,
        'message' => 'Puesto eliminado correctamente'
    ]);

} catch (Exception $e) {
    error_log("Error en eliminar_puesto.php: " . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
