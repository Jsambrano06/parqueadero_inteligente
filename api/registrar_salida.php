<?php
/**
 * API - REGISTRAR SALIDA Y COBRO
 * Registrar salida de vehículo con cálculo automático de cobro
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/funciones.php';
require_once __DIR__ . '/../core/csrf.php';
require_once __DIR__ . '/../core/middleware.php';

// Proteger API - solo empleado
middlewareAPIEmpleado();
verificarMetodo('POST');
verificarAjax();

header('Content-Type: application/json');

try {
    // Verificar CSRF
    verificarCSRFAjax();
    
    $usuario_id = obtenerIdUsuario();
    
    // Validar datos
    if (!isset($_POST['movimiento_id'])) {
        throw new Exception('ID de movimiento no especificado');
    }
    
    $movimiento_id = (int)$_POST['movimiento_id'];
    
    // Obtener movimiento activo
    $sql = "SELECT m.*, p.codigo as puesto_codigo, p.id as puesto_id
            FROM movimientos m
            INNER JOIN puestos p ON m.puesto_id = p.id
            WHERE m.id = ? AND m.hora_salida IS NULL";
    
    $movimiento = obtenerFila($sql, [$movimiento_id]);
    
    if (!$movimiento) {
        throw new Exception('Movimiento no encontrado o ya tiene salida registrada');
    }
    
    // Obtener tarifa del tipo de vehículo
    $tarifa = obtenerTarifa($movimiento['tipo_vehiculo']);
    
    if (!$tarifa) {
        throw new Exception('Tarifa no encontrada para el tipo de vehículo');
    }
    
    // Hora de salida actual
    $hora_salida = date('Y-m-d H:i:s');
    
    // Calcular cobro según reglas obligatorias
    $total_pagar = calcularCobro($movimiento['hora_entrada'], $hora_salida, $tarifa);
    
    // Iniciar transacción
    $pdo = getConnection();
    $pdo->beginTransaction();
    
    try {
        // Actualizar movimiento con hora de salida y total
        $sql = "UPDATE movimientos 
                SET hora_salida = ?, total_pagar = ? 
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$hora_salida, $total_pagar, $movimiento_id]);
        
        // Liberar puesto
        $sql = "UPDATE puestos SET estado = 'libre' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$movimiento['puesto_id']]);
        
        // Confirmar transacción
        $pdo->commit();
        
        // Calcular tiempo total transcurrido
        $entrada = new DateTime($movimiento['hora_entrada']);
        $salida = new DateTime($hora_salida);
        $diferencia = $entrada->diff($salida);
        $tiempo_total = sprintf('%dh %dm', 
            ($diferencia->days * 24 + $diferencia->h), 
            $diferencia->i
        );
        
        // Registrar en auditoría
        registrarAuditoria(
            $usuario_id,
            "Salida registrada",
            "Placa: {$movimiento['placa']}, Puesto: {$movimiento['puesto_codigo']}, Total: $total_pagar"
        );
        
        // Incluir timestamps ISO para frontend
        try {
            $hora_entrada_iso = (new DateTime($movimiento['hora_entrada']))->format(DATE_ATOM);
        } catch (Exception $e) {
            $hora_entrada_iso = $movimiento['hora_entrada'];
        }

        try {
            $hora_salida_iso = (new DateTime($hora_salida))->format(DATE_ATOM);
        } catch (Exception $e) {
            $hora_salida_iso = $hora_salida;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Salida registrada correctamente',
            'movimiento_id' => $movimiento_id,
            'puesto_codigo' => $movimiento['puesto_codigo'],
            'placa' => $movimiento['placa'],
            'tipo_vehiculo' => $movimiento['tipo_vehiculo'],
            'hora_entrada' => formatearFecha($movimiento['hora_entrada']),
            'hora_salida' => formatearFecha($hora_salida),
            'hora_entrada_iso' => $hora_entrada_iso,
            'hora_salida_iso' => $hora_salida_iso,
            'tiempo_total' => $tiempo_total,
            'total_pagar' => $total_pagar
        ]);
        
    } catch (Exception $e) {
        // Revertir transacción
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error en registrar_salida.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}