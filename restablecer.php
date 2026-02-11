<?php
// Si no hay token en la URL, los sacamos
if (!isset($_GET['token']) || empty($_GET['token'])) {
    header("Location: login.php");
    exit;
}
$token = htmlspecialchars($_GET['token']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Contraseña</title>
    <link rel="stylesheet" href="Css/estilos.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f4f4; }
        .login-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; text-align: center; }
        input[type="password"] { width: 90%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0069d9; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Restablecer Contraseña</h2>
        <form action="../Controlador/recovery_controller.php?action=reset" method="POST">
            <input type="hidden" name="token" value="<?php echo $token; ?>">
            
            <label style="float:left; font-size:0.9em;">Nueva Contraseña:</label>
            <input type="password" name="password" required minlength="6">
            
            <label style="float:left; font-size:0.9em;">Confirmar Contraseña:</label>
            <input type="password" name="confirm_password" required minlength="6">
            
            <button type="submit">Cambiar Contraseña</button>
        </form>
    </div>
</body>
</html>