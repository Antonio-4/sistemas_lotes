<?php
// Incluye la conexión a la base de datos MySQL
include("../conexion.php");

// Recibe y almacena los datos personales enviados desde el formulario por POST
$nombre = $_POST['nombre'];
$apellido_paterno = $_POST['apellido_paterno'];
$apellido_materno = $_POST['apellido_materno'];

// Recibe y almacena la ubicación del predio/lote
$zona = $_POST['zona'];
$manzana = $_POST['manzana'];
$lote = $_POST['lote'];

// Convierte el valor del Costo de Servicio a flotante para evitar errores de tipo de dato
$costo_servicio = floatval($_POST['costo']);

/* 🔥 El Costo de Servicio define la Deuda Total y el Saldo Inicial sin aplicar enganches */
$deuda_total = $costo_servicio;
$saldo = $costo_servicio;

/* VALIDAR DUPLICADO: Consulta mediante sentencia preparada si el lote/predio ya fue registrado */
$stmt_check = $conn->prepare("SELECT id FROM propietarios WHERE zona = ? AND manzana = ? AND lote = ?");
$stmt_check->bind_param("sss", $zona, $manzana, $lote);
$stmt_check->execute();
$check = $stmt_check->get_result();

// Si la consulta encuentra un registro existente, se interrumpe la inserción
if ($check->num_rows > 0) {
    echo "<script>
    alert('🚨 Este lote ya está ocupado');
    window.location='crear.php';
    </script>";
    exit(); // Detiene la ejecución del script
}

/* INSERT: Prepara la inserción limpia del propietario con el costo de servicio asignado */
$sql = "INSERT INTO propietarios (
    nombre, apellido_paterno, apellido_materno,
    zona, manzana, lote,
    costo, deuda_total, saldo
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt_insert = $conn->prepare($sql);
$stmt_insert->bind_param(
    "ssssssddd",
    $nombre,
    $apellido_paterno,
    $apellido_materno,
    $zona,
    $manzana,
    $lote,
    $costo_servicio,
    $deuda_total,
    $saldo
);

// Ejecuta la inserción y valida el resultado
if ($stmt_insert->execute()) {
    echo "<script>
    alert('✅ Propietario registrado correctamente');
    window.location='index.php';
    </script>";
} else {
    // Muestra el error en caso de fallo al insertar
    echo "Error en el registro: " . $conn->error;
}
?>