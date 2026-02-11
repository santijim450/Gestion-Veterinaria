<?php
// VISTA/dashboard.php

// 1. ELIMINAMOS EL BLOQUE DE session_start() DUPLICADO.
// La sesión ahora es iniciada por seguridad.php.
 
// 2. Incluir seguridad.php (Asegura que la ruta sea correcta)
include __DIR__ . "/../Controlador/seguridad.php";

// Todos los roles acceden al dashboard después de loguearse
check_access(['administrador', 'veterinario', 'recepcionista']); 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - One Pet</title>
    <link rel="stylesheet" href="Css/estilos.css">
    <script src="https://kit.fontawesome.com/b2643188f2.js" crossorigin="anonymous"></script>
    <style>
        .welcome-box { padding: 30px; border-radius: 8px; margin-top: 20px; text-align: center; }
        .admin-bg { background-color: #e6f7ff; border: 1px solid #91d5ff; }
        .vet-bg { background-color: #f6ffed; border: 1px solid #b7eb8f; }
        .recep-bg { background-color: #fffbe6; border: 1px solid #ffe58f; }

        /* Estilo para el botón de Cerrar Sesión en el encabezado */
        .logout-btn {
            background-color: #d9534f; /* Rojo para alertar */
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            transition: background-color 0.3s;
            margin-right: 20px; /* Separación del borde derecho */
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .logout-btn:hover {
            background-color: #c9302c;
        }
        /* Ajustar el header para alinear el botón */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="imagenes/one pet3.png" alt="Logo VetCode" class="logo-img">
            <h1>Gestión Veterinaria</h1>
        </div>
        <a href="../Controlador/logout.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            Cerrar Sesión
        </a>
    </header>
    <div class="contenedor-principal">
        <?php generate_sidebar(basename(__FILE__)); ?>
        <main>
            <h2>Panel de Control</h2>
            
            <?php 
            $rol = $_SESSION['usuario_rol'] ?? 'Invitado'; 
            $nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario');
            
            $bg_class = '';
            $mensaje = '';

            switch ($rol) {
                case 'administrador':
                    $bg_class = 'admin-bg';
                    $mensaje = "Como **Administrador**, tienes acceso completo a todos los módulos. ¡Revisa Empleados y Servicios!";
                    break;
                case 'veterinario':
                    $bg_class = 'vet-bg';
                    $mensaje = "Como **Veterinario**, solo tienes acceso a **Historial Médico**, **Mascotas** y **Citas**.";
                    break;
                case 'recepcionista':
                    $bg_class = 'recep-bg';
                    $mensaje = "Como **Recepcionista**, solo tienes acceso a **Clientes**, **Mascotas** y **Citas** desde el menú.";
                    break;
            }
            ?>

            <div class="welcome-box <?php echo $bg_class; ?>">
                <h3>¡Bienvenido de vuelta, <?php echo $nombre; ?>!</h3>
                <p>Tu rol es: **<?php echo ucfirst($rol); ?>**.</p>
                <p><?php echo $mensaje; ?></p>
            </div>
            
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
</html>