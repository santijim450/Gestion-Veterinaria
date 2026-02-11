<?php
include "../Modelo/conexion.php";

// Buscar empleados
$busqueda = isset($_GET['buscar']) ? $con->real_escape_string($_GET['buscar']) : '';

$sql = "SELECT * FROM empleados";
if ($busqueda !== '') {
    $sql .= " WHERE nombre LIKE '%$busqueda%' 
              OR apellido_paterno LIKE '%$busqueda%' 
              OR apellido_materno LIKE '%$busqueda%' 
              OR rol LIKE '%$busqueda%' 
              OR email LIKE '%$busqueda%'";
}
$sql .= " ORDER BY id_empleado ASC";
$result = $con->query($sql);

// Mensajes de alerta
$msg_code = isset($_GET['msg']) ? $_GET['msg'] : '';
$alert_text = '';
$alert_style = '';
if ($msg_code !== '') {
    switch ($msg_code) {
        case 'agregado': $alert_text = 'Empleado agregado correctamente.'; $alert_style = 'background:#d4edda;color:#155724;'; break;
        case 'actualizado': $alert_text = 'Datos del empleado actualizados.'; $alert_style = 'background:#cce5ff;color:#004085;'; break;
        case 'eliminado': $alert_text = 'Empleado eliminado correctamente.'; $alert_style = 'background:#f8d7da;color:#721c24;'; break;
        case 'id_invalido': $alert_text = 'ID inválido.'; $alert_style = 'background:#fff3cd;color:#856404;'; break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Empleados</title>
    <link rel="stylesheet" href="../Vista/Css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css">
    <script src="https://kit.fontawesome.com/b2643188f2.js" crossorigin="anonymous"></script>
</head>
<body>
    <header>
        <div class="logo">
            <img src="../Vista/imagenes/one pet3.png" alt="Logo VetCode" class="logo-img">
            <h1>Gestión Veterinaria</h1>
        </div>
    </header>
    <div class="contenedor-principal">
        <aside class="menu-lateral">
            <ul>
                <li><a href="clientes.php"><i class="fa-solid fa-user"></i> Clientes</a></li>
                <li><a href="mascotas.php"><i class="fa-solid fa-dog"></i> Mascotas</a></li>
                <li><a href="servicios.php"><i class="fa-solid fa-briefcase"></i> Servicios</a></li>
                <li><a href="empleados.php" class="active"><i class="fa-solid fa-users"></i> Empleados</a></li>
                <li><a href="citas.php"><i class="fa-solid fa-calendar-check"></i> Citas</a></li>
            </ul>
        </aside>
        <main>
            <h2>Lista de Empleados</h2>
            
            <?php if ($alert_text !== ''): ?>
                <div style="<?php echo $alert_style; ?> padding:10px; border-radius:4px; margin-bottom:12px; border:1px solid rgba(0,0,0,0.1);">
                    <?php echo $alert_text; ?>
                </div>
            <?php endif; ?>

            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:15px;">
                <a href="../Controlador/empleados_controller.php?action=add" class="Agregar" style="text-decoration:none;">+ Agregar</a>
                <form action="empleados.php" method="GET" style="display:flex; align-items:center; gap:8px;">
                    <input type="text" name="buscar" placeholder="Buscar empleados..." style="padding:6px 10px; border:1px solid #ccc; border-radius:4px;" value="<?php echo htmlspecialchars($busqueda); ?>">
                    <button type="submit" class="Agregar"><i class="fa fa-search"></i></button>
                </form>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Apellido Paterno</th>
                        <th>Apellido Materno</th>
                        <th>Nombre</th>
                        <th>Rol</th> <th>Domicilio</th>
                        <th>Whatsapp</th>
                        <th>Email</th>
                        <th>Editar</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // Obtener rol o valor por defecto
                            $rol = !empty($row['rol']) ? htmlspecialchars($row['rol']) : 'Empleado';
                            
                            echo "<tr>";
                            echo "<td>".$row['id_empleado']."</td>";
                            echo "<td>".htmlspecialchars($row['apellido_paterno'])."</td>";
                            echo "<td>".htmlspecialchars($row['apellido_materno'])."</td>";
                            echo "<td>".htmlspecialchars($row['nombre'])."</td>";
                            
                            // CELDA DE ROL (Resaltada en negrita suave)
                            echo "<td style='font-weight:500; color:#0056b3;'>".$rol."</td>";
                            
                            echo "<td>".htmlspecialchars($row['domicilio'])."</td>";
                            echo "<td>".htmlspecialchars($row['whatsapp'])."</td>";
                            echo "<td>".htmlspecialchars($row['email'])."</td>";
                            
                            echo "<td style='text-align:center;'><a href='../Controlador/empleados_controller.php?action=edit&id=".$row['id_empleado']."' class='edit-btn'>Editar</a></td>";
                            
                            echo "<td style='text-align:center;'>
                                    <form action='../Controlador/empleados_controller.php?action=delete' method='POST' onsubmit='return confirm(\"¿Estás seguro de eliminar este empleado?\");' style='display:inline;'>
                                        <input type='hidden' name='id' value='".$row['id_empleado']."'>
                                        <button type='submit' class='delete-btn'>Eliminar</button>
                                    </form>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='10' style='text-align:center;'>No se encontraron empleados.</td></tr>";
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
                OnePet Centro Veterinario<br><br>
                <a href="https://www.google.com/maps/place/OnePet+Centro+Veterinario/@19.4138976,-99.0608182,18.15z/data=!4m14!1m7!3m6!1s0x85d1fd22545035fd:0xa2555f13641c2d07!2sOnePet+Centro+Veterinario!8m2!3d19.4134147!4d-99.06003!16s%2Fg%2F11rsrz6mx_!3m5!1s0x85d1fd22545035fd:0xa2555f13641c2d07!8m2!3d19.4134147!4d-99.06003!16s%2Fg%2F11rsrz6mx_?entry=ttu&g_ep=EgoyMDI1MTExNy4wIKXMDSoASAFQAw%3D%3D" target="_blank">
                    <i class="fa-solid fa-location-dot"></i>Eje 1 Norte Av. Xochimilco #50, <br>
                    Agrícola Pantitlán, Iztacalco, 08100 CDMX
                </a>
                <br>
                <br>
                <i class="fa-solid fa-phone"></i>TEL: 55 6308 8151<br>
                <p>&copy; 2025 VetCode - Todos los derechos reservados.</p>
            </td>
            <td>
                <a href="https://www.facebook.com/share/1FRjPmVmGe/?mibextid=wwXIfr" target="_blank">
                    <i class="fa-brands fa-facebook-f"></i><br>Facebook<br><br>
                </a>
                
                <a href="https://wa.link/h255ft" target="_blank">
                    <i class="fa-brands fa-whatsapp"></i><br>WhatsApp<br><br>
                </a>
                
                <a href="https://www.instagram.com/onepetvet?igsh=MXZodHVvcmpiNmZ6cA==" target="_blank">
                    <i class="fa-brands fa-instagram"></i><br>Instagram<br><br>
                </a>
            </td>
        </tr>
    </table>
</footer>
</body>
</html>