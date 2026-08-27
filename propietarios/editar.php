<?php
include("../conexion.php");
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

/* OBTENER DATOS DEL PROPIETARIO Y TOTAL PAGADO */
$sql = "
SELECT 
    p.*,
    (
        SELECT IFNULL(SUM(monto), 0)
        FROM pagos
        WHERE id_propietario = p.id
    ) AS pagado
FROM propietarios p
WHERE p.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Error: Propietario no encontrado.");
}

/* VALORES CALCULADOS */
$deuda  = floatval($data['deuda_total']);
$pagado = floatval($data['pagado']);
$saldo  = floatval($data['saldo']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Propietario</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #f4f6f9;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.nav:focus {
    border: 2px solid #1d2671;
    box-shadow: 0 0 5px rgba(29,38,113,0.5);
}
</style>
</head>

<body>

<div class="container mt-5 mb-5">
    <div class="card p-4">

        <h3 class="mb-4">✏️ Editar Propietario</h3>

        <form id="formulario" method="POST" action="actualizar.php">

            <input type="hidden" name="id" value="<?= $data['id'] ?>">

            <!-- FILA 1: DATOS PERSONALES -->
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Apellido Paterno</label>
                    <input class="form-control nav" name="apellido_paterno" 
                           value="<?= htmlspecialchars($data['apellido_paterno']) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Apellido Materno</label>
                    <input class="form-control nav" name="apellido_materno" 
                           value="<?= htmlspecialchars($data['apellido_materno']) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Nombre</label>
                    <input class="form-control nav" name="nombre" 
                           value="<?= htmlspecialchars($data['nombre']) ?>" required>
                </div>
            </div>

            <!-- FILA 2: UBICACIÓN DEL PREDIO -->
            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Zona</label>
                    <select name="zona" class="form-control nav" required>
                        <option value="1" <?= (string)$data['zona'] === "1" || strtolower($data['zona']) === "z1" ? "selected" : "" ?>>Z1 / Zona 1</option>
                        <option value="2" <?= (string)$data['zona'] === "2" || strtolower($data['zona']) === "z2" ? "selected" : "" ?>>Z2 / Zona 2</option>
                        <option value="3" <?= (string)$data['zona'] === "3" || strtolower($data['zona']) === "z3" ? "selected" : "" ?>>Z3 / Zona 3</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Manzana</label>
                    <input type="text" name="manzana" class="form-control nav" 
                           value="<?= htmlspecialchars($data['manzana']) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Lote</label>
                    <input type="text" name="lote" class="form-control nav" 
                           value="<?= htmlspecialchars($data['lote']) ?>" required>
                </div>
            </div>

            <!-- FILA 3: COSTO DE SERVICIO Y SALDO -->
            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Costo Servicio ($)</label>
                    <input type="number" step="0.01" id="costo" name="costo" class="form-control nav" 
                           value="<?= $data['costo'] ?>" required>
                    <!-- Campo oculto para mantener sincronizada la deuda_total con costo -->
                    <input type="hidden" id="deuda_total" name="deuda_total" value="<?= $data['deuda_total'] ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Saldo Actual ($)</label>
                    <input type="text" id="saldo_display" class="form-control bg-light fw-bold text-danger" 
                           value="$<?= number_format($saldo, 2) ?>" readonly>
                    <input type="hidden" id="saldo" name="saldo" value="<?= $saldo ?>">
                </div>
            </div>

            <!-- FILA 4: PAGOS ACUMULADOS -->
            <div class="row g-3 mt-2">
                <div class="col-md-12">
                    <label class="form-label fw-bold">Total Pagado Registrado ($)</label>
                    <input type="text" id="pagado_display" class="form-control bg-light fw-bold text-success" 
                           value="$<?= number_format($pagado, 2) ?>" readonly>
                    <input type="hidden" id="pagado" value="<?= $pagado ?>">
                </div>
            </div>

            <!-- BOTONES -->
            <div class="mt-4 text-end">
                <a href="index.php" class="btn btn-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary px-4">Actualizar Propietario</button>
            </div>

        </form>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const campos = Array.from(document.querySelectorAll(".nav"));
    const costoInput = document.getElementById("costo");
    const deudaInput = document.getElementById("deuda_total");
    const pagadoVal = parseFloat(document.getElementById("pagado").value) || 0;
    const saldoInput = document.getElementById("saldo");
    const saldoDisplay = document.getElementById("saldo_display");

    // RECALCULAR SALDO Y DEUDA EN TIEMPO REAL SI SE EDITA EL COSTO DE SERVICIO
    costoInput.addEventListener("input", () => {
        let nuevoCosto = parseFloat(costoInput.value) || 0;
        deudaInput.value = nuevoCosto.toFixed(2);

        let nuevoSaldo = nuevoCosto - pagadoVal;
        if (nuevoSaldo < 0) nuevoSaldo = 0;

        saldoInput.value = nuevoSaldo.toFixed(2);
        saldoDisplay.value = "$" + nuevoSaldo.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    });

    // NAVEGACIÓN TECLADO
    campos.forEach((campo, index) => {
        campo.addEventListener("keydown", function(e) {
            if (e.key === "ArrowRight" && campos[index + 1]) campos[index + 1].focus();
            if (e.key === "ArrowLeft" && campos[index - 1]) campos[index - 1].focus();
            if (e.key === "ArrowDown" && campos[index + 3]) campos[index + 3].focus();
            if (e.key === "ArrowUp" && campos[index - 3]) campos[index - 3].focus();
        });
    });

    if (campos[0]) campos[0].focus();
});
</script>

</body>
</html>