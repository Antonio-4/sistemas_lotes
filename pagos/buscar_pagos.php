<?php
session_start();
include("../conexion.php");

header('Content-Type: application/json');

// Parámetros de paginación y filtros
$pagina  = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$limite  = 10;
$offset  = ($pagina - 1) * $limite;

$buscar  = trim($_GET['buscar'] ?? '');
$zona    = trim($_GET['zona'] ?? '');
$manzana = trim($_GET['manzana'] ?? '');
$lote    = trim($_GET['lote'] ?? '');
$estado  = trim($_GET['estado'] ?? '');

// Construir condiciones SQL
$where = ["1=1"];
$params = [];
$types  = "";

if ($buscar !== '') {
    $where[] = "(pr.nombre LIKE ? OR pr.apellido_paterno LIKE ? OR pr.apellido_materno LIKE ?)";
    $term = "%$buscar%";
    $params[] = $term; $params[] = $term; $params[] = $term;
    $types .= "sss";
}

if ($zona !== '') {
    $where[] = "pr.zona = ?";
    $params[] = $zona;
    $types .= "s";
}

if ($manzana !== '') {
    $where[] = "pr.manzana = ?";
    $params[] = $manzana;
    $types .= "s";
}

if ($lote !== '') {
    $where[] = "pr.lote = ?";
    $params[] = $lote;
    $types .= "s";
}

if ($estado !== '') {
    $where[] = "p.estado = ?";
    $params[] = $estado;
    $types .= "s";
}

$where_clause = implode(" AND ", $where);

// 1. Contar total de registros para paginación
$sql_count = "SELECT COUNT(*) as total 
              FROM pagos p 
              INNER JOIN propietarios pr ON p.id_propietario = pr.id 
              WHERE $where_clause";

$stmt_count = $conn->prepare($sql_count);
if ($params) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$res_count = $stmt_count->get_result()->fetch_assoc();
$total_registros = $res_count['total'] ?? 0;
$total_paginas = ceil($total_registros / $limite);
$stmt_count->close();

// 2. Obtener los datos paginados
$sql_data = "SELECT p.id, p.monto, p.interes, p.total, p.fecha_pago, p.estado, 
                    pr.nombre, pr.apellido_paterno, pr.apellido_materno, 
                    pr.zona, pr.manzana, pr.lote 
             FROM pagos p 
             INNER JOIN propietarios pr ON p.id_propietario = pr.id 
             WHERE $where_clause 
             ORDER BY p.id DESC 
             LIMIT ? OFFSET ?";

$params[] = $limite;
$params[] = $offset;
$types .= "ii";

$stmt_data = $conn->prepare($sql_data);
$stmt_data->bind_param($types, ...$params);
$stmt_data->execute();
$res_data = $stmt_data->get_result();

$datos = [];
while ($row = $res_data->fetch_assoc()) {
    $datos[] = $row;
}
$stmt_data->close();

// Retornar respuesta JSON
echo json_encode([
    'datos' => $datos,
    'paginas' => $total_paginas > 0 ? $total_paginas : 1,
    'pagina_actual' => $pagina
]);

$conn->close();
?>