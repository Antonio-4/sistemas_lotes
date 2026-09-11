<?php
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión expirada']);
    exit();
}

include("../conexion.php");

$id_propietario = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_propietario <= 0) {
    echo json_encode([]);
    exit();
}

try {
    $sql = "SELECT id, 
                   IFNULL(monto, 0) AS monto, 
                   IFNULL(interes, 0) AS interes, 
                   IFNULL(mes_interes, '') AS mes_interes, 
                   IFNULL(mes_vencimiento, '') AS mes_pago,
                   IFNULL(fecha_pago, '') AS fecha_pago 
            FROM pagos 
            WHERE id_propietario = ? 
            ORDER BY id DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("i", $id_propietario);
    $stmt->execute();
    $result = $stmt->get_result();

    $pagos = [];
    while ($row = $result->fetch_assoc()) {
        $pagos[] = [
            'id'          => intval($row['id']),
            'monto'       => floatval($row['monto']),
            'interes'     => floatval($row['interes']),
            'mes_interes' => $row['mes_interes'],
            'mes_pago'    => $row['mes_pago'],
            'fecha_pago'  => $row['fecha_pago']
        ];
    }

    echo json_encode($pagos);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>