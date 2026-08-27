<?php
session_start();

// Proteger sesión de usuario
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$conexion_path = __DIR__ . "/conexion.php";
if (file_exists($conexion_path)) {
    include($conexion_path);
} else {
    die("Error: No se encontró conexion.php en " . __DIR__);
}

if (!isset($conn) || $conn === null || $conn->connect_error) {
    die("Error en la conexión a la base de datos.");
}

/* ====================================
   CONSULTAS GENERALES DE BASE DE DATOS
   ==================================== */

// Total de Propietarios Registrados
$resLotes = $conn->query("SELECT COUNT(*) AS total FROM propietarios");
$totalLotes = ($resLotes) ? $resLotes->fetch_assoc()['total'] : 0;

// Total de Pagos Registrados
$resPagos = $conn->query("SELECT COUNT(*) AS total FROM pagos");
$totalPagos = ($resPagos) ? $resPagos->fetch_assoc()['total'] : 0;

// Total de Deudores (Saldo mayor a 0)
$resMorosos = $conn->query("SELECT COUNT(*) AS total FROM propietarios WHERE saldo > 0");
$totalDeudores = ($resMorosos) ? $resMorosos->fetch_assoc()['total'] : 0;

// Conteos de propietarios al corriente por Zona (Saldo <= 0)
$pagadosPorZona = ["1" => 0, "2" => 0, "3" => 0];

$resZonaPagos = $conn->query("SELECT zona, COUNT(*) AS total FROM propietarios WHERE saldo <= 0 GROUP BY zona");
if ($resZonaPagos) {
    while ($row = $resZonaPagos->fetch_assoc()) {
        $z = preg_replace('/[^0-9]/', '', strval($row['zona']));
        if (array_key_exists($z, $pagadosPorZona)) {
            $pagadosPorZona[$z] += $row['total'];
        }
    }
}

// 💵 MONTO TOTAL ABONADO POR ZONA (Para gráfica)
$montoAbonadoPorZona = ["1" => 0, "2" => 0, "3" => 0];

$resAbonoZona = $conn->query("SELECT 
    pr.zona, 
    IFNULL(SUM(p.monto), 0) AS total_monto 
FROM propietarios pr 
LEFT JOIN pagos p ON pr.id = p.id_propietario 
GROUP BY pr.zona");

if ($resAbonoZona) {
    while ($row = $resAbonoZona->fetch_assoc()) {
        $z = preg_replace('/[^0-9]/', '', strval($row['zona']));
        if (array_key_exists($z, $montoAbonadoPorZona)) {
            $montoAbonadoPorZona[$z] += floatval($row['total_monto']);
        }
    }
}

// 👤 ABONOS AGRUPADOS POR ZONA (Para desplegable)
$abonosPorZona = ["1" => [], "2" => [], "3" => []];

$resConteoPagos = $conn->query("SELECT 
    pr.id, 
    pr.nombre, 
    pr.apellido_paterno, 
    pr.apellido_materno, 
    pr.zona, 
    pr.manzana,
    pr.lote,
    COUNT(p.id) AS total_pagos,
    IFNULL(SUM(p.monto), 0) AS total_abonado
FROM propietarios pr 
LEFT JOIN pagos p ON pr.id = p.id_propietario 
GROUP BY pr.id 
ORDER BY total_abonado DESC, pr.apellido_paterno ASC");

if ($resConteoPagos) {
    while ($row = $resConteoPagos->fetch_assoc()) {
        $zonaP = preg_replace('/[^0-9]/', '', strval($row['zona'] ?? '1'));
        if (array_key_exists($zonaP, $abonosPorZona)) {
            $abonosPorZona[$zonaP][] = $row;
        } else {
            $abonosPorZona["1"][] = $row;
        }
    }
}

// Obtener Lista de Deudores Agrupados por Zona
$deudoresPorZona = ["1" => [], "2" => [], "3" => []];

$resDeudores = $conn->query("SELECT * FROM propietarios WHERE saldo > 0 ORDER BY zona ASC, apellido_paterno ASC");

if ($resDeudores) {
    while ($p = $resDeudores->fetch_assoc()) {
        $zonaP = preg_replace('/[^0-9]/', '', strval($p['zona'] ?? '1'));
        
        $nombreCompleto = trim(($p['nombre'] ?? '') . ' ' . ($p['apellido_paterno'] ?? '') . ' ' . ($p['apellido_materno'] ?? ''));
        
        $item = [
            "id" => $p['id'],
            "nombre" => $nombreCompleto,
            "manzana" => $p['manzana'] ?? 'N/A',
            "lote" => $p['lote'] ?? 'N/A',
            "costo" => floatval($p['costo'] ?? 0),
            "deuda_total" => floatval($p['deuda_total'] ?? 0),
            "saldo" => floatval($p['saldo'] ?? 0)
        ];

        if (array_key_exists($zonaP, $deudoresPorZona)) {
            $deudoresPorZona[$zonaP][] = $item;
        } else {
            $deudoresPorZona["1"][] = $item;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Control de Pagos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f6f9;
}

.sidebar {
    width: 250px;
    height: 100vh;
    background: #1d2671;
    position: fixed;
    color: white;
    padding: 20px;
}

.sidebar a {
    display: block;
    color: white;
    padding: 12px;
    text-decoration: none;
    border-radius: 8px;
    margin-bottom: 5px;
}

.sidebar a:hover {
    background: #c33764;
}

.topbar {
    margin-left: 250px;
    height: 60px;
    background: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.content {
    margin-left: 250px;
    padding: 20px;
}

.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.zona-header {
    background: #1d2671;
    color: white;
    padding: 12px 15px;
    margin-top: 10px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.3s ease;
}

.zona-header:hover {
    background: #23318c;
}

.zona-header-abono {
    background: #198754;
    color: white;
    padding: 12px 15px;
    margin-top: 10px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.3s ease;
}

.zona-header-abono:hover {
    background: #146c43;
}

.deudor-item {
    background: white;
    padding: 10px 15px;
    margin: 5px 0 5px 15px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

.detalle-card {
    display: none;
    margin-left: 30px;
    background: #fff8e6;
    padding: 15px;
    border-radius: 6px;
    border-left: 4px solid #ffc107;
}

.table-responsive {
    max-height: 380px;
    overflow-y: auto;
}
</style>
</head>

<body>

<div class="sidebar">
    <h3>🏡 Sistema</h3>
    <a href="#" class="fw-bold bg-white text-dark">📊 Dashboard</a>
    <a href="propietarios/index.php">👤 Propietarios</a>
    <a href="pagos/">💳 Pagos</a>
    <a href="logout.php">🚪 Salir</a>
</div>

<div class="topbar">
    <h5>📊 Tablero Principal</h5>
    <span>👤 <?= htmlspecialchars($_SESSION['user'] ?? 'Usuario') ?> | 📅 <?= date("d/m/Y") ?></span>
</div>

<div class="content">

    <h2 class="mb-4">Resumen General</h2>

    <!-- METRICAS PRINCIPALES -->
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card p-4 text-center">
                <h6 class="text-muted">Total Propietarios</h6>
                <h2 class="fw-bold text-primary m-0"><?= $totalLotes ?></h2>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-4 text-center">
                <h6 class="text-muted">Pagos Registrados</h6>
                <h2 class="fw-bold text-info m-0"><?= $totalPagos ?></h2>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-4 text-center">
                <h6 class="text-muted">🔴 Deudores Activos</h6>
                <h2 class="fw-bold text-danger m-0"><?= $totalDeudores ?></h2>
            </div>
        </div>
    </div>

    <!-- SECCIÓN DE GRÁFICAS -->
    <div class="row g-4 mt-2">
        <div class="col-md-6">
            <div class="card p-4">
                <h5 class="fw-bold mb-3">📊 Total Abonado ($) por Zona</h5>
                <canvas id="graficaAbonos" height="220"></canvas>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-4">
                <h5 class="fw-bold mb-3">✅ Propietarios Al Corriente por Zona</h5>
                <canvas id="graficaZonas" height="220"></canvas>
            </div>
        </div>
    </div>

    <!-- DESPLEGABLE DE ABONOS POR PROPIETARIO SEGÚN ZONA -->
    <div class="card p-4 mt-4">
        <h5 class="mb-3">💵 Acumulado Abonado por Zona (Haz clic para desplegar)</h5>

        <?php foreach(['1', '2', '3'] as $z): ?>
            <?php $listaAbonos = $abonosPorZona[$z] ?? []; ?>

            <div class="zona-header-abono" onclick="toggleElemento('abono_z<?= $z ?>')">
                <span>🏡 <b>Zona <?= $z ?></b></span>
                <span class="badge bg-light text-dark fs-6">$<?= number_format($montoAbonadoPorZona[$z], 2) ?> Recaudados</span>
            </div>

            <div id="abono_z<?= $z ?>" style="display:none;" class="p-2">
                <?php if (empty($listaAbonos)): ?>
                    <div class="p-3 text-muted fst-italic ms-3">
                        ⚠️ No hay registros de pagos en esta zona.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle bg-white rounded shadow-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Propietario</th>
                                    <th>Ubicación</th>
                                    <th class="text-center">Pagos Realizados</th>
                                    <th class="text-end">Total Abonado ($)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listaAbonos as $prop): ?>
                                    <tr>
                                        <td><b><?= htmlspecialchars($prop['nombre'] . ' ' . $prop['apellido_paterno'] . ' ' . $prop['apellido_materno']) ?></b></td>
                                        <td>Mz: <?= htmlspecialchars($prop['manzana']) ?> / Lt: <?= htmlspecialchars($prop['lote']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-info text-dark fs-6"><?= $prop['total_pagos'] ?></span>
                                        </td>
                                        <td class="text-end fw-bold text-success">
                                            $<?= number_format($prop['total_abonado'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- LISTADO DESPLEGABLE DE DEUDORES -->
    <div class="card p-4 mt-4">
        <h5 class="mb-3">💳 Deudores por Zona (Haz clic para desplegar)</h5>

        <?php foreach(['1', '2', '3'] as $z): ?>
            <?php $lista = $deudoresPorZona[$z] ?? []; ?>

            <div class="zona-header" onclick="toggleElemento('z<?= $z ?>')">
                <span>🏡 <b>Zona <?= $z ?></b></span>
                <span class="badge bg-danger fs-6"><?= count($lista) ?> deudores</span>
            </div>

            <div id="z<?= $z ?>" style="display:none;">

                <?php if (empty($lista)): ?>
                    <div class="p-3 text-muted fst-italic ms-3">
                        ✅ No hay deudores registrados en esta zona.
                    </div>
                <?php else: ?>
                    <?php foreach ($lista as $i => $d): ?>

                        <div class="deudor-item d-flex justify-content-between align-items-center cursor-pointer" onclick="toggleElemento('d<?= $z.'_'.$i ?>')">
                            <span>👤 <b><?= htmlspecialchars($d['nombre']) ?></b> <small class="text-muted">(Mz: <?= htmlspecialchars($d['manzana']) ?> / Lt: <?= htmlspecialchars($d['lote']) ?>)</small></span>
                            <span class="badge bg-warning text-dark">$<?= number_format($d['saldo'], 2) ?></span>
                        </div>

                        <div id="d<?= $z.'_'.$i ?>" class="detalle-card">
                            <div class="row">
                                <div class="col-md-4">
                                    <b>Monto Total Costo:</b> $<?= number_format($d['costo'], 2) ?>
                                </div>
                                <div class="col-md-4">
                                    <b>Deuda Acumulada:</b> $<?= number_format($d['deuda_total'], 2) ?>
                                </div>
                                <div class="col-md-4">
                                    <b class="text-danger">Saldo Restante:</b> $<?= number_format($d['saldo'], 2) ?>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>
                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    </div>

</div>

<script>
function toggleElemento(id) {
    let el = document.getElementById(id);
    if (el) {
        el.style.display = (el.style.display === "none" || el.style.display === "") ? "block" : "none";
    }
}

// 1. Gráfica de Abonos acumulados ($) por Zona
const ctxAbonos = document.getElementById('graficaAbonos').getContext('2d');
new Chart(ctxAbonos, {
    type: 'bar',
    data: {
        labels: ['Zona 1', 'Zona 2', 'Zona 3'],
        datasets: [{
            label: 'Total Abonado ($)',
            data: [
                <?= $montoAbonadoPorZona['1'] ?>, 
                <?= $montoAbonadoPorZona['2'] ?>, 
                <?= $montoAbonadoPorZona['3'] ?>
            ],
            backgroundColor: 'rgba(40, 167, 69, 0.7)',
            borderColor: '#28a745',
            borderWidth: 1.5,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) { return '$' + value.toLocaleString(); }
                }
            }
        }
    }
});

// 2. Gráfica de Propietarios Al Corriente por Zona
const ctxZonas = document.getElementById('graficaZonas').getContext('2d');
new Chart(ctxZonas, {
    type: 'bar',
    data: {
        labels: ['Zona 1', 'Zona 2', 'Zona 3'],
        datasets: [{
            label: 'Propietarios Pagados',
            data: [
                <?= $pagadosPorZona['1'] ?>, 
                <?= $pagadosPorZona['2'] ?>, 
                <?= $pagadosPorZona['3'] ?>
            ],
            backgroundColor: 'rgba(23, 162, 184, 0.7)',
            borderColor: '#17a2b8',
            borderWidth: 1.5,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});
</script>

</body>
</html>