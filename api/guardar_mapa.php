<?php
/**
 * API - GUARDAR CAMBIOS DEL MAPA
 * Guardar posiciones X,Y de puestos y cambios de estado
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
    
    // Verificar si es cambio de estado o cambios de posición
    if (isset($_POST['puesto_id']) && isset($_POST['estado'])) {
        // Cambio de estado individual
        $puesto_id = (int)$_POST['puesto_id'];
        $nuevo_estado = $_POST['estado'];
        
        // Validar estado
        if (!in_array($nuevo_estado, ['libre', 'ocupado', 'inactivo'])) {
            throw new Exception('Estado no válido');
        }
        
        // Verificar que el puesto existe
        $sql = "SELECT id, codigo, estado FROM puestos WHERE id = ?";
        $puesto = obtenerFila($sql, [$puesto_id]);
        
        if (!$puesto) {
            throw new Exception('Puesto no encontrado');
        }
        
        // No permitir cambiar estado de puesto ocupado
        if ($puesto['estado'] === 'ocupado' && $nuevo_estado !== 'ocupado') {
            throw new Exception('No se puede cambiar el estado de un puesto ocupado');
        }
        
        // Actualizar estado
        $sql = "UPDATE puestos SET estado = ? WHERE id = ?";
        ejecutarConsulta($sql, [$nuevo_estado, $puesto_id]);
        
        // Registrar en log
        registrarLogMapa($usuario_id, "Estado del puesto {$puesto['codigo']} cambiado a: $nuevo_estado");
        
        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado correctamente'
        ]);

    } else if (isset($_POST['puesto_id']) && isset($_POST['orientacion'])) {
        // Cambio de orientación
        $puesto_id = (int)$_POST['puesto_id'];
        $orientacion = (int)$_POST['orientacion'];

        // Validar orientación
        if (!in_array($orientacion, [0, 90])) {
            throw new Exception('Orientación no válida');
        }

        // Verificar que el puesto existe
        $sql = "SELECT id, codigo, orientacion FROM puestos WHERE id = ?";
        $puesto = obtenerFila($sql, [$puesto_id]);

        if (!$puesto) {
            throw new Exception('Puesto no encontrado');
        }

        // Actualizar orientación en la base de datos
        $sql = "UPDATE puestos SET orientacion = ? WHERE id = ?";
        ejecutarConsulta($sql, [$orientacion, $puesto_id]);

        // Registrar en log
        registrarLogMapa($usuario_id, "Puesto {$puesto['codigo']} rotado a {$orientacion} grados");

        echo json_encode([
            'success' => true,
            'message' => 'Puesto rotado correctamente'
        ]);
        
    } else if (isset($_POST['map_width']) && isset($_POST['map_height'])) {
        // Guardar tamaño del mapa en configuraciones
        $map_width = (int)$_POST['map_width'];
        $map_height = (int)$_POST['map_height'];

        // Validar tamaños razonables
        if ($map_width < 600 || $map_height < 300 || $map_width > 5000 || $map_height > 3000) {
            throw new Exception('Dimensiones del mapa fuera de rango');
        }

        // Guardar en configuraciones (insert/update handled by actualizarConfiguracion)
        if (!actualizarConfiguracion('map_width', $map_width) || !actualizarConfiguracion('map_height', $map_height)) {
            throw new Exception('No se pudo guardar la configuración del mapa');
        }

        // Registrar en log
        registrarLogMapa($usuario_id, "Tamaño del mapa actualizado a: {$map_width}x{$map_height}");

        echo json_encode([
            'success' => true,
            'message' => 'Tamaño del mapa guardado correctamente'
        ]);

    } else {
        // Cambios de posición (múltiples puestos)
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!isset($data['cambios']) || !is_array($data['cambios'])) {
            throw new Exception('Datos de cambios no válidos');
        }
        
        $cambios = $data['cambios'];
        
        if (empty($cambios)) {
            throw new Exception('No hay cambios para guardar');
        }
        
        // Obtener límites del mapa desde configuración (si existen)
        $cfg_width = (int)obtenerConfiguracion('map_width');
        $cfg_height = (int)obtenerConfiguracion('map_height');
        if ($cfg_width <= 0) $cfg_width = 1200;
        if ($cfg_height <= 0) $cfg_height = 600;

        // Iniciar transacción
        iniciarTransaccion();
        
        $actualizados = 0;
        
        foreach ($cambios as $cambio) {
            if (!isset($cambio['id'], $cambio['x'], $cambio['y'])) {
                continue;
            }
            
            $puesto_id = (int)$cambio['id'];
            $x = (int)$cambio['x'];
            $y = (int)$cambio['y'];
            
            // Validar coordenadas usando límites configurados
            if ($x < 0 || $y < 0 || $x > $cfg_width || $y > $cfg_height) {
                continue;
            }
            
            // Verificar que el puesto existe y no está ocupado
            $sql = "SELECT id, codigo, estado FROM puestos WHERE id = ?";
            $puesto = obtenerFila($sql, [$puesto_id]);
            
            if (!$puesto) {
                continue;
            }
            
            if ($puesto['estado'] === 'ocupado') {
                throw new Exception("No se puede mover el puesto {$puesto['codigo']} porque está ocupado");
            }
            
            // Actualizar posición
            $sql = "UPDATE puestos SET x = ?, y = ? WHERE id = ?";
            ejecutarConsulta($sql, [$x, $y, $puesto_id]);
            
            $actualizados++;
            
            // Registrar en log
            registrarLogMapa($usuario_id, "Puesto {$puesto['codigo']} movido a X:$x, Y:$y");
        }
        
        // Confirmar transacción
        confirmarTransaccion();
        
        echo json_encode([
            'success' => true,
            'message' => "$actualizados puesto(s) actualizado(s) correctamente"
        ]);
    }
    
} catch (Exception $e) {
    // Revertir transacción si está activa
    if (getConnection()->inTransaction()) {
        revertirTransaccion();
    }
    
    error_log("Error en guardar_mapa.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}