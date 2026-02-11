<?php
include __DIR__ . "/../Controlador/seguridad.php"; 
check_access(['administrador']); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . "/../Modelo/conexion.php";

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

function redirect_list($msg = '') {
    $location = '../Vista/servicios.php';
    if (!empty($msg)) $location .= '?msg=' . urlencode($msg);
    header("Location: $location");
    exit;
}

// --- Helpers para imágenes ---
function ensureImageColumnExists($con) {
    $res = $con->query("SHOW COLUMNS FROM servicios LIKE 'imagen'");
    if ($res && $res->num_rows === 0) {
        @$con->query("ALTER TABLE servicios ADD COLUMN imagen VARCHAR(255) NULL");
    }
}

function saveUploadedImage($file) {
    if (!isset($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) return '';
    if ($file['error'] !== UPLOAD_ERR_OK) return 'ERR_UPLOAD';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed_ext)) return 'ERR_TYPE';
    $name = uniqid('srv_') . '.' . $ext;
    $destDir = __DIR__ . '/../Vista/imagenes/servicios/';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    $dest = $destDir . $name;
    if (move_uploaded_file($file['tmp_name'], $dest)) return $name;
    return 'ERR_MOVE';
}

// GET: Formularios
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $scriptPath = htmlspecialchars($_SERVER['SCRIPT_NAME']);
    $servicios_comunes = [
        'Consulta General', 'Vacunación', 'Desparasitación', 'Esterilización/Castración', 
        'Cirugía Mayor', 'Análisis de Laboratorio', 'Radiografía', 'Limpieza Dental', 
        'Hospedaje', 'Estética y Baño'
    ];

    if ($action === 'add') {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <title>Agregar Servicio</title>
            <link rel="stylesheet" href="../Vista/Css/estilos.css">
        </head>
        <body>
            <main>
                <h2>Agregar Servicio</h2>
                <form method="POST" action="<?php echo $scriptPath; ?>?action=add" enctype="multipart/form-data">
                    <label>Nombre del Servicio:</label><br>
                    <select name="nombre_servicio" required>
                        <option value="">Seleccione un servicio</option>
                        <?php foreach ($servicios_comunes as $servicio): ?>
                            <option value="<?php echo htmlspecialchars($servicio); ?>"><?php echo htmlspecialchars($servicio); ?></option>
                        <?php endforeach; ?>
                    </select><br><br>
                    
                    <label>Descripción:</label><br>
                    <textarea name="descripcion" rows="4" cols="50" required></textarea><br><br>

                    <label>Precio ($):</label><br>
                    <input type="number" step="0.01" name="precio" required><br><br>

                    <label>Duración (en minutos):</label><br>
                    <input type="number" id="duracionInput" name="duracion" value="30" required min="5" oninput="calcularHoras(this.value)">
                    <span id="duracionTexto" style="color:#666; font-size:0.9em; margin-left:10px;">(30 min)</span>
                    <br><small style="color:#888;">Ej: 60 = 1 hora, 90 = 1h 30m, 120 = 2 horas.</small><br><br>

                    <label>Imagen (opcional):</label><br>
                    <input type="file" name="imagen" accept=".webp,image/*"><br><br>

                    <button type="submit" class="Agregar">Guardar</button>
                    <a href="../Vista/servicios.php" class="delete-btn" style="padding:6px 12px; text-decoration:none; margin-left:10px;">Cancelar</a>
                </form>
                <script>
                    function calcularHoras(minutos) {
                        minutos = parseInt(minutos);
                        if(!minutos) { document.getElementById('duracionTexto').innerText = ''; return; }
                        let h = Math.floor(minutos / 60);
                        let m = minutos % 60;
                        let txt = "Equivale a: ";
                        if(h > 0) txt += h + "h ";
                        if(m > 0 || h === 0) txt += m + "min";
                        document.getElementById('duracionTexto').innerText = txt;
                    }
                </script>
            </main>
        </body>
        </html>
        <?php
        exit;
    }

    if ($action === 'edit') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) redirect_list('id_invalido');

        $stmt = $con->prepare("SELECT * FROM servicios WHERE id_servicio = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) { $stmt->close(); die('Servicio no encontrado.'); }
        $s = $res->fetch_assoc();
        $stmt->close();
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <title>Editar Servicio</title>
            <link rel="stylesheet" href="../Vista/Css/estilos.css">
        </head>
        <body>
            <main>
                <h2>Editar Servicio</h2>
                <form method="POST" action="<?php echo $scriptPath; ?>?action=edit" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $s['id_servicio']; ?>">

                    <label>Nombre del Servicio:</label><br>
                    <select name="nombre_servicio" required>
                        <option value="">Seleccione un servicio</option>
                        <?php 
                        $opciones_servicios = array_unique(array_merge($servicios_comunes, [$s['nombre_servicio']]));
                        foreach ($opciones_servicios as $servicio): ?>
                            <option value="<?php echo htmlspecialchars($servicio); ?>"
                                <?php echo ($s['nombre_servicio'] == $servicio) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($servicio); ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>
                    
                    <label>Descripción:</label><br>
                    <textarea name="descripcion" rows="4" cols="50" required><?php echo htmlspecialchars($s['descripcion']); ?></textarea><br><br>

                    <label>Precio ($):</label><br>
                    <input type="number" step="0.01" name="precio" value="<?php echo htmlspecialchars($s['precio']); ?>" required><br><br>

                    <label>Duración (en minutos):</label><br>
                    <input type="number" id="duracionInput" name="duracion" value="<?php echo isset($s['duracion']) ? htmlspecialchars($s['duracion']) : '30'; ?>" required min="5" oninput="calcularHoras(this.value)">
                    <span id="duracionTexto" style="color:#666; font-size:0.9em; margin-left:10px;"></span>
                    <br><small style="color:#888;">Calcula el tiempo de bloqueo en la agenda.</small><br><br>

                    <label>Imagen (opcional):</label><br>
                    <?php if (!empty($s['imagen'])): ?>
                        <div style="margin-bottom:8px;"><img src="../Vista/imagenes/servicios/<?php echo htmlspecialchars($s['imagen']); ?>" alt="" style="max-width:120px;"></div>
                    <?php endif; ?>
                    <input type="file" name="imagen" accept=".webp,image/*"><br><br>

                    <button type="submit" class="Agregar">Guardar cambios</button>
                    <a href="../Vista/servicios.php" class="delete-btn" style="padding:6px 12px; text-decoration:none; margin-left:10px;">Cancelar</a>
                </form>
                <script>
                    function calcularHoras(minutos) {
                        minutos = parseInt(minutos);
                        if(!minutos) { document.getElementById('duracionTexto').innerText = ''; return; }
                        let h = Math.floor(minutos / 60);
                        let m = minutos % 60;
                        let txt = "Equivale a: ";
                        if(h > 0) txt += h + "h ";
                        if(m > 0 || h === 0) txt += m + "min";
                        document.getElementById('duracionTexto').innerText = txt;
                    }
                    // Ejecutar al cargar para mostrar el valor actual
                    document.addEventListener('DOMContentLoaded', function() {
                        calcularHoras(document.getElementById('duracionInput').value);
                    });
                </script>
            </main>
        </body>
        </html>
        <?php
        exit;
    }
}

// POST handlers
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = isset($_POST['nombre_servicio']) ? $con->real_escape_string($_POST['nombre_servicio']) : '';
    $descripcion = isset($_POST['descripcion']) ? $con->real_escape_string($_POST['descripcion']) : '';
    $precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;
    $duracion = isset($_POST['duracion']) ? intval($_POST['duracion']) : 30; 

    ensureImageColumnExists($con);
    $stmt = $con->prepare("INSERT INTO servicios (nombre_servicio, descripcion, precio, duracion) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('ssdi', $nombre, $descripcion, $precio, $duracion);
        if ($stmt->execute()) {
            $insertId = $con->insert_id;
            $stmt->close();
            $uploaded = saveUploadedImage(isset($_FILES['imagen']) ? $_FILES['imagen'] : null);
            if ($uploaded && !in_array($uploaded, ['ERR_TYPE', 'ERR_UPLOAD', 'ERR_MOVE'])) {
                $uStmt = $con->prepare("UPDATE servicios SET imagen = ? WHERE id_servicio = ?");
                $uStmt->bind_param('si', $uploaded, $insertId);
                $uStmt->execute(); $uStmt->close();
            }
            redirect_list('agregado');
        } else { $stmt->close(); die('Error al guardar: ' . $con->error); }
    } else { die('Error en prepare: ' . $con->error); }
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nombre = isset($_POST['nombre_servicio']) ? $con->real_escape_string($_POST['nombre_servicio']) : '';
    $descripcion = isset($_POST['descripcion']) ? $con->real_escape_string($_POST['descripcion']) : '';
    $precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;
    $duracion = isset($_POST['duracion']) ? intval($_POST['duracion']) : 30;
    if ($id <= 0) redirect_list('id_invalido');

    $stmt = $con->prepare("UPDATE servicios SET nombre_servicio=?, descripcion=?, precio=?, duracion=? WHERE id_servicio = ?");
    if ($stmt) {
        $stmt->bind_param('ssdii', $nombre, $descripcion, $precio, $duracion, $id);
        if ($stmt->execute()) {
            $stmt->close();
            $uploaded = saveUploadedImage(isset($_FILES['imagen']) ? $_FILES['imagen'] : null);
            if ($uploaded && !in_array($uploaded, ['ERR_TYPE', 'ERR_UPLOAD', 'ERR_MOVE'])) {
                $sel = $con->prepare("SELECT imagen FROM servicios WHERE id_servicio = ?");
                $sel->bind_param('i', $id); $sel->execute(); $r = $sel->get_result();
                if ($r && $r->num_rows > 0) {
                    $rowOld = $r->fetch_assoc();
                    if (!empty($rowOld['imagen'])) @unlink(__DIR__ . '/../Vista/imagenes/servicios/' . $rowOld['imagen']);
                }
                $sel->close();
                $uStmt = $con->prepare("UPDATE servicios SET imagen = ? WHERE id_servicio = ?");
                $uStmt->bind_param('si', $uploaded, $id);
                $uStmt->execute(); $uStmt->close();
            }
            redirect_list('actualizado');
        } else { $stmt->close(); die('Error al actualizar: ' . $con->error); }
    } else { die('Error en prepare: ' . $con->error); }
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id <= 0) redirect_list('id_invalido');
    $sel = $con->prepare("SELECT imagen FROM servicios WHERE id_servicio = ?");
    $sel->bind_param('i', $id); $sel->execute(); $res = $sel->get_result();
    if($res && $r=$res->fetch_assoc()) {
         if(!empty($r['imagen'])) @unlink(__DIR__ . '/../Vista/imagenes/servicios/' . $r['imagen']);
    }
    $stmt = $con->prepare("DELETE FROM servicios WHERE id_servicio = ?");
    $stmt->bind_param('i', $id);
    if($stmt->execute()) redirect_list('eliminado');
    else die($con->error);
}
redirect_list();
?>