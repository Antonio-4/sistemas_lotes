<?php
include("../conexion.php");

// 1. Limpieza de variables recibidas por GET
$zona = isset($_GET['zona']) ? trim($_GET['zona']) : '';
$manzana = isset($_GET['manzana']) ? trim($_GET['manzana']) : '';
$lote = isset($_GET['lote']) ? trim($_GET['lote']) : '';

// 2. Construcción de consulta con LEFT JOIN a la última transacción
$sql = "
SELECT 
    p.id, p.nombre, p.apellido_paterno, p.apellido_materno, p.zona, p.manzana, p.lote, p.costo, p.saldo,
    MAX(pg.fecha_pago) as ultima_fecha,
    IFNULL(ult_pg.monto, 0) as ultimo_monto,
    IFNULL(ult_pg.interes, 0) as ultimo_interes
FROM propietarios p
LEFT JOIN pagos pg ON p.id = pg.id_propietario AND pg.eliminado = 0
LEFT JOIN (
    SELECT id_propietario, monto, interes
    FROM pagos p1
    WHERE p1.eliminado = 0 
      AND p1.id = (
          SELECT MAX(id) FROM pagos p2 WHERE p2.id_propietario = p1.id_propietario AND p2.eliminado = 0
      )
) ult_pg ON p.id = ult_pg.id_propietario
WHERE 1=1
";

$params = [];
$types = "";

if ($zona !== "") {
    $sql .= " AND LOWER(p.zona) = LOWER(?)";
    $params[] = $zona;
    $types .= "s";
}
if ($manzana !== "") {
    $sql .= " AND LOWER(p.manzana) = LOWER(?)";
    $params[] = $manzana;
    $types .= "s";
}
if ($lote !== "") {
    $sql .= " AND LOWER(p.lote) = LOWER(?)";
    $params[] = $lote;
    $types .= "s";
}

$sql .= " GROUP BY p.id, p.nombre, p.apellido_paterno, p.apellido_materno, p.zona, p.manzana, p.lote, p.costo, p.saldo, ult_pg.monto, ult_pg.interes 
          ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "<div class='alert alert-warning text-center my-3'>No se encontraron registros con los filtros seleccionados.</div>";
    exit();
}

echo "<table class='table table-hover table-bordered align-middle mb-0'>
<thead class='table-dark'>
<tr>
    <th>ID</th>
    <th>Nombre Completo</th>
    <th>Zona</th>
    <th>Manzana</th>
    <th>Lote</th>
    <th>Costo</th>
    <th>Saldo</th>
    <th>Último Pago</th>
    <th>Monto</th>
    <th>Interés</th>
    <th>Total</th>
    <th class='text-center'>Acción</th>
</tr>
</thead>
<tbody>";

while ($r = $res->fetch_assoc()) {
    $ultimo_monto = (float)($r['ultimo_monto'] ?? 0);
    $ultimo_interes = (float)($r['ultimo_interes'] ?? 0);
    $total = $ultimo_monto + $ultimo_interes;
    $fecha_formateada = $r['ultima_fecha'] ? date("d/m/Y", strtotime($r['ultima_fecha'])) : 'Sin pago';

    echo "<tr>
    <td><b>{$r['id']}</b></td>
    <td>{$r['apellido_paterno']} {$r['apellido_materno']} {$r['nombre']}</td>
    <td>{$r['zona']}</td>
    <td>{$r['manzana']}</td>
    <td>{$r['lote']}</td>
    <td>$" . number_format((float)$r['costo'], 2) . "</td>
    <td style='color:" . ($r['saldo'] > 0 ? "red" : "green") . "; font-weight:bold;'>
        $" . number_format((float)$r['saldo'], 2) . "
    </td>
    <td>{$fecha_formateada}</td>
    <td class='text-success fw-bold'>$" . number_format($ultimo_monto, 2) . "</td>
    <td class='text-danger fw-bold'>$" . number_format($ultimo_interes, 2) . "</td>
    <td class='fw-bold'>$" . number_format($total, 2) . "</td>
    <td class='text-center'>
        <a class='btn btn-warning btn-sm fw-bold' href='crear.php?id={$r['id']}'>💳 Pagar</a>
    </td>
    </tr>";
}

echo "</tbody></table>";
?>