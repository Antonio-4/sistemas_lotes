<?php
include("../conexion.php");
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

/* 🔐 CAPTURA Y SANITIZACIÓN DE DATOS */
$apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
$apellido_materno = trim($_POST['apellido_materno'] ?? '');
$nombre           = trim($_POST['nombre'] ?? '');

$zona    = trim($_POST['zona'] ?? '');
$manzana = trim($_POST['manzana'] ?? '');
$lote    = trim($_POST['lote'] ?? '');

$costo       = floatval($_POST['costo'] ?? 0);
$deuda_total = floatval($_POST['deuda_total'] ?? $costo); // Sincroniza con costo si no se envió
$saldo       = floatval($_POST['saldo'] ?? 0);

/* 🔥 RECALCULO DE SEGURIDAD EN BACKEND */
// Obtener el total pagado desde la BD para garantizar que el saldo sea exacto
$sql_pagos = "SELECT IFNULL(SUM(monto), 0) AS pagado FROM pagos WHERE id_propietario = ?";
$stmt_pago = $conn->prepare($sql_pagos);
$stmt_pago->bind_param("i", $id);
$stmt_pago->execute();
$res_pago = $stmt_pago->get_result()->fetch_assoc();
$pagado = floatval($res_pago['pagado']);
$stmt_pago->close();

// Recalcular saldo real: Deuda Total - Pagado
$saldo_real = $deuda_total - $pagado;
if ($saldo_real < 0) {
    $saldo_real = 0;
}

/* 📝 ACTUALIZACIÓN EN LA BASE DE DATOS */
$sql = "
UPDATE propietarios SET
    apellido_paterno = ?,
    apellido_materno = ?,
    nombre = ?,
    zona = ?,
    manzana = ?,
    lote = ?,
    costo = ?,
    deuda_total = ?,
    saldo = ?
WHERE id = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("❌ Error en la preparación de la consulta: " . $conn->error);
}

$stmt->bind_param(
    "ssssssdddi",
    $apellido_paterno,
    $apellido_materno,
    $nombre,
    $zona,
    $manzana,
    $lote,
    $costo,
    $deuda_total,
    $saldo_real,
    $id
);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: index.php?msg=edit_ok");
    exit();
} else {
    echo "❌ Error al actualizar el registro: " . $stmt->error;
    $stmt->close();
    $conn->close();
}
?>