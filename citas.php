<?php
// Iniciamos sesión para saber QUIÉN está conectado
session_start();
include "../Modelo/conexion.php";

// Obtenemos datos del usuario actual
$id_usuario_actual = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;
$rol_usuario = isset($_SESSION['usuario_rol']) ? $_SESSION['usuario_rol'] : '';

// Lógica de eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id > 0) {
        $con->query("DELETE FROM citas WHERE id_cita=$id");
    }
}

// Lógica de búsqueda
$busqueda = isset($_GET['buscar']) ? $con->real_escape_string($_GET['buscar']) : "";

// Consulta base
$sql = "SELECT c.id_cita, c.fecha, c.hora, c.estado,
               cl.nombre AS nombre_cliente, cl.apellido_paterno,
               m.nombre AS nombre_mascota,
               s.nombre_servicio,
               u.nombre AS nombre_veterinario 
        FROM citas c
        LEFT JOIN clientes cl ON c.id_cliente = cl.id
        LEFT JOIN mascotas m ON c.id_mascota = m.id
        LEFT JOIN servicios s ON c.id_servicio = s.id_servicio
        LEFT JOIN usuarios u ON c.id_veterinario = u.id_usuario";

// --- CONSTRUCCIÓN DE FILTROS ---
$condiciones = [];

// 1. Filtro por Búsqueda (Texto)
if ($busqueda !== '') {
    $condiciones[] = "(cl.nombre LIKE '%$busqueda%' 
                       OR cl.apellido_paterno LIKE '%$busqueda%' 
                       OR m.nombre LIKE '%$busqueda%'
                       OR s.nombre_servicio LIKE '%$busqueda%'
                       OR u.nombre LIKE '%$busqueda%')";
}

// 2. Filtro por ROL (¡NUEVO!)
// Si es veterinario, SOLO ve sus propias citas
if ($rol_usuario === 'veterinario') {
    $condiciones[] = "c.id_veterinario = $id_usuario_actual";
}

// Aplicar los filtros al SQL si existen
if (count($condiciones) > 0) {
    $sql .= " WHERE " . implode(' AND ', $condiciones);
}

$sql .= " ORDER BY c.fecha DESC, c.hora DESC";
$result = $con->query($sql);

// Mensajes de alerta
$msg_code = isset($_GET['msg']) ? $_GET['msg'] : '';
$alert_text = '';
$alert_style = '';
if ($msg_code !== '') {
    switch ($msg_code) {
        case 'agregada': $alert_text = 'Cita agendada correctamente.'; $alert_style = 'background:#d4edda;color:#155724;'; break;
        case 'actualizada': $alert_text = 'Cita actualizada correctamente.'; $alert_style = 'background:#cce5ff;color:#004085;'; break;
        case 'eliminada': $alert_text = 'Cita eliminada correctamente.'; $alert_style = 'background:#f8d7da;color:#721c24;'; break;
        case 'acceso_denegado': $alert_text = 'No tienes permisos para realizar esta acción.'; $alert_style = 'background:#fff3cd;color:#856404;'; break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agenda de Citas</title>
    <link rel="stylesheet" href="../Vista/Css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css">
    <script src="https://kit.fontawesome.com/b2643188f2.js" crossorigin="anonymous"></script>
    <style>
        .badge { padding: 5px 10px; border-radius: 12px; color: white; font-weight: bold; font-size: 0.85em; text-transform: capitalize; }
        .bg-agendada { background-color: #17a2b8; }
        .bg-confirmada { background-color: #28a745; }
        .bg-cancelada { background-color: #dc3545; }
        .bg-completada { background-color: #6c757d; }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="../Vista/imagenes/one pet3.png" alt="Logo VetCode" class="logo-img">
            <h1>Gestión Veterinaria</h1>
        </div>
        <div style="color:white; margin-right:20px;">
            Hola, <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?> 
            (<?php echo htmlspecialchars($rol_usuario); ?>)
        </div>
    </header>
    <div class="contenedor-principal">
        <aside class="menu-lateral">
            <ul>
                <li><a href="clientes.php"><i class="fa-solid fa-user"></i> Clientes</a></li>
                <li><a href="mascotas.php"><i class="fa-solid fa-dog"></i> Mascotas</a></li>
                <li><a href="servicios.php"><i class="fa-solid fa-briefcase"></i> Servicios</a></li>
                <li><a href="empleados.php"><i class="fa-solid fa-users"></i> Empleados</a></li>
                <li><a href="citas.php" class="active"><i class="fa-solid fa-calendar-check"></i> Citas</a></li>
            </ul>
        </aside>
        <main>
            <h2>Agenda de Citas</h2>

            <?php if ($alert_text !== ''): ?>
                <div style="<?php echo $alert_style; ?> padding:10px; border-radius:4px; margin-bottom:12px; border:1px solid rgba(0,0,0,0.1);">
                    <?php echo $alert_text; ?>
                </div>
            <?php endif; ?>

            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:15px;">
                <a href="../Controlador/citas_controller.php?action=add" class="Agregar" style="text-decoration:none;">+ Agendar Nueva Cita</a>
                <form action="citas.php" method="GET" style="display:flex; align-items:center; gap:8px;">
                    <input type="text" name="buscar" placeholder="Buscar citas..." style="padding:6px 10px; border:1px solid #ccc; border-radius:4px;" value="<?php echo htmlspecialchars($busqueda); ?>">
                    <button type="submit" class="Agregar"><i class="fa fa-search"></i></button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID Cita</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Cliente</th>
                        <th>Mascota</th>
                        <th>Servicio</th>
                        <th>Veterinario</th>
                        <th>Estado</th>
                        <th>Editar</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $estadoClass = 'bg-agendada';
                            if($row['estado'] == 'Confirmada') $estadoClass = 'bg-confirmada';
                            if($row['estado'] == 'Cancelada') $estadoClass = 'bg-cancelada';
                            if($row['estado'] == 'Completada') $estadoClass = 'bg-completada';
                            
                            $vetName = !empty($row['nombre_veterinario']) ? htmlspecialchars($row['nombre_veterinario']) : '<span style="color:#999; font-style:italic;">No asignado</span>';

                            echo "<tr>";
                            echo "<td>" . $row['id_cita'] . "</td>";
                            echo "<td>" . $row['fecha'] . "</td>";
                            echo "<td>" . $row['hora'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['nombre_cliente'] . ' ' . $row['apellido_paterno']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nombre_mascota']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nombre_servicio']) . "</td>";
                            echo "<td>" . $vetName . "</td>";
                            echo "<td><span class='badge $estadoClass'>" . htmlspecialchars($row['estado']) . "</span></td>";
                            
                            echo "<td style='text-align:center;'><a href='../Controlador/citas_controller.php?action=edit&id=" . $row['id_cita'] . "' class='edit-btn'>Editar</a></td>";
                            
                            echo "<td style='text-align:center;'>
                                    <form action='../Controlador/citas_controller.php?action=delete' method='POST' onsubmit='return confirm(\"¿Estás seguro de eliminar esta cita?\");' style='display:inline;'>
                                        <input type='hidden' name='id' value='" . $row['id_cita'] . "'>
                                        <button type='submit' class='delete-btn'>Eliminar</button>
                                    </form>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='10' style='text-align:center;'>No hay citas registradas para ti.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </main>
    </div>
    <footer class="PiePagina">
        <table>
            <tr>
                <td>
                    One Pet<br>
                    <i class="fa-solid fa-location-dot"></i><br>
                    <i class="fa-solid fa-phone"></i>TEL: 55 19 28 53 57<br>
                    <p>&copy; 2025 VetCode - Todos los derechos reservados.</p>
                </td>
                <td>
                    <i class="fa-brands fa-facebook-f"></i><br>Facebook<br><br>
                    <i class="fa-brands fa-whatsapp"></i><br>WhatsApp<br><br>
                    <i class="fa-brands fa-instagram"></i><br>Instagram<br>
                </td>
            </tr>
        </table>
    </footer>
</body>
</html>