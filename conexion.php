<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$db   = "pagos_servicios";
$port = 3307; // El puerto se pasa separado como un número entero

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Configurar el juego de caracteres a UTF-8
$conn->set_charset("utf8");
?>