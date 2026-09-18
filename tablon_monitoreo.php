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
if (!in_array($rol_clean, ['administrador', 'coordinación', 'coordinacion', 'dirección general', 'direccion general'])) {
    header("Location: tablon.php");
    exit;
}
$es_coordinacion = in_array($rol_clean, ['coordinación', 'coordinacion']);

$totales = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado IN ('Pendiente', 'Rechazado') THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN estado = 'Concluido' THEN 1 ELSE 0 END) as concluidos,
    SUM(CASE WHEN estado = 'Resuelto_Para_Validar' THEN 1 ELSE 0 END) as revision,
    SUM(CASE WHEN prioridad = 'Extraurgente' AND estado != 'Concluido' THEN 1 ELSE 0 END) as extraurgentes,
    SUM(CASE WHEN prioridad = 'A la brevedad posible' AND estado != 'Concluido' THEN 1 ELSE 0 END) as brevedad
FROM documentos")->fetch();

$areas_stats = $conn->query("SELECT 
    a.id_area,
    a.nombre_area,
    COUNT(d.id_doc) as total,
    SUM(CASE WHEN d.estado IN ('Pendiente', 'Rechazado') THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN d.estado = 'Resuelto_Para_Validar' THEN 1 ELSE 0 END) as revision,
    SUM(CASE WHEN d.estado = 'Concluido' THEN 1 ELSE 0 END) as concluidos
FROM areas a
LEFT JOIN documentos d ON a.id_area = d.id_area_asignada
GROUP BY a.id_area, a.nombre_area
ORDER BY a.nombre_area ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoreo Estratégico | Sistema de Gestión</title>
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
        
        .card-header-guinda { background: linear-gradient(90deg, var(--guinda-base) 0%, var(--guinda-oscuro) 100%); color: white; border-radius: 12px 12px 0 0 !important; border-bottom: 2px solid var(--guinda-oro); }

        .card-metrica { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); transition: transform 0.2s; }
        .card-metrica:hover { transform: translateY(-2px); }
        .icon-box { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
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
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1 align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="tablon.php"><i class="bi bi-folder2-open me-1.5"></i>Expedientes</a></li>
                 <li class="nav-item"><a class="nav-link" href="empleados_tareas.php"><i class="bi bi-people me-1.5"></i>Tareas</a></li>
                    <li class="nav-item"><a class="nav-link" href="gestion_oficios.php"><i class="fas fa-file-signature me-1.5"></i>Gestión Oficios</a></li>
                    <li class="nav-item"><a class="nav-link active" href="tablon_monitoreo.php"><i class="bi bi-graph-up me-1.5"></i>Monitoreo</a></li>
                    <li class="nav-item"><a class="nav-link" href="crud_global.php"><i class="bi bi-sliders me-1.5"></i>Ajustes</a></li>
               
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
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h4 class="fw-bold text-dark m-0"><i class="bi bi-grid-1x2-fill me-2" style="color: var(--guinda-base);"></i>Indicadores de Productividad Institucional (Dirección)</h4>
        <?php if ($es_coordinacion) { ?>
        <a href="reporte_monitoreo.php" target="_blank" class="btn fw-bold text-white shadow-sm" style="background-color: var(--guinda-base);"><i class="bi bi-printer-fill me-1"></i>Imprimir Informe</a>
        <?php } ?>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card-metrica d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted small fw-bold uppercase mb-1">Folios Totales</h6>
                    <h3 class="fw-bold m-0 text-dark"><?php echo intval($totales['total']); ?></h3>
                </div>
                <div class="icon-box bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-folder-fill"></i></div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-metrica d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted small fw-bold uppercase mb-1">Activos en Proceso</h6>
                    <h3 class="fw-bold m-0 text-danger"><?php echo intval($totales['activos']); ?></h3>
                </div>
                <div class="icon-box bg-danger bg-opacity-10 text-danger"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-metrica d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted small fw-bold uppercase mb-1">En Revisión (Dirección)</h6>
                    <h3 class="fw-bold m-0 text-info"><?php echo intval($totales['revision']); ?></h3>
                </div>
                <div class="icon-box bg-info bg-opacity-10 text-info"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card-metrica d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted small fw-bold uppercase mb-1">Validados Concluidos</h6>
                    <h3 class="fw-bold m-0 text-success"><?php echo intval($totales['concluidos']); ?></h3>
                </div>
                <div class="icon-box bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill"></i></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden mb-4">
        <div class="card-header card-header-guinda py-3 border-0">
            <h6 class="m-0 fw-bold text-white"><i class="bi bi-building me-2"></i>Rendimiento y Eficiencia por Jefatura de Unidad Departamental (JUD)</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">JUD / Área Operativa</th>
                        <th>Carga Total</th>
                        <th>Pendientes / Rechazados</th>
                        <th>En Revisión</th>
                        <th>Validados Concluidos</th>
                        <th>Eficiencia Real</th>
                        <th style="width: 15%">Progreso Validados</th>
                        <th class="pe-4 text-end">Informe</th>
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
                        <td><span class="badge <?php echo $color; ?> px-2.5 py-1.5 rounded-pill"><?php echo $pct; ?>% Eficiencia</span></td>
                        <td>
                            <div class="progress" style="height: 8px; border-radius: 4px;">
                                <div class="progress-bar <?php echo $color; ?>" style="width: <?php echo $pct; ?>%"></div>
                            </div>
                        </td>
                        <td class="pe-4 text-end">
                            <?php if ($es_coordinacion) { ?>
                            <a href="reporte_monitoreo.php?area=<?php echo $as['id_area']; ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="Imprimir informe de esta área"><i class="bi bi-printer-fill"></i></a>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
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