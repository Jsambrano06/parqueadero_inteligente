<?php
/**
 * API - CREAR NUEVO PUESTO
 * Agregar puestos al parqueadero con validaciones
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
    if (!isset($_POST['tipo_id']) || !isset($_POST['codigo'])) {
        throw new Exception('Datos incompletos');
    }
    
    $tipo_id = (int)$_POST['tipo_id'];
    $codigo = strtoupper(trim($_POST['codigo']));
    
    // Validar código (alfanumérico, 1-20 caracteres)
    if (empty($codigo) || strlen($codigo) > 20 || !preg_match('/^[A-Z0-9]+$/', $codigo)) {
        throw new Exception('Código inválido. Debe ser alfanumérico y máximo 20 caracteres');
    }
    
    // Verificar que el tipo existe
    $sql = "SELECT id, nombre, ancho FROM tipos_puesto WHERE id = ?";
    $tipo = obtenerFila($sql, [$tipo_id]);
    
    if (!$tipo) {
        throw new Exception('Tipo de puesto no válido');
    }
    
    // Verificar que el código no exista
    $sql = "SELECT id FROM puestos WHERE codigo = ?";
    $existe = obtenerFila($sql, [$codigo]);
    
    if ($existe) {
        throw new Exception('Ya existe un puesto con ese código');
    }
    
    // Verificar límite de puestos
    if (!puedeAgregarPuestos()) {
        $limite = obtenerConfiguracion('limite_puestos');
        throw new Exception("Has alcanzado el límite de $limite puestos");
    }
    
    // Calcular posición inicial (buscar espacio libre)
    // Por defecto: añadir en la última posición + offset
    $sql = "SELECT MAX(y) as max_y FROM puestos";
    $resultado = obtenerFila($sql);
    $nueva_y = ($resultado['max_y'] ?? 0) + 70;
    $nueva_x = 50;
    
    // Si se pasa de altura, reiniciar
    if ($nueva_y > 500) {
        $sql = "SELECT MAX(x) as max_x FROM puestos WHERE y < 100";
        $resultado = obtenerFila($sql);
        $nueva_x = ($resultado['max_x'] ?? 0) + 200;
        $nueva_y = 30;
    }
    
    // Insertar puesto
    $sql = "INSERT INTO puestos (codigo, tipo_id, estado, x, y, ancho_unidades) 
            VALUES (?, ?, 'libre', ?, ?, ?)";
    
    ejecutarConsulta($sql, [
        $codigo,
        $tipo_id,
        $nueva_x,
        $nueva_y,
        $tipo['ancho']
    ]);
    
    $puesto_id = ultimoId();
    
    // Registrar en auditoría y log
    registrarAuditoria($usuario_id, "Puesto creado: $codigo", "Tipo: {$tipo['nombre']}, ID: $puesto_id");
    registrarLogMapa($usuario_id, "Nuevo puesto creado: $codigo (tipo: {$tipo['nombre']})");
    
    echo json_encode([
        'success' => true,
        'message' => 'Puesto creado correctamente',
        'puesto_id' => $puesto_id
    ]);
    
} catch (Exception $e) {
    error_log("Error en crear_puesto.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}