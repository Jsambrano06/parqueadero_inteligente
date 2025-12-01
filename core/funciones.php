<?php
/**
 * FUNCIONES GLOBALES DEL SISTEMA
 * Utilidades reutilizables en todo el proyecto
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Sanitizar entrada de texto
 * @param string $data Dato a sanitizar
 * @return string
 */
function limpiar($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validar placa de vehículo
 * Regex obligatorio: ^[A-Za-z0-9]{4,8}$
 * @param string $placa
 * @return bool
 */
function validarPlaca($placa) {
    return preg_match('/^[A-Za-z0-9]{4,8}$/', $placa);
}

/**
 * Redireccionar a una URL
 * @param string $url
 */
function redirigir($url) {
    // Si la URL es absoluta (http:// o https://) o esquema protocol-relative, mantenerla
    if (preg_match('#^https?://#i', $url) || strpos($url, '//') === 0) {
        header("Location: " . $url);
        exit();
    }

    // Si se definió BASE_PATH, anteponerla cuando la URL comienza con '/'
    if (defined('BASE_PATH')) {
        // Normalizar la url para asegurar que comienza con '/'
        if ($url === '' || $url[0] !== '/') {
            $url = '/' . ltrim($url, '/');
        }

        // Prevenir duplicar BASE_PATH si ya fue incluida
        if (strpos($url, BASE_PATH) !== 0) {
            $url = rtrim(BASE_PATH, '/') . $url;
        }
    }

    header("Location: " . $url);
    exit();
}

/**
 * Obtener configuración del sistema
 * @param string $nombre Nombre de la configuración
 * @return string|null
 */
function obtenerConfiguracion($nombre) {
    $sql = "SELECT valor FROM configuraciones WHERE nombre = ?";
    $resultado = obtenerFila($sql, [$nombre]);
    return $resultado ? $resultado['valor'] : null;
}

/**
 * Actualizar configuración del sistema
 * @param string $nombre
 * @param string $valor
 * @return bool
 */
function actualizarConfiguracion($nombre, $valor) {
    try {
        // Si existe, actualizar
        $exists = obtenerFila("SELECT id FROM configuraciones WHERE nombre = ?", [$nombre]);
        if ($exists) {
            $sql = "UPDATE configuraciones SET valor = ? WHERE nombre = ?";
            ejecutarConsulta($sql, [$valor, $nombre]);
        } else {
            // Insertar nueva configuración
            $sql = "INSERT INTO configuraciones (nombre, valor) VALUES (?, ?)";
            ejecutarConsulta($sql, [$nombre, $valor]);
        }
        return true;
    } catch (Exception $e) {
        error_log("Error al actualizar configuración: " . $e->getMessage());
        return false;
    }
}

/**
 * Registrar en auditoría
 * @param int|null $usuario_id ID del usuario (puede ser NULL)
 * @param string $accion Descripción de la acción
 * @param string|null $detalles Detalles adicionales
 * @return bool
 */
function registrarAuditoria($usuario_id, $accion, $detalles = null) {
    $sql = "INSERT INTO auditoria (usuario_id, accion, detalles) VALUES (?, ?, ?)";
    try {
        ejecutarConsulta($sql, [$usuario_id, $accion, $detalles]);
        return true;
    } catch (Exception $e) {
        error_log("Error al registrar auditoría: " . $e->getMessage());
        return false;
    }
}

/**
 * Registrar en log del mapa
 * @param int|null $usuario_id
 * @param string $descripcion
 * @return bool
 */
function registrarLogMapa($usuario_id, $descripcion) {
    $sql = "INSERT INTO log_mapa (usuario_id, descripcion) VALUES (?, ?)";
    try {
        ejecutarConsulta($sql, [$usuario_id, $descripcion]);
        return true;
    } catch (Exception $e) {
        error_log("Error al registrar log mapa: " . $e->getMessage());
        return false;
    }
}

/**
 * Calcular cobro según reglas obligatorias
 * @param string $hora_entrada Formato: Y-m-d H:i:s
 * @param string $hora_salida Formato: Y-m-d H:i:s
 * @param float $tarifa_hora Precio por hora
 * @return float Total a pagar
 */
function calcularCobro($hora_entrada, $hora_salida, $tarifa_hora) {
    // Obtener configuraciones
    $redondeo = (int)obtenerConfiguracion('redondeo_minutos');
    $tarifa_minima = (int)obtenerConfiguracion('tarifa_minima_horas');
    
    // Calcular diferencia en minutos
    $entrada = new DateTime($hora_entrada);
    $salida = new DateTime($hora_salida);
    $diferencia = $entrada->diff($salida);
    $minutos = ($diferencia->days * 24 * 60) + ($diferencia->h * 60) + $diferencia->i;
    
    // Redondear según configuración (por defecto 15 minutos)
    $min_redondeados = ceil($minutos / $redondeo) * $redondeo;
    
    // Convertir a horas
    $horas = $min_redondeados / 60;
    
    // Aplicar tarifa mínima
    $horas = max($tarifa_minima, $horas);
    
    // Calcular total
    $total = $horas * $tarifa_hora;
    
    return round($total, 2);
}

/**
 * Obtener tarifa por tipo de vehículo
 * @param string $tipo_vehiculo moto|carro|camion
 * @return float|null
 */
function obtenerTarifa($tipo_vehiculo) {
    $sql = "SELECT precio_hora FROM tarifas WHERE tipo_vehiculo = ?";
    $resultado = obtenerFila($sql, [$tipo_vehiculo]);
    return $resultado ? (float)$resultado['precio_hora'] : null;
}

/**
 * Formatear moneda colombiana
 * @param float $monto
 * @return string
 */
function formatearMoneda($monto) {
    return '$' . number_format($monto, 0, ',', '.');
}

/**
 * Formatear fecha y hora
 * @param string $fecha
 * @param string $formato
 * @return string
 */
function formatearFecha($fecha, $formato = 'd/m/Y H:i') {
    if (empty($fecha)) return '-';
    $dt = new DateTime($fecha);
    return $dt->format($formato);
}

/**
 * Verificar si hay puestos disponibles
 * @param int $tipo_id Tipo de puesto (1=moto, 2=carro, 3=camión)
 * @return bool
 */
function hayPuestosDisponibles($tipo_id) {
    $sql = "SELECT COUNT(*) as total FROM puestos WHERE tipo_id = ? AND estado = 'libre'";
    $resultado = obtenerFila($sql, [$tipo_id]);
    return $resultado['total'] > 0;
}

/**
 * Obtener cantidad total de puestos
 * @return int
 */
function contarPuestos() {
    $sql = "SELECT COUNT(*) as total FROM puestos";
    $resultado = obtenerFila($sql);
    return (int)$resultado['total'];
}

/**
 * Verificar límite de puestos
 * @return bool True si se puede agregar más puestos
 */
function puedeAgregarPuestos() {
    $limite = (int)obtenerConfiguracion('limite_puestos');
    $total_actual = contarPuestos();
    return $total_actual < $limite;
}

/**
 * Generar mensaje de éxito en sesión
 * @param string $mensaje
 */
function mensajeExito($mensaje) {
    $_SESSION['mensaje_exito'] = $mensaje;
}

/**
 * Generar mensaje de error en sesión
 * @param string $mensaje
 */
function mensajeError($mensaje) {
    $_SESSION['mensaje_error'] = $mensaje;
}

/**
 * Mostrar y limpiar mensajes de sesión
 * @return array ['exito' => string|null, 'error' => string|null]
 */
function obtenerMensajes() {
    $mensajes = [
        'exito' => isset($_SESSION['mensaje_exito']) ? $_SESSION['mensaje_exito'] : null,
        'error' => isset($_SESSION['mensaje_error']) ? $_SESSION['mensaje_error'] : null
    ];
    
    unset($_SESSION['mensaje_exito']);
    unset($_SESSION['mensaje_error']);
    
    return $mensajes;
}