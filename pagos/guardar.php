<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

include("../conexion.php");

// Recibir y desinfectar datos del formulario
$id_propietario  = intval($_POST['id_propietario'] ?? 0);
$monto           = floatval($_POST['monto'] ?? 0);
$fecha_pago      = $_POST['fecha_pago'] ?? date('Y-m-d');
$aplicar_interes = isset($_POST['aplicar_interes']) ? 1 : 0;
$estado          = "CAPTURADO";

// Validar selección de propietario y monto
if ($id_propietario <= 0 || $monto <= 0) {
    echo "<script>
            alert('⚠️ Error: Debes seleccionar un propietario válido y un monto mayor a 0.');
            window.history.back();
          </script>";
    exit();
}

/* 📈 CÁLCULO DE INTERÉS POR MORA (2% mensual desde Mayo 2026) */
$interes = 0;

if ($aplicar_interes === 1) {
    $anio_pago = intval(date("Y", strtotime($fecha_pago)));
    $mes_pago  = intval(date("n", strtotime($fecha_pago)));

    $anio_base = 2026;
    $mes_base  = 5; // Mayo

    if (($anio_pago > $anio_base) || ($anio_pago == $anio_base && $mes_pago >= $mes_base)) {
        $meses_atraso = (($anio_pago - $anio_base) * 12) + ($mes_pago - $mes_base);

        if ($meses_atraso > 0) {
            $interes = $monto * 0.02 * $meses_atraso;
        }
    }
}

$total = $monto + $interes;

/* 💾 INSERTAR REGISTRO DE PAGO */
$sql_insert = "INSERT INTO pagos (id_propietario, monto, interes, total, fecha_pago, estado, eliminado) 
               VALUES (?, ?, ?, ?, ?, ?, 0)";

$stmt = $conn->prepare($sql_insert);

if ($stmt) {
    // 6 marcadores (?) correspondientes: i, d, d, d, s, s
    $stmt->bind_param("idddss", $id_propietario, $monto, $interes, $total, $fecha_pago, $estado);
    
    if ($stmt->execute()) {
        $stmt->close();

        /* 🔄 RECALCULAR Y SINCRONIZAR SALDO DEL PROPIETARIO */
        $sql_update_saldo = "
            UPDATE propietarios p
            SET saldo = deuda_total - (
                SELECT IFNULL(SUM(total), 0)
                FROM pagos pa
                WHERE pa.id_propietario = p.id AND pa.eliminado = 0
            )
            WHERE id = ?
        ";
        
        $stmt_saldo = $conn->prepare($sql_update_saldo);
        $stmt_saldo->bind_param("i", $id_propietario);
        $stmt_saldo->execute();
        $stmt_saldo->close();

        header("Location: index.php?msg=guardado_ok");
        exit();
    } else {
        echo "Error al ejecutar el registro: " . $stmt->error;
    }
} else {
    echo "Error en la preparación de la consulta: " . $conn->error;
}

$conn->close();
?>