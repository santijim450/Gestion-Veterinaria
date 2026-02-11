<?php
// Incluir seguridad.php
include __DIR__ . "/seguridad.php"; 
// Admin, Veterinario y Recepcionista pueden CRUD Mascotas
check_access(['administrador', 'veterinario', 'recepcionista']); 

// La sesión ya fue iniciada en seguridad.php, pero si hay otras llamadas la omitimos:
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . "/../Modelo/conexion.php";
// ...

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

function redirect_list($msg = '') {
    $location = '../Vista/mascotas.php';
    if (!empty($msg)) $location .= '?msg=' . urlencode($msg);
    header("Location: $location");
    exit;
}

function ensureImageColumnExists($con) {
    $res = $con->query("SHOW COLUMNS FROM mascotas LIKE 'imagen'");
    if ($res && $res->num_rows === 0) {
        @$con->query("ALTER TABLE mascotas ADD COLUMN imagen VARCHAR(255) NULL");
    }
}

function saveUploadedImage($file) {
    if (!isset($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) return '';
    if ($file['error'] !== UPLOAD_ERR_OK) return 'ERR_UPLOAD';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed_ext)) return 'ERR_TYPE';

    $name = uniqid('msc_') . '.' . $ext;
    $destDir = __DIR__ . '/../Vista/imagenes/mascotas/';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    if (!is_writable($destDir)) return 'ERR_PERM';
    $dest = $destDir . $name;
    if (move_uploaded_file($file['tmp_name'], $dest)) return $name;
    return 'ERR_MOVE';
}

function getClientes($con) {
    $sql = "SELECT id, nombre, apellido_paterno, apellido_materno FROM clientes ORDER BY apellido_paterno";
    $result = $con->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// --- GET: mostrar formularios ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $scriptPath = htmlspecialchars($_SERVER['SCRIPT_NAME']);
    $clientes = getClientes($con);

    // --- Definición de datos de Tipos y Razas (Usaremos esto en ambos formularios) ---
    $especies_razas = [
        'Perro' => ['Labrador', 'Pastor Alemán', 'Chihuahua', 'Pug', 'Golden Retriever', 'Mestizo'],
        'Gato' => ['Siamés', 'Persa', 'Maine Coon', 'Sphynx', 'Bengalí', 'Mestizo'],
        'Ave' => ['Periquito', 'Canario', 'Ninfa', 'Loro Gris', 'Cacatúa'],
        'Otro' => ['Reptil', 'Roedor', 'Pez', 'Otro']
    ];

    if ($action === 'add') {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <title>Agregar Mascota</title>
            <link rel="stylesheet" href="../Vista/Css/estilos.css">
        </head>
        <body>
            <main>
                <h2>Agregar Mascota</h2>
                <form method="POST" action="<?php echo $scriptPath; ?>?action=add" enctype="multipart/form-data">
                    
                    <label>Dueño (Cliente):</label><br>
                    <select name="id_cliente" required>
                        <option value="">Seleccione un cliente</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?php echo $c['id']; ?>">
                                <?php echo htmlspecialchars($c['apellido_paterno'] . ' ' . $c['apellido_materno'] . ', ' . $c['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>
                    
                    <label>Tipo (Especie):</label><br>
                    <select name="tipo" id="select_tipo" required onchange="actualizarRazas()">
                        <option value="">Seleccione el tipo</option>
                        <?php foreach (array_keys($especies_razas) as $tipo): ?>
                            <option value="<?php echo htmlspecialchars($tipo); ?>"><?php echo htmlspecialchars($tipo); ?></option>
                        <?php endforeach; ?>
                    </select><br><br>
                    
                    <label>Raza:</label><br>
                    <select name="raza" id="select_raza" required>
                        <option value="">Seleccione el tipo primero</option>
                    </select><br><br>
                    
                    <label>Nombre:</label><br>
                    <input type="text" name="nombre" required><br><br>
                    <label>Domicilio:</label><br>
                    <input type="text" name="domicilio" required><br><br>
                    <label>Imagen (opcional):</label><br>
                    <input type="file" name="imagen" accept=".webp,image/*"><br><br>
                    <button type="submit" class="Agregar">Guardar</button>
                    <a href="../Vista/mascotas.php" class="delete-btn" style="padding:6px 12px; text-decoration:none; margin-left:10px;">Cancelar</a>
                </form>
            </main>
            <script>
                const especiesRazas = <?php echo json_encode($especies_razas); ?>;
                const selectTipo = document.getElementById('select_tipo');
                const selectRaza = document.getElementById('select_raza');

                function actualizarRazas() {
                    const tipoSeleccionado = selectTipo.value;
                    selectRaza.innerHTML = ''; // Limpiar opciones anteriores

                    if (!tipoSeleccionado) {
                        selectRaza.innerHTML = '<option value="">Seleccione el tipo primero</option>';
                        return;
                    }

                    // Obtener lista de razas o usar un valor por defecto
                    const razas = especiesRazas[tipoSeleccionado] || ['Mestizo', 'Otra'];
                    
                    // Añadir la opción por defecto/placeholder
                    const defaultOption = document.createElement('option');
                    defaultOption.value = "";
                    defaultOption.textContent = "Seleccione una raza";
                    selectRaza.appendChild(defaultOption);

                    razas.forEach(raza => {
                        const option = document.createElement('option');
                        option.value = raza;
                        option.textContent = raza;
                        selectRaza.appendChild(option);
                    });
                }
            </script>
        </body>
        </html>
        <?php
        exit;
    }
    
    if ($action === 'edit') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) redirect_list('id_invalido');
        $stmt = $con->prepare("SELECT * FROM mascotas WHERE id = ?");
        if (!$stmt) die('Error en prepare: ' . $con->error);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) { $stmt->close(); die('Mascota no encontrada.'); }
        $m = $res->fetch_assoc();
        $stmt->close();
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <title>Editar Mascota</title>
            <link rel="stylesheet" href="../Vista/Css/estilos.css">
        </head>
        <body>
            <main>
                <h2>Editar Mascota</h2>
                <form method="POST" action="<?php echo $scriptPath; ?>?action=edit" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                    
                    <label>Dueño (Cliente):</label><br>
                    <select name="id_cliente" required>
                        <option value="">Seleccione un cliente</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $m['id_cliente'] ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($c['apellido_paterno'] . ' ' . $c['apellido_materno'] . ', ' . $c['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>
                    
                    <label>Tipo (Especie):</label><br>
                    <select name="tipo" id="select_tipo_edit" required onchange="actualizarRazasEdit()">
                        <option value="">Seleccione el tipo</option>
                        <?php foreach (array_keys($especies_razas) as $tipo): ?>
                            <option value="<?php echo htmlspecialchars($tipo); ?>" <?php echo ($tipo == $m['tipo'] ? 'selected' : ''); ?>><?php echo htmlspecialchars($tipo); ?></option>
                        <?php endforeach; ?>
                    </select><br><br>
                    
                    <label>Raza:</label><br>
                    <select name="raza" id="select_raza_edit" required>
                        <option value="<?php echo htmlspecialchars($m['raza']); ?>"><?php echo htmlspecialchars($m['raza']); ?></option>
                    </select><br><br>

                    <label>Nombre:</label><br>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($m['nombre']); ?>" required><br><br>
                    <label>Domicilio:</label><br>
                    <input type="text" name="domicilio" value="<?php echo htmlspecialchars($m['domicilio']); ?>" required><br><br>
                    <label>Imagen (opcional):</label><br>
                    <?php if (!empty($m['imagen'])): ?>
                        <div style="margin-bottom:8px;">
                            <img src="../Vista/imagenes/mascotas/<?php echo htmlspecialchars($m['imagen']); ?>" alt="" style="max-width:120px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="imagen" accept=".webp,image/*"><br><br>
                    <button type="submit" class="Agregar">Guardar cambios</button>
                    <a href="../Vista/mascotas.php" class="delete-btn" style="padding:6px 12px; text-decoration:none; margin-left:10px;">Cancelar</a>
                </form>
            </main>
            <script>
                const especiesRazasEdit = <?php echo json_encode($especies_razas); ?>;
                const selectTipoEdit = document.getElementById('select_tipo_edit');
                const selectRazaEdit = document.getElementById('select_raza_edit');
                const razaActual = "<?php echo htmlspecialchars($m['raza']); ?>";

                function actualizarRazasEdit() {
                    const tipoSeleccionado = selectTipoEdit.value;
                    selectRazaEdit.innerHTML = ''; // Limpiar opciones anteriores

                    if (!tipoSeleccionado) {
                        selectRazaEdit.innerHTML = '<option value="">Seleccione el tipo primero</option>';
                        return;
                    }

                    const razas = especiesRazasEdit[tipoSeleccionado] || ['Mestizo', 'Otra'];
                    
                    // Añadir la opción por defecto
                    const defaultOption = document.createElement('option');
                    defaultOption.value = "";
                    defaultOption.textContent = "Seleccione una raza";
                    selectRazaEdit.appendChild(defaultOption);

                    razas.forEach(raza => {
                        const option = document.createElement('option');
                        option.value = raza;
                        option.textContent = raza;
                        // Marcar como seleccionado si coincide con la raza actual de la mascota
                        if (raza === razaActual) {
                            option.selected = true;
                        }
                        selectRazaEdit.appendChild(option);
                    });
                    
                    // Si la raza actual no está en la nueva lista, la agregamos si es necesario (ej. si fue escrita manualmente)
                    if (!razas.includes(razaActual) && razaActual !== "" && selectRazaEdit.querySelector('[value="' + razaActual + '"]') === null) {
                        const currentRazaOption = document.createElement('option');
                        currentRazaOption.value = razaActual;
                        currentRazaOption.textContent = razaActual + " (actual)";
                        currentRazaOption.selected = true;
                        selectRazaEdit.appendChild(currentRazaOption);
                    }
                }
                
                // Ejecutar al cargar la página para llenar las razas iniciales
                document.addEventListener('DOMContentLoaded', actualizarRazasEdit);
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}

// --- POST: añadir o editar ---
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    ensureImageColumnExists($con);
    $id_cliente = isset($_POST['id_cliente']) ? intval($_POST['id_cliente']) : 0;
    // Captura los valores de los selectores
    $tipo = isset($_POST['tipo']) ? $con->real_escape_string($_POST['tipo']) : '';
    $raza = isset($_POST['raza']) ? $con->real_escape_string($_POST['raza']) : '';
    $nombre = isset($_POST['nombre']) ? $con->real_escape_string($_POST['nombre']) : '';
    $domicilio = isset($_POST['domicilio']) ? $con->real_escape_string($_POST['domicilio']) : '';
    
    $stmt = $con->prepare("INSERT INTO mascotas (id_cliente, tipo, raza, nombre, domicilio) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('issss', $id_cliente, $tipo, $raza, $nombre, $domicilio);
        if ($stmt->execute()) {
            $insertId = $con->insert_id;
            $stmt->close();
            $uploaded = saveUploadedImage(isset($_FILES['imagen']) ? $_FILES['imagen'] : null);
            if ($uploaded === 'ERR_TYPE') redirect_list('error_tipo');
            if ($uploaded === 'ERR_UPLOAD' || $uploaded === 'ERR_MOVE' || $uploaded === 'ERR_PERM') redirect_list('error_subida');
            if ($uploaded !== '') {
                $uStmt = $con->prepare("UPDATE mascotas SET imagen = ? WHERE id = ?");
                if ($uStmt) {
                    $uStmt->bind_param('si', $uploaded, $insertId);
                    $uStmt->execute();
                    $uStmt->close();
                }
            }
            redirect_list('agregado');
        } else { $stmt->close(); die('Error al guardar: ' . $con->error); }
    } else { die('Error en prepare: ' . $con->error); }
}

// Editar mascota
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $id_cliente = isset($_POST['id_cliente']) ? intval($_POST['id_cliente']) : 0;
    // Captura los valores de los selectores
    $tipo = isset($_POST['tipo']) ? $con->real_escape_string($_POST['tipo']) : '';
    $raza = isset($_POST['raza']) ? $con->real_escape_string($_POST['raza']) : '';
    $nombre = isset($_POST['nombre']) ? $con->real_escape_string($_POST['nombre']) : '';
    $domicilio = isset($_POST['domicilio']) ? $con->real_escape_string($_POST['domicilio']) : '';
    if ($id <= 0) redirect_list('id_invalido');
    
    $stmt = $con->prepare("UPDATE mascotas SET id_cliente=?, tipo=?, raza=?, nombre=?, domicilio=? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('issssi', $id_cliente, $tipo, $raza, $nombre, $domicilio, $id);
        if ($stmt->execute()) {
            $stmt->close();
            $uploaded = saveUploadedImage(isset($_FILES['imagen']) ? $_FILES['imagen'] : null);
            if ($uploaded === 'ERR_TYPE') redirect_list('error_tipo');
            if ($uploaded === 'ERR_UPLOAD' || $uploaded === 'ERR_MOVE' || $uploaded === 'ERR_PERM') redirect_list('error_subida');
            if ($uploaded !== '') {
                $sel = $con->prepare("SELECT imagen FROM mascotas WHERE id = ?");
                if ($sel) {
                    $sel->bind_param('i', $id);
                    $sel->execute();
                    $r = $sel->get_result();
                    if ($r && $r->num_rows > 0) {
                        $rowOld = $r->fetch_assoc();
                        if (!empty($rowOld['imagen'])) {
                            $oldPath = __DIR__ . '/../Vista/imagenes/mascotas/' . $rowOld['imagen'];
                            if (file_exists($oldPath)) @unlink($oldPath);
                        }
                    }
                    $sel->close();
                }
                $uStmt = $con->prepare("UPDATE mascotas SET imagen = ? WHERE id = ?");
                if ($uStmt) {
                    $uStmt->bind_param('si', $uploaded, $id);
                    $uStmt->execute();
                    $uStmt->close();
                }
            }
            redirect_list('actualizado');
        } else { $stmt->close(); die('Error al actualizar: ' . $con->error); }
    } else { die('Error en prepare: ' . $con->error); }
}

// Eliminar mascota
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id <= 0) redirect_list('id_invalido');
    $colRes = $con->query("SHOW COLUMNS FROM mascotas LIKE 'imagen'");
    if ($colRes && $colRes->num_rows > 0) {
        $sel = $con->prepare("SELECT imagen FROM mascotas WHERE id = ?");
        if ($sel) {
            $sel->bind_param('i', $id);
            $sel->execute();
            $res = $sel->get_result();
            if ($res && $res->num_rows > 0) {
                $r = $res->fetch_assoc();
                if (!empty($r['imagen'])) {
                    $path = __DIR__ . '/../Vista/imagenes/mascotas/' . $r['imagen'];
                    if (file_exists($path)) @unlink($path);
                }
            }
            $sel->close();
        }
    }
    $stmt = $con->prepare("DELETE FROM mascotas WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) { $stmt->close(); redirect_list('eliminado'); }
        else { $stmt->close(); die('Error al eliminar: ' . $con->error); }
    } else { die('Error en prepare: ' . $con->error); }
}
redirect_list();
?>