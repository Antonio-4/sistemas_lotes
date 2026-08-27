<?php
include("../conexion.php");

header('Content-Type: application/json');

// Limpiar espacios en blanco al inicio y final
$zona    = trim($_GET['zona'] ?? '');
$manzana = trim($_GET['manzana'] ?? '');
$lote    = trim($_GET['lote'] ?? '');

if ($zona === '' || $manzana === '' || $lote === '') {
    echo json_encode([]);
    exit();
}

// Consulta flexible utilizando Prepared Statements
$sql = "SELECT id, nombre, apellido_paterno, apellido_materno, zona, manzana, lote, saldo 
        FROM propietarios 
        WHERE (zona = ? OR zona LIKE ?) 
          AND manzana = ? 
          AND lote = ? 
        LIMIT 1";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $likeZona = "%" . $zona . "%";
    $stmt->bind_param("ssss", $zona, $likeZona, $manzana, $lote);
    $stmt->execute();
    $res = $stmt->get_result();

    $data = [];
    if ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
    $stmt->close();
} else {
    echo json_encode(["error" => $conn->error]);
}

$conn->close();
?>