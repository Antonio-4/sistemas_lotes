<?php 
include("../conexion.php");

/* 🔐 CAPTURA SEGURA */
$buscar  = $_GET['buscar']  ?? '';
$zona    = $_GET['zona']    ?? '';
$manzana = $_GET['manzana'] ?? '';
$lote    = $_GET['lote']    ?? '';

/* 🔥 QUERY BASE */
$sql = "SELECT * FROM propietarios WHERE 1=1";
$params = [];
$types  = "";

/* 🔎 BUSCADOR INTELIGENTE */
if($buscar != ""){
    $sql .= " AND (
        nombre LIKE ?
        OR apellido_paterno LIKE ?
        OR apellido_materno LIKE ?
        OR zona LIKE ?
        OR zona LIKE ?
        OR manzana LIKE ?
        OR lote LIKE ?
        OR id LIKE ?
    )";

    $buscar_like = "%$buscar%";
    $buscar_zona = "%Z$buscar%";

    array_push($params,
        $buscar_like,
        $buscar_like,
        $buscar_like,
        $buscar_like,
        $buscar_zona,
        $buscar_like,
        $buscar_like,
        $buscar_like
    );

    $types .= "ssssssss";
}

/* 🔎 FILTRO ZONA INTELIGENTE */
if($zona != ""){
    $sql .= " AND (zona LIKE ? OR zona LIKE ?)";

    $params[] = "%$zona%";
    $params[] = "%Z$zona%";

    $types .= "ss";
}

/* 🔎 FILTRO MANZANA */
if($manzana != ""){
    $sql .= " AND manzana = ?";
    $params[] = $manzana;
    $types .= "s";
}

/* 🔎 FILTRO LOTE */
if($lote != ""){
    $sql .= " AND lote = ?";
    $params[] = $lote;
    $types .= "s";
}

/* 🔥 PREPARAR */
$stmt = $conn->prepare($sql);

if(!$stmt){
    die("❌ Error en consulta: " . $conn->error);
}

/* 🔐 BIND DINÁMICO */
if(!empty($params)){
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$res = $stmt->get_result();

/* 🧾 TABLA CON ESTILO DE TEXTO NEGRO FORZADO */
echo "<table class='table table-hover table-bordered' style='color: #000000;'>
<thead class='table-dark'>
<tr>
<th>ID</th>
<th>Propietario</th>
<th>Zona</th>
<th>Manzana</th>
<th>Lote</th>
<th>Costo Servicio</th>
<th>Deuda Total</th>
<th>Pagado</th>
<th>Saldo</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>";

while($row = $res->fetch_assoc()){

    /* 🔥 CALCULOS */
    $pagado = $row['deuda_total'] - $row['saldo'];

    /* 🔥 ESTADO */
    if($row['saldo'] <= 0){
        $estado = "PAGADO";
        $color = "success";
    }elseif($pagado > 0){
        $estado = "EN PROCESO";
        $color = "warning text-dark";
    }else{
        $estado = "MOROSO";
        $color = "danger";
    }

    echo "<tr style='color: #000000 !important;'>

    <td style='color: #000000 !important; font-weight: bold;'>{$row['id']}</td>

    <td style='color: #000000 !important; font-weight: 500;'>
        {$row['apellido_paterno']} 
        {$row['apellido_materno']} 
        {$row['nombre']}
    </td>

    <td style='color: #000000 !important;'>{$row['zona']}</td>
    <td style='color: #000000 !important;'>{$row['manzana']}</td>
    <td style='color: #000000 !important;'>{$row['lote']}</td>

    <td style='color: #000000 !important;'>$" . number_format($row['costo'], 2) . "</td>
    <td style='color: #000000 !important;'>$" . number_format($row['deuda_total'], 2) . "</td>

    <td style='color: green !important; font-weight: bold;'>
        $" . number_format($pagado, 2) . "
    </td>

    <td style='color: red !important; font-weight: bold;'>
        $" . number_format($row['saldo'], 2) . "
    </td>

    <td>
        <span class='badge bg-$color'>$estado</span>
    </td>

    <td>
        <a href='../pagos/crear.php?id_propietario={$row['id']}' class='btn btn-success btn-sm'>💳</a>
        <a href='editar.php?id={$row['id']}' class='btn btn-warning btn-sm'>✏️</a>
        <a href='eliminar.php?id={$row['id']}' class='btn btn-danger btn-sm' onclick=\"return confirm('¿Eliminar registro?')\">🗑️</a>
    </td>

    </tr>";
}

echo "</table>";

$stmt->close();
$conn->close();
?>