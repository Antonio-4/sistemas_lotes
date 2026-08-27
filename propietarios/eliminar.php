<?php
include("../conexion.php");
session_start();

/* 🔐 VERIFICACIÓN DE SESIÓN */
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

/* 🔐 CAPTURA Y VALIDACIÓN DEL ID */
$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    /* 🗑️ ELIMINACIÓN SEGURA CON PREPARED STATEMENT */
    $sql = "DELETE FROM propietarios WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: index.php?msg=delete_ok");
            exit();
        } else {
            echo "❌ Error al eliminar el registro: " . $stmt->error;
        }
    } else {
        echo "❌ Error en la preparación de la consulta: " . $conn->error;
    }
} else {
    // Si el ID no es válido, redirige directamente
    header("Location: index.php");
    exit();
}
?>