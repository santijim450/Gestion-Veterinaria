<?php
include __DIR__ . "/../Controlador/seguridad.php";
check_access(['administrador', 'veterinario']); 

include "../Modelo/conexion.php";

$id_mascota = isset($_GET['id_mascota']) ? intval($_GET['id_mascota']) : 0;

if ($id_mascota <= 0) {
    // Si no se proporciona ID de mascota, redirigimos al listado de mascotas (para que elijan una)
    header("Location: mascotas.php?msg=" . urlencode("Seleccione una mascota para ver su historial."));
    exit;
}

// 2. Obtener datos de la mascota y su dueño
$sql_mascota = "SELECT m.*, CONCAT(c.nombre, ' ', c.apellido_paterno) AS nombre_cliente, c.whatsapp 
                FROM mascotas m
                JOIN clientes c ON m.id_cliente = c.id
                WHERE m.id = ?";
$stmt_m = $con->prepare($sql_mascota);
$stmt_m->bind_param('i', $id_mascota);
$stmt_m->execute();
$res_m = $stmt_m->get_result();

if ($res_m->num_rows === 0) {
    // Mascota no encontrada
    header("Location: mascotas.php?msg=" . urlencode("Mascota no encontrada."));
    exit;
}
$mascota = $res_m->fetch_assoc();
$stmt_m->close();

// 3. Obtener el historial médico de esa mascota
$sql_historial = "SELECT h.*, s.nombre_servicio 
                  FROM historial_medico h
                  LEFT JOIN servicios s ON h.id_servicio = s.id_servicio
                  WHERE h.id_mascota = ?
                  ORDER BY h.fecha_visita DESC, h.id_historial DESC";
$stmt_h = $con->prepare($sql_historial);
$stmt_h->bind_param('i', $id_mascota);
$stmt_h->execute();
$result_historial = $stmt_h->get_result();
$stmt_h->close();


// Mensajes flash
$msg_code = isset($_GET['msg']) ? $_GET['msg'] : '';
$alert_text = '';
$alert_style = 'background:#d4edda;color:#155724;padding:10px;border-radius:4px;margin-bottom:12px;border:1px solid #c3e6cb;';
if ($msg_code === 'historial_agregado') $alert_text = 'Registro de visita guardado correctamente.';
if ($msg_code === 'historial_actualizado') $alert_text = 'Registro de visita actualizado correctamente.';
if ($msg_code === 'historial_eliminado') $alert_text = 'Registro de visita eliminado correctamente.';

// Lógica de permisos de CRUD (Administrador y Veterinario pueden CRUD Historial)
$can_crud_historial = in_array($_SESSION['usuario_rol'], ['administrador', 'veterinario']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de <?php echo htmlspecialchars($mascota['nombre']); ?></title>
    <link rel="stylesheet" href="../Vista/Css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css">
    <script src="https://kit.fontawesome.com/b2643188f2.js" crossorigin="anonymous"></script>
    <style>
        .info-box {
            background: #e9ecef;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 5px solid #007bff;
        }
        .info-box p { margin: 5px 0; }
        .registro-detalle {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .registro-detalle h4 {
            margin-top: 0;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            color: #007bff;
        }
        .acciones-historial {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="imagenes/one pet3.png" alt="Logo VetCode" class="logo-img">
            <h1>Gestión Veterinaria</h1>
        </div>
    </header>
    <div class="contenedor-principal">
        <?php generate_sidebar(basename(__FILE__)); ?> 
        
        <main>
            <h2>Historial Médico de **<?php echo htmlspecialchars($mascota['nombre']); ?>**</h2>
            
            <div class="info-box">
                <p><strong>Especie/Raza:</strong> <?php echo htmlspecialchars($mascota['tipo'] . ' / ' . $mascota['raza']); ?></p>
                <p><strong>Dueño:</strong> <?php echo htmlspecialchars($mascota['nombre_cliente']); ?></p>
                <p><strong>Contacto:</strong> <?php echo htmlspecialchars($mascota['whatsapp']); ?></p>
            </div>
            
            <?php if ($alert_text !== ''): ?>
                <div style="<?php echo $alert_style; ?>"><?php echo $alert_text; ?></div>
            <?php endif; ?>
            
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:15px;">
                <a href="mascotas.php" class="delete-btn" style="text-decoration:none; padding: 6px 12px; background-color: #6c757d;">
                    <i class="fa-solid fa-arrow-left"></i> Volver a Mascotas
                </a>
                <?php if ($can_crud_historial): ?>
                    <a href="../Controlador/historial_controller.php?action=add&id_mascota=<?php echo $id_mascota; ?>" class="Agregar" style="text-decoration:none;">
                        + Registrar Nueva Visita
                    </a>
                <?php endif; ?>
            </div>
            
            <?php
            if ($result_historial->num_rows > 0) {
                while ($h = $result_historial->fetch_assoc()) {
                    ?>
                    <div class="registro-detalle">
                        <h4>Visita del <?php echo date('d/m/Y', strtotime($h['fecha_visita'])); ?></h4>
                        <p><strong>Motivo:</strong> <?php echo nl2br(htmlspecialchars($h['motivo_visita'])); ?></p>
                        <p><strong>Servicio:</strong> <?php echo $h['nombre_servicio'] ? htmlspecialchars($h['nombre_servicio']) : 'N/A'; ?></p>
                        <p><strong>Costo:</strong> $<?php echo number_format($h['costo'], 2); ?></p>
                        <p><strong>Diagnóstico/Obs.:</strong> <?php echo nl2br(htmlspecialchars($h['diagnostico'])); ?></p>
                        <p><strong>Tratamiento/Med.:</strong> <?php echo nl2br(htmlspecialchars($h['tratamiento'])); ?></p>
                        
                        <?php if ($can_crud_historial): ?>
                        <div class="acciones-historial">
                            <a href="../Controlador/historial_controller.php?action=edit&id=<?php echo $h['id_historial']; ?>" class="edit-btn">Editar</a>
                            <form action="../Controlador/historial_controller.php?action=delete" method="POST" onsubmit='return confirm("¿Eliminar este registro de historial?");' style='display:inline;'>
                                <input type="hidden" name="id" value="<?php echo $h['id_historial']; ?>">
                                <input type="hidden" name="id_mascota" value="<?php echo $id_mascota; ?>">
                                <button type='submit' class='delete-btn'>Eliminar</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php
                }
            } else {
                echo "<p style='padding: 20px; background: #f0f0f0; border-radius: 4px;'>No hay registros de historial médico para esta mascota.</p>";
            }
            ?>
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