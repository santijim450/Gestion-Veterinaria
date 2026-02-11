<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="Css/estilos.css">
    <style>
        /* Estilos rápidos para centrar */
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f4f4; }
        .login-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; text-align: center; }
        input[type="email"] { width: 90%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; }
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #218838; }
        a { color: #007bff; text-decoration: none; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Recuperar Acceso</h2>
        <p>Ingresa tu correo electrónico registrado.</p>
        
        <?php if(isset($_GET['msg']) && $_GET['msg']=='enviado'): ?>
            <p style="color:green; font-size:0.9em;">Si el correo existe, se ha enviado un enlace de recuperación.</p>
        <?php endif; ?>

        <form action="../Controlador/recovery_controller.php?action=request" method="POST">
            <input type="email" name="email" placeholder="tucorreo@ejemplo.com" required>
            <button type="submit">Enviar Enlace</button>
        </form>
        <br>
        <a href="login.php">Volver al Login</a>
    </div>
</body>
</html>