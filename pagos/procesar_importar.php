<?php
// ACTIVAR MOSTRAR ERRORES PARA DEPURACIÓN
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Validar que exista la conexión a la BD
if (!file_exists("../conexion.php")) {
    die("❌ Error: No se encuentra el archivo ../conexion.php");
}
include("../conexion.php");

/* VALIDACIONES INICIALES */
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] != 0) {
    die("❌ Error al subir el archivo (Código de error: " . ($_FILES['archivo']['error'] ?? 'desconocido') . ")");
}

if (empty($_POST['zona'])) {
    die("❌ Selecciona una zona.");
}

$zona = trim($_POST['zona']);
$archivo_tmp = $_FILES['archivo']['tmp_name'];

$archivo = fopen($archivo_tmp, "r");
if (!$archivo) {
    die("❌ No se pudo abrir el archivo subido.");
}

/* DETECTAR DELIMITADOR (Coma o Tabulación) */
$linea_muestra = fgets($archivo);
$delimitador = (strpos($linea_muestra, "\t") !== false) ? "\t" : ",";
rewind($archivo);

$fila_num = 0;

// Iniciar transacción
$conn->begin_transaction();

try {
    $insertados = 0;
    $omitidos   = 0;
    $errores    = 0;

    while (($fila = fgetcsv($archivo, 10000, $delimitador)) !== FALSE) {
        $fila_num++;

        // Limpiar espacios en blanco
        $fila = array_map('trim', $fila);

        /* Saltar cabecera si la primera fila no empieza con un ID numérico */
        if ($fila_num == 1) {
            if (!is_numeric($fila[0])) {
                continue;
            }
        }

        /* Validar ID del propietario */
        $id_propietario = intval($fila[0] ?? 0);
        if ($id_propietario <= 0) {
            continue;
        }

        /* Validar que el propietario exista en la BD */
        $stmt_val = $conn->prepare("SELECT id FROM propietarios WHERE id = ? LIMIT 1");
        if (!$stmt_val) {
            throw new Exception("Error en consulta de validación: " . $conn->error);
        }
        $stmt_val->bind_param("i", $id_propietario);
        $stmt_val->execute();
        $res_val = $stmt_val->get_result();

        if ($res_val->num_rows == 0) {
            $errores++;
            $stmt_val->close();
            continue;
        }
        $stmt_val->close();

        /* Recorrer columnas en pares [Abono, Fecha-pago] */
        $num_columnas = count($fila);

        for ($i = 1; $i < $num_columnas; $i += 2) {
            $val_monto = $fila[$i] ?? '';
            $val_fecha = $fila[$i + 1] ?? '';

            if ($val_monto === '' || $val_fecha === '') {
                continue;
            }

            // Limpiar monto de signos de pesos y comas
            $val_monto_clean = preg_replace('/[^\d.]/', '', str_replace(',', '', $val_monto));
            $monto = floatval($val_monto_clean);

            if ($monto <= 0) {
                continue;
            }

            // Formatear Fecha
            $fecha_preparada = str_replace('/', '-', $val_fecha);
            $timestamp = strtotime($fecha_preparada);

            if (!$timestamp) {
                continue; // Fecha inválida
            }

            $fecha_pago = date('Y-m-d', $timestamp);

            /* EVITAR DUPLICADOS POR PROPIETARIO Y FECHA DE PAGO */
            $stmt_dup = $conn->prepare("
                SELECT id 
                FROM pagos 
                WHERE id_propietario = ? 
                  AND fecha_pago = ? 
                  AND eliminado = 0 
                LIMIT 1
            ");
            if (!$stmt_dup) {
                throw new Exception("Error en preparación de duplicados: " . $conn->error);
            }
            $stmt_dup->bind_param("is", $id_propietario, $fecha_pago);
            $stmt_dup->execute();
            $res_dup = $stmt_dup->get_result();

            if ($res_dup->num_rows > 0) {
                $omitidos++; // Ya existe un pago en esta misma fecha
                $stmt_dup->close();
                continue;
            }
            $stmt_dup->close();

            /* INSERTAR PAGO (Usando solo columnas reales de la BD) */
            $interes = 0.00;
            $mes_interes = '';
            $total = $monto;
            $estado = 'CAPTURADO';
            $eliminado = 0;

            $stmt_ins = $conn->prepare("
                INSERT INTO pagos 
                (id_propietario, monto, interes, mes_interes, total, fecha_pago, estado, eliminado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt_ins) {
                throw new Exception("Error en preparación de insert: " . $conn->error);
            }

            // Tipos: i=int, d=double, s=string
            $stmt_ins->bind_param(
                "iddsdssi",
                $id_propietario,
                $monto,
                $interes,
                $mes_interes,
                $total,
                $fecha_pago,
                $estado,
                $eliminado
            );

            if (!$stmt_ins->execute()) {
                throw new Exception("Error al insertar registro: " . $stmt_ins->error);
            }
            $stmt_ins->close();

            $insertados++;
        }
    }

    fclose($archivo);

    /* RECALCULAR SALDOS EN PROPIETARIOS POR SEGURIDAD */
    $sqlSaldo = "
        UPDATE propietarios p
        SET saldo = GREATEST(
            p.deuda_total - (
                SELECT IFNULL(SUM(pa.monto), 0)
                FROM pagos pa
                WHERE pa.id_propietario = p.id
                  AND pa.eliminado = 0
            ),
            0
        )
    ";

    if (!$conn->query($sqlSaldo)) {
        throw new Exception("Error al actualizar saldos: " . $conn->error);
    }

    $conn->commit();

    // Redirección con mensaje de reporte en JS
    echo "<script>
        alert('Proceso finalizado:\\n- Pagos nuevos: {$insertados}\\n- Pagos omitidos (ya existían en esa fecha): {$omitidos}');
        window.location.href = 'importar.php?ok=1&insertados={$insertados}&omitidos={$omitidos}&errores={$errores}';
    </script>";
    exit();

} catch (Exception $e) {
    $conn->rollback();
    if (isset($archivo) && is_resource($archivo)) {
        fclose($archivo);
    }
    echo "<div style='padding:20px; background-color:#f8d7da; color:#721c24; font-family:sans-serif;'>";
    echo "<h3>❌ Ocurrió un error durante la importación:</h3>";
    echo "<p><strong>Detalle:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<a href='importar.php'>Volver al formulario</a>";
    echo "</div>";
    exit();
}
?>