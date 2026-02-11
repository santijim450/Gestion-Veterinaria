<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function check_access($allowed_roles) {
 
    $base_path = basename(getcwd()) == 'Vista' ? '' : '../Vista/';
    
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: {$base_path}login.php?error=" . urlencode("Sesión expirada o no iniciada."));
        exit;
    }

    $current_role = $_SESSION['usuario_rol'] ?? 'Invitado';

    if (!in_array($current_role, $allowed_roles)) {
        $location_file = 'dashboard.php'; 
        switch ($current_role) {
            case 'administrador': $location_file = 'dashboard.php'; break;
            case 'veterinario': $location_file = 'mascotas.php'; break;
            case 'recepcionista': $location_file = 'clientes.php'; break;
            default: $location_file = 'login.php'; break;
        }

        header("Location: {$base_path}{$location_file}?error=" . urlencode("Acceso denegado para su rol: " . $current_role));
        exit;
    }
}

function generate_sidebar($current_page) {
    $rol = $_SESSION['usuario_rol'] ?? 'guest';

    $menu_items = [
        'administrador' => [
            'dashboard.php' => ['icon' => 'fa-solid fa-house', 'text' => 'Dashboard'],
            'empleados.php' => ['icon' => 'fa-solid fa-user-tie', 'text' => 'Empleados'],
            'servicios.php' => ['icon' => 'fa-solid fa-syringe', 'text' => 'Servicios'],
            'clientes.php' => ['icon' => 'fa-solid fa-user', 'text' => 'Clientes'],
            'mascotas.php' => ['icon' => 'fa-solid fa-dog', 'text' => 'Mascotas'],
            'citas.php' => ['icon' => 'fa-solid fa-calendar-check', 'text' => 'Citas'],
            'reportes.php' => ['icon' => 'fa-solid fa-chart-line', 'text' => 'Reportes'],
        ],

        'veterinario' => [
            'dashboard.php' => ['icon' => 'fa-solid fa-house', 'text' => 'Dashboard'],
            // 'clientes.php' => ['icon' => 'fa-solid fa-user', 'text' => 'Clientes'], <-- Eliminado
            'mascotas.php' => ['icon' => 'fa-solid fa-dog', 'text' => 'Mascotas'],
            'citas.php' => ['icon' => 'fa-solid fa-calendar-check', 'text' => 'Citas'],
            'historial.php' => ['icon' => 'fa-solid fa-clock-rotate-left', 'text' => 'Historial'], // Añadido "Historial" si existe ese archivo
        ],
        'recepcionista' => [
            'dashboard.php' => ['icon' => 'fa-solid fa-house', 'text' => 'Dashboard'],
            'clientes.php' => ['icon' => 'fa-solid fa-user', 'text' => 'Clientes'],
            'mascotas.php' => ['icon' => 'fa-solid fa-dog', 'text' => 'Mascotas'],
            'citas.php' => ['icon' => 'fa-solid fa-calendar-check', 'text' => 'Citas'],
        ]
    ];

    echo '<aside class="menu-lateral"><ul>';
    $menu = $menu_items[$rol] ?? [];
    foreach ($menu as $file => $item) {
        $active_class = (basename($_SERVER['PHP_SELF']) == $file) ? 'class="active"' : '';
        echo "<li><a href=\"$file\" $active_class><i class=\"{$item['icon']}\"></i> {$item['text']}</a></li>";
    }
    echo '</ul></aside>';
}