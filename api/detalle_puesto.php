<?php
/**
 * API - OBTENER DETALLE COMPLETO DE UN PUESTO
 * Información del puesto y movimiento activo si existe
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/funciones.php';
require_once __DIR__ . '/../core/middleware.php';

// Proteger API - solo admin
middlewareAPIAdmin();
verificarMetodo('GET');

header('Content-Type: application/json');

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('ID de puesto no especificado');
    }
    
    $puesto_id = (int)$_GET['id'];
    
    // Obtener información del puesto
    $sql = "SELECT p.*, tp.nombre as tipo_nombre, tp.ancho as tipo_ancho, tp.alto as tipo_alto
            FROM puestos p
            INNER JOIN tipos_puesto tp ON p.tipo_id = tp.id
            WHERE p.id = ?";
    
    $puesto = obtenerFila($sql, [$puesto_id]);
    
    if (!$puesto) {
        throw new Exception('Puesto no encontrado');
    }
    
    // Si está ocupado, obtener información del movimiento activo
    $movimiento = null;
    if ($puesto['estado'] === 'ocupado') {
        $sql = "SELECT m.*, u.nombre as empleado_nombre
                FROM movimientos m
                LEFT JOIN usuarios u ON m.creado_por = u.id
                WHERE m.puesto_id = ? AND m.hora_salida IS NULL
                LIMIT 1";
        
        $movimiento = obtenerFila($sql, [$puesto_id]);
    }
    
    echo json_encode([
        'success' => true,
        'puesto' => $puesto,
        'movimiento' => $movimiento
    ]);
    
} catch (Exception $e) {
    error_log("Error en detalle_puesto.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}