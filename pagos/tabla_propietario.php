<?php
include("../conexion.php");

$zona = $_GET['zona'] ?? '';
$manzana = $_GET['manzana'] ?? '';
$lote = $_GET['lote'] ?? '';

$sql = "
SELECT 
    p.*,
    MAX(pg.fecha_pago) as ultima_fecha,
    IFNULL(SUM(pg.monto), 0) as total_pagado,
    IFNULL(SUM(pg.interes), 0) as total_interes,
    (SELECT monto FROM pagos WHERE id_propietario = p.id AND eliminado = 0 ORDER BY fecha_pago DESC LIMIT 1) as ultimo_monto,
    (SELECT interes FROM pagos WHERE id_propietario = p.id AND eliminado = 0 ORDER BY fecha_pago DESC LIMIT 1) as ultimo_interes
FROM propietarios p
LEFT JOIN pagos pg ON p.id = pg.id_propietario AND pg.eliminado = 0
WHERE 1=1
";

if ($zona != "") $sql .= " AND p.zona='$zona'";
if ($manzana != "") $sql .= " AND p.manzana='$manzana'";
if ($lote != "") $sql .= " AND p.lote='$lote'";

$sql .= " GROUP BY p.id ORDER BY p.id DESC";

$res = $conn->query($sql);

echo "<table class='table table-hover table-bordered align-middle'>
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
    <th>Acción</th>
</tr>
</thead>
<tbody>";

while ($r = $res->fetch_assoc()) {
    $ultimo_monto = $r['ultimo_monto'] ?? 0;
    $ultimo_interes = $r['ultimo_interes'] ?? 0;
    $total = $ultimo_monto + $ultimo_interes;

    echo "<tr>
    <td>{$r['id']}</td>
    <td><b>{$r['apellido_paterno']} {$r['apellido_materno']} {$r['nombre']}</b></td>
    <td>{$r['zona']}</td>
    <td>{$r['manzana']}</td>
    <td>{$r['lote']}</td>
    <td>$" . number_format($r['costo'], 2) . "</td>
    <td style='color:" . ($r['saldo'] > 0 ? "red" : "green") . "; font-weight:bold;'>
        $" . number_format($r['saldo'], 2) . "
    </td>
    <td>" . ($r['ultima_fecha'] ? $r['ultima_fecha'] : 'Sin pago') . "</td>
    <td class='text-success'>$" . number_format($ultimo_monto, 2) . "</td>
    <td class='text-danger'>$" . number_format($ultimo_interes, 2) . "</td>
    <td class='fw-bold'>$" . number_format($total, 2) . "</td>
    <td>
        <a class='btn btn-warning btn-sm fw-bold' href='crear.php?id={$r['id']}'>💳 Pagar</a>
    </td>
    </tr>";
}

echo "</tbody></table>";
?>