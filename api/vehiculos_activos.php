<?php
/**
 * API - LISTAR VEHÍCULOS ACTIVOS
 * Obtener todos los vehículos actualmente en el parqueadero
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
    // Obtener todos los movimientos activos (sin salida)
    $sql = "SELECT m.*, p.codigo as puesto_codigo
            FROM movimientos m
            INNER JOIN puestos p ON m.puesto_id = p.id
            WHERE m.hora_salida IS NULL
            ORDER BY m.hora_entrada DESC";
    
    $vehiculos = obtenerFilas($sql);

    // Añadir campo ISO para hora de entrada en cada registro
    foreach ($vehiculos as &$v) {
        try {
            $dt = new DateTime($v['hora_entrada']);
            $v['hora_entrada_iso'] = $dt->format(DATE_ATOM);
        } catch (Exception $e) {
            $v['hora_entrada_iso'] = $v['hora_entrada'];
        }
    }

    echo json_encode([
        'success' => true,
        'vehiculos' => $vehiculos,
        'total' => count($vehiculos)
    ]);
    
} catch (Exception $e) {
    error_log("Error en vehiculos_activos.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener datos'
    ]);
}