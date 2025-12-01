<?php
/**
 * CONFIGURACIÓN DE BASE DE DATOS
 * Conexión PDO segura y centralizada
 * Sistema de Parqueadero Inteligente
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'parqueadero_inteligente');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Base path del proyecto (ruta dentro de htdocs). Ajusta si cambias el nombre de la carpeta.
define('BASE_PATH', '/parqueadero_inteligente');

/**
 * Obtener conexión PDO
 * @return PDO Conexión a la base de datos
 * @throws PDOException Si no se puede conectar
 */
function getConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log("Error de conexión a BD: " . $e->getMessage());
            die("Error al conectar con la base de datos. Contacte al administrador.");
        }
    }
    
    return $pdo;
}

/**
 * Ejecutar consulta preparada
 * @param string $sql Consulta SQL
 * @param array $params Parámetros de la consulta
 * @return PDOStatement
 */
function ejecutarConsulta($sql, $params = []) {
    $pdo = getConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Obtener una sola fila
 * @param string $sql Consulta SQL
 * @param array $params Parámetros
 * @return array|false
 */
function obtenerFila($sql, $params = []) {
    $stmt = ejecutarConsulta($sql, $params);
    return $stmt->fetch();
}

/**
 * Obtener todas las filas
 * @param string $sql Consulta SQL
 * @param array $params Parámetros
 * @return array
 */
function obtenerFilas($sql, $params = []) {
    $stmt = ejecutarConsulta($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Obtener el último ID insertado
 * @return string
 */
function ultimoId() {
    $pdo = getConnection();
    return $pdo->lastInsertId();
}

/**
 * Iniciar transacción
 * @return bool
 */
function iniciarTransaccion() {
    $pdo = getConnection();
    return $pdo->beginTransaction();
}

/**
 * Confirmar transacción
 * @return bool
 */
function confirmarTransaccion() {
    $pdo = getConnection();
    return $pdo->commit();
}

/**
 * Revertir transacción
 * @return bool
 */
function revertirTransaccion() {
    $pdo = getConnection();
    return $pdo->rollBack();
}