<?php
/**
 * API - OBTENER ÚLTIMAS ENTRADAS
 * Lista de últimas entradas activas
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
    // Obtener últimas 10 entradas activas (sin salida)
    $sql = "SELECT m.*, p.codigo as puesto_codigo
            FROM movimientos m
            INNER JOIN puestos p ON m.puesto_id = p.id
            WHERE m.hora_salida IS NULL
            ORDER BY m.hora_entrada DESC
            LIMIT 10";
    
    $entradas = obtenerFilas($sql);
    
    echo json_encode([
        'success' => true,
        'entradas' => $entradas
    ]);
    
} catch (Exception $e) {
    error_log("Error en ultimas_entradas.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener datos'
    ]);
}