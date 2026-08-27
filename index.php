<?php
session_start();

// Incluir conexión asegurando la ruta
$conexion_path = __DIR__ . "/conexion.php";
if (file_exists($conexion_path)) {
    include($conexion_path);
} else {
    die("Error: No se encontró el archivo conexion.php en " . __DIR__);
}

// Validar que la conexión $conn esté lista
if (!isset($conn) || $conn === null || $conn->connect_error) {
    die("Error de conexión: Revisa los parámetros en conexion.php.");
}

// Si la sesión ya existe, redirigir al dashboard
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['usuario'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if (!empty($user) && !empty($pass)) {
        
        // Limpieza básica para evitar fallos de sintaxis SQL
        $user_clean = $conn->real_escape_string($user);

        // ⚠️ Consulta actualizada apuntando a la tabla 'usuarios_sistema'
        $sql = "SELECT * FROM usuarios_sistema WHERE usuario='$user_clean' LIMIT 1";
        $res = $conn->query($sql);

        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();

            // Permite validar si la contraseña está encriptada O si está en texto plano en la BD
            if (password_verify($pass, $row['password']) || $pass === $row['password']) {
                
                $_SESSION['user'] = $row['usuario'];
                $_SESSION['rol']  = $row['rol'] ?? 'admin';

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Contraseña incorrecta";
            }
        } else {
            $error = "Usuario no encontrado";
        }
    } else {
        $error = "Por favor, ingresa usuario y contraseña";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistema - Iniciar Sesión</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
}
.login-container {
    display: flex;
    width: 800px;
    max-width: 90%;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.4);
}
.left {
    background: #1d2671;
    color: white;
    padding: 40px;
    width: 50%;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.right {
    background: white;
    padding: 40px;
    width: 50%;
}
.input-group-text {
    background: #1d2671;
    color: white;
    border: none;
}
.btn-login {
    background: #1d2671;
    color: white;
    font-weight: 600;
    transition: background 0.3s ease;
}
.btn-login:hover {
    background: #c33764;
    color: white;
}
</style>
</head>

<body>

<div class="login-container">

    <div class="left">
        <h2>🏡 Sistema de Pagos y Lotes</h2>
        <p class="mt-2 text-white-50">Control de servicios, estados de cuenta y pagos por zonas.</p>
    </div>

    <div class="right">

        <h3 class="mb-4">Iniciar sesión</h3>

        <?php if(!empty($error)): ?>
            <div class="alert alert-danger p-2 small" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php">

            <div class="input-group mb-3">
                <span class="input-group-text">👤</span>
                <input class="form-control" name="usuario" placeholder="Usuario" required autofocus>
            </div>

            <div class="input-group mb-3">
                <span class="input-group-text">🔒</span>
                <input id="pass" type="password" class="form-control" name="password" placeholder="Contraseña" required>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="showPassword" onclick="ver()">
                <label class="form-check-label small text-secondary" for="showPassword">
                    Mostrar contraseña
                </label>
            </div>

            <button type="submit" class="btn btn-login w-100 py-2">Entrar</button>

        </form>

    </div>

</div>

<script>
function ver(){
    let x = document.getElementById("pass");
    x.type = x.type === "password" ? "text" : "password";
}
</script>

</body>
</html>