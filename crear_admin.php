<?php
// Mostrar errores en pantalla para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/conexion.php";

if (!isset($conn) || $conn->connect_error) {
    die("❌ Error de conexión: " . ($conn->connect_error ?? "La variable \$conn no existe"));
}

/* 🔐 ADMINISTRADOR */
$user1 = "admin";
$pass1 = password_hash("vivienda", PASSWORD_DEFAULT);
$rol1  = "admin";

/* 👤 USUARIO STANDARD */
$user2 = "usuario";
$pass2 = password_hash("vivienda1", PASSWORD_DEFAULT);
$rol2  = "usuario";

// ⚠️ Se cambia 'usuarios' por 'usuarios_sistema'
$sql1 = "INSERT INTO usuarios_sistema (usuario, password, rol) 
         VALUES ('$user1', '$pass1', '$rol1') 
         ON DUPLICATE KEY UPDATE password='$pass1', rol='$rol1'";

$sql2 = "INSERT INTO usuarios_sistema (usuario, password, rol) 
         VALUES ('$user2', '$pass2', '$rol2') 
         ON DUPLICATE KEY UPDATE password='$pass2', rol='$rol2'";

if ($conn->query($sql1) && $conn->query($sql2)) {
    echo "<h3>✅ Usuarios creados correctamente en 'usuarios_sistema'</h3>";
    echo "<ul>";
    echo "<li><b>Admin:</b> $user1 | <b>Clave:</b> vivienda</li>";
    echo "<li><b>Usuario:</b> $user2 | <b>Clave:</b> vivienda1</li>";
    echo "</ul>";
    echo "<a href='index.php'>Ir al Login</a>";
} else {
    echo "❌ Error al insertar en MySQL: " . $conn->error;
}
?>