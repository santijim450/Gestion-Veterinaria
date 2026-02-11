<?php
include "conexion.php";

// Eliminar cliente si se envía POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id > 0) {
        $con->query("DELETE FROM clientes WHERE id=$id");
    }
}

// Buscar clientes
$busqueda = isset($_GET['buscar']) ? $con->real_escape_string($_GET['buscar']) : '';

$sql = "SELECT * FROM clientes";
if ($busqueda !== '') {
    $sql .= " WHERE nombre LIKE '%$busqueda%' 
              OR apellido_paterno LIKE '%$busqueda%' 
              OR apellido_materno LIKE '%$busqueda%' 
              OR domicilio LIKE '%$busqueda%'
              OR whatsapp LIKE '%$busqueda%'
              OR email LIKE '%$busqueda%'";
}
$sql .= " ORDER BY id ASC";
$result = $con->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión Veterinaria</title>
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
                <li><a href="#"><i class="fa-solid fa-user"></i> Clientes</a></li>
                <li><a href="../Vista/mascotas.php"><i class="fa-solid fa-dog"></i> Mascotas</a></li>
                <li><a href="../Vista/empleados.php"><i class="fa-solid fa-users"></i> Empleados</a></li>
                <li><a href="../Vista/servicios.php"><i class="fa-solid fa-briefcase"></i> Servicios</a></li>
                <li><a href="#"><i class="fa-solid fa-clock-rotate-left"></i> Historial</a></li>
            </ul>
        </aside>
        <main>
            <h2>Lista de Clientes</h2>
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:15px;">
                <a href="../Controlador/clientes_controller.php" class="Agregar" style="text-decoration:none;">+ Agregar</a>
                <form action="clientes.php" method="GET" style="display:flex; align-items:center; gap:8px;">
                    <input type="text" name="buscar" placeholder="Buscar clientes..." style="padding:6px 10px; border:1px solid #ccc; border-radius:4px;" value="<?php echo htmlspecialchars($busqueda); ?>">
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
                        <th>Domicilio</th>
                        <th>Whatsapp</th>
                        <th>Email</th>
                        <th>Editar</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>".$row['id']."</td>";
                            echo "<td>".htmlspecialchars($row['apellido_paterno'])."</td>";
                            echo "<td>".htmlspecialchars($row['apellido_materno'])."</td>";
                            echo "<td>".htmlspecialchars($row['nombre'])."</td>";
                            echo "<td>".htmlspecialchars($row['domicilio'])."</td>";
                            echo "<td>".htmlspecialchars($row['whatsapp'])."</td>";
                            echo "<td>".htmlspecialchars($row['email'])."</td>";
                            echo "<td><a href='../Controlador/clientes_controller.php?action=edit&id=".$row['id']."' class='edit-btn'>Editar</a></td>";
                            echo "<td>
                                    <form action='clientes.php' method='POST' onsubmit='return confirm(\"¿Eliminar cliente?\");' style='display:inline;'>
                                        <input type='hidden' name='delete' value='true'>
                                        <input type='hidden' name='id' value='".$row['id']."'>
                                        <button type='submit' class='delete-btn'>Eliminar</button>
                                    </form>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='9'>No se encontraron clientes.</td></tr>";
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
