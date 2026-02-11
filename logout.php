<?php
// Inicia la sesión si aún no está iniciada (necesario para destruir variables de sesión)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Destruye todas las variables de sesión
$_SESSION = array();

// 2. Si se desea destruir la cookie de sesión, también se debe eliminar
// Nota: Esto borrará la sesión, no solo los datos de sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finalmente, destruye la sesión
session_destroy();

// Redirige al usuario a la página de inicio de sesión (ajusta la ruta según tu estructura)
header("Location: ../Vista/login.php"); 
exit;
?>