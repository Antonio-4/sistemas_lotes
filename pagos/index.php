<?php
session_start();

// Validar sesión
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

include("../conexion.php");

$rol_usuario = $_SESSION['rol'] ?? 'usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pagos - Control de Pagos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            background: #1d2671;
            position: fixed;
            color: white;
            padding: 20px;
        }
        .sidebar a {
            display: block;
            color: white;
            padding: 12px;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 5px;
        }
        .sidebar a:hover {
            background: #c33764;
        }
        .topbar {
            margin-left: 250px;
            height: 60px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>🏡 Sistema</h3>
    <a href="../dashboard.php">📊 Dashboard</a>
    <a href="../propietarios/index.php">👤 Propietarios</a>
    <a href="index.php" class="fw-bold bg-white text-dark">💳 Pagos</a>
    <a href="../logout.php">🚪 Salir</a>
</div>

<div class="topbar">
    <h5>💳 Control y Historial de Pagos</h5>
    <span>👤 <?= htmlspecialchars($_SESSION['user'] ?? 'Usuario') ?> | 📅 <?= date("d/m/Y") ?></span>
</div>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Historial de Pagos</h2>
        <a href="crear.php" class="btn btn-success fw-bold px-4 shadow-sm">
            ➕ Registrar Nuevo Pago
        </a>
    </div>

    <!-- PANEL DE FILTROS -->
    <div class="card p-4 mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-funnel"></i> Filtros de Búsqueda</h5>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Propietario / Nombre</label>
                <input type="text" id="filtro_buscar" class="form-control" placeholder="Buscar por nombre..." onkeyup="cargarPagos(1)">
            </div>
            <div class="col-md-2">
                <label class="form-label">Zona</label>
                <select id="filtro_zona" class="form-select" onchange="cargarPagos(1)">
                    <option value="">Todas</option>
                    <option value="1">Zona 1</option>
                    <option value="2">Zona 2</option>
                    <option value="3">Zona 3</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Manzana</label>
                <input type="text" id="filtro_manzana" class="form-control" placeholder="Mz" onkeyup="cargarPagos(1)">
            </div>
            <div class="col-md-2">
                <label class="form-label">Lote</label>
                <input type="text" id="filtro_lote" class="form-control" placeholder="Lt" onkeyup="cargarPagos(1)">
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

    <!-- TABLA DE RESULTADOS -->
    <div class="card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Folio</th>
                        <th>Propietario</th>
                        <th>Ubicación</th>
                        <th>Monto</th>
                        <th>Interés</th>
                        <th>Total</th>
                        <th>Fecha Pago</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla_pagos_body">
                    <!-- Se carga mediante AJAX desde buscar_pagos.php -->
                </tbody>
            </table>
        </div>

        <!-- PAGINACIÓN -->
        <nav class="mt-3">
            <ul class="pagination justify-content-center" id="paginacion">
                <!-- Links de páginas generados por JS -->
            </ul>
        </nav>
    </div>

</div>

<script>
let rolUsuario = "<?= $rol_usuario ?>";

function cargarPagos(pagina = 1) {
    let buscar = document.getElementById("filtro_buscar").value;
    let zona = document.getElementById("filtro_zona").value;
    let manzana = document.getElementById("filtro_manzana").value;
    let lote = document.getElementById("filtro_lote").value;
    let estado = document.getElementById("filtro_estado").value;

    let url = `buscar_pagos.php?pagina=${pagina}&buscar=${encodeURIComponent(buscar)}&zona=${encodeURIComponent(zona)}&manzana=${encodeURIComponent(manzana)}&lote=${encodeURIComponent(lote)}&estado=${encodeURIComponent(estado)}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            let html = "";
            if (!data.datos || data.datos.length === 0) {
                html = `<tr><td colspan="9" class="text-center text-muted py-4">No se encontraron pagos registrados con los filtros seleccionados.</td></tr>`;
            } else {
                data.datos.forEach(p => {
                    let estadoBadge = p.estado === "FACTURADO" 
                        ? `<span class="badge bg-success">FACTURADO</span>` 
                        : `<span class="badge bg-warning text-dark">CAPTURADO</span>`;

                    let acciones = `
                        <a href="editar.php?id=${p.id}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i> Editar</a>
                        <a href="cambiar_estado.php?id=${p.id}&estado=${p.estado === 'CAPTURADO' ? 'FACTURADO' : 'CAPTURADO'}" class="btn btn-sm btn-outline-secondary" title="Cambiar Estado">Estado</a>
                    `;

                    if (rolUsuario === "admin") {
                        acciones += ` <button onclick="eliminarPago(${p.id})" class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>`;
                    }

                    html += `
                        <tr>
                            <td><b>#${p.id}</b></td>
                            <td><b>${p.apellido_paterno} ${p.apellido_materno} ${p.nombre}</b></td>
                            <td>Z:${p.zona} / Mz:${p.manzana} / Lt:${p.lote}</td>
                            <td>$${parseFloat(p.monto).toFixed(2)}</td>
                            <td class="text-danger">$${parseFloat(p.interes || 0).toFixed(2)}</td>
                            <td class="fw-bold text-success">$${parseFloat(p.total).toFixed(2)}</td>
                            <td>${p.fecha_pago}</td>
                            <td>${estadoBadge}</td>
                            <td class="text-center">${acciones}</td>
                        </tr>
                    `;
                });
            }

            document.getElementById("tabla_pagos_body").innerHTML = html;

            // Renderizar paginación
            let pagHtml = "";
            for (let i = 1; i <= data.paginas; i++) {
                pagHtml += `<li class="page-item ${i === pagina ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="cargarPagos(${i}); return false;">${i}</a>
                </li>`;
            }
            document.getElementById("paginacion").innerHTML = pagHtml;
        })
        .catch(err => {
            console.error(err);
            document.getElementById("tabla_pagos_body").innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">Error al cargar la información.</td></tr>`;
        });
}

function eliminarPago(id) {
    if (confirm("¿Estás seguro de eliminar este pago? Esta acción recalculará el saldo del propietario.")) {
        fetch(`eliminar.php?id=${id}`)
            .then(res => res.text())
            .then(res => {
                if (res.trim() === "OK") {
                    cargarPagos(1);
                } else {
                    alert("Error al eliminar el pago: " + res);
                }
            });
    }
}

// Cargar primera página al iniciar
document.addEventListener("DOMContentLoaded", () => {
    cargarPagos(1);
});
</script>

</body>
</html>