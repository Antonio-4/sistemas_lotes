<?php
include("../conexion.php");

$id_propietario  = intval($_POST['id_propietario'] ?? 0);
$monto_ingresado = floatval($_POST['monto'] ?? 0);
$mes_vencimiento = $_POST['mes_vencimiento'] ?? null;
$fecha_pago      = $_POST['fecha_pago'] ?? date('Y-m-d');
$aplicar_interes = isset($_POST['aplicar_interes']) ? 1 : 0;

if ($id_propietario <= 0 || $monto_ingresado <= 0) {
    die("Error: El propietario o el monto son inválidos.");
}

// 1. Calcular el interés únicamente como REGISTRO INFORMATIVO (2%)
$monto_interes = 0;
if ($aplicar_interes === 1 && !empty($mes_vencimiento)) {
    $fecha_venc = new DateTime($mes_vencimiento . "-01");
    $fecha_act  = new DateTime(date('Y-m-01'));
    
    if ($fecha_venc < $fecha_act) {
        $monto_interes = $monto_ingresado * 0.02; // Nota informativa
    }
}

$abono_deuda   = $monto_ingresado;
$total_cobrado = $monto_ingresado;

// 2. Insertar el pago en la base de datos
$sql = "INSERT INTO pagos (id_propietario, monto, interes, total, mes_vencimiento, fecha_pago, estado, eliminado) 
        VALUES (?, ?, ?, ?, ?, ?, 'CAPTURADO', 0)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("idddss", $id_propietario, $abono_deuda, $monto_interes, $total_cobrado, $mes_vencimiento, $fecha_pago);

if ($stmt->execute()) {
    
    // 3. Recalcular el saldo descontando la suma de los pagos reales
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
    $stmtSaldo->execute();

    header("Location: index.php?msg=success");
    exit();
} else {
    echo "Error al registrar el pago: " . $conn->error;
}
?>