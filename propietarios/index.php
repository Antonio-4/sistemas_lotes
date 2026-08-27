<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

$rol = $_SESSION['rol'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Propietarios PRO</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }

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

.sidebar a:hover { background: #c33764; }

.topbar {
    margin-left: 250px;
    height: 60px;
    background: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.content { margin-left: 250px; padding: 20px; }

.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.seleccionado {
    background: #c33764 !important;
    color: white !important;
}

.seleccionado td {
    color: white !important;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h3>🏡 Sistema</h3>
    <a href="../dashboard.php">📊 Dashboard</a>
    <a href="#" class="fw-bold bg-white text-dark">👤 Propietarios</a>
    <a href="../pagos/">💳 Pagos</a>
    <a href="../logout.php">🚪 Salir</a>
</div>

<!-- TOPBAR -->
<div class="topbar">
    <h5>📋 Gestión de Propietarios</h5>
    <span>👤 <?= htmlspecialchars($_SESSION['user']) ?> (<?= htmlspecialchars($rol) ?>) | 📅 <?= date("d/m/Y") ?></span>
</div>

<!-- CONTENIDO -->
<div class="content">

    <div class="card p-4">

        <!-- BOTONES DE ACCIÓN -->
        <div class="d-flex justify-content-between mb-3">
            <div>
                <a href="crear.php" class="btn btn-success me-2">➕ Nuevo Propietario (N)</a>
                <a href="../dashboard.php" class="btn btn-secondary">⬅ Dashboard (D)</a>
            </div>
            <div>
                <button class="btn btn-outline-dark btn-sm" onclick="cargar()">🔄 Recargar</button>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <input id="buscar" class="form-control nav" placeholder="🔍 Buscar por nombre..." autofocus>
            </div>
            <div class="col-md-3">
                <input id="zona" class="form-control nav" placeholder="📍 Zona">
            </div>
            <div class="col-md-3">
                <input id="manzana" class="form-control nav" placeholder="🧱 Manzana">
            </div>
            <div class="col-md-3">
                <input id="lote" class="form-control nav" placeholder="📐 Lote">
            </div>
        </div>

        <!-- TABLA DINÁMICA -->
        <div id="tabla" class="table-responsive">
            <div class="text-center py-4 text-muted">Cargando propietarios...</div>
        </div>

    </div>

</div>

<script>
let filaSeleccionada = 1;
let timeout = null;

const buscarInput = document.getElementById('buscar');
const zonaInput = document.getElementById('zona');
const manzanaInput = document.getElementById('manzana');
const loteInput = document.getElementById('lote');
const tablaDiv = document.getElementById('tabla');

/* CARGAR DATOS VÍA AJAX */
function cargar() {
    let url = `buscar.php?buscar=${encodeURIComponent(buscarInput.value)}&zona=${encodeURIComponent(zonaInput.value)}&manzana=${encodeURIComponent(manzanaInput.value)}&lote=${encodeURIComponent(loteInput.value)}`;

    fetch(url)
    .then(res => res.text())
    .then(data => {
        tablaDiv.innerHTML = data;
        filaSeleccionada = 1;
        seleccionarFila(filaSeleccionada);
    })
    .catch(err => {
        tablaDiv.innerHTML = `<div class="alert alert-danger">Error al cargar registros: ${err}</div>`;
    });
}

/* BUSCADOR AUTOMÁTICO CON DEBOUNCE */
function auto() {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        cargar();
    }, 300);
}

/* LIMPIAR FILTROS CON TECLA ESCAPE */
document.addEventListener("keydown", e => {
    if (e.key === "Escape") {
        document.querySelectorAll(".nav").forEach(i => i.value = "");
        cargar();
    }
    if (e.key.toLowerCase() === "n" && document.activeElement.tagName !== 'INPUT') {
        e.preventDefault();
        window.location.href = "crear.php";
    }
    if (e.key.toLowerCase() === "d" && document.activeElement.tagName !== 'INPUT') {
        e.preventDefault();
        window.location.href = "../dashboard.php";
    }
});

/* NAVEGACIÓN ENTRE INPUTS CON FLECHAS */
let campos = document.querySelectorAll(".nav");
campos.forEach((c, i) => {
    c.addEventListener("keydown", e => {
        if (e.key === "ArrowRight" && campos[i + 1]) campos[i + 1].focus();
        if (e.key === "ArrowLeft" && campos[i - 1]) campos[i - 1].focus();
    });
});

/* SELECCIÓN VISUAL DE FILA */
function seleccionarFila(index) {
    let filas = document.querySelectorAll("#tabla table tr");
    if (filas.length <= 1) return;

    filas.forEach(f => f.classList.remove("seleccionado"));

    if (filas[index]) {
        filas[index].classList.add("seleccionado");
        filaSeleccionada = index;
    }
}

/* NAVEGACIÓN Y ATAJOS EN TABLA */
document.addEventListener("keydown", function(e) {
    if (document.activeElement.tagName === 'INPUT') return;

    let filas = document.querySelectorAll("#tabla table tr");
    if (filas.length <= 1) return;

    if (e.key === "ArrowDown") {
        e.preventDefault();
        if (filaSeleccionada < filas.length - 1) {
            filaSeleccionada++;
            seleccionarFila(filaSeleccionada);
        }
    }

    if (e.key === "ArrowUp") {
        e.preventDefault();
        if (filaSeleccionada > 1) {
            filaSeleccionada--;
            seleccionarFila(filaSeleccionada);
        }
    }

    if (e.key === "Enter") {
        e.preventDefault();
        let fila = filas[filaSeleccionada];
        if (fila) {
            let link = fila.querySelector(".btn-warning, .btn-primary");
            if (link) window.location = link.href;
        }
    }

    if (e.key === "Delete") {
        e.preventDefault();
        let fila = filas[filaSeleccionada];
        if (fila) {
            let link = fila.querySelector(".btn-danger");
            if (link && confirm("¿Deseas eliminar este propietario?")) {
                window.location = link.href;
            }
        }
    }
});

/* ASIGNACIÓN DE EVENTOS DE BÚSQUEDA */
buscarInput.onkeyup = auto;
zonaInput.onkeyup = auto;
manzanaInput.onkeyup = auto;
loteInput.onkeyup = auto;

/* CARGA INICIAL */
cargar();
</script>

</body>
</html>