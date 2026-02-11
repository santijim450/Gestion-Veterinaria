<?php
session_start();
include __DIR__ . "/../Modelo/conexion.php";

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// 1. SOLICITAR RECUPERACIÓN (El usuario pone su email)
if ($action === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $con->real_escape_string($_POST['email']);

    // Verificar si el correo existe
    $check = $con->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
    $check->bind_param('s', $email);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        // Generar token único y fecha de expiración (1 hora)
        $token = bin2hex(random_bytes(32)); // Genera un código largo al azar
        $expiracion = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Guardar token en la tabla temporal
        $stmt = $con->prepare("INSERT INTO password_resets (email, token, expiracion) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $email, $token, $expiracion);
        
        if ($stmt->execute()) {
            // --- MODO PRUEBA ESCOLAR ---
            // En la vida real, aquí usarías mail() o PHPMailer.
            // Para tu proyecto, mostramos el link en pantalla:
            $link = "http://localhost/tu_proyecto/Vista/restablecer.php?token=" . $token; 
            // AJUSTA LA RUTA 'tu_proyecto' SI TU CARPETA SE LLAMA DIFERENTE
            
            echo "<div style='background:#d4edda; color:#155724; padding:20px; text-align:center; font-family:sans-serif;'>";
            echo "<h2>¡Simulación de Correo Enviado!</h2>";
            echo "<p>El sistema ha generado el siguiente enlace de recuperación:</p>";
            echo "<a href='../Vista/restablecer.php?token=$token' style='font-size:18px; color:blue;'>CLICK AQUÍ PARA RESTABLECER CONTRASEÑA</a>";
            echo "<p><small>(Copia este enlace si no funciona el clic directo)</small></p>";
            echo "</div>";
            exit; 
        } else {
            die("Error al generar token: " . $con->error);
        }
    } else {
        // Por seguridad, no decimos si el correo no existe, solo redirigimos con mensaje genérico
        header("Location: ../Vista/recuperar.php?msg=enviado");
        exit;
    }
}

// 2. GUARDAR NUEVA CONTRASEÑA
if ($action === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $pass_nueva = $_POST['password'];
    $confirmar = $_POST['confirm_password'];

    if ($pass_nueva !== $confirmar) {
        die("Las contraseñas no coinciden. <a href='../Vista/restablecer.php?token=$token'>Intentar de nuevo</a>");
    }

    // Verificar si el token es válido y no ha expirado
    $now = date("Y-m-d H:i:s");
    $stmt = $con->prepare("SELECT email FROM password_resets WHERE token = ? AND expiracion > ?");
    $stmt->bind_param('ss', $token, $now);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $email = $row['email'];

        // Encriptar nueva contraseña
        $hash_nuevo = password_hash($pass_nueva, PASSWORD_DEFAULT);

        // Actualizar usuario real
        $update = $con->prepare("UPDATE usuarios SET password_hash = ? WHERE email = ?");
        $update->bind_param('ss', $hash_nuevo, $email);
        
        if ($update->execute()) {
            // Borrar el token usado para que no se use dos veces
            $del = $con->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->bind_param('s', $email);
            $del->execute();

            header("Location: ../Vista/login.php?msg=reset_ok");
            exit;
        } else {
            die("Error al actualizar contraseña.");
        }
    } else {
        die("El enlace ha expirado o no es válido. <a href='../Vista/recuperar.php'>Solicitar nuevo</a>");
    }
}
?>