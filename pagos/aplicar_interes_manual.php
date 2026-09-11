<?php
session_start();
include("../conexion.php");

// Verificar sesión
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo "Error: Acceso no autorizado.";
    exit();
}

// Recibir y validar datos
$id_propietario = isset($_POST['id_propietario']) ? intval($_POST['id_propietario']) : 0;
$detalles_json  = isset($_POST['detalles']) ? $_POST['detalles'] : '[]';
$detalles       = json_decode($detalles_json, true);

if ($id_propietario <= 0) {
    echo "Error: Selecciona un propietario válido.";
    exit();
}

if (!is_array($detalles) || empty($detalles)) {
    echo "Error: Debes ingresar al menos un concepto y monto de recargo.";
    exit();
}

$interes_total = 0;
$conceptos_arr = [];

foreach ($detalles as $row) {
    $concepto = isset($row['concepto']) ? trim($row['concepto']) : '';
    $monto    = isset($row['monto']) ? floatval($row['monto']) : 0;
    
    if (!empty($concepto) && $monto > 0) {
        $interes_total += $monto;
        $conceptos_arr[] = "$concepto ($" . number_format($monto, 2) . ")";
    }
}

if ($interes_total <= 0) {
    echo "Error: El monto total de recargos debe ser mayor a $0.00.";
    exit();
}

// Transacción en base de datos
$conn->begin_transaction();

try {
    // 1. Verificar si el propietario existe
    $stmt = $conn->prepare("SELECT id FROM propietarios WHERE id = ?");
    $stmt->bind_param("i", $id_propietario);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        throw new Exception("El propietario seleccionado no existe.");
    }

    // 2. Registrar el interés solo como un registro de historial en la tabla pagos
    // - monto = 0.00 (No afecta lo abonado/pagado)
    // - total = 0.00 (No incrementa la deuda)
    // - interes = $interes_total (Se guarda solo de manera informativa en la tabla pagos)
    $estado = "CAPTURADO";
    $fecha_actual = date("Y-m-d H:i:s");

    $stmt_ins = $conn->prepare("INSERT INTO pagos (id_propietario, monto, interes, total, fecha_pago, estado) VALUES (?, 0.00, ?, 0.00, ?, ?)");
    $stmt_ins->bind_param("idss", $id_propietario, $interes_total, $fecha_actual, $estado);
    $stmt_ins->execute();

    $conn->commit();
    echo "OK";
} catch (Exception $e) {
    $conn->rollback();
    echo "Error en la base de datos: " . $e->getMessage();
}
?>