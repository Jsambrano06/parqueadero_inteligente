<?php
/**
 * API - ACTUALIZAR CONFIGURACIONES
 * Actualizar configuraciones del sistema
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
    
    if (!isset($_POST['tipo'])) {
        throw new Exception('Tipo de configuración no especificado');
    }
    
    $tipo = $_POST['tipo'];
    $cambios = [];
    
    switch ($tipo) {
        case 'general':
            // Configuración general
            if (!isset($_POST['nombre_parqueadero']) || !isset($_POST['moneda']) || !isset($_POST['zona_horaria'])) {
                throw new Exception('Datos incompletos');
            }
            
            $nombre = trim($_POST['nombre_parqueadero']);
            $moneda = trim($_POST['moneda']);
            $zona_horaria = trim($_POST['zona_horaria']);
            
            if (empty($nombre)) {
                throw new Exception('El nombre del parqueadero es obligatorio');
            }
            
            actualizarConfiguracion('nombre_parqueadero', $nombre);
            actualizarConfiguracion('moneda', $moneda);
            actualizarConfiguracion('zona_horaria', $zona_horaria);
            
            $cambios[] = "Nombre: $nombre, Moneda: $moneda, Zona: $zona_horaria";
            break;
            
        case 'cobro':
            // Configuración de cobro
            if (!isset($_POST['redondeo_minutos']) || !isset($_POST['tarifa_minima_horas'])) {
                throw new Exception('Datos incompletos');
            }
            
            $redondeo = (int)$_POST['redondeo_minutos'];
            $tarifa_minima = (float)$_POST['tarifa_minima_horas'];
            
            if (!in_array($redondeo, [5, 10, 15, 30, 60])) {
                throw new Exception('Valor de redondeo no válido');
            }
            
            if ($tarifa_minima <= 0) {
                throw new Exception('La tarifa mínima debe ser mayor a 0');
            }
            
            actualizarConfiguracion('redondeo_minutos', $redondeo);
            actualizarConfiguracion('tarifa_minima_horas', $tarifa_minima);
            
            $cambios[] = "Redondeo: {$redondeo}min, Tarifa mínima: {$tarifa_minima}h";
            break;
            
        case 'capacidad':
            // Configuración de capacidad
            if (!isset($_POST['limite_puestos'])) {
                throw new Exception('Datos incompletos');
            }
            
            $limite = (int)$_POST['limite_puestos'];
            
            if ($limite < 1 || $limite > 1000) {
                throw new Exception('El límite debe estar entre 1 y 1000');
            }
            
            actualizarConfiguracion('limite_puestos', $limite);
            
            $cambios[] = "Límite de puestos: $limite";
            break;
            
        default:
            throw new Exception('Tipo de configuración no válido');
    }
    
    // Registrar en auditoría
    if (!empty($cambios)) {
        $detalles = implode(', ', $cambios);
        registrarAuditoria($usuario_id, "Configuración actualizada: $tipo", $detalles);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Configuración actualizada correctamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error en actualizar_configuraciones.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}