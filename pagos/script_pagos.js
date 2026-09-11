let timeout = null;
let filtroMesAnio = { 
    mes: typeof filtroMesInicial !== 'undefined' ? filtroMesInicial : '', 
    anio: typeof filtroAnioInicial !== 'undefined' ? filtroAnioInicial : '' 
};

const listaMeses = [
    "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", 
    "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
];

/* 🔥 TOGGLE SIDEBAR ZONAS */
function toggleZona(id) {
    let el = document.getElementById(id);
    if (el) {
        el.style.display = (el.style.display === "block") ? "none" : "block";
    }
}

/* 🔥 FILTRAR POR HISTORIAL DESDE EL SIDEBAR */
function filtrarPorHistorial(zona, mes, anio) {
    document.getElementById("filtro_zona").value = zona;
    filtroMesAnio.mes = mes;
    filtroMesAnio.anio = anio;
    
    document.getElementById("titulo_seccion").innerText = `Historial: Zona ${zona} - ${listaMeses[mes-1]} ${anio}`;
    cargarPagos(1);
}

function autoCargar() {
    clearTimeout(timeout);
    timeout = setTimeout(() => cargarPagos(1), 300);
}

function obtenerOpcionesMeses(mesSeleccionado = '') {
    let html = `<option value="">- Mes -</option>`;
    listaMeses.forEach(mes => {
        let selected = (mesSeleccionado.trim().toLowerCase() === mes.toLowerCase()) ? 'selected' : '';
        html += `<option value="${mes}" ${selected}>${mes}</option>`;
    });
    return html;
}

/* 🔥 CARGAR TABLA DE PAGOS GENERAL */
function cargarPagos(pagina = 1) {
    let buscar = document.getElementById("filtro_buscar").value;
    let zona = document.getElementById("filtro_zona").value;
    let manzana = document.getElementById("filtro_manzana").value;
    let lote = document.getElementById("filtro_lote").value;
    let estado = document.getElementById("filtro_estado").value;

    let url = `buscar_pagos.php?pagina=${pagina}&buscar=${encodeURIComponent(buscar)}&zona=${encodeURIComponent(zona)}&manzana=${encodeURIComponent(manzana)}&lote=${encodeURIComponent(lote)}&estado=${encodeURIComponent(estado)}&mes=${filtroMesAnio.mes}&anio=${filtroMesAnio.anio}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            let html = "";
            let sumaTotalGeneral = 0;

            if (!data.datos || data.datos.length === 0) {
                html = `<tr><td colspan="13" class="text-center text-muted py-4">No se encontraron pagos registrados.</td></tr>`;
            } else {
                data.datos.forEach(p => {
                    let nombreCompleto = `${p.apellido_paterno || ''} ${p.apellido_materno || ''} ${p.nombre || ''}`.trim();
                    let montoPago = parseFloat(p.monto || 0);
                    let interesPago = parseFloat(p.interes || 0);

                    // 🎯 NO SE SUMA EL INTERÉS AL TOTAL (Mantiene únicamente el Monto dado)
                    let totalFila = montoPago; 
                    sumaTotalGeneral += totalFila;

                    let mesPago = p.mes_vencimiento || p.mes_pago || '-';
                    
                    let mesInteresTexto = '-';
                    if (p.mes_interes && p.mes_interes.trim() !== '') {
                        let arregloMeses = p.mes_interes.split(',')
                            .map(m => m.trim())
                            .filter(m => m !== '');
                        
                        if (arregloMeses.length > 0) {
                            mesInteresTexto = arregloMeses.join(', ');
                        }
                    }

                    let estadoTxt = p.estado === "FACTURADO" 
                        ? `<span class="badge bg-success">FACTURADO</span>` 
                        : `<span class="badge bg-warning text-dark">CAPTURADO</span>`;

                    let acciones = `
                        <a href="editar.php?id=${p.id}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                    `;

                    if (typeof ROL !== 'undefined' && ROL === "admin") {
                        acciones += ` <button onclick="eliminarPago(${p.id})" class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>`;
                    }

                    html += `
                        <tr data-id="${p.id}">
                            <td><input type="checkbox" class="check" value="${p.id}"></td>
                            <td><b>#${p.id}</b></td>
                            <td><b>${nombreCompleto}</b></td>
                            <td>${p.zona || ''}</td>
                            <td>${p.manzana || ''}</td>
                            <td>${p.lote || ''}</td>
                            <td><span class="badge bg-secondary">${mesPago}</span></td>
                            <td class="text-success fw-bold">$${montoPago.toFixed(2)}</td>
                            <td class="text-danger fw-bold">$${interesPago.toFixed(2)}</td>
                            <td>
                                ${mesInteresTexto !== '-' 
                                    ? `<span class="badge-mes-interes">${mesInteresTexto}</span>` 
                                    : '<span class="text-muted">-</span>'}
                            </td>
                            <td class="text-dark fw-bold">$${totalFila.toFixed(2)}</td>
                            <td>${estadoTxt}</td>
                            <td class="text-center">${acciones}</td>
                        </tr>
                    `;
                });

                html += `
                    <tr class="table-light fw-bold">
                        <td colspan="10" class="text-end">Total Pagina:</td>
                        <td colspan="3" class="text-success">$${sumaTotalGeneral.toFixed(2)}</td>
                    </tr>
                `;
            }

            document.getElementById("tabla_pagos_body").innerHTML = html;

            // 🎯 RENDERIZADO DE PAGINACIÓN ACOTADO (EVITA DESBORDAMIENTO)
            renderPaginacion(data.paginas, pagina);
        })
        .catch(err => {
            console.error("Error al cargar pagos:", err);
        });
}

/* 🎯 FUNCIÓN ACOTADA DE PAGINACIÓN INTELIGENTE */
function renderPaginacion(totalPaginas, paginaActual) {
    let contenedor = document.getElementById("paginacion");
    if (!contenedor) return;

    totalPaginas = parseInt(totalPaginas) || 1;
    paginaActual = parseInt(paginaActual) || 1;

    if (totalPaginas <= 1) {
        contenedor.innerHTML = "";
        return;
    }

    let html = "";
    const rango = 2; // Rango de páginas adyacentes a mostrar

    // Botón Anterior
    html += `
        <li class="page-item ${paginaActual <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="cargarPagos(${paginaActual - 1}); return false;">&laquo; Anterior</a>
        </li>
    `;

    // Primera página si no se está al inicio
    if (paginaActual > (rango + 1)) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="cargarPagos(1); return false;">1</a></li>`;
        if (paginaActual > (rango + 2)) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    // Bloque central dinámico
    const inicio = Math.max(1, paginaActual - rango);
    const fin    = Math.min(totalPaginas, paginaActual + rango);

    for (let i = inicio; i <= fin; i++) {
        html += `
            <li class="page-item ${i === paginaActual ? 'active' : ''}">
                <a class="page-link" href="#" onclick="cargarPagos(${i}); return false;">${i}</a>
            </li>
        `;
    }

    // Última página si no se está al final
    if (paginaActual < (totalPaginas - rango)) {
        if (paginaActual < (totalPaginas - rango - 1)) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        html += `<li class="page-item"><a class="page-link" href="#" onclick="cargarPagos(${totalPaginas}); return false;">${totalPaginas}</a></li>`;
    }

    // Botón Siguiente
    html += `
        <li class="page-item ${paginaActual >= totalPaginas ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="cargarPagos(${paginaActual + 1}); return false;">Siguiente &raquo;</a>
        </li>
    `;

    contenedor.innerHTML = html;
}

/* 🔥 MARCAR TODOS LOS CHECKBOXES */
function toggleAll(cb) {
    document.querySelectorAll(".check").forEach(c => c.checked = cb.checked);
}

/* 🔥 ELIMINAR MÚLTIPLES REGISTROS */
function eliminarSeleccionados() {
    let ids = [];
    document.querySelectorAll(".check:checked").forEach(c => ids.push(c.value));

    if (ids.length === 0) {
        alert("Selecciona al menos un registro para eliminar.");
        return;
    }

    if (confirm(`¿Estás seguro de que deseas eliminar los ${ids.length} pagos seleccionados?`)) {
        fetch("eliminar_multiple.php", {
            method: "POST",
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: ids })
        })
        .then(res => res.text())
        .then(() => {
            alert("✅ Pagos eliminados.");
            cargarPagos(1);
        });
    }
}

/* MODAL INTERESES Y MONTOS */
function filtrarPropietarioInteres() {
    let z = document.getElementById("interes_zona").value.trim().toLowerCase();
    let m = document.getElementById("interes_manzana").value.trim().toLowerCase();
    let l = document.getElementById("interes_lote").value.trim().toLowerCase();

    let select = document.getElementById("interes_id_propietario");
    let options = select.options;
    let encontrado = false;

    for (let i = 1; i < options.length; i++) {
        let optZ = (options[i].getAttribute("data-zona") || "").toLowerCase();
        let optM = (options[i].getAttribute("data-manzana") || "").toLowerCase();
        let optL = (options[i].getAttribute("data-lote") || "").toLowerCase();

        let matchZ = (z === "" || optZ === z);
        let matchM = (m === "" || optM === m);
        let matchL = (l === "" || optL === l);

        if (matchZ && matchM && matchL) {
            options[i].hidden = false;
            if (!encontrado && (z !== "" || m !== "" || l !== "")) {
                select.selectedIndex = i;
                encontrado = true;
            }
        } else {
            options[i].hidden = true;
        }
    }

    if (!encontrado && (z !== "" || m !== "" || l !== "")) {
        select.value = "";
        cargarMesesPagados("");
    } else if (encontrado) {
        cargarMesesPagados(select.value);
    }
}

function crearSubFilaInteres(montoInteres = '', mesInteres = '') {
    let div = document.createElement("div");
    div.className = "d-flex gap-1 align-items-center interes-row-item";
    div.innerHTML = `
        <div class="input-group input-group-sm" style="width: 120px;">
            <span class="input-group-text">$</span>
            <input type="number" step="0.01" min="0" class="form-control input-interes-monto fw-bold text-danger" value="${montoInteres}" placeholder="0.00" oninput="calcularTotalesModal()">
        </div>
        <select class="form-select form-select-sm select-interes-mes" style="width: 130px;">
            ${obtenerOpcionesMeses(mesInteres)}
        </select>
        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="eliminarSubFilaInteres(this)">
            <i class="bi bi-x"></i>
        </button>
    `;
    return div;
}

function agregarSubFilaInteres(btn) {
    let container = btn.closest('td').querySelector('.container-intereses');
    container.appendChild(crearSubFilaInteres());
    calcularTotalesModal();
}

function eliminarSubFilaInteres(btn) {
    let container = btn.closest('.container-intereses');
    btn.closest('.interes-row-item').remove();
    if (container.children.length === 0) {
        container.appendChild(crearSubFilaInteres());
    }
    calcularTotalesModal();
}

function cargarMesesPagados(idPropietario) {
    let tbody = document.getElementById("body_meses_propietario");
    document.getElementById("txt_total_recargo").innerText = "$0.00";
    document.getElementById("txt_total_montos").innerText = "$0.00";

    if (!idPropietario) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3"><i>Selecciona un propietario para ver o agregar sus pagos.</i></td></tr>`;
        return;
    }

    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-primary py-3"><div class="spinner-border spinner-border-sm"></div> Cargando pagos...</td></tr>`;

    fetch(`obtener_pagos_propietario.php?id=${idPropietario}`)
        .then(res => res.json())
        .then(pagos => {
            tbody.innerHTML = "";

            if (Array.isArray(pagos) && pagos.length > 0) {
                pagos.forEach(pago => {
                    let fecha = pago.fecha_pago ? pago.fecha_pago.split(' ')[0] : 'Hoy';
                    let montoActual = parseFloat(pago.monto || 0);
                    let mesPagoActual = pago.mes_pago || '';

                    let arrIntereses = String(pago.interes || '').split(',');
                    let arrMeses = String(pago.mes_interes || '').split(',');

                    let tr = document.createElement("tr");
                    tr.setAttribute("data-id-pago", pago.id);
                    
                    let htmlIntereses = `<div class="container-intereses">`;
                    let maxItems = Math.max(arrIntereses.length, arrMeses.length);

                    for (let i = 0; i < maxItems; i++) {
                        let mInt = parseFloat(arrIntereses[i]) > 0 ? parseFloat(arrIntereses[i]) : (i === 0 && parseFloat(pago.interes) > 0 ? parseFloat(pago.interes) : '');
                        let mMes = arrMeses[i] ? arrMeses[i].trim() : '';
                        let tempDiv = crearSubFilaInteres(mInt, mMes);
                        htmlIntereses += tempDiv.outerHTML;
                    }
                    htmlIntereses += `</div>
                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 mt-1 text-danger fw-bold" onclick="agregarSubFilaInteres(this)">
                        <i class="bi bi-plus-circle-fill"></i> Agregar Interés
                    </button>`;

                    tr.innerHTML = `
                        <td><b>#${pago.id}</b></td>
                        <td><small class="text-muted">${fecha}</small></td>
                        <td>
                            <select class="form-select form-select-sm select-mes-pago">
                                ${obtenerOpcionesMeses(mesPagoActual)}
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm input-pago-monto fw-bold text-success" value="${montoActual.toFixed(2)}" oninput="calcularTotalesModal()">
                        </td>
                        <td>${htmlIntereses}</td>
                        <td class="text-center">---</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                agregarFilaNuevaPago();
            }

            calcularTotalesModal();
        })
        .catch(err => {
            console.error("Error:", err);
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-3">Error al obtener el historial.</td></tr>`;
        });
}

function agregarFilaNuevaPago() {
    let idProp = document.getElementById("interes_id_propietario").value;
    if (!idProp) {
        alert("Primero selecciona un propietario.");
        return;
    }

    let tbody = document.getElementById("body_meses_propietario");
    if (tbody.querySelector("td[colspan]")) {
        tbody.innerHTML = "";
    }

    let fechaHoy = new Date().toISOString().split('T')[0];
    let tr = document.createElement("tr");
    tr.setAttribute("data-id-pago", "0");
    tr.className = "table-warning";

    let tempInteres = crearSubFilaInteres();

    tr.innerHTML = `
        <td><span class="badge bg-warning text-dark">Nuevo</span></td>
        <td><small class="text-muted">${fechaHoy}</small></td>
        <td>
            <select class="form-select form-select-sm select-mes-pago">
                ${obtenerOpcionesMeses("")}
            </select>
        </td>
        <td>
            <input type="number" step="0.01" min="0" class="form-control form-control-sm input-pago-monto fw-bold text-success" value="" placeholder="0.00" oninput="calcularTotalesModal()">
        </td>
        <td>
            <div class="container-intereses">
                ${tempInteres.outerHTML}
            </div>
            <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 mt-1 text-danger fw-bold" onclick="agregarSubFilaInteres(this)">
                <i class="bi bi-plus-circle-fill"></i> Agregar Interés
            </button>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="eliminarFilaTemporal(this)">
                <i class="bi bi-x-lg"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
}

function eliminarFilaTemporal(btn) {
    btn.closest("tr").remove();
    calcularTotalesModal();
}

function calcularTotalesModal() {
    let inputsMontos = document.querySelectorAll(".input-pago-monto");
    let inputsInteres = document.querySelectorAll(".input-interes-monto");
    
    let totalMontos = 0;
    let totalIntereses = 0;

    inputsMontos.forEach(input => {
        let val = parseFloat(input.value);
        if (!isNaN(val) && val > 0) totalMontos += val;
    });

    inputsInteres.forEach(input => {
        let val = parseFloat(input.value);
        if (!isNaN(val) && val > 0) totalIntereses += val;
    });

    document.getElementById("txt_total_montos").innerText = '$' + totalMontos.toFixed(2);
    document.getElementById("txt_total_recargo").innerText = '$' + totalIntereses.toFixed(2);
}

function guardarInteresEnPagos(e) {
    e.preventDefault();

    let idPropietario = document.getElementById("interes_id_propietario").value;
    let filas = document.querySelectorAll("#body_meses_propietario tr");
    let actualizaciones = [];

    filas.forEach(fila => {
        let idPago = fila.getAttribute("data-id-pago");
        let inputMonto = fila.querySelector(".input-pago-monto");
        let selectMesPago = fila.querySelector(".select-mes-pago");

        let subFilasInteres = fila.querySelectorAll(".interes-row-item");
        let listaInteresesVal = [];
        let listaMesesVal = [];
        let sumaInteresFila = 0;

        subFilasInteres.forEach(sub => {
            let inInt = sub.querySelector(".input-interes-monto");
            let selMes = sub.querySelector(".select-interes-mes");

            let vInt = parseFloat(inInt.value) || 0;
            let vMes = selMes ? selMes.value.trim() : '';

            if (vInt > 0 || vMes !== '') {
                listaInteresesVal.push(vInt);
                listaMesesVal.push(vMes);
                sumaInteresFila += vInt;
            }
        });

        if (inputMonto) {
            let montoVal = parseFloat(inputMonto.value) || 0;

            if (montoVal > 0 || sumaInteresFila > 0) {
                actualizaciones.push({
                    id_pago: idPago,
                    monto: montoVal,
                    interes_sum: sumaInteresFila,
                    interes: listaInteresesVal.join(', '),
                    mes_vencimiento: selectMesPago ? selectMesPago.value : '',
                    mes_interes: listaMesesVal.join(', ')
                });
            }
        }
    });

    if (actualizaciones.length === 0) {
        alert("Ingresa al menos un monto de pago o interés válido.");
        return;
    }

    let formData = new FormData();
    formData.append("id_propietario", idPropietario);
    formData.append("actualizaciones", JSON.stringify(actualizaciones));

    fetch('actualizar_interes_pagos.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(res => {
        if (res.trim() === "OK") {
            alert("✅ Pagos e intereses guardados correctamente.");
            location.reload();
        } else {
            alert("❌ " + res);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Error al intentar guardar los datos.");
    });
}

function recalcularSaldosGlobales() {
    if (confirm("¿Deseas recalcular los saldos de todos los propietarios?")) {
        fetch('recalcular_saldos.php')
            .then(res => res.text())
            .then(() => {
                alert("✅ Saldos recalculados correctamente.");
                cargarPagos(1);
            });
    }
}

function eliminarPagosMes() {
    let mesAnio = prompt("Ingresa el periodo a eliminar (AAAA-MM):");
    if (mesAnio && confirm(`¿Eliminar todos los pagos del periodo ${mesAnio}?`)) {
        fetch(`eliminar_mes.php?periodo=${encodeURIComponent(mesAnio)}`)
            .then(res => res.text())
            .then(res => {
                alert(res);
                cargarPagos(1);
            });
    }
}

function eliminarPago(id) {
    if (confirm("¿Deseas eliminar este registro de pago?")) {
        fetch(`eliminar.php?id=${id}`)
            .then(res => res.text())
            .then(res => {
                if (res.trim() === "OK") {
                    cargarPagos(1);
                } else {
                    alert("Error: " + res);
                }
            });
    }
}

// Inicialización de la vista
document.addEventListener("DOMContentLoaded", () => {
    cargarPagos(1);
});