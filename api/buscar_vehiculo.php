<?php
/**
 * API - BUSCAR VEHÍCULO ACTIVO
 * Buscar vehículo por placa o código de puesto
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/funciones.php';
require_once __DIR__ . '/../core/middleware.php';

// Proteger API - empleados autenticados
middlewareAPI();
verificarMetodo('GET');

header('Content-Type: application/json');

try {
    // Validar parámetro de búsqueda
    if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
        throw new Exception('Término de búsqueda no especificado');
    }
    
    $termino = strtoupper(trim($_GET['q']));
    
    // Buscar por placa o por código de puesto
    $sql = "SELECT m.*, p.codigo as puesto_codigo
            FROM movimientos m
            INNER JOIN puestos p ON m.puesto_id = p.id
            WHERE m.hora_salida IS NULL 
              AND (m.placa = ? OR p.codigo = ?)
            LIMIT 1";
    
    $vehiculo = obtenerFila($sql, [$termino, $termino]);
    
    if (!$vehiculo) {
        throw new Exception('No se encontró ningún vehículo activo con esa placa o puesto');
    }
    
    echo json_encode([
        'success' => true,
        'vehiculo' => $vehiculo
    ]);
    
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}