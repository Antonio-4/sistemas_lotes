<?php
include("../conexion.php");

header('Content-Type: application/json; charset=utf-8');

/* ----------------------------------------------------
   1. RECEPCIÓN Y LIMPIEZA DE PARÁMETROS
---------------------------------------------------- */
$buscar  = trim($_GET['buscar'] ?? '');
$zona    = trim($_GET['zona'] ?? '');
$manzana = trim($_GET['manzana'] ?? '');
$lote    = trim($_GET['lote'] ?? '');
$estado  = trim($_GET['estado'] ?? '');
$mes     = trim($_GET['mes'] ?? '');
$anio    = trim($_GET['anio'] ?? '');
$pagina  = max(1, intval($_GET['pagina'] ?? 1));

$limite  = 10;
$inicio  = ($pagina - 1) * $limite;

/* ----------------------------------------------------
   2. TRADUCCIÓN Y MAPEO DE MES
---------------------------------------------------- */
$meses_map = [
    'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
    'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
    'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
];

$num_mes = 0;
if ($mes !== '') {
    $mes_lower = mb_strtolower($mes, 'UTF-8');
    if (isset($meses_map[$mes_lower])) {
        $num_mes = $meses_map[$mes_lower];
    } elseif (is_numeric($mes)) {
        $num_mes = intval($mes);
    }
}

/* ----------------------------------------------------
   3. CONSTRUCCIÓN DE LA CLÁUSULA WHERE
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

// Filtro Flexible por Mes (evalúa nombre o número de mes en fecha_pago y mes_interes)
if ($mes !== '') {
    $mes_esc = $conn->real_escape_string($mes);
    $condiciones_mes = [
        "pa.mes_interes LIKE '%$mes_esc%'"
    ];
    
    if ($num_mes > 0) {
        $condiciones_mes[] = "MONTH(pa.fecha_pago) = $num_mes";
    }
    
    $where .= " AND (" . implode(" OR ", $condiciones_mes) . ")";
}

// Filtro por Año basado en fecha_pago
if ($anio !== '') {
    $anio_esc = $conn->real_escape_string($anio);
    $where .= " AND YEAR(pa.fecha_pago) = '$anio_esc'";
}

/* ----------------------------------------------------
   4. CONTEO TOTAL DE REGISTROS (PAGINACIÓN)
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
   5. CONSULTA DE REGISTROS (FECHA COMPLETA)
---------------------------------------------------- */
$sql_datos = "
    SELECT 
        pa.id,
        pa.id_propietario,
        pa.monto,
        pa.interes,
        pa.total,
        pa.fecha_pago,
        pa.estado,
        pa.eliminado,
        pa.mes_interes,
        CONCAT(
            DAY(pa.fecha_pago), ' de ',
            ELT(MONTH(pa.fecha_pago), 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'),
            ' de ', YEAR(pa.fecha_pago)
        ) AS mes_pago,
        CONCAT(
            DAY(pa.fecha_pago), ' de ',
            ELT(MONTH(pa.fecha_pago), 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'),
            ' de ', YEAR(pa.fecha_pago)
        ) AS mes_vencimiento,
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
    echo json_encode(["error" => "Error en consulta: " . $conn->error]);
    exit();
}

$datos = [];
while ($row = $res_datos->fetch_assoc()) {
    $datos[] = $row;
}

/* ----------------------------------------------------
   6. RESPUESTA JSON
---------------------------------------------------- */
echo json_encode([
    "datos"           => $datos,
    "paginas"         => $total_paginas,
    "pagina_actual"   => $pagina,
    "total_registros" => $total_registros
], JSON_UNESCAPED_UNICODE);

exit();
?>