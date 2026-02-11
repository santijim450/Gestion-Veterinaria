<?php
// Incluir seguridad.php
include __DIR__ . "/seguridad.php"; 
// Solo Admin y Recepcionista pueden CRUD Clientes
check_access(['administrador', 'recepcionista']); 

// El resto del código continúa...
// ELIMINAR O COMENTAR LA LÍNEA DUPLICADA DE session_start()
// session_start(); 
include __DIR__ . "/../Modelo/conexion.php";
// ...
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

function redirect_list($msg = '') {
    $location = '../Vista/clientes.php';
    if (!empty($msg)) {
        $location .= '?msg=' . urlencode($msg);
    }
    header("Location: $location");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'add') {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Agregar Cliente</title>
            <link rel="stylesheet" href="../Vista/Css/estilos.css">
        </head>
        <body>
            <main>
                <h2>Agregar Cliente</h2>
                <form method="POST" action="?action=add">
                    <label>Apellido Paterno:</label><br>
                    <input type="text" name="apellido_paterno" required><br><br>

                    <label>Apellido Materno:</label><br>
                    <input type="text" name="apellido_materno" required><br><br>

                    <label>Nombre:</label><br>
                    <input type="text" name="nombre" required><br><br>

                    <label>Domicilio:</label><br>
                    <input type="text" name="domicilio" required><br><br>

                    <label>Whatsapp:</label><br>
                    <input type="text" name="whatsapp" required><br><br>

                    <label>Email:</label><br>
                    <input type="email" name="email" required><br><br>

                    <button type="submit" class="Agregar">Guardar</button>
                    <a href="../Vista/clientes.php" class="delete-btn" style="padding: 6px 12px; text-decoration:none; margin-left: 10px;">Cancelar</a>
                </form>
            </main>
        </body>
        </html>
        <?php
        exit;
    }

    if ($action === 'edit') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) redirect_list('id_invalido');

        $stmt = $con->prepare("SELECT * FROM clientes WHERE id = ?");
        if (!$stmt) die('Error en prepare: ' . $con->error);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            $stmt->close();
            die('Cliente no encontrado.');
        }
        $cliente = $res->fetch_assoc();
        $stmt->close();
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Editar Cliente</title>
            <link rel="stylesheet" href="../Vista/Css/estilos.css">
        </head>
        <body>
            <main>
                <h2>Editar Cliente</h2>
                <form method="POST" action="?action=edit">
                    <input type="hidden" name="id" value="<?php echo $cliente['id']; ?>">
                    
                    <label>Apellido Paterno:</label><br>
                    <input type="text" name="apellido_paterno" value="<?php echo htmlspecialchars($cliente['apellido_paterno']); ?>" required><br><br>

                    <label>Apellido Materno:</label><br>
                    <input type="text" name="apellido_materno" value="<?php echo htmlspecialchars($cliente['apellido_materno']); ?>" required><br><br>

                    <label>Nombre:</label><br>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($cliente['nombre']); ?>" required><br><br>

                    <label>Domicilio:</label><br>
                    <input type="text" name="domicilio" value="<?php echo htmlspecialchars($cliente['domicilio']); ?>" required><br><br>

                    <label>Whatsapp:</label><br>
                    <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($cliente['whatsapp']); ?>" required><br><br>

                    <label>Email:</label><br>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($cliente['email']); ?>" required><br><br>

                    <button type="submit" class="Agregar">Guardar cambios</button>
                    <a href="../Vista/clientes.php" class="delete-btn" style="padding: 6px 12px; text-decoration:none; margin-left: 10px;">Cancelar</a>
                </form>
            </main>
        </body>
        </html>
        <?php
        exit;
    }
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $apellido_paterno = isset($_POST['apellido_paterno']) ? $con->real_escape_string($_POST['apellido_paterno']) : '';
    $apellido_materno = isset($_POST['apellido_materno']) ? $con->real_escape_string($_POST['apellido_materno']) : '';
    $nombre = isset($_POST['nombre']) ? $con->real_escape_string($_POST['nombre']) : '';
    $domicilio = isset($_POST['domicilio']) ? $con->real_escape_string($_POST['domicilio']) : '';
    $whatsapp = isset($_POST['whatsapp']) ? $con->real_escape_string($_POST['whatsapp']) : '';
    $email = isset($_POST['email']) ? $con->real_escape_string($_POST['email']) : '';

    $stmt = $con->prepare("INSERT INTO clientes (apellido_paterno, apellido_materno, nombre, domicilio, whatsapp, email) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('ssssss', $apellido_paterno, $apellido_materno, $nombre, $domicilio, $whatsapp, $email);
        if ($stmt->execute()) {
            $stmt->close();
            redirect_list('agregado');
        } else {
            $stmt->close();
            die('Error al guardar: ' . $con->error);
        }
    } else {
        die('Error en prepare: . $con->error');
    }
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $apellido_paterno = isset($_POST['apellido_paterno']) ? $con->real_escape_string($_POST['apellido_paterno']) : '';
    $apellido_materno = isset($_POST['apellido_materno']) ? $con->real_escape_string($_POST['apellido_materno']) : '';
    $nombre = isset($_POST['nombre']) ? $con->real_escape_string($_POST['nombre']) : '';
    $domicilio = isset($_POST['domicilio']) ? $con->real_escape_string($_POST['domicilio']) : '';
    $whatsapp = isset($_POST['whatsapp']) ? $con->real_escape_string($_POST['whatsapp']) : '';
    $email = isset($_POST['email']) ? $con->real_escape_string($_POST['email']) : '';

    if ($id <= 0) redirect_list('id_invalido');

    $stmt = $con->prepare("UPDATE clientes SET apellido_paterno=?, apellido_materno=?, nombre=?, domicilio=?, whatsapp=?, email=? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('ssssssi', $apellido_paterno, $apellido_materno, $nombre, $domicilio, $whatsapp, $email, $id);
        if ($stmt->execute()) {
            $stmt->close();
            redirect_list('actualizado');
        } else {
            $stmt->close();
            die('Error al actualizar: ' . $con->error);
        }
    } else {
        die('Error en prepare: ' . $con->error);
    }
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // NOTA: Esta acción POST de DELETE es la que deberías usar en la vista,
    // pero la vista 'clientes.php' actualmente usa su propia lógica de POST.
    // Para consistencia, la vista debe apuntar a este controlador.
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id <= 0) redirect_list('id_invalido');

    $stmt = $con->prepare("DELETE FROM clientes WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $stmt->close();
            redirect_list('eliminado');
        } else {
            $stmt->close();
            die('Error al eliminar: ' . $con->error);
        }
    } else {
        die('Error en prepare: ' . $con->error);
    }
}

redirect_list();
?>