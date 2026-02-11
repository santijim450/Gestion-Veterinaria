<?php
// Incluir seguridad.php
include __DIR__ . "/seguridad.php"; 
// Solo Admin y Veterinario pueden CRUD Historial
check_access(['administrador', 'veterinario']); 

// El resto del código continúa...
session_start();
include __DIR__ . "/../Modelo/conexion.php";
// ...

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// Helper para obtener opciones de select
function getFormOptions($con) {
    $servicios = $con->query("SELECT id_servicio, nombre_servicio FROM servicios ORDER BY nombre_servicio");
    $mascotas = $con->query("SELECT id, nombre, id_cliente FROM mascotas ORDER BY nombre");
    return [
        'servicios' => $servicios ? $servicios->fetch_all(MYSQLI_ASSOC) : [],
        'mascotas' => $mascotas ? $mascotas->fetch_all(MYSQLI_ASSOC) : []
    ];
}

// Helper para redirigir a la lista de historial de una mascota
function redirect_historial($id_mascota, $msg = '') {
    $location = '../Vista/historial.php?id_mascota=' . $id_mascota;
    if (!empty($msg)) $location .= '&msg=' . urlencode($msg);
    header("Location: $location");
    exit;
}

// --- GET: mostrar formularios ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $scriptPath = htmlspecialchars($_SERVER['SCRIPT_NAME']);
    $options = getFormOptions($con);
    $id_mascota_url = isset($_GET['id_mascota']) ? intval($_GET['id_mascota']) : 0;
    
    // Obtener nombre de la mascota para el título
    $nombre_mascota = 'Mascota Desconocida';
    if ($id_mascota_url > 0) {
        $stmt_m = $con->prepare("SELECT nombre FROM mascotas WHERE id = ?");
        $stmt_m->bind_param('i', $id_mascota_url);
        $stmt_m->execute();
        $res_m = $stmt_m->get_result();
        if ($res_m->num_rows > 0) {
            $nombre_mascota = htmlspecialchars($res_m->fetch_assoc()['nombre']);
        }
        $stmt_m->close();
    }
    
    // Formulario de AGREGAR
    if ($action === 'add' && $id_mascota_url > 0) {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <title>Registrar Historial</title>
            <link rel="stylesheet" href="../Vista/Css/estilos.css">
        </head>
        <body>
            <main>
                <h2>Nueva Visita para <?php echo $nombre_mascota; ?></h2>
                <form method="POST" action="<?php echo $scriptPath; ?>?action=add">
                    <input type="hidden" name="id_mascota" value="<?php echo $id_mascota_url; ?>">
                    
                    <label>Fecha de Visita:</label><br>
                    <input type="date" name="fecha_visita" required value="<?php echo date('Y-m-d'); ?>"><br><br>

                    <label>Motivo de Visita:</label><br>
                    <input type="text" name="motivo_visita" required><br><br>
                    
                    <label>Servicio Realizado:</label><br>
                    <select name="id_servicio">
                        <option value="">(Ninguno)</option>
                        <?php foreach ($options['servicios'] as $s): ?>
                            <option value="<?php echo $s['id_servicio']; ?>"><?php echo htmlspecialchars($s['nombre_servicio']); ?></option>
                        <?php endforeach; ?>
                    </select><br><br>
                    
                    <label>Diagnóstico/Observaciones:</label><br>
                    <textarea name="diagnostico" rows="4" cols="50"></textarea><br><br>

                    <label>Tratamiento/Medicamentos:</label><br>
                    <textarea name="tratamiento" rows="4" cols="50"></textarea><br><br>
                    
                    <label>Costo (Opcional):</label><br>
                    <input type="number" step="0.01" name="costo" value="0.00"><br><br>

                    <button type="submit" class="Agregar">Guardar Historial</button>
                    <a href="../Vista/historial.php?id_mascota=<?php echo $id_mascota_url; ?>" class="delete-btn" style="padding:6px 12px; text-decoration:none; margin-left:10px;">Cancelar</a>
                </form>
            </main>
        </body>
        </html>
        <?php
        exit;
    }

    // Formulario de EDITAR
    if ($action === 'edit') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) die('ID de historial inválido.');

        $stmt = $con->prepare("SELECT * FROM historial_medico WHERE id_historial = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) { $stmt->close(); die('Registro de historial no encontrado.'); }
        $h = $res->fetch_assoc();
        $stmt->close();
        
        $id_mascota_url = $h['id_mascota']; // Usar el ID real del registro
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <title>Editar Historial</title>
            <link rel="stylesheet" href="../Vista/Css/estilos.css">
        </head>
        <body>
            <main>
                <h2>Editar Historial de <?php echo $nombre_mascota; ?></h2>
                <form method="POST" action="<?php echo $scriptPath; ?>?action=edit">
                    <input type="hidden" name="id_historial" value="<?php echo $h['id_historial']; ?>">
                    <input type="hidden" name="id_mascota" value="<?php echo $h['id_mascota']; ?>">
                    
                    <label>Fecha de Visita:</label><br>
                    <input type="date" name="fecha_visita" required value="<?php echo htmlspecialchars($h['fecha_visita']); ?>"><br><br>

                    <label>Motivo de Visita:</label><br>
                    <input type="text" name="motivo_visita" required value="<?php echo htmlspecialchars($h['motivo_visita']); ?>"><br><br>
                    
                    <label>Servicio Realizado:</label><br>
                    <select name="id_servicio">
                        <option value="">(Ninguno)</option>
                        <?php foreach ($options['servicios'] as $s): ?>
                            <option value="<?php echo $s['id_servicio']; ?>" <?php echo ($h['id_servicio'] == $s['id_servicio'] ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($s['nombre_servicio']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>
                    
                    <label>Diagnóstico/Observaciones:</label><br>
                    <textarea name="diagnostico" rows="4" cols="50"><?php echo htmlspecialchars($h['diagnostico']); ?></textarea><br><br>

                    <label>Tratamiento/Medicamentos:</label><br>
                    <textarea name="tratamiento" rows="4" cols="50"><?php echo htmlspecialchars($h['tratamiento']); ?></textarea><br><br>
                    
                    <label>Costo (Opcional):</label><br>
                    <input type="number" step="0.01" name="costo" value="<?php echo htmlspecialchars($h['costo']); ?>"><br><br>

                    <button type="submit" class="Agregar">Guardar Cambios</button>
                    <a href="../Vista/historial.php?id_mascota=<?php echo $id_mascota_url; ?>" class="delete-btn" style="padding:6px 12px; text-decoration:none; margin-left:10px;">Cancelar</a>
                </form>
            </main>
        </body>
        </html>
        <?php
        exit;
    }
}

// --- POST: añadir / editar / eliminar ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_mascota = isset($_POST['id_mascota']) ? intval($_POST['id_mascota']) : 0;
    if ($id_mascota <= 0) die('ID de mascota requerido.');
    
    if ($action === 'add') {
        $fecha = $_POST['fecha_visita'];
        $motivo = $con->real_escape_string($_POST['motivo_visita']);
        $id_servicio = isset($_POST['id_servicio']) && $_POST['id_servicio'] !== '' ? intval($_POST['id_servicio']) : NULL;
        $diagnostico = $con->real_escape_string($_POST['diagnostico']);
        $tratamiento = $con->real_escape_string($_POST['tratamiento']);
        $costo = isset($_POST['costo']) ? floatval($_POST['costo']) : 0.00; // float

        $stmt = $con->prepare("INSERT INTO historial_medico (id_mascota, fecha_visita, motivo_visita, id_servicio, diagnostico, tratamiento, costo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        // CORRECCIÓN: 7 variables, 7 tipos: i(id_mascota), s(fecha), s(motivo), i(id_servicio), s(diagnostico), s(tratamiento), d(costo)
        $stmt->bind_param('ississs', $id_mascota, $fecha, $motivo, $id_servicio, $diagnostico, $tratamiento, $costo);

        if ($stmt->execute()) { $stmt->close(); redirect_historial($id_mascota, 'historial_agregado'); }
        else { $stmt->close(); die('Error al guardar historial: ' . $con->error); }
    }

    if ($action === 'edit') {
        $id_historial = isset($_POST['id_historial']) ? intval($_POST['id_historial']) : 0;
        if ($id_historial <= 0) die('ID de historial inválido.');

        $fecha = $_POST['fecha_visita'];
        $motivo = $con->real_escape_string($_POST['motivo_visita']);
        $id_servicio = isset($_POST['id_servicio']) && $_POST['id_servicio'] !== '' ? intval($_POST['id_servicio']) : NULL;
        $diagnostico = $con->real_escape_string($_POST['diagnostico']);
        $tratamiento = $con->real_escape_string($_POST['tratamiento']);
        $costo = isset($_POST['costo']) ? floatval($_POST['costo']) : 0.00; // float

        $stmt = $con->prepare("UPDATE historial_medico SET fecha_visita=?, motivo_visita=?, id_servicio=?, diagnostico=?, tratamiento=?, costo=? WHERE id_historial=? AND id_mascota=?");
        
        // CORRECCIÓN: 8 variables, 8 tipos: s, s, i, s, s, d, i, i
        $stmt->bind_param('ssisidii', $fecha, $motivo, $id_servicio, $diagnostico, $tratamiento, $costo, $id_historial, $id_mascota);

        if ($stmt->execute()) { $stmt->close(); redirect_historial($id_mascota, 'historial_actualizado'); }
        else { $stmt->close(); die('Error al actualizar historial: ' . $con->error); }
    }
    
    if ($action === 'delete') {
        $id_historial = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id_historial <= 0) die('ID de historial inválido.');

        $id_mascota_redirect = $id_mascota; 

        $stmt = $con->prepare("DELETE FROM historial_medico WHERE id_historial = ?");
        $stmt->bind_param('i', $id_historial);
        
        if ($stmt->execute()) { $stmt->close(); redirect_historial($id_mascota_redirect, 'historial_eliminado'); }
        else { $stmt->close(); die('Error al eliminar historial: ' . $con->error); }
    }
}
?>