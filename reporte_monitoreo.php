<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    exit("Acceso denegado. Por favor, inicie sesión.");
}

require_once 'conexion.php';
$db = new Conexion();
$conn = $db->getConexion();
$rol = $_SESSION['rol'];

$rol_clean = mb_strtolower(trim($rol), 'UTF-8');
if (!in_array($rol_clean, ['coordinación', 'coordinacion'])) {
    exit("Acceso denegado. Este informe está reservado a Coordinación.");
}

$areas = $conn->query("SELECT * FROM areas ORDER BY nombre_area ASC")->fetchAll();
$id_area_filtro = isset($_GET['area']) ? trim($_GET['area']) : 'todas';
$area_seleccionada = null;

if ($id_area_filtro !== 'todas' && $id_area_filtro !== '') {
    foreach ($areas as $a) {
        if ((string)$a['id_area'] === (string)$id_area_filtro) { $area_seleccionada = $a; break; }
    }
    if (!$area_seleccionada) { $id_area_filtro = 'todas'; }
}

// --- Resumen estadístico (general o filtrado por área) ---
$where_area = ($id_area_filtro !== 'todas') ? "WHERE id_area_asignada = :id_area" : "";
$sql_totales = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado IN ('Pendiente', 'Rechazado') THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN estado = 'Concluido' THEN 1 ELSE 0 END) as concluidos,
    SUM(CASE WHEN estado = 'Resuelto_Para_Validar' THEN 1 ELSE 0 END) as revision,
    SUM(CASE WHEN prioridad = 'Extraurgente' AND estado != 'Concluido' THEN 1 ELSE 0 END) as extraurgentes,
    SUM(CASE WHEN prioridad = 'A la brevedad posible' AND estado != 'Concluido' THEN 1 ELSE 0 END) as brevedad
FROM documentos $where_area";
$stmt_tot = $conn->prepare($sql_totales);
if ($id_area_filtro !== 'todas') { $stmt_tot->execute([':id_area' => $area_seleccionada['id_area']]); }
else { $stmt_tot->execute(); }
$totales = $stmt_tot->fetch();

// --- Desglose por área (solo cuando el informe es general) ---
$areas_stats = [];
if ($id_area_filtro === 'todas') {
    $areas_stats = $conn->query("SELECT 
        a.id_area, a.nombre_area,
        COUNT(d.id_doc) as total,
        SUM(CASE WHEN d.estado IN ('Pendiente', 'Rechazado') THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN d.estado = 'Resuelto_Para_Validar' THEN 1 ELSE 0 END) as revision,
        SUM(CASE WHEN d.estado = 'Concluido' THEN 1 ELSE 0 END) as concluidos
    FROM areas a
    LEFT JOIN documentos d ON a.id_area = d.id_area_asignada
    GROUP BY a.id_area, a.nombre_area
    ORDER BY a.nombre_area ASC")->fetchAll();
}

// --- Listado detallado de expedientes (paginado de 50 en 50) ---
$registros_por_pagina = 50;
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;

$sql_conteo = "SELECT COUNT(*) FROM documentos d $where_area";
$stmt_conteo = $conn->prepare($sql_conteo);
if ($id_area_filtro !== 'todas') { $stmt_conteo->execute([':id_area' => $area_seleccionada['id_area']]); }
else { $stmt_conteo->execute(); }
$total_registros = intval($stmt_conteo->fetchColumn());
$total_paginas = max(1, ceil($total_registros / $registros_por_pagina));
if ($pagina_actual > $total_paginas) { $pagina_actual = $total_paginas; }
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$sql_detalle = "SELECT d.*, a.nombre_area 
    FROM documentos d 
    JOIN areas a ON d.id_area_asignada = a.id_area 
    $where_area
    ORDER BY a.nombre_area ASC, d.id_doc DESC
    LIMIT $registros_por_pagina OFFSET $offset";
$stmt_det = $conn->prepare($sql_detalle);
if ($id_area_filtro !== 'todas') { $stmt_det->execute([':id_area' => $area_seleccionada['id_area']]); }
else { $stmt_det->execute(); }
$detalle = $stmt_det->fetchAll();

$fecha_generacion = date('d/m/Y H:i');
$titulo_reporte = $area_seleccionada ? htmlspecialchars($area_seleccionada['nombre_area']) : 'Todas las Áreas (General)';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe de Monitoreo | <?php echo $titulo_reporte; ?></title>
    <link rel="icon" href="rayas.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" crossorigin="anonymous">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
        :root {
            --guinda-base: #9f2241;
            --guinda-oscuro: #6f1120;
            --guinda-oro: #bc955c;
            --surface-bg: #f4f6f8;
            --text-main: #2d3748;
        }
        body { background-color: var(--surface-bg); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); }
        .card-metrica { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; }
        .icon-box { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .card-header-guinda { background: linear-gradient(90deg, var(--guinda-base) 0%, var(--guinda-oscuro) 100%); color: white; border-radius: 12px 12px 0 0 !important; }
        .table th { font-size: 0.7rem; letter-spacing: 0.6px; text-transform: uppercase; font-weight: 700; color: #4a5568; background-color: #edf2f7; }
        .barra-controles { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; }
        .badge-estado { font-size: 0.72rem; padding: 0.35em 0.7em; }

        @media print {
            .no-imprimir { display: none !important; }
            body { background: #fff; }
            .card-metrica, .card { box-shadow: none !important; border: 1px solid #ccc !important; }
        }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="barra-controles no-imprimir d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
            <label class="fw-bold small text-secondary m-0">Área:</label>
            <select name="area" class="form-select form-select-sm" style="min-width: 220px;" onchange="this.form.submit()">
                <option value="todas" <?php echo ($id_area_filtro === 'todas') ? 'selected' : ''; ?>>-- Todas las Áreas (General) --</option>
                <?php foreach ($areas as $a) { ?>
                <option value="<?php echo $a['id_area']; ?>" <?php echo ($area_seleccionada && $area_seleccionada['id_area'] == $a['id_area']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($a['nombre_area']); ?></option>
                <?php } ?>
            </select>
        </form>
        <div class="d-flex gap-2">
            <a href="tablon_monitoreo.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver a Monitoreo</a>
            <button class="btn btn-sm btn-dark" onclick="window.print();"><i class="bi bi-printer-fill me-1"></i>Imprimir Informe</button>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0"><i class="bi bi-grid-1x2-fill me-2" style="color: var(--guinda-base);"></i>Informe de Monitoreo — <?php echo $titulo_reporte; ?></h4>
            <span class="text-muted small">Generado el <?php echo $fecha_generacion; ?> por <?php echo htmlspecialchars($_SESSION['nombre'] ?? $rol); ?></span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card-metrica d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted small fw-bold mb-1">Folios Totales</h6>
                    <h3 class="fw-bold m-0 text-dark"><?php echo intval($totales['total']); ?></h3>
                </div>
                <div class="icon-box bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-folder-fill"></i></div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-metrica d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted small fw-bold mb-1">Activos en Proceso</h6>
                    <h3 class="fw-bold m-0 text-danger"><?php echo intval($totales['activos']); ?></h3>
                </div>
                <div class="icon-box bg-danger bg-opacity-10 text-danger"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-metrica d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted small fw-bold mb-1">En Revisión</h6>
                    <h3 class="fw-bold m-0 text-info"><?php echo intval($totales['revision']); ?></h3>
                </div>
                <div class="icon-box bg-info bg-opacity-10 text-info"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-metrica d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted small fw-bold mb-1">Validados Concluidos</h6>
                    <h3 class="fw-bold m-0 text-success"><?php echo intval($totales['concluidos']); ?></h3>
                </div>
                <div class="icon-box bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill"></i></div>
            </div>
        </div>
    </div>

    <?php if ($id_area_filtro === 'todas') { ?>
    <div class="card border-0 shadow-sm overflow-hidden mb-4">
        <div class="card-header card-header-guinda py-3 border-0">
            <h6 class="m-0 fw-bold text-white"><i class="bi bi-building me-2"></i>Desglose por Jefatura de Unidad Departamental (JUD)</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">JUD / Área</th>
                        <th>Carga Total</th>
                        <th>Pendientes / Rechazados</th>
                        <th>En Revisión</th>
                        <th>Concluidos</th>
                        <th class="pe-4">Eficiencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($areas_stats as $as) {
                        $pct = $as['total'] > 0 ? round(($as['concluidos'] / $as['total']) * 100) : 0;
                        $color = $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger');
                    ?>
                    <tr>
                        <td class="fw-bold text-dark ps-4"><?php echo htmlspecialchars($as['nombre_area']); ?></td>
                        <td class="fw-bold text-secondary"><?php echo $as['total']; ?></td>
                        <td class="text-danger fw-bold"><?php echo $as['pendientes']; ?></td>
                        <td class="text-info fw-bold"><?php echo $as['revision']; ?></td>
                        <td class="text-success fw-bold"><?php echo $as['concluidos']; ?></td>
                        <td class="pe-4"><span class="badge <?php echo $color; ?> px-2 py-1 rounded-pill"><?php echo $pct; ?>%</span></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php } ?>

    <div class="card border-0 shadow-sm overflow-hidden mb-4">
        <div class="card-header card-header-guinda py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="m-0 fw-bold text-white"><i class="bi bi-list-ul me-2"></i>Listado Detallado de Expedientes (<?php echo $total_registros; ?>)</h6>
            <span class="text-white small opacity-75">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?> — mostrando <?php echo count($detalle); ?> de <?php echo $total_registros; ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Folio / N° Ingreso</th>
                        <th>Área</th>
                        <th>Asunto</th>
                        <th>Remitente</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($detalle) === 0) { ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay expedientes para este filtro.</td></tr>
                    <?php } ?>
                    <?php foreach ($detalle as $d) {
                        $estado_color = 'secondary';
                        if ($d['estado'] === 'Pendiente') $estado_color = 'danger';
                        elseif ($d['estado'] === 'Rechazado') $estado_color = 'danger';
                        elseif ($d['estado'] === 'Resuelto_Para_Validar') $estado_color = 'info';
                        elseif ($d['estado'] === 'Concluido') $estado_color = 'success';
                    ?>
                    <tr>
                        <td class="ps-4 fw-bold text-secondary">#<?php echo $d['id_doc']; ?></td>
                        <td><?php echo htmlspecialchars($d['num_ingreso']); ?></td>
                        <td><?php echo htmlspecialchars($d['nombre_area']); ?></td>
                        <td style="max-width: 320px;"><?php echo htmlspecialchars(mb_strimwidth($d['titulo'], 0, 90, '...')); ?></td>
                        <td><?php echo htmlspecialchars($d['enviado_por']); ?></td>
                        <td><?php echo !empty($d['fecha_documento']) ? date('d/m/Y', strtotime($d['fecha_documento'])) : 'S/N'; ?></td>
                        <td><span class="badge bg-<?php echo $estado_color; ?> badge-estado"><?php echo htmlspecialchars($d['estado']); ?></span></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="no-imprimir d-flex justify-content-between align-items-center p-3 border-top">
            <a href="?area=<?php echo urlencode($id_area_filtro); ?>&pagina=<?php echo max(1, $pagina_actual - 1); ?>"
               class="btn btn-sm btn-outline-secondary <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
               <i class="bi bi-arrow-left me-1"></i>Página anterior
            </a>
            <span class="small text-muted fw-bold">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
            <a href="?area=<?php echo urlencode($id_area_filtro); ?>&pagina=<?php echo min($total_paginas, $pagina_actual + 1); ?>"
               class="btn btn-sm btn-outline-secondary <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
               Página siguiente<i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>

</div>

</body>
</html>
