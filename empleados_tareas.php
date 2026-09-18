<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

require_once 'conexion.php';
$db = new Conexion();
$conn = $db->getConexion();
$rol = trim($_SESSION['rol'] ?? '');
$id_area_usuario = $_SESSION['id_area'];

// Normalización de roles tolerante a mayúsculas/minúsculas y acentos
$rol_clean = mb_strtolower($rol, 'UTF-8');
$is_global_admin = in_array($rol_clean, [
    'administrador',
    'coordinación',
    'coordinacion',
    'dirección general',
    'direccion general'
]);
$es_coordinacion = in_array($rol_clean, ['coordinación', 'coordinacion']);
$es_direccion_general = in_array($rol_clean, ['dirección general', 'direccion general']);
$puede_alta_personal = ($es_coordinacion || $es_direccion_general);

$filtro_area = isset($_GET['area_auditoria']) ? intval($_GET['area_auditoria']) : $id_area_usuario;
if ($is_global_admin && !isset($_GET['area_auditoria'])) {
    $primer_area = $conn->query("SELECT id_area FROM areas ORDER BY nombre_area ASC LIMIT 1")->fetch();
    $filtro_area = $primer_area ? intval($primer_area['id_area']) : 0;
}

$areas_lista = $conn->query("SELECT * FROM areas ORDER BY nombre_area ASC")->fetchAll();
$stmt_personal = $conn->prepare("SELECT * FROM personal WHERE id_area = :area ORDER BY nombre ASC");
$stmt_personal->execute([':area' => $filtro_area]);
$plantilla = $stmt_personal->fetchAll();

$stmt_tareas = $conn->prepare("SELECT id_doc, titulo, num_ingreso, trae_cach, es_dgaf, folio_cjsl, estado, operativo FROM documentos WHERE id_area_asignada = :area");
$stmt_tareas->execute([':area' => $filtro_area]);
$todas_tareas = $stmt_tareas->fetchAll();

$area_nombre = '';
foreach ($areas_lista as $al) {
    if (intval($al['id_area']) === $filtro_area) {
        $area_nombre = $al['nombre_area'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productividad por Personal | Control Documental</title>
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

        .card-empleado { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01); }
        .lista-folios { max-height: 200px; overflow-y: auto; font-size: 12px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; }
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
                 <li class="nav-item"><a class="nav-link active" href="empleados_tareas.php"><i class="bi bi-people me-1.5"></i>Tareas</a></li>
                <?php if ($is_global_admin) { ?>
                    <li class="nav-item"><a class="nav-link" href="gestion_oficios.php"><i class="fas fa-file-signature me-1.5"></i>Gestión Oficios</a></li>
                    <li class="nav-item"><a class="nav-link" href="tablon_monitoreo.php"><i class="bi bi-graph-up me-1.5"></i>Monitoreo</a></li>
                <?php } ?>
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

<div class="container-fluid px-4 mb-5">
    
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="fw-bold m-0 text-dark">Distribución de Carga de Trabajo por Analista Operativo</h4>
            <p class="text-muted small m-0">Monitoreo funcional del estado de expedientes turnados a cada integrante de la plantilla.</p>
        </div>
        
        <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php if ($is_global_admin) { ?>
            <div class="card p-2 border-0 shadow-sm">
                <form method="GET" class="d-flex align-items-center gap-2 m-0">
                    <label class="small fw-bold text-secondary text-nowrap m-0 px-2"><i class="bi bi-building"></i> Auditar JUD:</label>
                    <select name="area_auditoria" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 250px;">
                        <?php foreach ($areas_lista as $al) { ?>
                            <option value="<?php echo $al['id_area']; ?>" <?php echo (intval($filtro_area) === intval($al['id_area'])) ? 'selected' : ''; ?>><?php echo htmlspecialchars($al['nombre_area']); ?></option>
                        <?php } ?>
                    </select>
                </form>
            </div>
        <?php } ?>
        <?php if ($puede_alta_personal) { ?>
            <a href="alta_personal.php<?php echo $filtro_area ? '?id_area=' . intval($filtro_area) : ''; ?>" class="btn fw-bold text-white shadow-sm" style="background-color: var(--guinda-base);">
                <i class="bi bi-person-plus-fill me-1"></i> Dar de Alta Personal
            </a>
        <?php } ?>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'personal_alta') { ?>
        <div class="alert alert-success border-0 shadow-sm py-2 mb-3">
            <i class="bi bi-check-circle-fill me-1"></i> Personal dado de alta correctamente.
        </div>
    <?php } elseif (isset($_GET['msg']) && $_GET['msg'] === 'personal_baja') { ?>
        <div class="alert alert-success border-0 shadow-sm py-2 mb-3">
            <i class="bi bi-check-circle-fill me-1"></i> Personal dado de baja correctamente.
        </div>
    <?php } elseif (isset($_GET['msg']) && $_GET['msg'] === 'respuesta_subida') { ?>
        <div class="alert alert-success border-0 shadow-sm py-2 mb-3">
            <i class="bi bi-check-circle-fill me-1"></i> Respuesta enviada. El folio quedó en revisión para su validación.
        </div>
    <?php } elseif (isset($_GET['msg']) && $_GET['msg'] === 'validation_success') { ?>
        <div class="alert alert-success border-0 shadow-sm py-2 mb-3">
            <i class="bi bi-check-circle-fill me-1"></i> Validación registrada correctamente.
        </div>
    <?php } ?>

    <div class="alert alert-dark border-0 py-2.5 px-4 mb-4 fw-bold uppercase small shadow-sm" style="letter-spacing: 0.5px; background: #0f172a; color:#fff;">
        <i class="bi bi-shield-check me-2 text-warning"></i> Visualizando Plantilla de: <?php echo htmlspecialchars($area_nombre ?: 'Sin asignar'); ?>
    </div>

    <div class="row">
        <?php 
        if (count($plantilla) === 0) {
            echo "<div class='col-12 text-center py-5'><div class='card border-0 shadow-sm p-5 text-muted fw-semibold'><i class='bi bi-person-x text-secondary fs-1 mb-2'></i>No se encuentra personal operativo registrado en esta Jefatura.</div></div>";
        } else {
            foreach ($plantilla as $emp) {
                $nombre_emp = $emp['nombre'];
                
                $pendientes_emp = [];
                $revision_emp = [];
                $concluidos_emp = [];

                foreach ($todas_tareas as $t) {
                    if ($t['operativo'] === $nombre_emp) {
                        if (in_array($t['estado'], ['Pendiente', 'Rechazado'])) {
                            $pendientes_emp[] = $t;
                        } elseif ($t['estado'] === 'Resuelto_Para_Validar') {
                            $revision_emp[] = $t;
                        } elseif ($t['estado'] === 'Concluido') {
                            $concluidos_emp[] = $t;
                        }
                    }
                }
                ?>
                <div class="col-xl-6 col-xxl-4 mb-4">
                    <div class="card-empleado p-4">
                         <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3 gap-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 45px; height: 45px; background: linear-gradient(135deg, var(--guinda-oscuro) 0%, var(--guinda-base) 100%); border: 2px solid var(--guinda-oro);">
                                    <?php echo strtoupper(substr($nombre_emp, 0, 2)); ?>
                                </div>
                                <div>
                                    <h6 class="fw-bold m-0 text-dark" style="font-size: 14.5px;"><?php echo htmlspecialchars($nombre_emp); ?></h6>
                                    <small class="text-muted uppercase fw-bold" style="font-size: 10px;">Analista Dictaminador</small>
                                </div>
                            </div>
                            <?php if (count($pendientes_emp) > 0) { ?>
                                <button type="button" class="btn btn-sm btn-outline-danger fw-bold shadow-none" style="font-size: 11px; border-radius: 8px;" onclick="imprimirListadoPendientes('<?php echo htmlspecialchars($nombre_emp, ENT_QUOTES); ?>', 'print_box_<?php echo $emp['id_personal']; ?>')">
                                    <i class="bi bi-printer-fill me-1"></i> Imprimir Lista
                                </button>
                           <?php } ?>
                        </div>

                        <div class="row g-2 mb-3 text-center">
                            <div class="col-4">
                                <div class="p-2 bg-danger bg-opacity-10 text-danger rounded-3">
                                    <small class="d-block text-uppercase fw-bold" style="font-size: 9px;">Pendientes</small>
                                    <span class="fs-5 fw-bold"><?php echo count($pendientes_emp); ?></span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 bg-info bg-opacity-10 text-info rounded-3">
                                    <small class="d-block text-uppercase fw-bold text-nowrap" style="font-size: 9px;">En Revisión</small>
                                    <span class="fs-5 fw-bold"><?php echo count($revision_emp); ?></span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 bg-success bg-opacity-10 text-success rounded-3">
                                    <small class="d-block text-uppercase fw-bold" style="font-size: 9px;">Concluidos</small>
                                    <span class="fs-5 fw-bold"><?php echo count($concluidos_emp); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="text-danger fw-bold small d-block mb-1.5"><i class="bi bi-exclamation-triangle-fill me-1"></i> Folios Pendientes y por Corregir:</span>
                            <div class="lista-folios p-2" id="print_box_<?php echo $emp['id_personal']; ?>">
                                <?php
                                if (count($pendientes_emp) === 0) {
                                    echo "<div class='text-muted small text-center py-3 px-2'>Sin trámites pendientes en bandeja.</div>";
                                } else {
                                    foreach ($pendientes_emp as $act):
                                        $badge_st = ($act['estado'] === 'Rechazado') ? 'bg-danger text-white' : 'bg-secondary text-white';
                                        $label_st = ($act['estado'] === 'Rechazado') ? 'Rechazado' : 'Pendiente';
                                ?>
                                <div style="border-bottom: 1px solid #e2e8f0; padding: 8px 4px; page-break-inside: avoid;">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <span style="color: #0f172a; font-weight: 700;">#<?php echo $act['id_doc']; ?> - <?php echo htmlspecialchars($act['titulo']); ?></span>
                                        <span class="badge <?php echo $badge_st; ?>" style="font-size:9px;"><?php echo $label_st; ?></span>
                                    </div>
                                    <div class="mt-1.5 d-flex flex-wrap gap-1" style="font-size: 10px;">
                                        <span class="badge bg-dark bg-opacity-10 text-dark border px-1.5 py-0.5">Oficio: <?php echo htmlspecialchars($act['num_ingreso']); ?></span>
                                        <?php if (!empty($act['trae_cach'])) { ?><span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning-subtle px-1.5 py-0.5">CACH: <?php echo htmlspecialchars($act['trae_cach']); ?></span><?php } ?>
                                        <?php if (!empty($act['es_dgaf'])) { ?><span class="badge bg-info bg-opacity-10 text-info-emphasis border border-info-subtle px-1.5 py-0.5">DGAF: <?php echo htmlspecialchars($act['es_dgaf']); ?></span><?php } ?>
                                        <?php if (!empty($act['folio_cjsl'])) { ?><span class="badge bg-secondary bg-opacity-10 text-secondary-emphasis border border-secondary-subtle px-1.5 py-0.5">CJSL: <?php echo htmlspecialchars($act['folio_cjsl']); ?></span><?php } ?>
                                    </div>
                                    <div class="mt-2 no-print">
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 10.5px;" data-bs-toggle="modal" data-bs-target="#modalResp<?php echo $act['id_doc']; ?>">
                                            <i class="bi bi-upload me-1"></i>Subir Respuesta
                                        </button>
                                    </div>
                                </div>

                                <div class="modal fade" id="modalResp<?php echo $act['id_doc']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content text-start">
                                            <form action="finalizar_doc.php" method="POST" enctype="multipart/form-data">
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold text-dark m-0"><i class="bi bi-upload me-2" style="color: var(--guinda-base);"></i>Subir Respuesta - Folio #<?php echo $act['id_doc']; ?></h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_doc" value="<?php echo $act['id_doc']; ?>">
                                                    <input type="hidden" name="operativo" value="<?php echo htmlspecialchars($nombre_emp, ENT_QUOTES); ?>">
                                                    <input type="hidden" name="redirect_msg" value="respuesta_subida">
                                                    <p class="small text-muted mb-3"><?php echo htmlspecialchars($act['titulo']); ?></p>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold text-secondary">No. de Oficio de Respuesta</label>
                                                        <input type="text" name="num_respuesta" class="form-control" placeholder="Ej. RESP-2026-045">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold text-secondary">Comentarios / Observaciones</label>
                                                        <textarea name="comentarios_abogado" class="form-control" rows="3" placeholder="Describe brevemente la respuesta otorgada..."></textarea>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small fw-bold text-secondary">Archivo PDF de Respuesta *</label>
                                                        <input type="file" name="archivo_respuesta" class="form-control" accept=".pdf" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-sm text-white fw-bold" style="background-color: var(--guinda-base);"><i class="bi bi-send-fill me-1"></i>Enviar Respuesta</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach;
                                }
                                ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="text-info fw-bold small d-block mb-1.5"><i class="bi bi-hourglass-split me-1"></i> Folios Sent al Coordinador (En Revisión):</span>
                            <div class="lista-folios p-2" style="max-height: 100px;">
                                <?php
                                if (count($revision_emp) === 0) {
                                    echo "<div class='text-muted small text-center py-3 px-2'>Ningún folio esperando firma de Dirección.</div>";
                                } else {
                                    foreach ($revision_emp as $rev):
                                ?>
                                <div class="border-bottom py-1.5 px-1 d-flex justify-content-between align-items-center flex-wrap gap-1">
                                    <span class="text-dark text-truncate" style="max-width: 75%;"><strong>#<?php echo $rev['id_doc']; ?></strong> - <?php echo htmlspecialchars($rev['titulo']); ?></span>
                                    <?php if ($es_coordinacion) { ?>
                                        <div class="d-flex gap-1 no-print">
                                            <a href="validar_doc.php?id=<?php echo $rev['id_doc']; ?>&accion=aprobar&return=tareas" class="btn btn-sm btn-success py-0 px-2" style="font-size: 9px;" onclick="return confirm('¿Confirmas que la respuesta del folio #<?php echo $rev['id_doc']; ?> es correcta y deseas avalarla?');"><i class="bi bi-check-lg"></i> Avalar</a>
                                            <a href="validar_doc.php?id=<?php echo $rev['id_doc']; ?>&accion=rechazar&return=tareas" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 9px;" onclick="return confirm('¿Deseas rechazar la respuesta del folio #<?php echo $rev['id_doc']; ?> y regresarla al operativo?');"><i class="bi bi-x-lg"></i> Rechazar</a>
                                        </div>
                                    <?php } else { ?>
                                        <span class="badge bg-info text-dark" style="font-size: 9px;">Revisión</span>
                                    <?php } ?>
                                </div>
                                <?php endforeach;
                                }
                                ?>
                            </div>
                        </div>

                        <div>
                            <span class="text-success fw-bold small d-block mb-1.5"><i class="bi bi-check-all me-1"></i> Últimos Resueltos Concluidos:</span>
                            <div class="lista-folios p-2" style="max-height: 100px;">
                                <?php 
                                if (count($concluidos_emp) === 0) {
                                    echo "<div class='text-muted small text-center py-3 px-2'>No registra asuntos concluidos en este ciclo.</div>";
                                } else {
                                    foreach ($concluidos_emp as $con) {
                                        echo "<div class='border-bottom py-1.5 px-1 text-success text-truncate'>";
                                        echo "<i class='bi bi-check-circle me-1'></i> <strong>#".$con['id_doc']."</strong> - ".htmlspecialchars($con['titulo']);
                                        echo "</div>";
                                    }
                                }
                                ?>
                            </div>
                        </div>

                    </div>
                </div>
            <?php } 
        } ?>
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

function imprimirListadoPendientes(nombreEmpleado, containerId) {
    var original = document.getElementById(containerId);
    var clone = original.cloneNode(true);
    clone.querySelectorAll('.no-print, .modal').forEach(function(el){ el.remove(); });
    var contenido = clone.innerHTML;
    var ventana = window.open('', '_blank');
    ventana.document.write('<html><head><title>Pendientes - ' + nombreEmpleado + '</title>');
    ventana.document.write('<style>');
    ventana.document.write('body { font-family: Arial, sans-serif; font-size: 12px; margin: 30px; color: #000; line-height: 1.4; }');
    ventana.document.write('.header-institucional { border-bottom: 3px solid #000; padding-bottom: 12px; margin-bottom: 25px; font-weight: bold; font-size: 13px; line-height: 17px; }');
    ventana.document.write('.titulo-principal { text-align: center; font-size: 15px; font-weight: bold; background-color: #f2f2f2; border: 1px solid #000; padding: 8px; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.5px; }');
    ventana.document.write('.meta-info { margin-bottom: 20px; font-size: 12px; }');
    ventana.document.write('.meta-info div { margin-bottom: 4px; }');
    ventana.document.write('.badge { display: inline-block; padding: 2px 6px; font-size: 9px; font-weight: bold; border: 1px solid #000; background: #fff; margin-right: 3px; text-transform: uppercase; border-radius: 3px; }');
    ventana.document.write('.bg-danger { background-color: #000 !important; color: #fff !important; }');
    ventana.document.write('.bg-secondary { background-color: #f2f2f2 !important; color: #000 !important; }');
    ventana.document.write('.bg-dark { background-color: #fff !important; color: #000 !important; }');
    ventana.document.write('.bg-warning { background-color: #fff !important; color: #000 !important; }');
    ventana.document.write('.bg-info { background-color: #fff !important; color: #000 !important; }');
    ventana.document.write('</style></head><body>');
    ventana.document.write('<div class="header-institucional">GOBIERNO DE LA CIUDAD DE MÉXICO<br>COORDINACIÓN DE ADMINISTRACIÓN DE CAPITAL HUMANO</div>');
    ventana.document.write('<div class="titulo-principal">Reporte Interno de Expedientes Pendientes de Atención</div>');
    ventana.document.write('<div class="meta-info">');
    ventana.document.write('<div><strong>Analista Dictaminador Asignado:</strong> ' + nombreEmpleado + '</div>');
    ventana.document.write('<div><strong>Fecha de Generación:</strong> ' + new Date().toLocaleDateString('es-MX') + ' a las ' + new Date().toLocaleTimeString('es-MX') + '</div>');
    ventana.document.write('</div>');
    ventana.document.write('<hr style="border: 0; border-top: 1px solid #000; margin-bottom: 20px;">');
    ventana.document.write('<div>' + contenido + '</div>');
    ventana.document.write('</body></html>');
    ventana.document.close();
    ventana.focus();
    setTimeout(function() {
        ventana.print();
        ventana.close();
    }, 400);
}
</script>
</body>
</html>