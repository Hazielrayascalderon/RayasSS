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
$id_area = $_SESSION['id_area'];
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

$is_global_admin = in_array($rol, ['Administrador', 'Coordinación', 'Coordinaci贸n']);
$is_observer = in_array($rol, ['Direccion General', 'Secretaria']);
$ver_todo = ($is_global_admin || $is_observer);

$finalizados = [];
if ($ver_todo) {
    if (!empty($buscar)) {
        $sql_p = "SELECT d.*, a.nombre_area FROM documentos d JOIN areas a ON d.id_area_asignada = a.id_area WHERE d.estado = 'Concluido' AND (d.id_doc = :b_id OR d.titulo LIKE :b_like OR d.num_ingreso LIKE :b_like OR d.num_respuesta LIKE :b_like OR d.operativo LIKE :b_like) ORDER BY d.fecha_respuesta DESC";
        $stmt_p = $conn->prepare($sql_p);
        $stmt_p->execute([':b_id' => intval($buscar), ':b_like' => '%' . $buscar . '%']);
        $finalizados = $stmt_p->fetchAll();
    } else {
        $sql_p = "SELECT d.*, a.nombre_area FROM documentos d JOIN areas a ON d.id_area_asignada = a.id_area WHERE d.estado = 'Concluido' ORDER BY d.fecha_respuesta DESC";
        $finalizados = $conn->query($sql_p)->fetchAll();
    }
} else {
    if (!empty($buscar)) {
        $sql_p = "SELECT d.*, a.nombre_area FROM documentos d JOIN areas a ON d.id_area_asignada = a.id_area WHERE d.id_area_asignada = :area AND d.estado = 'Concluido' AND (d.id_doc = :b_id OR d.titulo LIKE :b_like OR d.num_ingreso LIKE :b_like OR d.num_respuesta LIKE :b_like) ORDER BY d.fecha_respuesta DESC";
        $stmt_p = $conn->prepare($sql_p);
        $stmt_p->execute([':area' => $id_area, ':b_id' => intval($buscar), ':b_like' => '%' . $buscar . '%']);
        $finalizados = $stmt_p->fetchAll();
    } else {
        $sql_p = "SELECT d.*, a.nombre_area FROM documentos d JOIN areas a ON d.id_area_asignada = a.id_area WHERE d.id_area_asignada = :area AND d.estado = 'Concluido' ORDER BY d.fecha_respuesta DESC";
        $stmt_p = $conn->prepare($sql_p);
        $stmt_p->execute([':area' => $id_area]);
        $finalizados = $stmt_p->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico Finalizados | Sistema de Gestión</title>
    <link rel="icon" href="rayas.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" crossorigin="anonymous">
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
        body { background-color: var(--surface-bg); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); -webkit-font-smoothing: antialiased; }
        
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

        .card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .card-header-guinda { background: linear-gradient(90deg, var(--guinda-base) 0%, var(--guinda-oscuro) 100%); color: white; border-radius: 12px 12px 0 0 !important; border-bottom: 2px solid var(--guinda-oro); }
        .btn-guinda { background-color: var(--guinda-base); color: white; font-weight: 600; border-radius: 8px; padding: 0.5rem 1rem; border: none; transition: 0.2s; }
        .btn-guinda:hover { background-color: var(--guinda-oscuro); color: white; }
        .table th { font-size: 0.75rem; letter-spacing: 0.8px; text-transform: uppercase; font-weight: 700; color: #4a5568; background-color: #edf2f7; padding: 1rem 1.25rem; }
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
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1 mt-3 mt-lg-0">
                <li class="nav-item"><a class="nav-link px-3" href="tablon.php"><i class="bi bi-folder2-open me-2"></i>Expedientes</a></li>
                <li class="nav-item"><a class="nav-link active px-3" href="finalizados.php"><i class="bi bi-check2-circle me-2"></i>Histórico</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="empleados_tareas.php"><i class="bi bi-people me-2"></i>Tareas</a></li>
                <?php if ($is_global_admin) { ?>
                    <li class="nav-item"><a class="nav-link px-3" href="tablon_monitoreo.php"><i class="bi bi-graph-up me-2"></i>Monitoreo</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="crud_global.php"><i class="bi bi-sliders me-2"></i>Ajustes</a></li>
                <?php } ?>
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
    <div class="row">
        <div class="col-12 mb-4">
            <form method="GET" action="finalizados.php" class="mb-4">
                <div class="input-group shadow-sm rounded-3 overflow-hidden bg-white border p-1" style="max-width: 600px;">
                    <span class="input-group-text bg-transparent border-0 text-muted ps-3"><i class="bi bi-search"></i></span>
                    <input type="text" name="buscar" class="form-control border-0 bg-transparent px-2 shadow-none" placeholder="Buscar en el histórico de concluidos..." value="<?php echo htmlspecialchars($buscar); ?>">
                    <input type="submit" class="btn btn-guinda rounded-3 px-4 shadow-none" value="Buscar">
                </div>
            </form>

            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-header card-header-guinda py-3 border-0">
                    <h6 class="m-0 fw-bold text-white"><i class="bi bi-archive-fill me-2" style="color: var(--oro-claro);"></i>Expedientes Concluidos y Validados</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 10%">ID</th>
                                <th style="width: 50%">Detalles de Recepción y Respuesta</th>
                                <th style="width: 25%">Concluido Por (Operativo)</th>
                                <th class="text-end pe-4" style="width: 15%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($finalizados) == 0) { ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted fw-medium">No hay registros concluidos en este momento.</td></tr>
                            <?php } else { 
                                foreach ($finalizados as $doc) { ?>
                                <tr>
                                    <td class="fw-bold text-secondary ps-4">#<?php echo $doc['id_doc']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1.5 flex-wrap mb-1.5">
                                            <span class="badge bg-success font-weight-bold">Concluido</span>
                                            <span class="badge bg-dark bg-opacity-10 text-dark-emphasis border border-secondary px-2">Oficio: <?php echo htmlspecialchars($doc['num_ingreso']); ?></span>
                                            <span class="badge bg-primary bg-opacity-10 text-primary-emphasis border border-primary px-2">Respuesta: <?php echo htmlspecialchars($doc['num_respuesta']); ?></span>
                                        </div>
                                        <div class="text-dark fw-bold mb-1" style="font-size:13.5px;"><?php echo htmlspecialchars($doc['titulo']); ?></div>
                                        <div class="small text-muted"><strong>Remitente:</strong> <?php echo htmlspecialchars($doc['enviado_por']); ?> | <strong>Finalizado el:</strong> <?php echo date('d/m/Y H:i', strtotime($doc['fecha_respuesta'])); ?></div>
                                    </td>
                                    <td>
                                        <div class="small text-dark fw-bold">
                                            <i class="bi bi-person-check-fill text-success"></i> 
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2.5 py-1.5 text-wrap text-start">
                                                <?php echo htmlspecialchars($doc['operativo'] ?: 'Jefatura de Unidad Departamental'); ?>
                                            </span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Área: <?php echo htmlspecialchars($doc['nombre_area']); ?></small>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-inline-flex gap-1">
                                            <a href="descargar_doc.php?id=<?php echo $doc['id_doc']; ?>&tipo=ingreso" target="_blank" class="btn btn-sm btn-outline-secondary p-2" title="Ver Oficio de Entrada"><i class="bi bi-file-earmark-pdf fs-6"></i></a>
                                            <a href="descargar_doc.php?id=<?php echo $doc['id_doc']; ?>&tipo=respuesta" target="_blank" class="btn btn-sm btn-outline-success p-2" title="Ver PDF de Contestación"><i class="bi bi-file-earmark-check-fill fs-6"></i></a>
                                            <a href="imprimir_acuse.php?id=<?php echo $doc['id_doc']; ?>" target="_blank" class="btn btn-sm btn-dark p-2" title="Imprimir Acuse Completo"><i class="bi bi-printer-fill fs-6"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php } } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
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