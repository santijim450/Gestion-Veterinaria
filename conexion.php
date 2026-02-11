<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "gestion_veterinaria";

$con = new mysqli($host, $user, $password, $dbname);

if ($con->connect_error) {
    die("Error de conexión: " . $con->connect_error);
}

