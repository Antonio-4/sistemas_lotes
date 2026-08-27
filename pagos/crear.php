<?php
include("../conexion.php");
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Pago</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f4f6f9; font-family:'Segoe UI', sans-serif; }
        .card { border:none; border-radius:15px; box-shadow:0 5px 20px rgba(0,0,0,0.1); }
        .nav:focus { border:2px solid #1d2671; box-shadow:0 0 5px rgba(29,38,113,0.5); }
        .label-bold { font-weight:600; color:#333; }
    </style>
</head>
<body>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4">
                <h3 class="mb-4" style="color:#1d2671;">➕ Registrar Nuevo Pago</h3>

                <form id="formulario" method="POST" action="guardar.php">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="label-bold">Zona</label>
                            <input type="text" id="zona" class="form-control nav" placeholder="Ej: 1" autocomplete="off" required>
                        </div>
                        <div class="col-md-4">
                            <label class="label-bold">Manzana</label>
                            <input type="number" id="manzana" class="form-control nav" placeholder="Ej: 5" required>
                        </div>
                        <div class="col-md-4">
                            <label class="label-bold">Lote</label>
                            <input type="number" id="lote" class="form-control nav" placeholder="Ej: 10" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="label-bold">Propietario</label>
                        <input type="text" id="nombre_propietario" class="form-control bg-light" readonly placeholder="Ingrese ubicación para buscar...">
                        <input type="hidden" name="id_propietario" id="id_propietario">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="label-bold">Monto Cuota ($)</label>
                            <input type="number" step="0.01" name="monto" class="form-control nav" required placeholder="0.00">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="label-bold text-primary">Mes de Cobertura (Opcional)</label>
                            <input type="month" name="mes_vencimiento" class="form-control nav">
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch p-3 border rounded shadow-sm bg-white">
                            <input class="form-check-input nav" type="checkbox" name="aplicar_interes" id="aplicar_interes" value="1" checked style="margin-left: -1.5em; cursor:pointer;">
                            <label class="form-check-label label-bold ms-2" for="aplicar_interes" style="cursor:pointer;">
                                ¿Aplicar recargo por mora? (2% mensual)
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="label-bold">Fecha de Pago</label>
                        <input type="date" name="fecha_pago" class="form-control nav" value="<?= date('Y-m-d'); ?>" required>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-secondary px-4">Cancelar</a>
                        <button type="submit" class="btn btn-success px-5 fw-bold">Guardar Pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let campos = [];

function buscarPropietario(){
    let zona = document.getElementById("zona").value.trim();
    let manzana = document.getElementById("manzana").value.trim();
    let lote = document.getElementById("lote").value.trim();

    if(zona && manzana && lote){
        fetch(`buscar_propietario.php?zona=${zona}&manzana=${manzana}&lote=${lote}`)
        .then(res => res.json())
        .then(data => {
            if(data.length > 0){
                let p = data[0];
                document.getElementById("id_propietario").value = p.id;
                document.getElementById("nombre_propietario").value = p.apellido_paterno + " " + p.apellido_materno + " " + p.nombre;
                document.getElementById("nombre_propietario").style.color = "black";
            } else {
                document.getElementById("nombre_propietario").value = "❌ No encontrado";
                document.getElementById("nombre_propietario").style.color = "red";
                document.getElementById("id_propietario").value = "";
            }
        });
    }
}

["zona","manzana","lote"].forEach(id => {
    document.getElementById(id).addEventListener("input", buscarPropietario);
});

document.addEventListener("DOMContentLoaded", () => {
    campos = Array.from(document.querySelectorAll(".nav"));
    campos.forEach((campo, index) => {
        campo.addEventListener("keydown", function(e){
            if(e.key === "ArrowRight") { e.preventDefault(); if(campos[index+1]) campos[index+1].focus(); }
            if(e.key === "ArrowLeft") { e.preventDefault(); if(campos[index-1]) campos[index-1].focus(); }
            if(e.key === "ArrowDown") { e.preventDefault(); let next = index + 3; if(campos[next]) campos[next].focus(); }
            if(e.key === "ArrowUp") { e.preventDefault(); let prev = index - 3; if(campos[prev]) campos[prev].focus(); }
            if(e.key === "Enter"){
                if(index !== campos.length - 1){ e.preventDefault(); campos[index+1].focus(); }
            }
        });
    });
    if(campos[0]) campos[0].focus();
});
</script>
</body>
</html>