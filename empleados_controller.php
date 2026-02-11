<?php
include __DIR__ . "/seguridad.php"; 
check_access(['administrador']); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . "/../Modelo/conexion.php";

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

function redirect_list($msg = '') {
    $location = '../Vista/empleados.php';
    if (!empty($msg)) $location .= '?msg=' . urlencode($msg);
    header("Location: $location");
    exit;
}

// --- LISTA DE ROLES ACTUALIZADA ---
// Se eliminaron: Estilista, Ayudante General, Limpieza
$roles_disponibles = ['Veterinario', 'Recepcionista', 'Administrador']; 

// GET: Mostrar formularios
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $scriptPath = htmlspecialchars($_SERVER['SCRIPT_NAME']);
    
    if ($action === 'add') {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <title>Agregar Empleado</title>
            <link rel="stylesheet" href="../Vista/Css/estilos.css">
        </head>
        <body>
            <main>
                <h2>Agregar Empleado</h2>
                <form method="POST" action="<?php echo $scriptPath; ?>?action=add">
                    <label>Apellido Paterno:</label><br>
                    <input type="text" name="apellido_paterno" required><br><br>

                    <label>Apellido Materno:</label><br>
                    <input type="text" name="apellido_materno" required><br><br>

                    <label>Nombre:</label><br>
                    <input type="text" name="nombre" required><br><br>

                    <label>Rol / Puesto:</label><br>
                    <select name="rol" required>
                        <option value="">Seleccione un rol</option>
                        <?php foreach($roles_disponibles as $r): ?>
                            <option value="<?php echo $r; ?>"><?php echo $r; ?></option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label>Domicilio:</label><br>
                    <input type="text" name="domicilio" required><br><br>

                    <label>Whatsapp:</label><br>
                    <input type="text" name="whatsapp" required><br><br>

                    <label>Email:</label><br>
                    <input type="email" name="email" required><br><br>

                    <button type="submit" class="Agregar">Guardar</button>
                    <a href="../Vista/empleados.php" class="delete-btn" style="padding:6px 12px; text-decoration:none; margin-left:10px;">Cancelar</a>
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

        $stmt = $con->prepare("SELECT * FROM empleados WHERE id_empleado = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) { $stmt->close(); die('Empleado no encontrado.'); }
        $e = $res->fetch_assoc();
        $stmt->close();
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <title>Editar Empleado</title>
            <link rel="stylesheet" href="../Vista/Css/estilos.css">
        </head>
        <body>
            <main>
                <h2>Editar Empleado</h2>
                <form method="POST" action="<?php echo $scriptPath; ?>?action=edit">
                    <input type="hidden" name="id" value="<?php echo $e['id_empleado']; ?>">

                    <label>Apellido Paterno:</label><br>
                    <input type="text" name="apellido_paterno" value="<?php echo htmlspecialchars($e['apellido_paterno']); ?>" required><br><br>

                    <label>Apellido Materno:</label><br>
                    <input type="text" name="apellido_materno" value="<?php echo htmlspecialchars($e['apellido_materno']); ?>" required><br><br>

                    <label>Nombre:</label><br>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($e['nombre']); ?>" required><br><br>

                    <label>Rol / Puesto:</label><br>
                    <select name="rol" required>
                        <option value="">Seleccione un rol</option>
                        <?php foreach($roles_disponibles as $r): ?>
                            <option value="<?php echo $r; ?>" <?php echo (isset($e['rol']) && $e['rol'] == $r) ? 'selected' : ''; ?>>
                                <?php echo $r; ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label>Domicilio:</label><br>
                    <input type="text" name="domicilio" value="<?php echo htmlspecialchars($e['domicilio']); ?>" required><br><br>

                    <label>Whatsapp:</label><br>
                    <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($e['whatsapp']); ?>" required><br><br>

                    <label>Email:</label><br>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($e['email']); ?>" required><br><br>

                    <button type="submit" class="Agregar">Guardar cambios</button>
                    <a href="../Vista/empleados.php" class="delete-btn" style="padding:6px 12px; text-decoration:none; margin-left:10px;">Cancelar</a>
                </form>
            </main>
        </body>
        </html>
        <?php
        exit;
    }
}

// POST: Guardar y Editar
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ap = $con->real_escape_string($_POST['apellido_paterno']);
    $am = $con->real_escape_string($_POST['apellido_materno']);
    $nom = $con->real_escape_string($_POST['nombre']);
    $rol = isset($_POST['rol']) ? $con->real_escape_string($_POST['rol']) : 'Empleado General'; 
    $dom = $con->real_escape_string($_POST['domicilio']);
    $whats = $con->real_escape_string($_POST['whatsapp']);
    $email = $con->real_escape_string($_POST['email']);

    $stmt = $con->prepare("INSERT INTO empleados (apellido_paterno, apellido_materno, nombre, rol, domicilio, whatsapp, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('sssssss', $ap, $am, $nom, $rol, $dom, $whats, $email);
        if ($stmt->execute()) { $stmt->close(); redirect_list('agregado'); }
        else { $stmt->close(); die('Error al guardar: ' . $con->error); }
    } else { die('Error en prepare: ' . $con->error); }
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id <= 0) redirect_list('id_invalido');

    $ap = $con->real_escape_string($_POST['apellido_paterno']);
    $am = $con->real_escape_string($_POST['apellido_materno']);
    $nom = $con->real_escape_string($_POST['nombre']);
    $rol = isset($_POST['rol']) ? $con->real_escape_string($_POST['rol']) : 'Empleado General'; 
    $dom = $con->real_escape_string($_POST['domicilio']);
    $whats = $con->real_escape_string($_POST['whatsapp']);
    $email = $con->real_escape_string($_POST['email']);

    $stmt = $con->prepare("UPDATE empleados SET apellido_paterno=?, apellido_materno=?, nombre=?, rol=?, domicilio=?, whatsapp=?, email=? WHERE id_empleado = ?");
    if ($stmt) {
        $stmt->bind_param('sssssssi', $ap, $am, $nom, $rol, $dom, $whats, $email, $id);
        if ($stmt->execute()) { $stmt->close(); redirect_list('actualizado'); }
        else { $stmt->close(); die('Error al actualizar: ' . $con->error); }
    } else { die('Error en prepare: ' . $con->error); }
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id <= 0) redirect_list('id_invalido');
    $stmt = $con->prepare("DELETE FROM empleados WHERE id_empleado = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) { $stmt->close(); redirect_list('eliminado'); }
    else { die('Error: ' . $con->error); }
}

redirect_list();
?>