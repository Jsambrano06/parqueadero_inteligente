<?php
/**
 * API - CONVERTIR TIPO DE PUESTO
 * Cambiar tipo de un puesto (moto ↔ carro ↔ camión)
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
    if (!isset($_POST['puesto_id']) || !isset($_POST['nuevo_tipo_id'])) {
        throw new Exception('Datos incompletos');
    }
    
    $puesto_id = (int)$_POST['puesto_id'];
    $nuevo_tipo_id = (int)$_POST['nuevo_tipo_id'];
    
    // Verificar que el puesto existe
    $sql = "SELECT p.*, tp.nombre as tipo_nombre, tp.ancho as tipo_ancho 
            FROM puestos p
            INNER JOIN tipos_puesto tp ON p.tipo_id = tp.id
            WHERE p.id = ?";
    $puesto = obtenerFila($sql, [$puesto_id]);
    
    if (!$puesto) {
        throw new Exception('Puesto no encontrado');
    }
    
    // NO permitir convertir puestos ocupados
    if ($puesto['estado'] === 'ocupado') {
        throw new Exception('No se puede convertir un puesto ocupado. Primero debe registrarse la salida del vehículo.');
    }
    
    // Verificar que el nuevo tipo existe
    $sql = "SELECT id, nombre, ancho FROM tipos_puesto WHERE id = ?";
    $nuevo_tipo = obtenerFila($sql, [$nuevo_tipo_id]);
    
    if (!$nuevo_tipo) {
        throw new Exception('Tipo de puesto no válido');
    }
    
    // Verificar que no sea el mismo tipo
    if ($puesto['tipo_id'] == $nuevo_tipo_id) {
        throw new Exception('El puesto ya es de ese tipo');
    }
    
    // Actualizar puesto
    $sql = "UPDATE puestos SET tipo_id = ?, ancho_unidades = ? WHERE id = ?";
    ejecutarConsulta($sql, [$nuevo_tipo_id, $nuevo_tipo['ancho'], $puesto_id]);
    
    // Registrar en auditoría y log
    $mensaje = "Puesto {$puesto['codigo']} convertido de {$puesto['tipo_nombre']} a {$nuevo_tipo['nombre']}";
    registrarAuditoria($usuario_id, "Puesto convertido", $mensaje);
    registrarLogMapa($usuario_id, $mensaje);
    
    echo json_encode([
        'success' => true,
        'message' => 'Puesto convertido correctamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error en convertir_puesto.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}