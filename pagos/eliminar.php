<?php
session_start();
include("../conexion.php");

// Verificación de sesión de administrador
if (!isset($_SESSION['user']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    die("ACCESO_DENEGADO");
}

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID_INVALIDO");
}

// 1. Obtener el id_propietario antes de borrar el pago
$sqlGetProp = "SELECT id_propietario FROM pagos WHERE id = ?";
$stmtGet = $conn->prepare($sqlGetProp);
$stmtGet->bind_param("i", $id);
$stmtGet->execute();
$resGet = $stmtGet->get_result();

if ($resGet->num_rows === 0) {
    die("PAGO_NO_ENCONTRADO");
}

$row = $resGet->fetch_assoc();
$id_propietario = $row['id_propietario'];

// 2. ELIMINAR FÍSICAMENTE EL PAGO DE LA BASE DE DATOS
$sqlDelete = "DELETE FROM pagos WHERE id = ?";
$stmtDel = $conn->prepare($sqlDelete);
$stmtDel->bind_param("i", $id);

if (!$stmtDel->execute()) {
    die("ERROR_AL_ELIMINAR: " . $stmtDel->error);
}

// 3. Recalcular el saldo descontando solo los pagos existentes en la BD
$sqlSaldo = "
UPDATE propietarios p
SET saldo = GREATEST(
    p.deuda_total - (
        SELECT IFNULL(SUM(pa.monto), 0)
        FROM pagos pa
        WHERE pa.id_propietario = p.id
    ),
    0
)
WHERE p.id = ?
";

$stmtSaldo = $conn->prepare($sqlSaldo);
$stmtSaldo->bind_param("i", $id_propietario);

if ($stmtSaldo->execute()) {
    echo "OK";
} else {
    echo "ERROR_RECALCULO: " . $stmtSaldo->error;
}
?>