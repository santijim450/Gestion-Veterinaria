<?php
include "../Modelo/conexion.php";

$busqueda = isset($_GET['buscar']) ? $con->real_escape_string($_GET['buscar']) : "";
$sql = "SELECT * FROM mascotas";
if ($busqueda !== '') {
    $sql .= " WHERE nombre LIKE '%$busqueda%' OR tipo LIKE '%$busqueda%' OR raza LIKE '%$busqueda%' OR domicilio LIKE '%$busqueda%'";
}
$sql .= " ORDER BY id ASC";
$result = $con->query($sql);

// Mensajes flash opcionales desde el controlador
$msg_code = isset($_GET['msg']) ? $_GET['msg'] : '';
$alert_text = '';
$alert_style = '';
if ($msg_code !== '') {
    switch ($msg_code) {
        case 'agregado':
            $alert_text = 'Mascota agregada correctamente.';
            $alert_style = 'background:#d4edda;color:#155724;padding:10px;border-radius:4px;margin-bottom:12px;border:1px solid #c3e6cb;';
            break;
        case 'actualizado':
            $alert_text = 'Datos de la mascota actualizados.';
            $alert_style = 'background:#cce5ff;color:#004085;padding:10px;border-radius:4px;margin-bottom:12px;border:1px solid #b8daff;';
            break;
        case 'eliminado':
            $alert_text = 'Mascota eliminada correctamente.';
            $alert_style = 'background:#f8d7da;color:#721c24;padding:10px;border-radius:4px;margin-bottom:12px;border:1px solid #f5c6cb;';
            break;
        case 'id_invalido':
            $alert_text = 'ID inválido.';
            $alert_style = 'background:#fff3cd;color:#856404;padding:10px;border-radius:4px;margin-bottom:12px;border:1px solid #ffeeba;';
            break;
        case 'error_tipo':
            $alert_text = 'Tipo de archivo no admitido. Usa JPG, PNG, GIF o WEBP.';
            $alert_style = 'background:#fff3cd;color:#856404;padding:10px;border-radius:4px;margin-bottom:12px;border:1px solid #ffeeba;';
            break;
        case 'error_subida':
            $alert_text = 'Ocurrió un error al subir la imagen. Verifica permisos y tamaño.';
            $alert_style = 'background:#f8d7da;color:#721c24;padding:10px;border-radius:4px;margin-bottom:12px;border:1px solid #f5c6cb;';
            break;
        default:
            $alert_text = htmlspecialchars($msg_code);
            $alert_style = 'background:#e2e3e5;color:#383d41;padding:10px;border-radius:4px;margin-bottom:12px;border:1px solid #d6d8db;';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión Veterinaria</title>
    <link rel="stylesheet" href="../Vista/Css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css">
    <script src="https://kit.fontawesome.com/b2643188f2.js" crossorigin="anonymous"></script>
    <style>
        /* Para asegurar que las imágenes queden bien en la tabla */
        td.img-cell {
            padding: 0; 
            text-align: center; 
            vertical-align: middle; 
            width: 180px; 
            height: 140px; 
            background: #fff;
        }
        .img-container {
            width: 180px; 
            height: 140px; 
            overflow: hidden; 
            margin: auto; 
            display: flex; 
            align-items: center; 
            justify-content: center;
        }
        .img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            border-radius: 4px;
        }
    </style>
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
                <li><a href="empleados.php"><i class="fa-solid fa-users"></i> Empleados</a></li>
                <li><a href="#"><i class="fa-solid fa-clock-rotate-left"></i> Historial</a></li>
            </ul>
        </aside>
        <main>
            <h2>Mascotas</h2>
            <?php if ($alert_text !== ''): ?>
                <div style="<?php echo $alert_style; ?>"><?php echo $alert_text; ?></div>
            <?php endif; ?>
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:15px;">
                <a href="../Controlador/mascotas_controller.php?action=add" class="Agregar" style="text-decoration:none;">+ Agregar</a>
                <form action="mascotas.php" method="GET" style="display:flex; align-items:center; gap:8px;">
                    <input type="text" name="buscar" placeholder="Buscar mascotas..." style="padding:6px 10px; border:1px solid #ccc; border-radius:4px;" value="<?php echo htmlspecialchars($busqueda); ?>">
                    <button type="submit" class="Agregar"><i class="fa fa-search"></i></button>
                </form>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Raza</th>
                        <th>Nombre</th>
                        <th>Domicilio</th>
                        <th>Imagen</th>
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
                            echo "<td>".htmlspecialchars($row['tipo'])."</td>";
                            echo "<td>".htmlspecialchars($row['raza'])."</td>";
                            echo "<td>".htmlspecialchars($row['nombre'])."</td>";
                            echo "<td>".htmlspecialchars($row['domicilio'])."</td>";
                            // Celda de imagen adaptada
                            echo "<td class='img-cell'>";
                            if (!empty($row['imagen'])) {
                                echo "<div class='img-container'>";
                                echo "<img src='imagenes/mascotas/" . htmlspecialchars($row['imagen']) . "' alt=''>";
                                echo "</div>";
                            } else {
                                echo "<span>-</span>";
                            }
                            echo "</td>";
                            // Botones de editar/eliminar
                            echo "<td style='text-align:center; vertical-align:middle;'><a href='../Controlador/mascotas_controller.php?action=edit&id=".$row['id']."' class='edit-btn' style='display:inline-block;margin:0 auto;'>Editar</a></td>";
                            echo "<td style='text-align:center; vertical-align:middle;'>"
                                 . "<form action='../Controlador/mascotas_controller.php?action=delete' method='POST' onsubmit='return confirm(\"¿Eliminar mascota?\");' style='display:inline-block;margin:0;'>"
                                 . "<input type='hidden' name='id' value='".$row['id']."'>"
                                 . "<button type='submit' class='delete-btn' style='display:inline-block;margin:0 auto;'>Eliminar</button>"
                                 . "</form></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8'>No se encontraron mascotas.</td></tr>";
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
