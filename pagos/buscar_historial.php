<?php
include("../conexion.php");

header('Content-Type: application/json');

/* =========================
   FILTROS
========================= */
$zona    = trim($_GET['zona'] ?? '');
$mes     = trim($_GET['mes'] ?? '');
$anio    = trim($_GET['anio'] ?? '');
$buscar  = trim($_GET['buscar'] ?? '');
$manzana = trim($_GET['manzana'] ?? '');
$lote    = trim($_GET['lote'] ?? '');
$estado  = trim($_GET['estado'] ?? '');

/* =========================
   QUERY BASE
========================= */
$sql = "
SELECT 
    pa.*,
    p.nombre,
    p.apellido_paterno,
    p.apellido_materno,
    p.zona,
    p.manzana,
    p.lote
FROM pagos pa
INNER JOIN propietarios p 
    ON p.id = pa.id_propietario
WHERE pa.eliminado = 0
";

$params = [];
$types  = "";

/* =========================
   ZONA
========================= */
if($zona !== ""){
    $sql .= " AND p.zona = ?";
    $params[] = $zona;
    $types .= "s";
}

/* =========================
   MES
========================= */
if($mes !== ""){
    $sql .= " AND MONTH(pa.fecha_pago) = ?";
    $params[] = intval($mes);
    $types .= "i";
}

/* =========================
   AÑO
========================= */
if($anio !== ""){
    $sql .= " AND YEAR(pa.fecha_pago) = ?";
    $params[] = intval($anio);
    $types .= "i";
}

/* =========================
   BUSCADOR NOMBRE
========================= */
if($buscar !== ""){

    $sql .= " AND (
        p.nombre LIKE ?
        OR p.apellido_paterno LIKE ?
        OR p.apellido_materno LIKE ?
        OR CONCAT(
            p.apellido_paterno,' ',
            p.apellido_materno,' ',
            p.nombre
        ) LIKE ?
    )";

    $like = "%{$buscar}%";

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;

    $types .= "ssss";
}

/* =========================
   MANZANA EXACTA
========================= */
if($manzana !== ""){
    $sql .= " AND p.manzana = ?";
    $params[] = $manzana;
    $types .= "s";
}

/* =========================
   LOTE EXACTO
   🔥 AQUÍ ESTÁ EL ARREGLO
========================= */
if($lote !== ""){
    $sql .= " AND CAST(p.lote AS CHAR) = ?";
    $params[] = $lote;
    $types .= "s";
}

/* =========================
   ESTADO
========================= */
if($estado !== ""){
    $sql .= " AND pa.estado = ?";
    $params[] = $estado;
    $types .= "s";
}

/* =========================
   ORDENAR MÁS RECIENTES
========================= */
$sql .= " ORDER BY pa.id DESC";

/* =========================
   PREPARAR
========================= */
$stmt = $conn->prepare($sql);

if(!$stmt){
    die(json_encode([
        "error" => $conn->error
    ]));
}

/* =========================
   BIND PARAMS
========================= */
if(count($params) > 0){
    $stmt->bind_param($types, ...$params);
}

/* =========================
   EJECUTAR
========================= */
$stmt->execute();

$res = $stmt->get_result();

$data = [];

/* =========================
   RESULTADOS
========================= */
while($row = $res->fetch_assoc()){

    $data[] = [
        "id" => $row['id'],
        "apellido_paterno" => $row['apellido_paterno'],
        "apellido_materno" => $row['apellido_materno'],
        "nombre" => $row['nombre'],
        "zona" => $row['zona'],
        "manzana" => $row['manzana'],
        "lote" => $row['lote'],
        "monto" => $row['monto'],
        "interes" => $row['interes'],
        "mes_interes" => $row['mes_interes'],
        "total" => $row['total'],
        "fecha_pago" => $row['fecha_pago'],
        "estado" => $row['estado']
    ];
}

/* =========================
   RESPUESTA JSON
========================= */
echo json_encode($data);

$stmt->close();
$conn->close();
?>