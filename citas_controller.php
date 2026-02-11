<?php
include __DIR__ . "/seguridad.php";
include __DIR__ . "/../Modelo/conexion.php";

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

if ($action === 'add' || $action === 'edit' || $action === 'delete' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    check_access(['administrador', 'veterinario', 'recepcionista']);
}
$can_crud = in_array($_SESSION['usuario_rol'] ?? '', ['administrador', 'recepcionista']);

function redirect_list($msg = '') {
    $location = '../Vista/citas.php';
    if (!empty($msg)) $location .= '?msg=' . urlencode($msg);
    header("Location: $location");
    exit;
}

// Función auxiliar para mostrar "1h 30m" en lugar de "90"
function formatDuracion($min) {
    $h = floor($min / 60);
    $m = $min % 60;
    $txt = '';
    if ($h > 0) $txt .= $h . 'h ';
    if ($m > 0 || $h == 0) $txt .= str_pad($m, 2, '0', STR_PAD_LEFT) . 'm';
    return trim($txt);
}

function getFormOptions($con) {
    $clientes = $con->query("SELECT id, nombre, apellido_paterno, apellido_materno FROM clientes ORDER BY apellido_paterno");
    $mascotas = $con->query("SELECT id, nombre, tipo, raza FROM mascotas ORDER BY nombre");
    $servicios = $con->query("SELECT id_servicio, nombre_servicio, duracion FROM servicios ORDER BY nombre_servicio");
    $veterinarios = $con->query("SELECT id_usuario, nombre FROM usuarios WHERE rol = 'veterinario' ORDER BY nombre");
    
    return [
        'clientes' => $clientes ? $clientes->fetch_all(MYSQLI_ASSOC) : [],
        'mascotas' => $mascotas ? $mascotas->fetch_all(MYSQLI_ASSOC) : [],
        'servicios' => $servicios ? $servicios->fetch_all(MYSQLI_ASSOC) : [],
        'veterinarios' => $veterinarios ? $veterinarios->fetch_all(MYSQLI_ASSOC) : []
    ];
}

// LÓGICA ANTI-EMPALMES (Funciona igual para 30 min o 3 horas)
function hayConflictoHorario($con, $fecha, $hora_inicio, $id_servicio, $id_veterinario, $id_cita_actual = 0) {
    // 1. Duración de la NUEVA cita
    $stmtS = $con->prepare("SELECT duracion FROM servicios WHERE id_servicio = ?");
    $stmtS->bind_param('i', $id_servicio);
    $stmtS->execute();
    $resS = $stmtS->get_result();
    $duracion_nueva = ($resS && $row = $resS->fetch_assoc()) ? $row['duracion'] : 30; 
    $stmtS->close();

    $inicio_nuevo = strtotime($fecha . ' ' . $hora_inicio);
    $fin_nuevo = $inicio_nuevo + ($duracion_nueva * 60);

    // 2. Revisar citas EXISTENTES
    $sql = "SELECT c.hora, s.duracion 
            FROM citas c 
            JOIN servicios s ON c.id_servicio = s.id_servicio
            WHERE c.fecha = ? AND c.id_veterinario = ? AND c.estado != 'Cancelada' AND c.id_cita != ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('sii', $fecha, $id_veterinario, $id_cita_actual);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $inicio_existente = strtotime($fecha . ' ' . $row['hora']);
        $fin_existente = $inicio_existente + ($row['duracion'] * 60);

        // Si se solapan los tiempos
        if ($inicio_nuevo < $fin_existente && $fin_nuevo > $inicio_existente) {
            return true; 
        }
    }
    return false;
}

// GET: Formularios
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $scriptPath = htmlspecialchars($_SERVER['SCRIPT_NAME']);
    $options = getFormOptions($con);

    if ($action === 'add') {
        if (!$can_crud) redirect_list('acceso_denegado_add');
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <title>Agendar Cita</title>
            <link rel="stylesheet" href="../Vista/Css/estilos.css">
        </head>
        <body>
            <main>
                <h2>Agendar Nueva Cita</h2>
                <?php if(isset($_GET['error']) && $_GET['error'] == 'empalme'): ?>
                    <div style="background:#f8d7da; color:#721c24; padding:10px; margin-bottom:15px; border-radius:4px;">
                        ⚠️ <strong>Horario no disponible:</strong> El veterinario ya tiene una cita que se cruza con este horario debido a la duración de los servicios.
                    </div>
                <?php endif; ?>
                <form method="POST" action="<?php echo $scriptPath; ?>?action=add">
                    <label>Cliente:</label><br>
                    <select name="id_cliente" required>
                        <option value="">Seleccione un cliente</option>
                        <?php foreach ($options['clientes'] as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre'] . ' ' . $c['apellido_paterno']); ?></option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label>Mascota:</label><br>
                    <select name="id_mascota" required>
                        <option value="">Seleccione una mascota</option>
                        <?php foreach ($options['mascotas'] as $m): ?>
                            <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['nombre'] . ' (' . $m['tipo'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select><br><br>
                    
                    <label>Servicio (Duración):</label><br>
                    <select name="id_servicio" required>
                        <option value="">Seleccione un servicio</option>
                        <?php foreach ($options['servicios'] as $s): ?>
                            <option value="<?php echo $s['id_servicio']; ?>">
                                <?php echo htmlspecialchars($s['nombre_servicio'] . ' (' . formatDuracion($s['duracion'] ?? 30) . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label>Veterinario:</label><br>
                    <select name="id_veterinario" required>
                        <option value="">Seleccione un veterinario</option>
                        <?php foreach ($options['veterinarios'] as $v): ?>
                            <option value="<?php echo $v['id_usuario']; ?>"><?php echo htmlspecialchars($v['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label>Fecha:</label><br>
                    <input type="date" name="fecha" required min="<?php echo date('Y-m-d'); ?>"><br><br>
                    <label>Hora:</label><br>
                    <input type="time" name="hora" required><br><br>

                    <label>Estado:</label><br>
                    <select name="estado" required>
                        <option value="Agendada">Agendada</option>
                        <option value="Confirmada">Confirmada</option>
                    </select><br><br>

                    <button type="submit" class="Agregar">Guardar Cita</button>
                    <a href="../Vista/citas.php" class="delete-btn" style="padding:6px 12px; text-decoration:none; margin-left:10px;">Cancelar</a>
                </form>
            </main>
        </body>
        </html>
        <?php
        exit;
    }

    if ($action === 'edit') {
        if (!$can_crud) redirect_list('acceso_denegado_edit');
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) redirect_list('id_invalido');

        $stmt = $con->prepare("SELECT * FROM citas WHERE id_cita = ?");
        $stmt->bind_param('i', $id); $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) { $stmt->close(); die('Cita no encontrada.'); }
        $c = $res->fetch_assoc();
        $stmt->close();
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <title>Editar Cita</title>
            <link rel="stylesheet" href="../Vista/Css/estilos.css">
        </head>
        <body>
            <main>
                <h2>Editar Cita</h2>
                 <?php if(isset($_GET['error']) && $_GET['error'] == 'empalme'): ?>
                    <div style="background:#f8d7da; color:#721c24; padding:10px; margin-bottom:15px; border-radius:4px;">
                        ⚠️ <strong>Horario no disponible:</strong> Conflicto de horarios por duración.
                    </div>
                <?php endif; ?>
                <form method="POST" action="<?php echo $scriptPath; ?>?action=edit">
                    <input type="hidden" name="id" value="<?php echo $c['id_cita']; ?>">

                    <label>Cliente:</label><br>
                    <select name="id_cliente" required>
                        <?php foreach ($options['clientes'] as $cliente): ?>
                            <option value="<?php echo $cliente['id']; ?>" <?php echo ($c['id_cliente'] == $cliente['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido_paterno']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label>Mascota:</label><br>
                    <select name="id_mascota" required>
                        <?php foreach ($options['mascotas'] as $mascota): ?>
                            <option value="<?php echo $mascota['id']; ?>" <?php echo ($c['id_mascota'] == $mascota['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($mascota['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label>Servicio (Duración):</label><br>
                    <select name="id_servicio" required>
                        <?php foreach ($options['servicios'] as $s): ?>
                            <option value="<?php echo $s['id_servicio']; ?>" <?php echo ($c['id_servicio'] == $s['id_servicio']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['nombre_servicio'] . ' (' . formatDuracion($s['duracion'] ?? 30) . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label>Veterinario:</label><br>
                    <select name="id_veterinario" required>
                        <option value="">Seleccione un veterinario</option>
                        <?php foreach ($options['veterinarios'] as $v): ?>
                            <option value="<?php echo $v['id_usuario']; ?>" <?php echo (isset($c['id_veterinario']) && $c['id_veterinario'] == $v['id_usuario']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label>Fecha:</label><br>
                    <input type="date" name="fecha" value="<?php echo htmlspecialchars($c['fecha']); ?>" required><br><br>
                    <label>Hora:</label><br>
                    <input type="time" name="hora" value="<?php echo htmlspecialchars($c['hora']); ?>" required><br><br>

                    <label>Estado:</label><br>
                    <select name="estado" required>
                        <?php $estados = ['Agendada', 'Confirmada', 'Cancelada', 'Completada'];
                        foreach ($estados as $estado): ?>
                            <option value="<?php echo $estado; ?>" <?php echo ($c['estado'] == $estado) ? 'selected' : ''; ?>>
                                <?php echo $estado; ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <button type="submit" class="Agregar">Guardar cambios</button>
                    <a href="../Vista/citas.php" class="delete-btn" style="padding:6px 12px; text-decoration:none; margin-left:10px;">Cancelar</a>
                </form>
            </main>
        </body>
        </html>
        <?php
        exit;
    }
}

// POST HANDLERS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$can_crud) redirect_list('acceso_denegado');

    if ($action === 'add') {
        $fecha = $_POST['fecha'];
        $hora = $_POST['hora'];
        $id_cliente = intval($_POST['id_cliente']);
        $id_mascota = intval($_POST['id_mascota']);
        $id_servicio = intval($_POST['id_servicio']);
        $id_veterinario = intval($_POST['id_veterinario']);
        $estado = $con->real_escape_string($_POST['estado']); 

        if (hayConflictoHorario($con, $fecha, $hora, $id_servicio, $id_veterinario)) {
            header("Location: " . $_SERVER['SCRIPT_NAME'] . "?action=add&error=empalme");
            exit;
        }

        $stmt = $con->prepare("INSERT INTO citas (fecha, hora, id_cliente, id_mascota, id_servicio, id_veterinario, estado) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('ssiiiis', $fecha, $hora, $id_cliente, $id_mascota, $id_servicio, $id_veterinario, $estado);
            if ($stmt->execute()) { $stmt->close(); redirect_list('agregada'); }
            else { $stmt->close(); die('Error al guardar: ' . $con->error); }
        } else { die('Error en prepare: ' . $con->error); }
    }

    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $fecha = $_POST['fecha'];
        $hora = $_POST['hora'];
        $id_cliente = intval($_POST['id_cliente']);
        $id_mascota = intval($_POST['id_mascota']);
        $id_servicio = intval($_POST['id_servicio']);
        $id_veterinario = intval($_POST['id_veterinario']);
        $estado = $con->real_escape_string($_POST['estado']);

        if ($id <= 0) redirect_list('id_invalido');

        if (hayConflictoHorario($con, $fecha, $hora, $id_servicio, $id_veterinario, $id)) {
            header("Location: " . $_SERVER['SCRIPT_NAME'] . "?action=edit&id=$id&error=empalme");
            exit;
        }

        $stmt = $con->prepare("UPDATE citas SET fecha=?, hora=?, id_cliente=?, id_mascota=?, id_servicio=?, id_veterinario=?, estado=? WHERE id_cita = ?");
        if ($stmt) {
            $stmt->bind_param('ssiiiisi', $fecha, $hora, $id_cliente, $id_mascota, $id_servicio, $id_veterinario, $estado, $id);
            if ($stmt->execute()) { $stmt->close(); redirect_list('actualizada'); }
            else { $stmt->close(); die('Error al actualizar: ' . $con->error); }
        } else { die('Error en prepare: ' . $con->error); }
    }

    if ($action === 'delete') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) redirect_list('id_invalido');
        $stmt = $con->prepare("DELETE FROM citas WHERE id_cita = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) { $stmt->close(); redirect_list('eliminada'); }
        else { $stmt->close(); die('Error al eliminar: ' . $con->error); }
    }
}
redirect_list();
?>