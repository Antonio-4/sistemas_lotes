<?php
include("../conexion.php");

header('Content-Type: application/json; charset=utf-8');

/* ----------------------------------------------------
   1. PARÁMETROS Y LIMPIEZA DE ENTRADA
---------------------------------------------------- */
$buscar  = trim($_GET['buscar'] ?? '');
$zona    = trim($_GET['zona'] ?? '');
$manzana = trim($_GET['manzana'] ?? '');
$lote    = trim($_GET['lote'] ?? '');
$estado  = trim($_GET['estado'] ?? '');
$pagina  = max(1, intval($_GET['pagina'] ?? 1));

$limite  = 10;
$inicio  = ($pagina - 1) * $limite;

/* ----------------------------------------------------
   2. CONSTRUCCIÓN DE CONDICIONALES (WHERE)
---------------------------------------------------- */
$where = "WHERE pa.eliminado = 0";

if ($buscar !== '') {
    $buscar_esc = $conn->real_escape_string($buscar);
    $where .= " AND CONCAT(IFNULL(p.apellido_paterno,''), ' ', IFNULL(p.apellido_materno,''), ' ', IFNULL(p.nombre,'')) LIKE '%$buscar_esc%'";
}

if ($zona !== '') {
    $zona_esc = $conn->real_escape_string($zona);
    $where .= " AND p.zona LIKE '%$zona_esc%'";
}

if ($manzana !== '') {
    $manzana_esc = $conn->real_escape_string($manzana);
    $where .= " AND p.manzana = '$manzana_esc'";
}

if ($lote !== '') {
    $lote_esc = $conn->real_escape_string($lote);
    $where .= " AND p.lote = '$lote_esc'";
}

if ($estado !== '') {
    $estado_esc = $conn->real_escape_string($estado);
    $where .= " AND pa.estado = '$estado_esc'";
}

/* ----------------------------------------------------
   3. CONTEO TOTAL DE REGISTROS PARA PAGINACIÓN
---------------------------------------------------- */
$sql_total = "
    SELECT COUNT(*) AS total 
    FROM pagos pa 
    INNER JOIN propietarios p ON p.id = pa.id_propietario 
    $where
";

$res_total = $conn->query($sql_total);

if (!$res_total) {
    echo json_encode(["error" => "Error en conteo: " . $conn->error]);
    exit();
}

$total_registros = intval($res_total->fetch_assoc()['total']);
$total_paginas   = ($total_registros > 0) ? ceil($total_registros / $limite) : 1;

/* ----------------------------------------------------
   4. CONSULTA DE REGISTROS PAGINADOS
---------------------------------------------------- */
$sql_datos = "
    SELECT 
        pa.id,
        pa.id_propietario,
        pa.monto,
        pa.mes_vencimiento,
        pa.interes,
        pa.mes_interes,
        pa.total,
        pa.fecha_pago,
        pa.estado,
        pa.eliminado,
        p.nombre,
        p.apellido_paterno,
        p.apellido_materno,
        p.zona,
        p.manzana,
        p.lote
    FROM pagos pa
    INNER JOIN propietarios p ON p.id = pa.id_propietario
    $where
    ORDER BY pa.id DESC
    LIMIT $inicio, $limite
";

$res_datos = $conn->query($sql_datos);

if (!$res_datos) {
    echo json_encode(["error" => "Error en consulta de datos: " . $conn->error]);
    exit();
}

$datos = [];
while ($row = $res_datos->fetch_assoc()) {
    $datos[] = $row;
}

/* ----------------------------------------------------
   5. RESPUESTA EN FORMATO JSON
---------------------------------------------------- */
echo json_encode([
    "datos"           => $datos,
    "paginas"         => $total_paginas,
    "pagina_actual"   => $pagina,
    "total_registros" => $total_registros
], JSON_UNESCAPED_UNICODE);

exit();
?>