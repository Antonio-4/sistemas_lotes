<?php
include("../conexion.php");

// Recalcular saldo de TODOS los propietarios según los pagos existentes en la tabla
$sql = "
UPDATE propietarios p
SET p.saldo = GREATEST(
    p.deuda_total - (
        SELECT IFNULL(SUM(pa.monto), 0)
        FROM pagos pa
        WHERE pa.id_propietario = p.id
    ),
    0
);
";

if ($conn->query($sql)) {
    echo "<h3>✅ Todos los saldos se han recalculado correctamente.</h3>";
    echo "<p><a href='index.php'>Volver al inicio</a></p>";
} else {
    echo "<h3>❌ Error al recalcular saldos: " . $conn->error . "</h3>";
}
?>