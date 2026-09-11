<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

include("../conexion.php");

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("<div style='padding:20px; font-family:sans-serif;'>
            <h3>Error: ID de pago no proporcionado o inválido.</h3>
            <a href='index.php'>Volver al listado</a>
         </div>");
}

$sql = "
SELECT pa.*, p.nombre, p.apellido_paterno, p.apellido_materno
FROM pagos pa
JOIN propietarios p ON p.id = pa.id_propietario
WHERE pa.id = ? AND pa.eliminado = 0
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    die("<div style='padding:20px; font-family:sans-serif;'>
            <h3>El pago #{$id} no existe o fue eliminado.</h3>
            <a href='index.php'>Volver al listado</a>
         </div>");
}

$data = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pago #<?= $data['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-4 shadow-sm border-0">

                <h3 class="mb-4 text-primary fw-bold">✏️ Editar Pago <?= $data['id'] ?></h3>

                <form method="POST" action="actualizar.php">

                    <input type="hidden" name="id" value="<?= $data['id'] ?>">
                    <input type="hidden" name="id_propietario" value="<?= $data['id_propietario'] ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Propietario</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars(trim(($data['apellido_paterno'] ?? '') . " " . ($data['apellido_materno'] ?? '') . " " . ($data['nombre'] ?? ''))) ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Monto ($)</label>
                        <input type="number" step="0.01" name="monto" class="form-control" value="<?= $data['monto'] ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Fecha de Pago</label>
                        <input type="date" name="fecha_pago" class="form-control" value="<?= $data['fecha_pago'] ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="CAPTURADO" <?= ($data['estado'] == "CAPTURADO") ? "selected" : "" ?>>CAPTURADO</option>
                            <option value="FACTURADO" <?= ($data['estado'] == "FACTURADO") ? "selected" : "" ?>>FACTURADO</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary fw-bold">Actualizar Pago</button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

</body>
</html>