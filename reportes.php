<?php
// Incluir la seguridad y verificar permisos
// Solo el rol 'administrador' tiene acceso a Reportes
include __DIR__ . "/../Controlador/seguridad.php";
check_access(['administrador']); 

include __DIR__ . "/../Modelo/conexion.php";

// --- Lógica de Reporte ---
// Ejemplo: Listado de clientes y el número de mascotas que poseen
$sql_reporte = "
    SELECT 
        c.id, 
        CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', c.apellido_materno) AS nombre_completo,
        c.email,
        c.whatsapp,
        COUNT(m.id) AS total_mascotas
    FROM clientes c
    LEFT JOIN mascotas m ON c.id = m.id_cliente
    GROUP BY c.id
    ORDER BY total_mascotas DESC, nombre_completo ASC
";
$result_reporte = $con->query($sql_reporte);

// Mensajes flash (opcional)
$msg_code = isset($_GET['msg']) ? $_GET['msg'] : '';
$alert_text = '';
if ($msg_code === 'reporte_generado') {
    $alert_text = 'Reporte generado con éxito.';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes Generales - One Pet</title>
    <link rel="stylesheet" href="../Vista/Css/estilos.css">
    <script src="https://kit.fontawesome.com/b2643188f2.js" crossorigin="anonymous"></script>
    <style>
        .report-header {
            background-color: #f7f7f7;
            border-bottom: 2px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .report-header h3 {
            margin-top: 0;
            color: #333;
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
            <h2>Módulo de Reportes</h2>

            <?php if ($alert_text): ?>
                <div style="background:#d4edda;color:#155724;padding:10px;border-radius:4px;margin-bottom:12px;border:1px solid #c3e6cb;"><?php echo $alert_text; ?></div>
            <?php endif; ?>

            <div class="report-header">
                <h3>Reporte: Clientes y Total de Mascotas</h3>
                <p>Lista de todos los clientes con el recuento de las mascotas registradas a su nombre.</p>
            </div>

            <?php if ($result_reporte && $result_reporte->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Cliente</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>WhatsApp</th>
                            <th>Total Mascotas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result_reporte->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['nombre_completo']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['whatsapp']); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['total_mascotas']); ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No se encontraron datos para generar este reporte.</p>
            <?php endif; ?>
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