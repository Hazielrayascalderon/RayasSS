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
$rol_clean_check = mb_strtolower(trim($rol ?? ''), 'UTF-8');
// Solo Coordinación y Administrador General pueden editar folios desde el CRUD Global
$puede_editar_crud = in_array($rol_clean_check, ['administrador', 'coordinación', 'coordinacion']);
$is_global_admin = in_array($rol_clean_check, ['administrador', 'coordinación', 'coordinacion', 'dirección general', 'direccion general']);

$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$filtro_area = isset($_GET['filtro_area']) ? $_GET['filtro_area'] : '';
$filtro_estado = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : '';
$orden_fecha = isset($_GET['orden_fecha']) && $_GET['orden_fecha'] === 'ASC' ? 'ASC' : 'DESC';
$areas = $conn->query("SELECT * FROM areas ORDER BY nombre_area ASC")->fetchAll();

$conditions = [];
$params = [];
if (!empty($buscar)) {
    $conditions[] = "(d.id_doc = :b_id OR d.titulo LIKE :b_like1 OR d.num_ingreso LIKE :b_like2 OR d.enviado_por LIKE :b_like3 OR d.capturista LIKE :b_like4 OR d.num_respuesta LIKE :b_like5)";
    $params[':b_id'] = intval($buscar);
    $params[':b_like1'] = '%' . $buscar . '%';
    $params[':b_like2'] = '%' . $buscar . '%';
    $params[':b_like3'] = '%' . $buscar . '%';
    $params[':b_like4'] = '%' . $buscar . '%';
    $params[':b_like5'] = '%' . $buscar . '%';
}
if ($is_global_admin && !empty($filtro_area)) {
    $conditions[] = "d.id_area_asignada = :area";
    $params[':area'] = intval($filtro_area);
}
if (!empty($filtro_estado)) {
    $conditions[] = "d.estado = :estado";
    $params[':estado'] = $filtro_estado;
}
// Si no es Coordinación/Administración/Dirección General, solo ve lo de su propia JUD
if (!$is_global_admin && isset($_SESSION['id_area']) && $_SESSION['id_area'] !== '') {
    $conditions[] = "d.id_area_asignada = :sesion_area";
    $params[':sesion_area'] = intval($_SESSION['id_area']);
}

$where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

$sql = "SELECT d.*, a.nombre_area 
        FROM documentos d 
        JOIN areas a ON d.id_area_asignada = a.id_area 
        $where_clause 
        ORDER BY d.fecha_ingreso $orden_fecha";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$documentos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Maestro Global | Coordinación y Administración</title>
    <link rel="icon" href="rayas.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
        :root {
            --guinda-base: #9f2241;
            --guinda-oscuro: #6f1120; 
            --guinda-oro: #bc955c;
            --oro-claro: #ddc9a3;
            --naranja-base: #f97316;
            --surface-bg: #f4f6f8; 
            --text-main: #2d3748;
        }
        body { background-color: var(--surface-bg); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); -webkit-font-smoothing: antialiased; font-size: 13px; }
        
        .navbar-custom { 
            background: linear-gradient(135deg, var(--guinda-oscuro) 0%, var(--guinda-base) 100%);
            border-bottom: 4px solid var(--guinda-oro);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .logo-esquina { height: 45px; object-fit: contain; border-radius: 6px; background: rgba(255,255,255,0.1); padding: 2px; }
        .nav-link { color: rgba(255, 255, 255, 0.8) !important; font-weight: 500; font-size: 0.9rem; border-radius: 6px; transition: all 0.2s ease; padding: 0.5rem 0.8rem !important; }
        .nav-link:hover { color: var(--guinda-oro) !important; background-color: rgba(255, 255, 255, 0.05); }
        .nav-link.active { color: #ffffff !important; font-weight: 700; background-color: rgba(255, 255, 255, 0.15); border-bottom: 2px solid var(--guinda-oro); border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
        .btn-salir { background-color: rgba(255, 255, 255, 0.1); color: white; border: 1px solid rgba(255,255,255,0.2); transition: all 0.2s; }
        .btn-salir:hover { background-color: #dc2626; border-color: #dc2626; color: white !important; }

        .table th { font-size: 0.72rem; letter-spacing: 0.8px; text-transform: uppercase; font-weight: 700; color: #475569; background-color: #f8fafc; }
        .modal-header { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom mb-4 sticky-top py-2">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2 me-lg-4" href="tablon.php">
            <img src="rayas.png" alt="Logo" class="logo-esquina">
            <div class="d-flex flex-column lh-1">
                <span class="fw-bold fs-6 text-white" style="letter-spacing: 0.5px;">Control Documental</span>
                <span class="fw-medium" style="color: var(--oro-claro); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px;">Gestión Interna</span>
            </div>
        </a>

         <button class="navbar-toggler border-0 text-white p-1" type="button" data-bs-toggle="collapse" data-bs-target="#menuNavegacion">
            <i class="bi bi-list fs-2 text-white"></i>
        </button>

        <div class="collapse navbar-collapse" id="menuNavegacion">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1 align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="tablon.php"><i class="bi bi-folder2-open me-1.5"></i>Expedientes</a></li>
                 <li class="nav-item"><a class="nav-link" href="empleados_tareas.php"><i class="bi bi-people me-1.5"></i>Tareas</a></li>
                <?php if ($is_global_admin) { ?>
                    <li class="nav-item"><a class="nav-link" href="gestion_oficios.php"><i class="fas fa-file-signature me-1.5"></i>Gestión Oficios</a></li>
                    <li class="nav-item"><a class="nav-link" href="tablon_monitoreo.php"><i class="bi bi-graph-up me-1.5"></i>Monitoreo</a></li>
                <?php } ?>
                    <li class="nav-item"><a class="nav-link active" href="crud_global.php"><i class="bi bi-sliders me-1.5"></i>Ajustes</a></li>
               
            </ul>
            <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0 pb-2 pb-lg-0 border-top border-lg-0 pt-3 pt-lg-0 border-secondary-subtle">
                <div id="reloj-banner" class="small fw-medium font-monospace d-none d-md-flex align-items-center px-3 py-1.5 rounded" style="background: rgba(0,0,0,0.2); color: var(--oro-claro);"></div>
                <div class="vr d-none d-lg-block opacity-25" style="height: 25px; background-color: white;"></div>
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center border border-light border-opacity-25" style="width: 36px; height: 36px; background: rgba(255,255,255,0.1);">
                        <i class="bi bi-person-fill text-white"></i>
                    </div>
                    <div class="d-flex flex-column lh-1">
                        <span class="small fw-bold text-white"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                        <span style="font-size: 0.65rem; color: var(--guinda-oro); font-weight: 700; text-transform: uppercase;"><?php echo $rol; ?></span>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-salir rounded p-2 ms-1 d-flex align-items-center justify-content-center" title="Cerrar Sesión" style="width: 36px; height: 36px;">
                    <i class="bi bi-power fs-5"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    
    <div class="card border-0 shadow-sm p-4 mb-4">
        <form method="GET" action="crud_global.php" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary">Buscador Inteligente</label>
                <input type="text" name="buscar" class="form-control" placeholder="Buscar..." value="<?php echo htmlspecialchars($buscar); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary">Filtrar por Área Turnada</label>
                <?php if ($is_global_admin) { ?>
                <select name="filtro_area" class="form-select">
                    <option value="">-- Ver Todas --</option>
                    <?php foreach ($areas as $a) { ?>
                        <option value="<?php echo $a['id_area']; ?>" <?php echo ($filtro_area == $a['id_area']) ? 'selected':''; ?>><?php echo htmlspecialchars($a['nombre_area']); ?></option>
                    <?php } ?>
                </select>
                <?php } else { ?>
                <input type="text" class="form-control" value="<?php
                    foreach ($areas as $a) { if ($a['id_area'] == ($_SESSION['id_area'] ?? null)) { echo htmlspecialchars($a['nombre_area']); break; } }
                ?>" disabled>
                <?php } ?>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold text-secondary">Estatus Técnico</label>
                <select name="filtro_estado" class="form-select">
                    <option value="">-- Todos --</option>
                    <option value="Pendiente" <?php echo ($filtro_estado === 'Pendiente') ? 'selected':''; ?>>Pendientes</option>
                    <option value="Rechazado" <?php echo ($filtro_estado === 'Rechazado') ? 'selected':''; ?>>Rechazados</option>
                    <option value="Resuelto_Para_Validar" <?php echo ($filtro_estado === 'Resuelto_Para_Validar') ? 'selected':''; ?>>En Revisión (Sin Validar)</option>
                    <option value="Concluido" <?php echo ($filtro_estado === 'Concluido') ? 'selected':''; ?>>Concluidos (Validados)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold text-secondary">Organizar por Fecha</label>
                <select name="orden_fecha" class="form-select">
                    <option value="DESC" <?php echo ($orden_fecha === 'DESC') ? 'selected':''; ?>>Más recientes primero</option>
                    <option value="ASC" <?php echo ($orden_fecha === 'ASC') ? 'selected':''; ?>>Más antiguos primero</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn w-100 fw-bold text-white shadow-sm" style="background-color: var(--guinda-oro); border:none;"><i class="bi bi-filter"></i> Filtrar</button>
            </div>
        </form>
    </div>

    <?php if(isset($_GET['msg'])) { ?>
        <div class="alert alert-success border-0 shadow-sm py-2">
            <i class="bi bi-check-circle-fill me-1"></i> Transacción ejecutada con éxito en la base de datos de Capital Humano.
        </div>
    <?php } ?>

    <div class="card border-0 shadow-sm overflow-hidden mb-5">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 5%">ID</th>
                        <th style="width: 10%">Estatus</th>
                        <th style="width: 15%">Área Destino</th>
                        <th style="width: 45%">Asunto e Información Técnico-Documental</th>
                        <th style="width: 10%">Archivos</th>
                        <th class="text-end pe-4" style="width: 15%">Acciones de Auditoría</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($documentos) === 0) { ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted fw-bold">No se encontraron expedientes con los parámetros seleccionados.</td></tr>
                    <?php } else { 
                        foreach($documentos as $doc) { 
                        $puede_editar_este_doc = $puede_editar_crud || ($rol_clean_check === 'jud' && isset($_SESSION['id_area']) && $doc['id_area_asignada'] == $_SESSION['id_area']);
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold text-secondary">#<?php echo $doc['id_doc']; ?></td>
                            <td>
                                <?php
                                    $badge_map = [
                                        'Pendiente' => 'bg-warning text-dark',
                                        'Rechazado' => 'bg-danger',
                                        'Resuelto_Para_Validar' => 'bg-info text-dark',
                                        'Concluido' => 'bg-success',
                                    ];
                                    $label_map = [
                                        'Pendiente' => 'Pendiente',
                                        'Rechazado' => 'Rechazado',
                                        'Resuelto_Para_Validar' => 'En Revisión',
                                        'Concluido' => 'Concluido',
                                    ];
                                    $badge_class = $badge_map[$doc['estado']] ?? 'bg-secondary';
                                    $badge_label = $label_map[$doc['estado']] ?? htmlspecialchars($doc['estado']);
                                ?>
                                <span class="badge <?php echo $badge_class; ?> px-2 py-1">
                                    <?php echo $badge_label; ?>
                                </span>
                            </td>
                            <td><span class="fw-semibold text-dark small uppercase"><?php echo htmlspecialchars($doc['nombre_area']); ?></span></td>
                            <td>
                                <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($doc['titulo']); ?></div>
                                <div class="small text-muted mb-1">
                                    Oficio Entrada: <strong><?php echo htmlspecialchars($doc['num_ingreso']); ?></strong> (<?php echo date('d/m/Y', strtotime($doc['fecha_documento'])); ?>) |
                                    Remitente: <strong><?php echo htmlspecialchars($doc['enviado_por']); ?></strong> |
                                    Capturó: <strong><?php echo htmlspecialchars($doc['capturista']); ?></strong>
                                </div>
                                <?php if (!empty($doc['operativo'])) { ?>
                                    <div class="small text-primary"><i class="bi bi-person-fill"></i> Operativo Asignado: <strong><?php echo htmlspecialchars($doc['operativo']); ?></strong></div>
                                <?php } ?>
                                <?php if (!empty($doc['num_respuesta'])) { ?>
                                    <div class="small text-success"><i class="bi bi-reply-fill"></i> Oficio Respuesta: <strong><?php echo htmlspecialchars($doc['num_respuesta']); ?></strong> (<?php echo date('d/m/Y', strtotime($doc['fecha_respuesta'])); ?>)</div>
                                <?php } ?>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <a href="descargar_doc.php?id=<?php echo $doc['id_doc']; ?>&tipo=ingreso" target="_blank" class="badge bg-secondary text-decoration-none text-start p-1.5"><i class="bi bi-file-pdf"></i> Entrada</a>
                                    <?php if(!empty($doc['archivo_respuesta'])) { ?>
                                        <a href="descargar_doc.php?id=<?php echo $doc['id_doc']; ?>&tipo=respuesta" target="_blank" class="badge bg-success text-decoration-none text-start p-1.5"><i class="bi bi-file-check"></i> Respuesta</a>
                                    <?php } ?>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex gap-1">
                                    <a href="imprimir_acuse.php?id=<?php echo $doc['id_doc']; ?>" target="_blank" class="btn btn-sm btn-dark" title="Imprimir Acuse"><i class="bi bi-printer-fill"></i></a>
                                    <?php if ($puede_editar_crud) { ?>
                                    <a href="eliminar_doc.php?id=<?php echo $doc['id_doc']; ?>" class="btn btn-sm btn-danger fw-bold" onclick="return confirm('¿Está completamente seguro de eliminar este folio?');"><i class="bi bi-trash-fill"></i></a>
                                    <?php } ?>
                                </div>
                            </td>
                        </tr>


                    <?php } } ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function inicializarReloj() {
    const ahora = new Date();
    const opciones = { weekday: 'short', day: 'numeric', month: 'short' };
    const fecha = ahora.toLocaleDateString('es-MX', opciones).replace('.', '');
    const hora = String(ahora.getHours()).padStart(2, '0') + ':' + String(ahora.getMinutes()).padStart(2, '0');
    
    const banner = document.getElementById('reloj-banner');
    if(banner) {
        banner.innerHTML = `<i class="bi bi-calendar-event me-2"></i> <span class="text-capitalize me-2">${fecha}</span> <span class="mx-1 opacity-50">|</span> <i class="bi bi-clock ms-2 me-1"></i> ${hora}`;
    }
}
setInterval(inicializarReloj, 1000); 
inicializarReloj();
</script>
</body>
</html>