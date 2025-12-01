<?php
/**
 * PÁGINA DE LOGIN
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/csrf.php';
require_once __DIR__ . '/../core/middleware.php';

// Iniciar sesión
iniciarSesion();

// Si ya está autenticado, redirigir según rol
middlewarePublico();

// Procesar login
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCSRF();
    
    $usuario = isset($_POST['usuario']) ? limpiar($_POST['usuario']) : '';
    $clave = isset($_POST['clave']) ? $_POST['clave'] : '';
    
    if (empty($usuario) || empty($clave)) {
        $error = 'Por favor completa todos los campos.';
    } else {
        $user = autenticar($usuario, $clave);
        
        if ($user) {
            iniciarSesionUsuario($user);
            
            // Redirigir según rol
            if ($user['rol_id'] == 1) {
                redirigir('/admin/dashboard.php');
            } else {
                redirigir('/empleado/entrada.php');
            }
        } else {
            $error = 'Usuario o contraseña incorrectos.';
            registrarAuditoria(null, "Intento de login fallido: $usuario", "IP: " . $_SERVER['REMOTE_ADDR']);
        }
    }
}

// Verificar si hay mensaje de sesión cerrada
if (isset($_GET['logout'])) {
    $success = 'Sesión cerrada correctamente.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Parqueadero Inteligente</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fa-solid fa-square-parking"></i>
                </div>
                <h1>Parqueadero Inteligente</h1>
                <p>Ingresa tus credenciales para continuar</p>
            </div>

            <?php if ($error): ?>
            <div class="login-alert error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="login-alert success">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form" id="loginForm">
                <?php echo campoCSRF(); ?>
                
                <div class="form-group">
                    <label for="usuario" class="form-label">Usuario</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-user"></i>
                        <input 
                            type="text" 
                            id="usuario" 
                            name="usuario" 
                            class="form-control" 
                            placeholder="Ingresa tu usuario"
                            value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="clave" class="form-label">Contraseña</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-lock"></i>
                        <input 
                            type="password" 
                            id="clave" 
                            name="clave" 
                            class="form-control" 
                            placeholder="Ingresa tu contraseña"
                            required
                        >
                    </div>
                </div>

                <button type="submit" class="login-btn" id="loginBtn">
                    <span class="btn-text">Iniciar Sesión</span>
                    <div class="spinner"></div>
                </button>
            </form>

            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> Parqueadero Inteligente. Todos los derechos reservados.</p>
            </div>
        </div>
    </div>

    <script>
        // Animación de envío
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.disabled = true;
        });

        // Limpiar mensajes después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.login-alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.3s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            });
        }, 5000);
    </script>
</body>
</html>