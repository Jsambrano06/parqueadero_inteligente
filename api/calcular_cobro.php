<?php
/**
 * API - CALCULAR COBRO ESTIMADO
 * Calcular total a pagar sin registrar salida
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/funciones.php';
require_once __DIR__ . '/../core/middleware.php';

// Proteger API - empleados autenticados
middlewareAPI();
verificarMetodo('POST');

header('Content-Type: application/json');

try {
    // Leer datos JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['hora_entrada']) || !isset($data['tipo_vehiculo'])) {
        throw new Exception('Datos incompletos');
    }
    
    $hora_entrada = $data['hora_entrada'];
    $tipo_vehiculo = $data['tipo_vehiculo'];
    
    // Obtener tarifa
    $tarifa = obtenerTarifa($tipo_vehiculo);
    
    if (!$tarifa) {
        throw new Exception('Tarifa no encontrada');
    }
    
    // Calcular cobro con hora actual
    $hora_salida = date('Y-m-d H:i:s');
    $total = calcularCobro($hora_entrada, $hora_salida, $tarifa);
    
    echo json_encode([
        'success' => true,
        'total' => $total
    ]);
    
} catch (Exception $e) {
    error_log("Error en calcular_cobro.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}