<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: ../index.php");
    exit();
}

include("../conexion.php");

$rol = $_SESSION['rol'] ?? 'usuario';

/* 🔥 HISTORIAL ZONAS */
$historial = [];

$sql = "
SELECT 
p.zona,
YEAR(pa.fecha_pago) anio,
MONTH(pa.fecha_pago) mes
FROM pagos pa
JOIN propietarios p ON p.id = pa.id_propietario
GROUP BY p.zona, anio, mes
ORDER BY p.zona, anio, mes
";

$res = $conn->query($sql);

while($row = $res->fetch_assoc()){
    $historial[$row['zona']][$row['anio']][] = $row['mes'];
}

$zona = $_GET['zona'] ?? '';
$mes  = $_GET['mes'] ?? date("n");
$anio = $_GET['anio'] ?? date("Y");

$meses = [
1=>"Enero",2=>"Febrero",3=>"Marzo",4=>"Abril",
5=>"Mayo",6=>"Junio",7=>"Julio",8=>"Agosto",
9=>"Septiembre",10=>"Octubre",11=>"Noviembre",12=>"Diciembre"
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Historial PRO</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{background:#f4f6f9;font-family:'Segoe UI';}

/* 🔥 SIDEBAR CON SCROLL */
.sidebar{
    width:260px;
    height:100vh;
    background:#1d2671;
    position:fixed;
    color:white;
    padding:20px;
    overflow-y:auto;
}

.sidebar::-webkit-scrollbar{width:8px;}
.sidebar::-webkit-scrollbar-thumb{background:#c33764;border-radius:10px;}

.sidebar a{
    display:block;
    color:white;
    padding:8px;
    text-decoration:none;
    border-radius:6px;
}
.sidebar a:hover{background:#c33764;}

.submenu{margin-left:10px;display:none;}

.content{margin-left:260px;padding:20px;}

.zona-title{
    cursor:pointer;
    font-weight:bold;
    margin-top:10px;
}
</style>
</head>

<body>

<!-- 🔥 SIDEBAR -->
<div class="sidebar">

<h4>📅 Historial</h4>

<?php foreach($historial as $z => $anios): ?>

<div>
<div class="zona-title" onclick="toggleZona('z<?= $z ?>')">
🏡 Zona <?= $z ?> ▾
</div>

<div id="z<?= $z ?>" class="submenu">

<?php foreach($anios as $a => $ms): ?>

<div><small><?= $a ?></small></div>

<?php foreach($ms as $m): ?>
<a href="?zona=<?= $z ?>&mes=<?= $m ?>&anio=<?= $a ?>">
👉 <?= $meses[$m] ?>
</a>
<?php endforeach; ?>

<?php endforeach; ?>

</div>
</div>

<?php endforeach; ?>

<hr>

<a href="index.php">💳 Pagos</a>
<a href="../logout.php">🚪 Salir</a>

</div>

<!-- 🔥 CONTENIDO -->
<div class="content">

<div class="card p-4">

<h4>Zona <?= $zona ?> - <?= $meses[$mes] ?> / <?= $anio ?></h4>
<p>👤 <?= $_SESSION['user'] ?> (<?= $rol ?>)</p>

<!-- 🔍 FILTROS -->
<div class="row mb-3">

<div class="col-md-3">
<input id="buscar" class="form-control nav" placeholder="Buscar nombre...">
</div>

<div class="col-md-2">
<input id="manzana" class="form-control nav" placeholder="Manzana">
</div>

<div class="col-md-2">
<input id="lote" class="form-control nav" placeholder="Lote">
</div>

<div class="col-md-2">
<select id="estado" class="form-control nav">
<option value="">Estado</option>
<option value="CAPTURADO">CAPTURADO</option>
<option value="FACTURADO">FACTURADO</option>
</select>
</div>

</div>

<div id="loader">⏳ Cargando...</div>

<?php if($rol=="admin"): ?>
<button onclick="eliminarSeleccionados()" class="btn btn-danger mb-2">
🗑️ Eliminar seleccionados
</button>
<?php endif; ?>

<div id="contenido"></div>

</div>
</div>

<script>

let timeout = null;
let ROL = "<?= $rol ?>";

/* 🔥 SIDEBAR TOGGLE */
function toggleZona(id){
let el = document.getElementById(id);
el.style.display = (el.style.display==="block")?"none":"block";
}

/* 🔥 CARGAR DATOS */
function cargar(){

loader.style.display="block";

fetch(`buscar_historial.php?zona=<?= $zona ?>&mes=<?= $mes ?>&anio=<?= $anio ?>&buscar=${buscar.value}&manzana=${manzana.value}&lote=${lote.value}&estado=${estado.value}`)
.then(r=>r.json())
.then(data=>{

let html="";
let total=0;

html += `<table class="table table-bordered">`;

html += `
<tr>
<th><input type="checkbox" onclick="toggleAll(this)"></th>
<th>ID</th>
<th>Propietario</th>
<th>Manzana</th>
<th>Lote</th>
<th>Monto</th>
<th>Total</th>
<th>Fecha</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
`;

data.forEach(r=>{

total += parseFloat(r.total);

let estadoTxt = r.estado=="CAPTURADO"
? "<span class='badge bg-warning'>CAPTURADO</span>"
: "<span class='badge bg-success'>FACTURADO</span>";

let estadoBtn = r.estado=="CAPTURADO"
? `<button onclick="cambiar(${r.id},'FACTURADO')" class="btn btn-success btn-sm">✔</button>`
: `<button onclick="cambiar(${r.id},'CAPTURADO')" class="btn btn-warning btn-sm">↺</button>`;

let eliminarBtn = "";
if(ROL=="admin"){
eliminarBtn = `<button onclick="eliminar(${r.id})" class="btn btn-danger btn-sm">🗑️</button>`;
}

html += `
<tr>
<td><input type="checkbox" class="check" value="${r.id}"></td>
<td>${r.id}</td>
<td>${r.apellido_paterno} ${r.apellido_materno} ${r.nombre}</td>
<td>${r.manzana}</td>
<td>${r.lote}</td>
<td>$${parseFloat(r.monto).toFixed(2)}</td>
<td>$${parseFloat(r.total).toFixed(2)}</td>
<td>${r.fecha_pago}</td>
<td>${estadoTxt}</td>
<td>
${estadoBtn}
<a href="editar.php?id=${r.id}" class="btn btn-primary btn-sm">✏️</a>
${eliminarBtn}
</td>
</tr>
`;
});

html += `
<tr>
<td colspan="6"><b>Total</b></td>
<td colspan="4"><b>$${total.toFixed(2)}</b></td>
</tr>
</table>
`;

contenido.innerHTML = html;
loader.style.display="none";

});
}

/* 🔥 SELECCIONAR TODO */
function toggleAll(cb){
document.querySelectorAll(".check").forEach(c=>c.checked = cb.checked);
}

/* 🔥 ELIMINAR UNO */
function eliminar(id){
if(confirm("¿Eliminar pago?")){
fetch("eliminar.php?id="+id).then(()=>cargar());
}
}

/* 🔥 ELIMINAR MÚLTIPLES */
function eliminarSeleccionados(){

let ids = [];

document.querySelectorAll(".check:checked").forEach(c=>{
ids.push(c.value);
});

if(ids.length===0){
alert("Selecciona al menos uno");
return;
}

if(confirm("¿Eliminar seleccionados?")){
fetch("eliminar_multiple.php",{
method:"POST",
body: JSON.stringify({ids:ids})
})
.then(()=>cargar());
}
}

/* 🔍 BUSCADOR */
function auto(){
clearTimeout(timeout);
timeout = setTimeout(()=>cargar(),300);
}

/* EVENTOS */
buscar.onkeyup = auto;
manzana.onkeyup = auto;
lote.onkeyup = auto;
estado.onchange = cargar;

cargar();

</script>

</body>
</html>