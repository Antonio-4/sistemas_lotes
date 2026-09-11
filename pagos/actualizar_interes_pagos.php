<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo "Error: Acceso no autorizado.";
    exit();
}

$id_propietario      = intval($_POST['id_propietario'] ?? 0);
$actualizaciones_raw = $_POST['actualizaciones'] ?? '[]';
$actualizaciones     = json_decode($actualizaciones_raw, true);

if (empty($actualizaciones) || !is_array($actualizaciones)) {
    echo "Error: No se enviaron datos válidos.";
    exit();
}

$conn->begin_transaction();

try {
    // Actualiza pagos existentes
    $stmtUpdate = $conn->prepare("UPDATE pagos SET monto = ?, interes = ?, mes_vencimiento = ?, mes_interes = ?, total = ? WHERE id = ?");
    
    // Inserta nuevos pagos
    $stmtInsert = $conn->prepare("INSERT INTO pagos (id_propietario, monto, interes, mes_vencimiento, mes_interes, total, fecha_pago, estado) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'CAPTURADO')");

    foreach ($actualizaciones as $item) {
        $id_pago         = intval($item['id_pago']);
        $monto           = floatval($item['monto']);
        $interes_total   = floatval($item['interes_sum']); // Suma numérica total de intereses para guardar en la BD
        $mes_vencimiento = trim($item['mes_vencimiento']);
        $mes_interes     = trim($item['mes_interes']);     // Cadena con meses (ej. "Enero, Febrero")

        if ($id_pago > 0) {
            // El total siempre es exactamente igual al monto base del pago
            $stmtUpdate->bind_param("ddssdi", $monto, $interes_total, $mes_vencimiento, $mes_interes, $monto, $id_pago);
            $stmtUpdate->execute();
        } else if ($id_propietario > 0 && ($monto > 0 || $interes_total > 0)) {
            // Inserta nuevo registro si se agregó una fila
            $stmtInsert->bind_param("iddssd", $id_propietario, $monto, $interes_total, $mes_vencimiento, $mes_interes, $monto);
            $stmtInsert->execute();
        }
    }

    $conn->commit();
    echo "OK";
} catch (Exception $e) {
    $conn->rollback();
    echo "Error al guardar los datos: " . $e->getMessage();
}
?>