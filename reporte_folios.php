<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

require_once 'conexion.php';
$db = new Conexion();
$conn = $db->getConexion();
$rol = $_SESSION['rol'];

$rol_clean = mb_strtolower(trim($rol), 'UTF-8');
if (!in_array($rol_clean, ['administrador', 'coordinación', 'coordinacion'])) {
    header("Location: tablon.php");
    exit;
}

$areas = $conn->query("SELECT * FROM areas ORDER BY nombre_area ASC")->fetchAll();

// =========================================================
// 1) BUSCADOR DE UN FOLIO ESPECÍFICO (ficha completa)
// =========================================================
$folio_buscado = isset($_GET['folio']) ? trim($_GET['folio']) : '';
$resultados_folio = [];
$ficha = null;
$ficha_hermanos = [];

if (!empty($folio_buscado) && !isset($_GET['ver'])) {
    $stmt_f = $conn->prepare(
        "SELECT d.*, a.nombre_area FROM documentos d
         JOIN areas a ON d.id_area_asignada = a.id_area
         WHERE d.num_ingreso LIKE :q OR d.id_doc = :id_exacto
         ORDER BY d.id_doc DESC LIMIT 50"
    );
    $stmt_f->execute([
        ':q'         => '%' . $folio_buscado . '%',
        ':id_exacto' => ctype_digit($folio_buscado) ? intval($folio_buscado) : 0,
    ]);
    $resultados_folio = $stmt_f->fetchAll();
}

if (isset($_GET['ver']) && ctype_digit($_GET['ver'])) {
    $id_ver = intval($_GET['ver']);
    $stmt_v = $conn->prepare(
        "SELECT d.*, a.nombre_area FROM documentos d
         JOIN areas a ON d.id_area_asignada = a.id_area
         WHERE d.id_doc = :id LIMIT 1"
    );
    $stmt_v->execute([':id' => $id_ver]);
    $ficha = $stmt_v->fetch();

    if ($ficha) {
        // Folios hermanos: mismo envío turnado a varias áreas a la vez
        $stmt_h = $conn->prepare(
            "SELECT d.id_doc, a.nombre_area FROM documentos d
             JOIN areas a ON d.id_area_asignada = a.id_area
             WHERE d.num_ingreso = :num_ingreso AND d.fecha_documento = :fecha_documento
               AND d.titulo = :titulo AND d.capturista = :capturista
             ORDER BY a.nombre_area ASC"
        );
        $stmt_h->execute([
            ':num_ingreso'     => $ficha['num_ingreso'],
            ':fecha_documento' => $ficha['fecha_documento'],
            ':titulo'          => $ficha['titulo'],
            ':capturista'      => $ficha['capturista'],
        ]);
        $ficha_hermanos = $stmt_h->fetchAll();
    }
}

// =========================================================
// 2) LISTADO FILTRABLE E IMPRIMIBLE
// =========================================================
$f_fecha_ini = isset($_GET['fecha_ini']) ? trim($_GET['fecha_ini']) : '';
$f_fecha_fin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
$f_estado    = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$f_area      = isset($_GET['area']) ? trim($_GET['area']) : '';
$f_texto     = isset($_GET['texto']) ? trim($_GET['texto']) : '';

$condiciones = [];
$params = [];

if (!empty($f_fecha_ini)) { $condiciones[] = "d.fecha_documento >= :fecha_ini"; $params[':fecha_ini'] = $f_fecha_ini; }
if (!empty($f_fecha_fin)) { $condiciones[] = "d.fecha_documento <= :fecha_fin"; $params[':fecha_fin'] = $f_fecha_fin; }
if (!empty($f_estado))    { $condiciones[] = "d.estado = :estado"; $params[':estado'] = $f_estado; }
if (!empty($f_area))      { $condiciones[] = "d.id_area_asignada = :area"; $params[':area'] = intval($f_area); }
if (!empty($f_texto)) {
    $condiciones[] = "(d.num_ingreso LIKE :t1 OR d.titulo LIKE :t2 OR d.enviado_por LIKE :t3 OR d.capturista LIKE :t4)";
    $like = '%' . $f_texto . '%';
    $params[':t1'] = $like; $params[':t2'] = $like; $params[':t3'] = $like; $params[':t4'] = $like;
}

$where_sql = count($condiciones) > 0 ? ('WHERE ' . implode(' AND ', $condiciones)) : '';
$hay_filtros = count($condiciones) > 0;

$registros_por_pagina = 50;
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;

$stmt_c = $conn->prepare("SELECT COUNT(*) FROM documentos d $where_sql");
$stmt_c->execute($params);
$total_registros = intval($stmt_c->fetchColumn());
$total_paginas = max(1, ceil($total_registros / $registros_por_pagina));
if ($pagina_actual > $total_paginas) { $pagina_actual = $total_paginas; }
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$listado = [];
if ($hay_filtros) {
    $sql_listado = "SELECT d.*, a.nombre_area FROM documentos d
        JOIN areas a ON d.id_area_asignada = a.id_area
        $where_sql
        ORDER BY d.id_doc DESC
        LIMIT $registros_por_pagina OFFSET $offset";
    $stmt_l = $conn->prepare($sql_listado);
    $stmt_l->execute($params);
    $listado = $stmt_l->fetchAll();
}

// Reconstruir querystring de filtros (sin 'pagina') para los links de paginación
$qs_filtros = $_GET;
unset($qs_filtros['pagina']);
$qs_filtros_str = http_build_query($qs_filtros);

$fecha_generacion = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte por Folios | Control Documental</title>
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
        .card-header-guinda { background: linear-gradient(90deg, var(--guinda-base) 0%, var(--guinda-oscuro) 100%); color: white; border-radius: 12px 12px 0 0 !important; }
        .table th { font-size: 0.7rem; letter-spacing: 0.6px; text-transform: uppercase; font-weight: 700; color: #4a5568; background-color: #edf2f7; }
        .panel-filtros { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 18px; }
        .lbl-ficha { font-size: 0.7rem; letter-spacing: 0.5px; text-transform: uppercase; font-weight: 700; color: #718096; display: block; }
        .val-ficha { font-size: 0.95rem; font-weight: 600; color: #2d3748; }
        .badge-estado { font-size: 0.72rem; padding: 0.35em 0.7em; }

        @media print {
            .no-imprimir { display: none !important; }
            body { background: #fff; }
        }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="no-imprimir d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h4 class="fw-bold text-dark m-0"><i class="bi bi-search me-2" style="color: var(--guinda-base);"></i>Reporte por Folios</h4>
        <a href="tablon.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver al Tablón</a>
    </div>

    <!-- ===================== BUSCADOR DE UN FOLIO ===================== -->
    <div class="panel-filtros no-imprimir mb-4">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
            <div class="flex-grow-1" style="min-width: 220px;">
                <label class="form-label small fw-bold text-secondary mb-1">Buscar un folio (N° de ingreso o ID)</label>
                <input type="text" name="folio" class="form-control" placeholder="Ej. 3728 ó #12" value="<?php echo htmlspecialchars($folio_buscado); ?>">
            </div>
            <button type="submit" class="btn fw-bold text-white" style="background-color: var(--guinda-base);"><i class="bi bi-search me-1"></i>Buscar Folio</button>
        </form>
    </div>

    <?php if (!empty($folio_buscado) && !$ficha) { ?>
    <div class="card border-0 shadow-sm overflow-hidden mb-4 no-imprimir">
        <div class="card-header card-header-guinda py-3 border-0">
            <h6 class="m-0 fw-bold text-white"><i class="bi bi-list-ul me-2"></i>Resultados para "<?php echo htmlspecialchars($folio_buscado); ?>" (<?php echo count($resultados_folio); ?>)</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th class="ps-4">ID</th><th>Folio</th><th>Área</th><th>Asunto</th><th>Estado</th><th class="pe-4 text-end">Ver</th></tr>
                </thead>
                <tbody>
                    <?php if (count($resultados_folio) === 0) { ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No se encontró ningún folio con ese criterio.</td></tr>
                    <?php } ?>
                    <?php foreach ($resultados_folio as $r) { ?>
                    <tr>
                        <td class="ps-4 fw-bold text-secondary">#<?php echo $r['id_doc']; ?></td>
                        <td><?php echo htmlspecialchars($r['num_ingreso']); ?></td>
                        <td><?php echo htmlspecialchars($r['nombre_area']); ?></td>
                        <td><?php echo htmlspecialchars(mb_strimwidth($r['titulo'], 0, 70, '...')); ?></td>
                        <td><span class="badge bg-secondary badge-estado"><?php echo htmlspecialchars($r['estado']); ?></span></td>
                        <td class="pe-4 text-end"><a href="?ver=<?php echo $r['id_doc']; ?>" class="btn btn-sm btn-outline-dark"><i class="bi bi-eye-fill"></i></a></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php } ?>

    <?php if ($ficha) { ?>
    <div class="card border-0 shadow-sm overflow-hidden mb-4">
        <div class="card-header card-header-guinda py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="m-0 fw-bold text-white"><i class="bi bi-file-earmark-text-fill me-2"></i>Ficha del Folio #<?php echo $ficha['id_doc']; ?></h6>
            <div class="no-imprimir d-flex gap-2">
                <a href="imprimir_acuse.php?id=<?php echo $ficha['id_doc']; ?>" target="_blank" class="btn btn-sm btn-light"><i class="bi bi-printer-fill me-1"></i>Ver Acuse</a>
                <button class="btn btn-sm btn-light" onclick="window.print();"><i class="bi bi-printer-fill me-1"></i>Imprimir Ficha</button>
                <a href="?" class="btn btn-sm btn-light"><i class="bi bi-x-lg"></i></a>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-3"><span class="lbl-ficha">N° de Ingreso</span><span class="val-ficha"><?php echo htmlspecialchars($ficha['num_ingreso']); ?></span></div>
                <div class="col-md-3"><span class="lbl-ficha">Estado</span><span class="val-ficha"><span class="badge bg-secondary badge-estado"><?php echo htmlspecialchars($ficha['estado']); ?></span></span></div>
                <div class="col-md-3"><span class="lbl-ficha">Prioridad</span><span class="val-ficha"><?php echo htmlspecialchars($ficha['prioridad']); ?></span></div>
                <div class="col-md-3"><span class="lbl-ficha">Fecha del Documento</span><span class="val-ficha"><?php echo !empty($ficha['fecha_documento']) ? date('d/m/Y', strtotime($ficha['fecha_documento'])) : 'S/N'; ?></span></div>

                <div class="col-md-6"><span class="lbl-ficha">Área / JUD Asignada</span><span class="val-ficha"><?php echo htmlspecialchars($ficha['nombre_area']); ?></span></div>
                <div class="col-md-6"><span class="lbl-ficha">Fecha de Ingreso al Sistema</span><span class="val-ficha"><?php echo !empty($ficha['fecha_ingreso']) ? date('d/m/Y H:i', strtotime($ficha['fecha_ingreso'])) : 'S/N'; ?></span></div>

                <?php if (count($ficha_hermanos) > 1) { ?>
                <div class="col-12">
                    <span class="lbl-ficha">Con copia a</span>
                    <span class="val-ficha">
                        <?php
                            $otros = [];
                            foreach ($ficha_hermanos as $fh) { if ($fh['id_doc'] != $ficha['id_doc']) { $otros[] = htmlspecialchars($fh['nombre_area']); } }
                            echo implode(', ', $otros);
                        ?>
                    </span>
                </div>
                <?php } ?>

                <div class="col-12"><span class="lbl-ficha">Asunto / Título</span><span class="val-ficha"><?php echo nl2br(htmlspecialchars($ficha['titulo'])); ?></span></div>
                <div class="col-md-6"><span class="lbl-ficha">Remitente / Enviado por</span><span class="val-ficha"><?php echo htmlspecialchars($ficha['enviado_por']); ?></span></div>
                <div class="col-md-6"><span class="lbl-ficha">Capturista</span><span class="val-ficha"><?php echo htmlspecialchars($ficha['capturista']); ?></span></div>

                <?php if (!empty($ficha['instruccion'])) { ?>
                <div class="col-12"><span class="lbl-ficha">Instrucción / Informe de Coordinación</span><span class="val-ficha"><?php echo nl2br(htmlspecialchars($ficha['instruccion'])); ?></span></div>
                <?php } ?>
                <?php if (!empty($ficha['operativo'])) { ?>
                <div class="col-md-6"><span class="lbl-ficha">Operativo Asignado</span><span class="val-ficha"><?php echo htmlspecialchars($ficha['operativo']); ?></span></div>
                <?php } ?>
                <?php if (!empty($ficha['num_respuesta'])) { ?>
                <div class="col-md-6"><span class="lbl-ficha">N° de Respuesta</span><span class="val-ficha"><?php echo htmlspecialchars($ficha['num_respuesta']); ?></span></div>
                <?php } ?>
                <?php if (!empty($ficha['fecha_respuesta'])) { ?>
                <div class="col-md-6"><span class="lbl-ficha">Fecha de Respuesta</span><span class="val-ficha"><?php echo date('d/m/Y', strtotime($ficha['fecha_respuesta'])); ?></span></div>
                <?php } ?>

                <div class="col-12 no-imprimir d-flex gap-2 mt-2">
                    <a href="descargar_doc.php?id=<?php echo $ficha['id_doc']; ?>&tipo=ingreso" target="_blank" class="btn btn-sm btn-outline-dark"><i class="bi bi-file-earmark-arrow-down me-1"></i>Ver Documento Entrada</a>
                    <?php if (!empty($ficha['num_respuesta'])) { ?>
                    <a href="descargar_doc.php?id=<?php echo $ficha['id_doc']; ?>&tipo=respuesta" target="_blank" class="btn btn-sm btn-outline-dark"><i class="bi bi-file-earmark-arrow-down me-1"></i>Ver Documento Respuesta</a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>

    <!-- ===================== LISTADO FILTRABLE ===================== -->
    <div class="panel-filtros no-imprimir mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary mb-1">Desde</label>
                <input type="date" name="fecha_ini" class="form-control form-control-sm" value="<?php echo htmlspecialchars($f_fecha_ini); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary mb-1">Hasta</label>
                <input type="date" name="fecha_fin" class="form-control form-control-sm" value="<?php echo htmlspecialchars($f_fecha_fin); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary mb-1">Estado</label>
                <select name="estado" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                    <?php foreach (['Pendiente', 'Rechazado', 'Resuelto_Para_Validar', 'Concluido'] as $es) { ?>
                    <option value="<?php echo $es; ?>" <?php echo ($f_estado === $es) ? 'selected' : ''; ?>><?php echo $es; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary mb-1">Área</label>
                <select name="area" class="form-select form-select-sm">
                    <option value="">-- Todas --</option>
                    <?php foreach ($areas as $a) { ?>
                    <option value="<?php echo $a['id_area']; ?>" <?php echo ($f_area == $a['id_area']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($a['nombre_area']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary mb-1">Texto (folio, asunto, remitente, capturista)</label>
                <input type="text" name="texto" class="form-control form-control-sm" value="<?php echo htmlspecialchars($f_texto); ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm fw-bold text-white w-100" style="background-color: var(--guinda-base);"><i class="bi bi-funnel-fill"></i></button>
            </div>
        </form>
    </div>

    <?php if ($hay_filtros) { ?>
    <div class="card border-0 shadow-sm overflow-hidden mb-4">
        <div class="card-header card-header-guinda py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="m-0 fw-bold text-white"><i class="bi bi-list-ul me-2"></i>Listado de Folios (<?php echo $total_registros; ?>)</h6>
            <div class="d-flex align-items-center gap-2">
                <span class="text-white small opacity-75">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
                <button class="btn btn-sm btn-light no-imprimir" onclick="window.print();"><i class="bi bi-printer-fill me-1"></i>Imprimir</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th class="ps-4">ID</th><th>Folio</th><th>Área</th><th>Asunto</th><th>Remitente</th><th>Capturista</th><th>Fecha</th><th>Estado</th><th class="pe-4 no-imprimir text-end">Ver</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($listado) === 0) { ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No hay folios que coincidan con los filtros.</td></tr>
                    <?php } ?>
                    <?php foreach ($listado as $l) {
                        $estado_color = 'secondary';
                        if ($l['estado'] === 'Pendiente' || $l['estado'] === 'Rechazado') $estado_color = 'danger';
                        elseif ($l['estado'] === 'Resuelto_Para_Validar') $estado_color = 'info';
                        elseif ($l['estado'] === 'Concluido') $estado_color = 'success';
                    ?>
                    <tr>
                        <td class="ps-4 fw-bold text-secondary">#<?php echo $l['id_doc']; ?></td>
                        <td><?php echo htmlspecialchars($l['num_ingreso']); ?></td>
                        <td><?php echo htmlspecialchars($l['nombre_area']); ?></td>
                        <td style="max-width: 260px;"><?php echo htmlspecialchars(mb_strimwidth($l['titulo'], 0, 80, '...')); ?></td>
                        <td><?php echo htmlspecialchars($l['enviado_por']); ?></td>
                        <td><?php echo htmlspecialchars($l['capturista']); ?></td>
                        <td><?php echo !empty($l['fecha_documento']) ? date('d/m/Y', strtotime($l['fecha_documento'])) : 'S/N'; ?></td>
                        <td><span class="badge bg-<?php echo $estado_color; ?> badge-estado"><?php echo htmlspecialchars($l['estado']); ?></span></td>
                        <td class="pe-4 no-imprimir text-end"><a href="?ver=<?php echo $l['id_doc']; ?>" class="btn btn-sm btn-outline-dark"><i class="bi bi-eye-fill"></i></a></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="no-imprimir d-flex justify-content-between align-items-center p-3 border-top">
            <a href="?<?php echo $qs_filtros_str; ?>&pagina=<?php echo max(1, $pagina_actual - 1); ?>"
               class="btn btn-sm btn-outline-secondary <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
               <i class="bi bi-arrow-left me-1"></i>Página anterior
            </a>
            <span class="small text-muted fw-bold">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
            <a href="?<?php echo $qs_filtros_str; ?>&pagina=<?php echo min($total_paginas, $pagina_actual + 1); ?>"
               class="btn btn-sm btn-outline-secondary <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
               Página siguiente<i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <?php } else { ?>
    <div class="text-center text-muted py-5 no-imprimir">
        <i class="bi bi-funnel fs-1 opacity-50"></i>
        <p class="mt-2">Usa los filtros de arriba para generar el listado de folios.</p>
    </div>
    <?php } ?>

</div>

</body>
</html>
