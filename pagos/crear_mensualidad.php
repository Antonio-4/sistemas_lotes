<?php
session_start();

// Proteger sesión
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

include("../conexion.php");

/* 👤 OBTENER ID DEL PROPIETARIO */
$id_propietario = intval($_GET['id'] ?? 0);

if (!$id_propietario) {
    die("ID de propietario no válido.");
}

/* 📅 FECHA Y VALORES BASE */
$mes = date("n");   // 1 - 12
$anio = date("Y");
$fecha_pago = date("Y-m-d");

$monto_base = 1000.00; // Cuota mensual base
$estado = "CAPTURADO";

/* 🔎 VERIFICAR SI YA TIENE UN PAGO REGISTRADO EN EL MES ACTUAL */
$verificar = $conn->query("
    SELECT id FROM pagos 
    WHERE id_propietario = $id_propietario 
    AND MONTH(fecha_pago) = $mes 
    AND YEAR(fecha_pago) = $anio 
    AND eliminado = 0
");

if ($verificar && $verificar->num_rows > 0) {
    echo "<script>
        alert('⚠️ Ya existe un pago/mensualidad registrada para este propietario en el mes actual.');
        window.location = 'index.php';
    </script>";
    exit();
}

/* 📈 CÁLCULO DE INTERÉS POR MORA (2% desde Mayo 2026) */
$interes = 0;
$mes_base = 5;
$anio_base = 2026;

if ($anio > $anio_base || ($anio == $anio_base && $mes >= $mes_base)) {
    $meses_atraso = (($anio - $anio_base) * 12) + ($mes - $mes_base);
    if ($meses_atraso > 0) {
        $interes = $monto_base * 0.02 * $meses_atraso;
    }
}

$total = $monto_base + $interes;

/* 💾 INSERTAR EN LA TABLA PAGOS */
$sql_insert = "
    INSERT INTO pagos (id_propietario, monto, interes, total, fecha_pago, estado, eliminado)
    VALUES ($id_propietario, $monto_base, $interes, $total, '$fecha_pago', '$estado', 0)
";

if ($conn->query($sql_insert)) {

    /* 🔄 RECALCULAR SALDO EN TABLA PROPIETARIOS */
    $conn->query("
        UPDATE propietarios p
        SET saldo = deuda_total - (
            SELECT IFNULL(SUM(total), 0)
            FROM pagos pa
            WHERE pa.id_propietario = p.id AND pa.eliminado = 0
        )
        WHERE id = $id_propietario
    ");

    header("Location: index.php?msg=mensualidad_ok");
    exit();
} else {
    echo "Error al generar mensualidad: " . $conn->error;
}
?>