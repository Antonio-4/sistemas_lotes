<?php
include("../conexion.php");

header('Content-Type: application/json');

$buscar  = $_GET['buscar'] ?? '';
$zona    = $_GET['zona'] ?? '';
$manzana = $_GET['manzana'] ?? '';
$lote    = $_GET['lote'] ?? '';
$estado  = $_GET['estado'] ?? '';
$pagina  = intval($_GET['pagina'] ?? 1);

$limite = 10;
$inicio = ($pagina - 1) * $limite;

$where = "WHERE pa.eliminado = 0";

if ($buscar != '') {
    $where .= " AND CONCAT(p.apellido_paterno,' ',p.apellido_materno,' ',p.nombre) LIKE '%$buscar%'";
}
if ($zona != '') $where .= " AND p.zona LIKE '%$zona%'";
if ($manzana != '') $where .= " AND p.manzana LIKE '%$manzana%'";
if ($lote != '') $where .= " AND p.lote LIKE '%$lote%'";
if ($estado != '') $where .= " AND pa.estado = '$estado'";

/* Conteo total */
$res_total = $conn->query("SELECT COUNT(*) total FROM pagos pa JOIN propietarios p ON p.id = pa.id_propietario $where");
$total = $res_total->fetch_assoc()['total'];
$paginas = ceil($total / $limite);

/* Obtener registros */
$sql = "
SELECT 
    pa.id, pa.monto, pa.total, pa.fecha_pago, pa.estado, pa.interes, pa.mes_interes,
    p.nombre, p.apellido_paterno, p.apellido_materno, p.zona, p.manzana, p.lote
FROM pagos pa
JOIN propietarios p ON p.id = pa.id_propietario
$where
ORDER BY pa.id DESC
LIMIT $inicio, $limite
";

$res = $conn->query($sql);

if (!$res) {
    echo json_encode(["error" => $conn->error]);
    exit();
}

$datos = [];
while ($row = $res->fetch_assoc()) {
    $datos[] = $row;
}

echo json_encode([
    "datos" => $datos,
    "paginas" => $paginas
]);
?>