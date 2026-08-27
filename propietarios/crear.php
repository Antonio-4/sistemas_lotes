<?php
include("../conexion.php");
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

$id = $_GET['id'] ?? null;
$data = null;

if ($id) {
    // Sanitizado básico para evitar SQL Injection
    $id_clean = (int)$id;
    $res = $conn->query("SELECT * FROM propietarios WHERE id = $id_clean");
    if ($res) {
        $data = $res->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $id ? "Editar" : "Nuevo" ?> Propietario</title>

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

        <h3 class="mb-4"><?= $id ? "✏️ Editar Propietario" : "➕ Nuevo Propietario" ?></h3>

        <form id="formulario" method="POST" action="<?= $id ? 'actualizar.php' : 'guardar.php' ?>">

            <?php if ($id): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($data['id']) ?>">
            <?php endif; ?>

            <!-- FILA 1: DATOS PERSONALES -->
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Apellido Paterno</label>
                    <input class="form-control nav" name="apellido_paterno" 
                           value="<?= htmlspecialchars($data['apellido_paterno'] ?? '') ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Apellido Materno</label>
                    <input class="form-control nav" name="apellido_materno" 
                           value="<?= htmlspecialchars($data['apellido_materno'] ?? '') ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Nombre(s)</label>
                    <input class="form-control nav" name="nombre" 
                           value="<?= htmlspecialchars($data['nombre'] ?? '') ?>" required>
                </div>
            </div>

            <!-- FILA 2: UBICACIÓN DEL PREDIO -->
            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Zona</label>
                    <select name="zona" class="form-control nav" required>
                        <option value="">Seleccionar...</option>
                        <option value="1" <?= ($data['zona'] ?? '') == "1" ? "selected" : "" ?>>Zona 1</option>
                        <option value="2" <?= ($data['zona'] ?? '') == "2" ? "selected" : "" ?>>Zona 2</option>
                        <option value="3" <?= ($data['zona'] ?? '') == "3" ? "selected" : "" ?>>Zona 3</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Manzana</label>
                    <select name="manzana" class="form-control nav" required>
                        <option value="">Seleccionar...</option>
                        <?php for ($i = 1; $i <= 15; $i++): ?>
                            <option value="<?= $i ?>" <?= ($data['manzana'] ?? '') == $i ? "selected" : "" ?>>
                                Manzana <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Lote</label>
                    <input type="text" name="lote" class="form-control nav" 
                           value="<?= htmlspecialchars($data['lote'] ?? '') ?>" placeholder="Ej. 12 o Mz1-L2" required>
                </div>
            </div>

            <!-- FILA 3: CÁLCULOS FINANCIEROS -->
            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Costo de Servicio ($)</label>
                    <input type="number" step="0.01" id="costo" name="costo" class="form-control nav" 
                           value="<?= htmlspecialchars($data['costo'] ?? '') ?>" placeholder="0.00" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Saldo Pendiente / Deuda ($)</label>
                    <input type="number" step="0.01" id="saldo" name="saldo" class="form-control nav bg-light" 
                           value="<?= htmlspecialchars($data['saldo'] ?? '') ?>" readonly>
                    <!-- Campo oculto para enviar la deuda_total calculada -->
                    <input type="hidden" id="deuda_total" name="deuda_total" value="<?= htmlspecialchars($data['deuda_total'] ?? '') ?>">
                </div>
            </div>

            <!-- BOTONES -->
            <div class="mt-4 text-end">
                <a href="index.php" class="btn btn-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary px-4">
                    <?= $id ? "Actualizar Propietario" : "Guardar Propietario" ?>
                </button>
            </div>

        </form>

    </div>
</div>

<!-- SCRIPT DE NAVEGACIÓN Y CÁLCULOS AUTOMÁTICOS -->
<script>
let campos = [];

document.addEventListener("DOMContentLoaded", () => {

    campos = Array.from(document.querySelectorAll(".nav"));

    const costo = document.getElementById("costo");
    const saldo = document.getElementById("saldo");
    const deudaTotal = document.getElementById("deuda_total");

    // ASIGNAR COSTO DIRECTO A SALDO Y DEUDA EN TIEMPO REAL
    function actualizarSaldo() {
        let c = parseFloat(costo.value) || 0;
        if (c < 0) c = 0;

        saldo.value = c.toFixed(2);
        deudaTotal.value = c.toFixed(2);
    }

    costo.addEventListener("input", actualizarSaldo);

    // NAVEGACIÓN TECLADO FLECHAS Y ENTER
    campos.forEach((campo, index) => {

        campo.addEventListener("keydown", function(e) {

            if (e.key === "ArrowRight") {
                e.preventDefault();
                if (campos[index + 1]) campos[index + 1].focus();
            }

            if (e.key === "ArrowLeft") {
                e.preventDefault();
                if (campos[index - 1]) campos[index - 1].focus();
            }

            if (e.key === "ArrowDown") {
                e.preventDefault();
                let next = index + 3;
                if (campos[next]) campos[next].focus();
            }

            if (e.key === "ArrowUp") {
                e.preventDefault();
                let prev = index - 3;
                if (campos[prev]) campos[prev].focus();
            }

            if (e.key === "Enter") {
                e.preventDefault();

                if (index === campos.length - 1) {
                    document.getElementById("formulario").submit();
                    return;
                }

                if (e.shiftKey) {
                    if (campos[index - 1]) campos[index - 1].focus();
                } else {
                    if (campos[index + 1]) campos[index + 1].focus();
                }
            }

        });

    });

    // Enfocar primer campo al cargar
    if (campos[0]) campos[0].focus();
});
</script>

</body>
</html>