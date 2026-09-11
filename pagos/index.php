<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

include("../conexion.php");

$rol_usuario = $_SESSION['rol'] ?? 'usuario';

/* 🔥 HISTORIAL ZONAS PARA EL SIDEBAR */
$historial = [];

$sql_historial = "
SELECT 
    p.zona,
    YEAR(pa.fecha_pago) AS anio,
    MONTH(pa.fecha_pago) AS mes
FROM pagos pa
JOIN propietarios p ON p.id = pa.id_propietario
GROUP BY p.zona, anio, mes
ORDER BY p.zona ASC, anio DESC, mes DESC
";

$res_historial = $conn->query($sql_historial);

if ($res_historial) {
    while ($row = $res_historial->fetch_assoc()) {
        $historial[$row['zona']][$row['anio']][] = $row['mes'];
    }
}

// Parámetros opcionales del historial
$zona_filtro = $_GET['zona'] ?? '';
$mes_filtro  = $_GET['mes'] ?? '';
$anio_filtro = $_GET['anio'] ?? '';

$meses_nombres = [
    1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril",
    5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto",
    9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
];

// Obtener lista completa de propietarios para el selector del modal
$sql_props = "SELECT id, nombre, apellido_paterno, apellido_materno, zona, manzana, lote FROM propietarios ORDER BY apellido_paterno ASC, nombre ASC";
$res_props = $conn->query($sql_props);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión e Historial de Pagos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        
        /* 🔥 SIDEBAR CON SCROLL */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: #1d2671;
            position: fixed;
            color: white;
            padding: 20px;
            overflow-y: auto;
            z-index: 1000;
        }
        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-thumb { background: #c33764; border-radius: 10px; }

        .sidebar a {
            display: block;
            color: white;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 4px;
        }
        .sidebar a:hover { background: #c33764; }

        .submenu { margin-left: 10px; display: none; }
        .zona-title { cursor: pointer; font-weight: bold; margin-top: 10px; padding: 6px; border-radius: 4px; background: rgba(255,255,255,0.05); }
        .zona-title:hover { background: rgba(255,255,255,0.15); }

        .topbar { margin-left: 260px; height: 60px; background: white; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .content { margin-left: 260px; padding: 20px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .table-responsive { max-height: 600px; overflow-y: auto; }
        .modal-header-custom { background-color: #dc3545; color: #ffffff; }
        .interes-row-item { background: #fff5f5; padding: 4px; border-radius: 6px; border: 1px solid #fecaca; margin-bottom: 4px; }
        .badge-mes-interes {
            background-color: #fff3cd;
            color: #664d03;
            border: 1px solid #ffe69c;
            font-size: 0.85rem;
            padding: 0.25em 0.5em;
            border-radius: 0.375rem;
        }
    </style>
</head>
<body>

<!-- 🔥 SIDEBAR HISTORIAL ZONAS -->
<div class="sidebar">
    <h4>📅 Historial</h4>
    
    <a href="../dashboard.php">📊 Dashboard</a>
    <a href="../propietarios/index.php">👤 Propietarios</a>
    <a href="index.php" class="fw-bold bg-white text-dark mb-3">💳 Todos los Pagos</a>

    <hr>
    <h6 class="text-uppercase text-light opacity-75 small">Navegación por Zona</h6>

    <?php if (!empty($historial)): ?>
        <?php foreach($historial as $z => $anios): ?>
            <div>
                <div class="zona-title" onclick="toggleZona('z<?= htmlspecialchars($z) ?>')">
                    🏡 Zona <?= htmlspecialchars($z) ?> ▾
                </div>

                <div id="z<?= htmlspecialchars($z) ?>" class="submenu">
                    <?php foreach($anios as $a => $ms): ?>
                        <div class="mt-2 text-warning fw-bold"><small>🗓️ <?= $a ?></small></div>
                        <?php foreach($ms as $m): ?>
                            <a href="#" onclick="filtrarPorHistorial('<?= htmlspecialchars($z) ?>', '<?= $m ?>', '<?= $a ?>'); return false;">
                                👉 <?= $meses_nombres[$m] ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="small text-white-50">Sin registros de historial.</p>
    <?php endif; ?>

    <hr>
    <a href="../logout.php">🚪 Salir</a>
</div>

<div class="topbar">
    <h5>💳 Control e Historial de Pagos</h5>
    <span>👤 <?= htmlspecialchars($_SESSION['user'] ?? 'Usuario') ?> (<?= htmlspecialchars($rol_usuario) ?>) | 📅 <?= date("d/m/Y") ?></span>
</div>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 id="titulo_seccion">Historial de Pagos</h2>
        <div class="d-flex gap-2 flex-wrap">
            <a href="crear.php" class="btn btn-success fw-bold shadow-sm">
                <i class="bi bi-plus-circle"></i> Nuevo Pago
            </a>

            <button type="button" class="btn btn-danger fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalInteresManual">
                <i class="bi bi-percent"></i> Agregar Interés / Nota
            </button>

            <button type="button" class="btn btn-warning fw-bold shadow-sm" onclick="recalcularSaldosGlobales()">
                <i class="bi bi-calculator"></i> Recalcular Saldos
            </button>
            
            <a href="importar.php" class="btn btn-info text-white fw-bold shadow-sm">
                <i class="bi bi-file-earmark-excel"></i> Importar Pagos
            </a>
            
            <?php if ($rol_usuario === 'admin'): ?>
                <button type="button" class="btn btn-outline-danger fw-bold shadow-sm" onclick="eliminarPagosMes()">
                    <i class="bi bi-calendar-x"></i> Eliminar Mes
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="card p-4 mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-funnel"></i> Filtros de Búsqueda</h5>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Propietario / Nombre</label>
                <input type="text" id="filtro_buscar" class="form-control" placeholder="Buscar por nombre..." onkeyup="autoCargar()">
            </div>
            <div class="col-md-2">
                <label class="form-label">Zona</label>
                <input type="text" id="filtro_zona" class="form-control" value="<?= htmlspecialchars($zona_filtro) ?>" placeholder="Zona" onkeyup="autoCargar()">
            </div>
            <div class="col-md-2">
                <label class="form-label">Manzana</label>
                <input type="text" id="filtro_manzana" class="form-control" placeholder="Mz" onkeyup="autoCargar()">
            </div>
            <div class="col-md-2">
                <label class="form-label">Lote</label>
                <input type="text" id="filtro_lote" class="form-control" placeholder="Lt" onkeyup="autoCargar()">
            </div>
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select id="filtro_estado" class="form-select" onchange="cargarPagos(1)">
                    <option value="">Todos los Estados</option>
                    <option value="CAPTURADO">CAPTURADO</option>
                    <option value="FACTURADO">FACTURADO</option>
                </select>
            </div>
        </div>
    </div>

    <!-- TABLA DE PAGOS GENERAL -->
    <div class="card p-4">
        <?php if ($rol_usuario === 'admin'): ?>
            <div class="mb-3">
                <button onclick="eliminarSeleccionados()" class="btn btn-danger btn-sm fw-bold">
                    <i class="bi bi-trash"></i> Eliminar seleccionados
                </button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" onclick="toggleAll(this)"></th>
                        <th>ID</th>
                        <th>Propietario</th>
                        <th>Zona</th>
                        <th>Manzana</th>
                        <th>Lote</th>
                        <th>Mes Pago</th>
                        <th>Monto</th>
                        <th>Interés Nota</th>
                        <th>Mes Interés</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla_pagos_body">
                    <!-- Datos cargados dinámicamente vía JS -->
                </tbody>
            </table>
        </div>

        <nav class="mt-3">
            <ul class="pagination justify-content-center" id="paginacion"></ul>
        </nav>
    </div>

</div>

<!-- MODAL ASIGNAR INTERÉS Y AGREGAR NUEVOS MONTOS -->
<div class="modal fade" id="modalInteresManual" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content border-0 shadow-lg">
      
      <div class="modal-header modal-header-custom py-3">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-percent me-1"></i> Asignar Interés y Gestionar Pagos
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formInteresManual" onsubmit="guardarInteresEnPagos(event)">
        <div class="modal-body p-4">
            
            <!-- BUSCADOR UBICACIÓN -->
            <label class="form-label fw-bold text-primary mb-2">
              <i class="bi bi-geo-alt-fill"></i> Buscar Propietario por Ubicación
            </label>
            <div class="row g-2 mb-3 bg-light p-3 rounded border">
                <div class="col-4">
                    <input type="text" id="interes_zona" class="form-control" placeholder="Zona (Ej: Z1)" oninput="filtrarPropietarioInteres()">
                </div>
                <div class="col-4">
                    <input type="text" id="interes_manzana" class="form-control" placeholder="Manzana (Ej: 1)" oninput="filtrarPropietarioInteres()">
                </div>
                <div class="col-4">
                    <input type="text" id="interes_lote" class="form-control" placeholder="Lote (Ej: 1)" oninput="filtrarPropietarioInteres()">
                </div>
            </div>

            <!-- SELECTOR PROPIETARIO -->
            <div class="mb-4">
                <label class="form-label fw-bold">Propietario Seleccionado</label>
                <select id="interes_id_propietario" class="form-select form-select-lg border-primary" onchange="cargarMesesPagados(this.value)" required>
                    <option value="">-- Seleccionar Propietario --</option>
                    <?php if ($res_props && $res_props->num_rows > 0): ?>
                        <?php while($p = $res_props->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>" 
                                    data-zona="<?= trim((string)$p['zona']) ?>" 
                                    data-manzana="<?= trim((string)$p['manzana']) ?>" 
                                    data-lote="<?= trim((string)$p['lote']) ?>">
                                <?= htmlspecialchars($p['apellido_paterno'] . ' ' . $p['apellido_materno'] . ' ' . $p['nombre']) ?> 
                                (Z:<?= htmlspecialchars($p['zona']) ?> / Mz:<?= htmlspecialchars($p['manzana']) ?> / Lt:<?= htmlspecialchars($p['lote']) ?>)
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- HISTORIAL Y AGREGADO DE MONTOS -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-dark m-0">
                        <i class="bi bi-clock-history"></i> Historial y Nuevos Pagos
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-success fw-bold" onclick="agregarFilaNuevaPago()">
                        <i class="bi bi-plus-lg"></i> Agregar Otro Mes de Pago
                    </button>
                </div>

                <div class="table-responsive border rounded" style="max-height: 380px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 70px;">ID</th>
                                <th style="width: 100px;">Fecha</th>
                                <th style="width: 150px;">Mes Pago</th>
                                <th style="width: 130px;">Monto Pago</th>
                                <th>Intereses y Meses Correspondientes</th>
                                <th style="width: 50px;" class="text-center">❌</th>
                            </tr>
                        </thead>
                        <tbody id="body_meses_propietario">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    <i>Selecciona un propietario para ver o agregar sus pagos.</i>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TOTALES -->
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="p-3 bg-success bg-opacity-10 rounded border border-success d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark m-0">Suma Total Pagos:</h6>
                        <h4 class="fw-bold text-success m-0" id="txt_total_montos">$0.00</h4>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-info bg-opacity-10 rounded border border-info d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark m-0">Suma Total Intereses Nota:</h6>
                        <h4 class="fw-bold text-danger m-0" id="txt_total_recargo">$0.00</h4>
                    </div>
                </div>
            </div>

        </div>

        <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger px-4 fw-bold">Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Librería JS de Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Variables globales enviadas desde PHP para el JS -->
<script>
    const ROL = "<?= $rol_usuario ?>";
    const MES_FILTRO = '<?= $mes_filtro ?>';
    const ANIO_FILTRO = '<?= $anio_filtro ?>';
</script>

<!-- 🔥 Inclusión de tu archivo JavaScript separado -->
<script src="script_pagos.js"></script>
</body>
</html>