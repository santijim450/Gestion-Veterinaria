<?php
session_start();
// Redirigir si ya está logueado
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php"); // Redirigir a la página principal
    exit;
}

$error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success_message = '';

// Verificar si viene con mensaje de éxito (ej. después de cambiar contraseña)
if (isset($_GET['msg']) && $_GET['msg'] === 'reset_ok') {
    $success_message = "¡Contraseña actualizada correctamente! Inicia sesión.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>One Pet - Inicio de Sesión</title>
    <link rel="stylesheet" href="Css/estilos.css">
    <script src="https://kit.fontawesome.com/b2643188f2.js" crossorigin="anonymous"></script>
    <style>
        /* Estilos básicos para el login */
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f0f2f5; font-family: Arial, sans-serif; }
        .login-container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; text-align: center; }
        .login-container h2 { margin-bottom: 20px; color: #333; }
        .login-container input[type="email"], .login-container input[type="password"] { width: 100%; padding: 12px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .login-container button { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 5px; }
        .login-container button:hover { background-color: #0056b3; }
        
        /* Estilos para mensajes */
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        
        .logo-img { max-width: 150px; margin-bottom: 20px; }
        
        /* Estilo para el enlace de recuperar contraseña (CENTRADO) */
        .forgot-pass {
            display: block;
            text-align: center; /* Centrado como pediste */
            margin-top: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .forgot-pass a {
            color: #007bff;
            text-decoration: none;
        }
        .forgot-pass a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="imagenes/one pet3.png" alt="Logo One Pet" class="logo-img">
        <h2>Inicio de Sesión</h2>
        
        <?php if ($error_message): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form action="../Controlador/login_controller.php" method="POST">
            <input type="email" name="email" placeholder="Correo Electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            
            <div class="forgot-pass">
                <a href="recuperar.php">¿Olvidaste tu contraseña?</a>
            </div>

            <button type="submit">Acceder</button>
        </form>
    </div>
</body>
</html>