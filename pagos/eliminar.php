<?php
session_start();

// Validar que el usuario esté autenticado
if (!isset($_SESSION['user'])) {
    echo "ERROR: Sesión no válida.";
    exit();
}

include("../conexion.php");

// Obtener y validar ID del pago
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "ERROR: ID de pago no válido.";
    exit();
}

// 1. Obtener el id_propietario antes de eliminar para poder recalcular su saldo
$sql_info = "SELECT id_propietario FROM pagos WHERE id = ?";
$stmt_info = $conn->prepare($sql_info);

if ($stmt_info) {
    $stmt_info->bind_param("i", $id);
    $stmt_info->execute();
    $res = $stmt_info->get_result();
    $pago = $res->fetch_assoc();
    $stmt_info->close();

    if (!$pago) {
        echo "ERROR: El pago no existe en la base de datos.";
        exit();
    }

    $id_propietario = $pago['id_propietario'];

    // 2. Eliminar el registro del pago
    $sql_delete = "DELETE FROM pagos WHERE id = ?";
    $stmt_del = $conn->prepare($sql_delete);

    if ($stmt_del) {
        $stmt_del->bind_param("i", $id);

        if ($stmt_del->execute()) {
            $stmt_del->close();

            // 3. Recalcular y actualizar el saldo del propietario
            $sql_update_saldo = "
                UPDATE propietarios p
                SET saldo = deuda_total - (
                    SELECT IFNULL(SUM(total), 0)
                    FROM pagos pa
                    WHERE pa.id_propietario = p.id
                )
                WHERE id = ?
            ";

            $stmt_saldo = $conn->prepare($sql_update_saldo);
            $stmt_saldo->bind_param("i", $id_propietario);
            $stmt_saldo->execute();
            $stmt_saldo->close();

            // Respuesta esperada por el JS para remover la fila de la interfaz
            echo "OK";
            exit();
        } else {
            echo "Error al ejecutar la eliminación: " . $stmt_del->error;
        }
    } else {
        echo "Error en la consulta DELETE: " . $conn->error;
    }
} else {
    echo "Error en la consulta SELECT: " . $conn->error;
}

$conn->close();
?>