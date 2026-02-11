<?php
session_start();
include __DIR__ . "/../Modelo/conexion.php"; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura y limpieza de datos (reinsertado aquí)
    $email = isset($_POST['email']) ? $con->real_escape_string($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($email) || empty($password)) {
        header("Location: ../Vista/login.php?error=" . urlencode("Por favor, ingrese correo y contraseña."));
        exit;
    }

    $stmt = $con->prepare("SELECT id_usuario, nombre, password_hash, rol FROM usuarios WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();

        // Verificar la contraseña
        if (password_verify($password, $usuario['password_hash'])) {
            // Contraseña correcta: iniciar sesión
            $_SESSION['usuario_id'] = $usuario['id_usuario'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol'] = $usuario['rol'];

            // Redirigir según el rol
            switch ($usuario['rol']) {
                case 'administrador':
                    header("Location: ../Vista/dashboard.php"); // O la página principal del admin
                    break;
                case 'veterinario':
                    header("Location: ../Vista/mascotas.php"); // Acceso a Mascotas/Historial
                    break;
                case 'recepcionista':
                    header("Location: ../Vista/clientes.php"); // Acceso a Clientes/Mascotas/Citas
                    break;
                default:
                    header("Location: ../Vista/dashboard.php"); // Por defecto
                    break;
            }
            exit;
        } else {
            // Contraseña incorrecta
            header("Location: ../Vista/login.php?error=" . urlencode("Correo o contraseña incorrectos."));
            exit;
        }
    } else {
        // Usuario no encontrado
        header("Location: ../Vista/login.php?error=" . urlencode("Correo o contraseña incorrectos."));
        exit;
    }

    $stmt->close();
} else {
    // Si se accede directamente al controlador sin POST
    header("Location: ../Vista/login.php");
    exit;
}