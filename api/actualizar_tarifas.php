<?php
/**
 * API - ACTUALIZAR TARIFAS
 * Actualizar precios por hora de cada tipo de vehículo
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
    
    // Validar datos
    if (!isset($_POST['tarifa_id']) || !isset($_POST['precio_hora'])) {
        throw new Exception('Datos incompletos');
    }
    
    $tarifa_ids = $_POST['tarifa_id'];
    $precios_hora = $_POST['precio_hora'];
    $tipos_vehiculo = $_POST['tipo_vehiculo'];
    
    if (!is_array($tarifa_ids) || !is_array($precios_hora) || !is_array($tipos_vehiculo)) {
        throw new Exception('Formato de datos inválido');
    }
    
    if (count($tarifa_ids) !== count($precios_hora) || count($tarifa_ids) !== count($tipos_vehiculo)) {
        throw new Exception('Cantidad de datos no coincide');
    }
    
    // Iniciar transacción
    $pdo = getConnection();
    $pdo->beginTransaction();
    
    try {
        $cambios = [];
        
        for ($i = 0; $i < count($tarifa_ids); $i++) {
            $tarifa_id = (int)$tarifa_ids[$i];
            $precio_hora = (float)$precios_hora[$i];
            $tipo_vehiculo = $tipos_vehiculo[$i];
            
            // Validar precio
            if ($precio_hora <= 0) {
                throw new Exception("El precio para {$tipo_vehiculo} debe ser mayor a cero");
            }
            
            // Obtener tarifa anterior
            $sql = "SELECT precio_hora FROM tarifas WHERE id = ?";
            $tarifa_anterior = obtenerFila($sql, [$tarifa_id]);
            
            if (!$tarifa_anterior) {
                throw new Exception("Tarifa ID {$tarifa_id} no encontrada");
            }
            
            // Solo actualizar si hay cambio
            if ($tarifa_anterior['precio_hora'] != $precio_hora) {
                $sql = "UPDATE tarifas SET precio_hora = ? WHERE id = ?";
                ejecutarConsulta($sql, [$precio_hora, $tarifa_id]);
                
                $cambios[] = ucfirst($tipo_vehiculo) . ": " . 
                            formatearMoneda($tarifa_anterior['precio_hora']) . " → " . 
                            formatearMoneda($precio_hora);
            }
        }
        
        // Confirmar transacción
        $pdo->commit();
        
        if (!empty($cambios)) {
            // Registrar en auditoría
            $detalles = implode(", ", $cambios);
            registrarAuditoria($usuario_id, "Tarifas actualizadas", $detalles);
            
            echo json_encode([
                'success' => true,
                'message' => 'Tarifas actualizadas correctamente',
                'cambios' => $cambios
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'No hubo cambios en las tarifas'
            ]);
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error en actualizar_tarifas.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}