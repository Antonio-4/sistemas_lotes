<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo "Error: Acceso no autorizado.";
    exit();
}

$id_propietario = isset($_POST['id_propietario']) ? intval($_POST['id_propietario']) : 0;
$desglose_raw   = isset($_POST['desglose']) ? $_POST['desglose'] : '[]';
$desglose       = json_decode($desglose_raw, true);

if ($id_propietario <= 0) {
    echo "Error: Propietario no válido.";
    exit();
}

if (empty($desglose) || !is_array($desglose)) {
    echo "Error: No se incluyeron meses ni montos de penalización.";
    exit();
}

$conn->begin_transaction();

try {
    $fecha_actual = date("Y-m-d H:i:s");

    foreach ($desglose as $item) {
        $mes_nombre = trim($item['mes']);
        $monto_interes = floatval($item['monto']);

        if ($monto_interes > 0) {
            $observaciones = "Penalización Manual | Interés " . $mes_nombre;
            $estado = "CAPTURADO";
            $monto_pago = 0.00;
            $total_pago = $monto_pago + $monto_interes;

            // Inserta en la tabla guardando el interés en su propia columna para verse reflejado en la tabla general
            $stmt_ins = $conn->prepare("INSERT INTO pagos (id_propietario, monto, interes, total, fecha_pago, estado, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_ins->bind_param("idddsss", $id_propietario, $monto_pago, $monto_interes, $total_pago, $fecha_actual, $estado, $observaciones);
            $stmt_ins->execute();
        }
    }

    $conn->commit();
    echo "OK";
} catch (Exception $e) {
    $conn->rollback();
    echo "Error al procesar recargos: " . $e->getMessage();
}
?>