<?php
/**
 * API - LIBERAR PUESTO MANUALMENTE
 * Liberar un puesto ocupado sin registrar cobro (solo admin, casos excepcionales)
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
        throw new Exception('ID de puesto no especificado');
    }
    
    $puesto_id = (int)$_POST['puesto_id'];
    
    // Verificar que el puesto existe y está ocupado
    $sql = "SELECT id, codigo, estado FROM puestos WHERE id = ?";
    $puesto = obtenerFila($sql, [$puesto_id]);
    
    if (!$puesto) {
        throw new Exception('Puesto no encontrado');
    }
    
    if ($puesto['estado'] !== 'ocupado') {
        throw new Exception('El puesto no está ocupado');
    }
    
    // Obtener movimiento activo
    $sql = "SELECT id, placa, tipo_vehiculo, hora_entrada 
            FROM movimientos 
            WHERE puesto_id = ? AND hora_salida IS NULL 
            LIMIT 1";
    $movimiento = obtenerFila($sql, [$puesto_id]);
    
    if (!$movimiento) {
        throw new Exception('No se encontró movimiento activo para este puesto');
    }
    
    // Iniciar transacción
    $pdo = getConnection();
    $pdo->beginTransaction();
    
    try {
        // Actualizar movimiento: marcar salida con total_pagar = 0
        $hora_salida = date('Y-m-d H:i:s');
        $sql = "UPDATE movimientos 
                SET hora_salida = ?, total_pagar = 0 
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$hora_salida, $movimiento['id']]);
        
        // Liberar puesto
        $sql = "UPDATE puestos SET estado = 'libre' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$puesto_id]);
        
        // Confirmar transacción
        $pdo->commit();
        
        // Registrar en auditoría
        $detalles = "Puesto {$puesto['codigo']} liberado manualmente. Placa: {$movimiento['placa']}, Sin cobro registrado.";
        registrarAuditoria($usuario_id, "Puesto liberado manualmente", $detalles);
        
        echo json_encode([
            'success' => true,
            'message' => 'Puesto liberado correctamente'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error en liberar_puesto.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}