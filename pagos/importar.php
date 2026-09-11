<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

include("../conexion.php");

/* 🔥 OBTENER ZONAS REALES DE LA BD */
$zonas = [];
$res = $conn->query("SELECT DISTINCT zona FROM propietarios ORDER BY zona ASC");

if ($res) {
    while ($z = $res->fetch_assoc()) {
        if (!empty(trim($z['zona']))) {
            $zonas[] = $z['zona'];
        }
    }
}

// Lectura de resultados enviados desde procesar_importar.php
$ok         = $_GET['ok'] ?? null;
$insertados = $_GET['insertados'] ?? 0;
$omitidos   = $_GET['omitidos'] ?? 0;
$errores    = $_GET['errores'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Pagos por Zona</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        .header-bg { background: linear-gradient(135deg, #1d2671 0%, #c33764 100%); color: white; border-radius: 12px 12px 0 0; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            
            <!-- ALERTA DE RESUMEN TRAS IMPORTAR -->
            <?php if ($ok == 1): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                    <h5 class="alert-heading fw-bold"><i class="bi bi-check-circle-fill me-2"></i> ¡Proceso Finalizado!</h5>
                    <hr>
                    <ul class="mb-0 ps-3">
                        <li><strong>Registros insertados:</strong> <?= intval($insertados) ?></li>
                        <li><strong>Omitidos (Duplicados):</strong> <?= intval($omitidos) ?></li>
                        <li><strong>Errores (ID no encontrado):</strong> <?= intval($errores) ?></li>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card card-custom">
                <div class="card-header header-bg p-4">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-excel me-2"></i> Importar Pagos por Zona</h4>
                    <small class="opacity-75">Sube tu archivo CSV o TSV ordenado en pares de Abono y Fecha</small>
                </div>

                <div class="card-body p-4">
                    
                    <form action="procesar_importar.php" method="POST" enctype="multipart/form-data">
                        
                        <!-- SELECTOR DE ZONA -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">
                                <i class="bi bi-geo-alt-fill text-danger me-1"></i> Selecciona la Zona:
                            </label>
                            <select name="zona" class="form-select form-select-lg" required>
                                <option value="">-- Seleccionar Zona --</option>
                                <?php foreach($zonas as $z): ?>
                                    <option value="<?= htmlspecialchars($z) ?>">Zona <?= htmlspecialchars(strtoupper($z)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- SUBIR ARCHIVO -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">
                                <i class="bi bi-upload text-primary me-1"></i> Archivo (.csv / .tsv):
                            </label>
                            <input type="file" name="archivo" class="form-control form-control-lg" accept=".csv, .tsv, .txt" required>
                            <div class="form-text text-muted">
                                Estructura requerida: <code>ID | Abono 1 | Fecha 1 | Abono 2 | Fecha 2...</code>
                            </div>
                        </div>

                        <!-- BOTONES -->
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-2">
                            <a href="index.php" class="btn btn-outline-secondary px-4 fw-bold">
                                <i class="bi bi-arrow-left"></i> Volver a Pagos
                            </a>
                            <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm">
                                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Iniciar Importación
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>