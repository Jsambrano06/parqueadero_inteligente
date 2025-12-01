<?php
/**
 * API - REGISTRAR ENTRADA DE VEHÍCULO
 * Asignación automática de puesto con control de concurrencia
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
    
    // Validar datos requeridos
    if (!isset($_POST['tipo_vehiculo']) || !isset($_POST['placa'])) {
        throw new Exception('Datos incompletos');
    }
    
    $tipo_vehiculo = strtolower(trim($_POST['tipo_vehiculo']));
    $placa = strtoupper(trim($_POST['placa']));
    $color = isset($_POST['color']) ? trim($_POST['color']) : null;
    
    // Validar tipo de vehículo
    if (!in_array($tipo_vehiculo, ['moto', 'carro', 'camion'])) {
        throw new Exception('Tipo de vehículo no válido');
    }
    
    // Validar placa con regex obligatorio
    if (!validarPlaca($placa)) {
        throw new Exception('Placa inválida. Debe ser alfanumérica de 4 a 8 caracteres');
    }
    
    // Verificar que la placa no esté ya registrada y activa
    $sql = "SELECT id, puesto_id FROM movimientos 
            WHERE placa = ? AND hora_salida IS NULL";
    $entrada_activa = obtenerFila($sql, [$placa]);
    
    if ($entrada_activa) {
        throw new Exception('La placa ya tiene una entrada activa en el sistema');
    }
    
    // Determinar tipo_id según el tipo de vehículo
    $tipo_map = [
        'moto' => 1,
        'carro' => 2,
        'camion' => 3
    ];
    $tipo_id = $tipo_map[$tipo_vehiculo];
    
    // ASIGNACIÓN AUTOMÁTICA CON CONTROL DE CONCURRENCIA
    $pdo = getConnection();
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    try {
        // Buscar puesto disponible con FOR UPDATE (bloqueo)
        // Orden: por tipo exacto primero, luego por ID ascendente
        $sql = "SELECT p.id, p.codigo, p.tipo_id
                FROM puestos p
                WHERE p.estado = 'libre' 
                  AND p.tipo_id = ?
                ORDER BY p.id ASC
                LIMIT 1
                FOR UPDATE";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tipo_id]);
        $puesto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si no hay puesto del tipo exacto, intentar alternativas
        if (!$puesto) {
            // Regla especial: En un puesto de carro caben dos motos
            if ($tipo_vehiculo === 'moto') {
                // Buscar puestos de carro libres
                $sql = "SELECT p.id, p.codigo, p.tipo_id
                        FROM puestos p
                        WHERE p.estado = 'libre' 
                          AND p.tipo_id = 2
                        ORDER BY p.id ASC
                        LIMIT 1
                        FOR UPDATE";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $puesto = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
        
        if (!$puesto) {
            throw new Exception("No hay puestos disponibles para $tipo_vehiculo");
        }
        
        // Marcar puesto como ocupado
        $sql = "UPDATE puestos SET estado = 'ocupado' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$puesto['id']]);
        
        // Registrar movimiento
        $hora_entrada = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO movimientos 
                (puesto_id, tipo_vehiculo, placa, color, hora_entrada, creado_por) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $puesto['id'],
            $tipo_vehiculo,
            $placa,
            $color,
            $hora_entrada,
            $usuario_id
        ]);
        
        $movimiento_id = $pdo->lastInsertId();
        
        // Confirmar transacción
        $pdo->commit();
        
        // Registrar en auditoría
        registrarAuditoria(
            $usuario_id, 
            "Entrada registrada", 
            "Placa: $placa, Puesto: {$puesto['codigo']}, Tipo: $tipo_vehiculo"
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Entrada registrada correctamente',
            'puesto_id' => $puesto['id'],
            'puesto_codigo' => $puesto['codigo'],
            'movimiento_id' => $movimiento_id,
            'hora_entrada' => date('d/m/Y H:i', strtotime($hora_entrada))
        ]);
        
    } catch (Exception $e) {
        // Revertir transacción
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error en registrar_entrada.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}