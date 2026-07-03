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

$puede_turnar = $is_global_admin;
$puede_gestionar_empleados = $is_global_admin;

$pendientes = [];
if ($ver_todo) {
    if (!empty($buscar)) {
        $sql_p = "SELECT d.*, a.nombre_area FROM documentos d JOIN areas a ON d.id_area_asignada = a.id_area WHERE d.estado != 'Concluido' AND (d.id_doc = :b_id OR d.titulo LIKE :b_like OR d.num_ingreso LIKE :b_like OR d.enviado_por LIKE :b_like OR d.capturista LIKE :b_like OR d.num_respuesta LIKE :b_like) ORDER BY d.id_doc DESC";
        $stmt_p = $conn->prepare($sql_p);
        $stmt_p->execute([':b_id' => intval($buscar), ':b_like' => '%' . $buscar . '%']);
        $pendientes = $stmt_p->fetchAll();
    } else {
        $sql_p = "SELECT d.*, a.nombre_area FROM documentos d JOIN areas a ON d.id_area_asignada = a.id_area WHERE d.estado != 'Concluido' ORDER BY d.id_doc DESC";
        $pendientes = $conn->query($sql_p)->fetchAll();
    }
} else {
    if (!empty($buscar)) {
        $sql_p = "SELECT d.*, a.nombre_area FROM documentos d JOIN areas a ON d.id_area_asignada = a.id_area WHERE d.id_area_asignada = :area AND d.estado != 'Concluido' AND (d.id_doc = :b_id OR d.titulo LIKE :b_like OR d.num_ingreso LIKE :b_like OR d.num_respuesta LIKE :b_like) ORDER BY d.id_doc DESC";
        $stmt_p = $conn->prepare($sql_p);
        $stmt_p->execute([':area' => $id_area, ':b_id' => intval($buscar), ':b_like' => '%' . $buscar . '%']);
        $pendientes = $stmt_p->fetchAll();
    } else {
        $sql_p = "SELECT d.*, a.nombre_area FROM documentos d JOIN areas a ON d.id_area_asignada = a.id_area WHERE d.id_area_asignada = :area AND d.estado != 'Concluido' ORDER BY d.id_doc DESC";
        $stmt_p = $conn->prepare($sql_p);
        $stmt_p->execute([':area' => $id_area]);
        $pendientes = $stmt_p->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control | Sistema de Gestión</title>
    <link rel="icon" href="rayas.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .form-control, .form-select { border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.9rem; color: #1e293b; transition: 0.2s; }
        .form-control:focus, .form-select:focus { border-color: var(--guinda-oro); box-shadow: 0 0 0 3px rgba(188, 149, 92, 0.2); }
        .table th { font-size: 0.75rem; letter-spacing: 0.8px; text-transform: uppercase; font-weight: 700; color: #4a5568; background-color: #edf2f7; padding: 1rem 1.25rem; }
        
        .badge-priority { font-size: 0.72rem; font-weight: 700; padding: 0.4em 0.8em; border-radius: 6px; }
        .prio-sin-termino { background-color: #f1f5f9; color: #475569; }
        .prio-urgente { background-color: #fef3c7; color: #d97706; }
        .prio-extraurgente { background-color: #fee2e2; color: #dc2626; }
        .prio-con-termino { background-color: #e0f2fe; color: #0284c7; }
        .prio-brevedad { background-color: #f3e8ff; color: #7c3aed; }
        
        .jud-scroll-zone { max-height: 180px; overflow-y: auto; border-radius: 6px; background-color: #f8fafc; border: 1px solid #e2e8f0; }
        .header-coordinacion { background: #fafafa; border-bottom: 3px solid var(--guinda-oro); border-radius: 12px 12px 0 0; padding: 1.25rem 1rem; }
        
        .termino-group { transition: all 0.3s ease-in-out; }
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
                <li class="nav-item"><a class="nav-link active px-3" href="tablon.php"><i class="bi bi-folder2-open me-2"></i>Expedientes</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="finalizados.php"><i class="bi bi-check2-circle me-2"></i>Histórico</a></li>
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

<div class="container-fluid px-4 pb-5">
    <div class="row">
        <?php $clase_columna_form = $puede_turnar ? 'col-12 mb-4' : 'col-lg-4 col-xl-3 mb-4'; ?>
        <div class="<?php echo $clase_columna_form; ?>">
            <div class="card border-0 shadow-sm position-sticky" style="top: 80px; <?php if($puede_turnar) echo 'border: 2px solid var(--guinda-oro) !important; transform: scale(1.01); transition: transform 0.2s;'; ?>">
                <div class="header-coordinacion text-center">
                    <img src="rayas.png" alt="CDMX" style="height: 40px;" class="mb-2">
                    <h6 class="m-0 fw-bold" style="font-size: 13px; letter-spacing: 0.5px; color: var(--guinda-base);">COORDINACIÓN DE ADMINISTRACIÓN DE CAPITAL HUMANO</h6>
                    <small class="text-muted fw-bold" style="font-size: 11px;">REGISTRO DOCUMENTAL INTERNO</small>
                </div>
                <div class="card-body p-4" style="font-size: 13px;">
                    <?php if ($puede_turnar) { ?>
                    <form id="formCapturaOriginal" action="subir_doc.php" method="POST" enctype="multipart/form-data" onsubmit="return validarCaptura(event)">
                        
                        <div class="p-3 rounded mb-3 border" style="background-color: #fafafa; border-color: #e2e8f0;">
                            <div class="row mb-3">
                                <div class="col-12 mb-2">
                                    <label class="form-label text-secondary fw-bold mb-1 small">Nombre Capturista</label>
                                    <input type="text" name="capturista" id="form_capturista" class="form-control form-control-sm border-primary" value="<?php echo htmlspecialchars($_SESSION['nombre']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary fw-bold mb-1 small">Año</label>
                                    <input type="text" class="form-control form-control-sm bg-white" value="<?php echo date('Y'); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary fw-bold mb-1 small">Fecha del Oficio *</label>
                                    <input type="date" name="fecha_documento" id="form_fecha_documento" class="form-control form-control-sm border-primary" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-bold mb-1 small">No. de Oficio / Ingreso *</label>
                                <input type="text" name="num_ingreso" id="form_num_ingreso" class="form-control" placeholder="Ej: S/N o Folio" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-bold mb-1 small">Enviado Por (Autoridad) *</label>
                                <input type="text" name="enviado_por" id="form_enviado_por" class="form-control" placeholder="Autoridad remitente" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold mb-1 small">Asunto Oficial *</label>
                            <textarea name="titulo" id="form_titulo" class="form-control" rows="2" placeholder="Resumen del asunto..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold mb-1 small">Tipo de Documento</label>
                            <select name="tipo_documento" id="form_tipo_documento" class="form-select">
                                <option value="Volante" selected>Volante</option>
                                <option value="Nota Informativa">Nota Informativa</option>
                                <option value="Escrito">Escrito</option>
                                <option value="Otros">Otros</option>
                            </select>
                        </div>

                        <div class="row mb-3 p-2 rounded border align-items-end" style="background-color: #fff9f0; border-color: var(--guinda-oro) !important;">
                            <div class="col-md-12 mb-2" id="div_prioridad_container">
                                <label class="form-label fw-bold mb-1 small" style="color: var(--guinda-base);"><i class="bi bi-clock-history me-1"></i> Atención / Estatus</label>
                                <select name="prioridad" id="form_prioridad" class="form-select border-warning" required>
                                    <option value="Sin término">Sin término</option>
                                    <option value="Urgente" selected>Urgente</option>
                                    <option value="Extraurgente">Extraurgente</option>
                                    <option value="Con término">Con término (Días Hábiles)</option>
                                    <option value="A la brevedad posible">A la brevedad posible</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 termino-group mt-1" style="display: none;">
                                <label class="form-label text-secondary fw-bold mb-1 small">Días Hábiles</label>
                                <div class="input-group">
                                    <input type="number" name="dias_termino" id="form_dias_termino" class="form-control border-info" min="1" placeholder="Ej: 5">
                                    <span class="input-group-text bg-info text-white border-info"><i class="bi bi-calendar-day"></i></span>
                                </div>
                            </div>
                            <div class="col-md-6 termino-group mt-1" style="display: none;">
                                <label class="form-label text-secondary fw-bold mb-1 small">Vencimiento</label>
                                <input type="date" name="fecha_vencimiento" id="form_fecha_vencimiento" class="form-control bg-info bg-opacity-10 text-info fw-bold border-info" readonly>
                            </div>
                        </div>

                        <div class="accordion mb-3 shadow-sm" id="acordeonFolios">
                            <div class="accordion-item border-0 rounded">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed py-2 rounded bg-light text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFolios" style="font-size: 13px;">
                                        <i class="bi bi-hash me-2" style="color: var(--guinda-oro);"></i> Referencias Extra (Folios CACH/DGAF/CJSL)
                                    </button>
                                </h2>
                                <div id="collapseFolios" class="accordion-collapse collapse" data-bs-parent="#acordeonFolios">
                                    <div class="accordion-body p-3 bg-white border mt-1 rounded">
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="form-label text-muted fw-bold mb-1 small">N° CACH</label>
                                                <input type="text" name="trae_cach" id="form_trae_cach" class="form-control form-control-sm border-warning">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted fw-bold mb-1 small">N° DGAF</label>
                                                <input type="text" name="es_dgaf" id="form_es_dgaf" class="form-control form-control-sm border-info">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted fw-bold mb-1 small">N° CJSL</label>
                                                <input type="text" name="folio_cjsl" id="form_folio_cjsl" class="form-control form-control-sm border-secondary">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold mb-1 small">Área / JUD a Turnar *</label>
                            <div class="jud-scroll-zone p-2">
                                <?php
                                if ($is_global_admin) {
                                    $areas = $conn->query("SELECT * FROM areas ORDER BY nombre_area ASC")->fetchAll();
                                    foreach ($areas as $area) {
                                        $id_a = intval($area['id_area']);
                                        echo "<div class='form-check py-1 border-bottom border-light'>";
                                        echo "<input class='form-check-input area-checkbox' type='checkbox' name='id_area[]' value='" . $id_a . "' id='area_" . $id_a . "'>";
                                        echo "<label class='form-check-label small ms-1' for='area_" . $id_a . "'>";
                                        echo "<strong class='text-dark'>" . htmlspecialchars($area['nombre_area']) . "</strong>";
                                        echo "</label></div>";
                                    }
                                } else {
                                    $stmt_a = $conn->prepare("SELECT * FROM areas WHERE id_area = :id_area");
                                    $stmt_a->execute([':id_area' => $id_area]);
                                    $area_propia = $stmt_a->fetch();
                                    if ($area_propia) {
                                        echo "<div class='form-check py-2 bg-light rounded px-3 border'>";
                                        echo "<input class='form-check-input area-checkbox' type='checkbox' name='id_area[]' value='" . intval($id_area) . "' id='area_" . intval($id_area) . "' checked onclick='return false;'>";
                                        echo "<label class='form-check-label small ms-2 text-dark fw-bold' for='area_" . intval($id_area) . "'>";
                                        echo "<i class='bi bi-shield-check-fill me-1' style='color: var(--guinda-base);'></i>" . htmlspecialchars($area_propia['nombre_area']) . " (Turno Local)";
                                        echo "</label></div>";
                                    }
                                }
                                ?>
                            </div>
                        </div>

                        <?php if ($rol === 'JUD') { ?>
                        <div class="mb-3 p-2 bg-light rounded border">
                            <label class="form-label text-dark fw-bold mb-1 small"><i class="bi bi-person-badge-fill me-1" style="color: var(--guinda-base);"></i> Asignar (Operativo Inicial)</label>
                            <select name="operativo_inicial" class="form-select border-primary">
                                <option value="">-- Dejar libre en Jefatura --</option>
                                <?php
                                $stmt_o = $conn->prepare("SELECT * FROM personal WHERE id_area = :area ORDER BY nombre ASC");
                                $stmt_o->execute([':area' => $id_area]);
                                foreach ($stmt_o->fetchAll() as $p) {
                                    echo "<option value='" . htmlspecialchars($p['nombre']) . "'>" . htmlspecialchars($p['nombre']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <?php } ?>

                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold mb-1 small">Instrucción / Informe de Coordinación</label>
                            <textarea name="instruccion" id="form_instruccion" class="form-control font-monospace" style="background-color: #fafafa;" rows="2" placeholder="<?php echo $is_global_admin ? 'Escribe la instrucción...' : 'Notas complementarias...'; ?>"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-secondary fw-bold mb-1 small">Digitalización de Oficio (PDF)</label>
                            <input type="file" name="documento_archivo" class="form-control" accept=".pdf" required>
                        </div>

                        <button type="submit" class="btn btn-guinda w-100 py-2.5 fs-6 shadow-sm"><i class="bi bi-file-earmark-arrow-up-fill me-2"></i>Guardar y Turnar Trámite</button>
                    </form>
                    <?php } else { ?>
                        <div class="alert text-center py-5 m-0 border-0 rounded" style="background-color: #fafafa;">
                            <i class="bi bi-eye-fill d-block mb-3" style="font-size: 3rem; color: var(--guinda-oro);"></i>
                            <h6 class="fw-bold" style="color: var(--guinda-base);">Modo Observación Habilitado</h6>
                            <p class="small text-muted m-0 mt-2">Su perfil dispone de privilegios institucionales de lectura global y monitoreo.</p>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <?php $clase_columna_tabla = $puede_turnar ? 'col-12 mb-4' : 'col-lg-8 col-xl-9 mb-4'; ?>
        <div class="<?php echo $clase_columna_tabla; ?>">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <form method="GET" action="tablon.php" class="flex-grow-1" style="max-width: 500px;">
                    <div class="input-group shadow-sm rounded overflow-hidden border" style="background-color: #fff;">
                        <span class="input-group-text bg-transparent border-0 text-muted ps-3"><i class="bi bi-search"></i></span>
                        <input type="text" name="buscar" class="form-control border-0 bg-transparent px-2 shadow-none py-2" placeholder="Buscar expediente, asunto, folio..." value="<?php echo htmlspecialchars($buscar); ?>">
                        <input type="submit" class="btn btn-guinda rounded-0 px-4 shadow-none" value="Buscar">
                    </div>
                </form>

                <?php if ($is_global_admin) { ?>
                <div>
                    <button class="btn shadow-sm text-white rounded px-4 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#formularioOficio" aria-expanded="false" style="background-color: var(--guinda-oro); font-weight: 600;">
                        <i class="fas fa-file-signature me-2"></i> Gestión de Oficios
                    </button>
                </div>
                <?php } ?>
            </div>

            <?php if ($is_global_admin) { ?>
            <div class="collapse mb-4" id="formularioOficio">
                <div class="card shadow border-0" style="border-top: 4px solid var(--guinda-oro) !important;">
                    <div class="card-header bg-white pb-0 border-0 d-flex justify-content-between align-items-center pt-3 px-4">
                        <div>
                            <h5 class="mb-0" id="tituloFormulario" style="color: var(--guinda-base); font-weight: bold;">Registrar Nuevo Oficio Oficial</h5>
                            <p class="text-muted small mb-0 mt-1">Gestión administrativa interna. Busque un oficio para duplicarlo.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#formularioOficio"></button>
                    </div>
                    <div class="card-body pt-3 px-4 pb-4">
                        <div class="position-relative mb-4">
                            <div class="input-group shadow-sm">
                                <span class="input-group-text bg-white" style="border-color: var(--guinda-oro);"><i class="fas fa-search" style="color: var(--guinda-oro);"></i></span>
                                <input type="text" id="buscadorOficios" class="form-control py-2" style="border-color: var(--guinda-oro);" placeholder="Buscar por número de oficio para autocompletar plantilla...">
                            </div>
                            <ul id="resultadosBusqueda" class="list-group position-absolute w-100 shadow-lg" style="display:none; z-index: 1000; max-height: 200px; overflow-y: auto; border: 1px solid var(--guinda-oro);"></ul>
                        </div>

                        <form action="guardar_oficio.php" method="POST" id="formOficios">
                            <input type="hidden" name="oficio_id" id="oficio_id" value="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary fw-bold small">Autoridad Emisora</label>
                                    <input type="text" class="form-control" id="autoridad" name="autoridad" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-secondary fw-bold small">Número de Oficio</label>
                                    <input type="text" class="form-control" id="numero_oficio" name="numero_oficio" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-secondary fw-bold small">Fecha del Oficio</label>
                                    <input type="date" class="form-control" id="fecha_oficio" name="fecha_oficio" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label text-secondary fw-bold small">Asunto / Temática</label>
                                    <textarea class="form-control" id="asunto" name="asunto" rows="2" required></textarea>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-4 border-top pt-3">
                                <div>
                                    <button type="button" class="btn btn-outline-secondary px-3" onclick="limpiarFormulario()"><i class="fas fa-eraser me-1"></i> Limpiar</button>
                                    <button type="button" class="btn text-white px-3 ms-2 shadow-sm" id="btnDuplicar" style="background-color: #4b6584; display: none;" onclick="prepararDuplicado()"><i class="fas fa-copy me-1"></i> Usar Plantilla</button>
                                </div>
                                <button type="submit" class="btn px-4 shadow-sm" id="btnGuardar" style="background-color: var(--guinda-oro); color: white; font-weight: bold;">Guardar Registro</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php } ?>

            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-header card-header-guinda py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2 px-4">
                    <h6 class="m-0 fw-bold text-white fs-6"><i class="bi bi-folder-symlink-fill me-2" style="color: var(--oro-claro);"></i>Bandeja de Expedientes Activos</h6>
                    <div class="btn-group btn-group-sm rounded shadow-sm bg-black bg-opacity-25 border border-white border-opacity-25 p-1">
                        <?php if ($puede_gestionar_empleados) { ?><button type="button" class="btn text-white fw-bold border-0" data-bs-toggle="modal" data-bs-target="#modalGestionPersonal"><i class="bi bi-people-fill me-1" style="color: var(--oro-claro);"></i> Personal</button><?php } ?>
                        <a href="reporte_pendientes.php" target="_blank" class="btn fw-bold border-0 text-white" style="background-color: rgba(255,255,255,0.15);"><i class="bi bi-file-earmark-text-fill me-1" style="color: var(--oro-claro);"></i> Pendientes</a>
                        <a href="reporte_rendimiento.php" target="_blank" class="btn fw-bold border-0 text-white" style="background-color: rgba(255,255,255,0.15);"><i class="bi bi-graph-up-arrow me-1" style="color: var(--oro-claro);"></i> Rendimiento</a>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 10%">ID</th>
                                <th style="width: 55%">Recepción e Instrucción</th>
                                <th style="width: 18%">Área Asignada</th>
                                <th class="text-end pe-4" style="width: 17%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pendientes) == 0) { ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted fw-medium"><i class="bi bi-inbox fs-1 d-block mb-2 text-black-50"></i>No hay registros pendientes de atención.</td></tr>
                            <?php } else { 
                                foreach ($pendientes as $doc) { 
                                    $prio = isset($doc['prioridad']) ? $doc['prioridad'] : 'Sin término';
                                    $prio_class = 'prio-sin-termino';
                                    if($prio == 'Urgente') { $prio_class = 'prio-urgente'; }
                                    if($prio == 'Extraurgente') { $prio_class = 'prio-extraurgente'; }
                                    if($prio == 'Con término') { $prio_class = 'prio-con-termino'; }
                                    if($prio == 'A la brevedad posible') { $prio_class = 'prio-brevedad'; }
                                    
                                    $is_rejected = ($doc['estado'] === 'Rechazado');
                                    $is_awaiting = ($doc['estado'] === 'Resuelto_Para_Validar');
                                    $row_bg = $is_rejected ? 'style="background-color: #fde8e8;"' : '';
                                ?>
                                <tr <?php echo $row_bg; ?>>
                                    <td class="fw-bold text-secondary ps-4">#<?php echo $doc['id_doc']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                            <span class="badge <?php echo $prio_class; ?>"><?php echo $prio; ?></span>
                                            
                                            <?php if ($prio == 'Con término' && !empty($doc['fecha_vencimiento'])) { ?>
                                                <span class="badge bg-info text-dark fw-bold border border-info"><i class="bi bi-calendar-x-fill me-1"></i> Vence: <?php echo date("d/m/Y", strtotime($doc['fecha_vencimiento'])); ?></span>
                                            <?php } ?>

                                            <span class="badge bg-light text-dark border border-secondary px-2"><i class="bi bi-hash text-muted"></i> <?php echo htmlspecialchars($doc['num_ingreso']); ?></span>
                                            
                                            <?php if ($is_rejected) { ?>
                                                <span class="badge bg-danger text-white"><i class="bi bi-x-octagon-fill me-1"></i> RECHAZADO</span>
                                            <?php } elseif ($is_awaiting) { ?>
                                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> ESPERANDO VALIDACIÓN</span>
                                            <?php } ?>
                                        </div>
                                        <div class="text-dark fw-bold mb-1" style="font-size:13.5px;"><?php echo htmlspecialchars($doc['titulo']); ?></div>
                                        <div class="small text-muted mb-1"><strong>De:</strong> <?php echo htmlspecialchars($doc['enviado_por']); ?> | <strong>Capturó:</strong> <?php echo htmlspecialchars($doc['capturista'] ?: 'Sistema'); ?></div>
                                        
                                        <div class="small text-dark mb-1 mt-2">
                                            <i class="bi bi-person-fill text-primary"></i> <strong>Operativo:</strong> 
                                            <span class="badge px-2" style="background-color: #e6f0ff; color: #0d6efd; border: 1px solid #b3d4ff;">
                                                <?php echo htmlspecialchars($doc['operativo'] ?: 'Jefatura Local / Sin asignar'); ?>
                                            </span>
                                        </div>

                                        <?php if ($is_awaiting && !empty($doc['num_respuesta'])) { ?>
                                            <div class="p-2 mt-2 rounded bg-light border border-info border-start border-start-3" style="font-size: 11.5px; border-left-width: 3px !important; border-left-color: #0dcaf0 !important;">
                                                <div class="text-info fw-bold mb-1"><i class="bi bi-reply-all-fill me-1"></i> RESPUESTA DE LA JUD</div>
                                                <div><strong>Oficio Respuesta:</strong> <?php echo htmlspecialchars($doc['num_respuesta']); ?></div>
                                                <?php if(!empty($doc['comentarios_abogado'])) { ?>
                                                    <div class="text-muted mt-1"><strong>Notas:</strong> <?php echo htmlspecialchars($doc['comentarios_abogado']); ?></div>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark-emphasis border p-2 rounded text-wrap w-100 text-start fw-medium"><i class="bi bi-building me-1 text-secondary"></i><?php echo $doc['nombre_area']; ?></span></td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end flex-wrap gap-1">
                                            <a href="descargar_doc.php?id=<?php echo $doc['id_doc']; ?>&tipo=ingreso" target="_blank" class="btn btn-sm btn-outline-secondary px-2" title="PDF Original"><i class="bi bi-file-earmark-pdf-fill fs-6"></i></a>
                                            
                                            <?php if (!empty($doc['num_respuesta'])) { ?>
                                                <a href="descargar_doc.php?id=<?php echo $doc['id_doc']; ?>&tipo=respuesta" target="_blank" class="btn btn-sm btn-outline-success px-2" title="PDF Respuesta"><i class="bi bi-file-earmark-check-fill fs-6"></i></a>
                                            <?php } ?>

                                            <a href="imprimir_acuse.php?id=<?php echo $doc['id_doc']; ?>" target="_blank" class="btn btn-sm btn-dark px-2" title="Imprimir Acuse"><i class="bi bi-printer-fill fs-6"></i></a>
                                            
                                            <?php if ($puede_turnar) { ?>
                                                <button type="button" class="btn btn-sm btn-info text-white px-2" title="Duplicar Información" onclick='clonarFormulario(<?php echo json_encode($doc, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                                <i class="bi bi-copy fs-6"></i>
                                            </button>
                                            <?php } ?>

                                            <?php if ($is_global_admin && $is_awaiting && !$is_observer) { ?>
                                                <a href="validar_doc.php?id=<?php echo $doc['id_doc']; ?>&accion=aprobar" class="btn btn-sm btn-success px-2" title="Aprobar Trámite" onclick="return confirm('¿Validar conclusión de este trámite? El folio se archivará definitivamente.');"><i class="bi bi-shield-fill-check fs-6"></i></a>
                                                <a href="validar_doc.php?id=<?php echo $doc['id_doc']; ?>&accion=rechazar" class="btn btn-sm btn-danger px-2" title="Rechazar Respuesta" onclick="return confirm('¿Rechazar respuesta? El folio se marcará en rojo para corrección.');"><i class="bi bi-shield-fill-x fs-6"></i></a>
                                            <?php } ?>

                                            <?php if (($doc['id_area_asignada'] == $id_area || $is_global_admin) && !$is_observer && !$is_awaiting) { ?>
                                                <button class="btn btn-sm btn-success text-white px-2" data-bs-toggle="modal" data-bs-target="#modalResponder<?php echo $doc['id_doc']; ?>" title="Finalizar Trámite"><i class="bi bi-check-lg fs-6"></i></button>
                                            <?php } ?>

                                            <?php if ($is_global_admin && !$is_observer) { ?>
                                                <button class="btn btn-sm btn-warning text-white px-2" data-bs-toggle="modal" data-bs-target="#modalEditarJUD<?php echo $doc['id_doc']; ?>" title="Editar / CRUD"><i class="bi bi-pencil-square fs-6"></i></button>
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
    </div>
</div>

<?php 
foreach ($pendientes as $doc) { 
?>
    <?php if ($is_global_admin && !$is_observer) { ?>
    <div class="modal fade" id="modalEditarJUD<?php echo $doc['id_doc']; ?>" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-light border-bottom">
            <h5 class="modal-title fw-bold text-dark d-flex align-items-center" style="font-size: 15px;"><i class="bi bi-pencil-square text-warning me-2 fs-5"></i>CRUD - Edición Técnica Avanzada (Folio #<?php echo $doc['id_doc']; ?>)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form action="editar_doc.php" method="POST">
              <div class="modal-body" style="font-size: 13px;">
                  <input type="hidden" name="id_doc" value="<?php echo $doc['id_doc']; ?>">
                  
                  <div class="row mb-3">
                      <div class="col-md-6">
                          <label class="form-label fw-bold text-secondary">No. de Oficio / Ingreso</label>
                          <input type="text" name="num_ingreso" class="form-control" value="<?php echo htmlspecialchars($doc['num_ingreso']); ?>" required>
                      </div>
                      <div class="col-md-6">
                          <label class="form-label fw-bold text-secondary">Fecha del Oficio</label>
                          <input type="date" name="fecha_documento" class="form-control" value="<?php echo htmlspecialchars($doc['fecha_documento']); ?>" required>
                      </div>
                  </div>

                  <div class="mb-3">
                      <label class="form-label fw-bold text-secondary">Asunto / Título Oficial</label>
                      <textarea name="titulo" class="form-control" rows="2" required><?php echo htmlspecialchars($doc['titulo']); ?></textarea>
                  </div>

                  <div class="row mb-3">
                      <div class="col-md-4">
                          <label class="form-label fw-bold text-secondary">Folio CACH</label>
                          <input type="text" name="trae_cach" class="form-control" value="<?php echo htmlspecialchars($doc['trae_cach']); ?>">
                      </div>
                      <div class="col-md-4">
                          <label class="form-label fw-bold text-secondary">Folio DGAF</label>
                          <input type="text" name="es_dgaf" class="form-control" value="<?php echo htmlspecialchars($doc['es_dgaf']); ?>">
                      </div>
                      <div class="col-md-4">
                          <label class="form-label fw-bold text-secondary">Folio CJSL</label>
                          <input type="text" name="folio_cjsl" class="form-control" value="<?php echo htmlspecialchars($doc['folio_cjsl']); ?>">
                      </div>
                  </div>

                  <div class="row mb-3">
                      <div class="col-md-6">
                          <label class="form-label fw-bold text-secondary">Enviado Por</label>
                          <input type="text" name="enviado_por" class="form-control" value="<?php echo htmlspecialchars($doc['enviado_por']); ?>" required>
                      </div>
                      <div class="col-md-6">
                          <label class="form-label fw-bold text-secondary">Capturista</label>
                          <input type="text" name="capturista" class="form-control" value="<?php echo htmlspecialchars($doc['capturista']); ?>" required>
                      </div>
                  </div>

                  <div class="row mb-3">
                      <div class="col-md-6">
                          <label class="form-label fw-bold text-secondary">Tipo de Documento</label>
                          <select name="tipo_documento" class="form-select">
                              <option value="Volante" <?php echo ($doc['tipo_documento'] === 'Volante') ? 'selected':''; ?>>Volante</option>
                              <option value="Nota Informativa" <?php echo ($doc['tipo_documento'] === 'Nota Informativa') ? 'selected':''; ?>>Nota Informativa</option>
                              <option value="Escrito" <?php echo ($doc['tipo_documento'] === 'Escrito') ? 'selected':''; ?>>Escrito</option>
                              <option value="Otros" <?php echo ($doc['tipo_documento'] === 'Otros') ? 'selected':''; ?>>Otros</option>
                          </select>
                      </div>
                      <div class="col-md-6">
                          <label class="form-label fw-bold text-secondary">Atención / Prioridad</label>
                          <select name="prioridad" class="form-select">
                              <option value="Sin término" <?php echo ($doc['prioridad'] === 'Sin término') ? 'selected':''; ?>>Sin término</option>
                              <option value="Urgente" <?php echo ($doc['prioridad'] === 'Urgente') ? 'selected':''; ?>>Urgente</option>
                              <option value="Extraurgente" <?php echo ($doc['prioridad'] === 'Extraurgente') ? 'selected':''; ?>>Extraurgente</option>
                              <option value="Con término" <?php echo ($doc['prioridad'] === 'Con término') ? 'selected':''; ?>>Con término</option>
                              <option value="A la brevedad posible" <?php echo ($doc['prioridad'] === 'A la brevedad posible') ? 'selected':''; ?>>A la brevedad posible</option>
                          </select>
                      </div>
                  </div>

                  <div class="mb-3">
                      <label class="form-label fw-bold text-secondary">Instrucción de Coordinación</label>
                      <textarea name="instruccion" class="form-control font-monospace" rows="2"><?php echo htmlspecialchars($doc['instruccion'] ?? ''); ?></textarea>
                  </div>

                  <div class="p-3 bg-light rounded border">
                      <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-diagram-3-fill me-1" style="color: var(--guinda-base);"></i>OPERATIVO ASIGNADO INTERNO</h6>
                      <div class="row">
                          <div class="col-md-6">
                              <label class="form-label small text-muted mb-1 fw-bold">Seleccionar Empleado</label>
                              <select name="operativo" class="form-select">
                                  <option value="">-- Jefatura Local --</option>
                                  <?php
                                  $stmt_p = $conn->prepare("SELECT * FROM personal WHERE id_area = :area ORDER BY nombre ASC");
                                  $stmt_p->execute([':area' => $doc['id_area_asignada']]);
                                  foreach ($stmt_p->fetchAll() as $p) {
                                      $selected = ($doc['operativo'] === $p['nombre']) ? 'selected' : '';
                                      echo "<option value='" . htmlspecialchars($p['nombre']) . "' $selected>" . htmlspecialchars($p['nombre']) . "</option>";
                                  }
                                  ?>
                              </select>
                          </div>
                          <div class="col-md-6">
                              <label class="form-label small text-muted mb-1 fw-bold">Fecha de Entrega Interna</label>
                              <input type="datetime-local" name="fecha_entrega_operativo" class="form-control" value="<?php echo !empty($doc['fecha_entrega_operativo']) ? date('Y-m-d\TH:i', strtotime($doc['fecha_entrega_operativo'])) : ''; ?>">
                          </div>
                      </div>
                  </div>
              </div>
              <div class="modal-footer bg-light border-top">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  <button type="submit" class="btn btn-warning text-dark fw-bold">Guardar Cambios (CRUD)</button>
              </div>
          </form>
        </div>
      </div>
    </div>
    <?php } ?>

    <?php if (($doc['id_area_asignada'] == $id_area || $is_global_admin) && !$is_observer) { ?>
    <div class="modal fade" id="modalResponder<?php echo $doc['id_doc']; ?>" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-light border-bottom">
            <h5 class="modal-title fw-bold text-dark d-flex align-items-center" style="font-size: 15px;"><i class="bi bi-check-circle-fill text-success me-2 fs-5"></i>Enviar Trámite a Validación de Dirección (Folio #<?php echo $doc['id_doc']; ?>)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form action="finalizar_doc.php" method="POST" enctype="multipart/form-data">
              <div class="modal-body" style="font-size: 13px;">
                  <input type="hidden" name="id_doc" value="<?php echo $doc['id_doc']; ?>">
                  
                  <div class="mb-3">
                      <label class="form-label fw-bold text-secondary">Fecha en la que está saliendo la respuesta *</label>
                      <input type="date" name="fecha_salida_respuesta" class="form-control border-success" required>
                  </div>

                  <div class="mb-3 p-2 bg-success bg-opacity-10 border border-success border-opacity-25 rounded">
                      <label class="form-label fw-bold text-success mb-1"><i class="bi bi-person-check-fill"></i> Analista que concluye el asunto:</label>
                      <select name="operativo" class="form-select border-success" required>
                          <?php
                          $stmt_p = $conn->prepare("SELECT * FROM personal WHERE id_area = :area ORDER BY nombre ASC");
                          $stmt_p->execute([':area' => $doc['id_area_asignada']]);
                          echo "<option value='Jefatura de Unidad Departamental'".($doc['operativo'] == 'Jefatura de Unidad Departamental' || empty($doc['operativo']) ? ' selected':'').">Jefatura de Unidad Departamental</option>";
                          foreach ($stmt_p->fetchAll() as $p) {
                              $selected = ($doc['operativo'] === $p['nombre']) ? 'selected' : '';
                              echo "<option value='" . htmlspecialchars($p['nombre']) . "' $selected>" . htmlspecialchars($p['nombre']) . "</option>";
                          }
                          ?>
                      </select>
                  </div>
                  <div class="mb-3">
                      <label class="form-label fw-bold text-secondary">Número de Oficio de Respuesta Oficial</label>
                      <input type="text" name="num_respuesta" class="form-control" placeholder="Ej: RESP-2026-ABC" required>
                  </div>
                  <div class="mb-3">
                      <label class="form-label fw-bold text-secondary">Archivo PDF de Contestación</label>
                      <input type="file" name="archivo_respuesta" class="form-control" accept=".pdf" required>
                  </div>
                  <div class="mb-3">
                      <label class="form-label fw-bold text-secondary">Observaciones finales para el Acuse</label>
                      <textarea name="comentarios_abogado" class="form-control" rows="3" placeholder="Detalles de la resolución..."></textarea>
                  </div>
              </div>
              <div class="modal-footer bg-light border-top">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  <button type="submit" class="btn btn-success fw-bold px-4">Subir y Turnar a Revisión</button>
              </div>
          </form>
        </div>
      </div>
    </div>
    <?php } ?>
<?php } ?>

<?php if ($puede_gestionar_empleados && !$is_observer) { ?>
    <div class="modal fade" id="modalGestionPersonal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center" style="font-size: 16px;"><i class="bi bi-people-fill me-2 fs-5" style="color: var(--guinda-base);"></i>Personal Operativo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="guardar_personal.php" method="POST" class="mb-4">
                        <input type="hidden" name="accion" value="alta">
                        <?php if ($is_global_admin) { ?>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Seleccionar JUD / Área Destino</label>
                                <select name="id_area" class="form-select border-primary" required>
                                    <?php
                                    $todas_areas = $conn->query("SELECT * FROM areas ORDER BY nombre_area ASC")->fetchAll();
                                    foreach($todas_areas as $ta) {
                                        echo "<option value='".$ta['id_area']."'>".htmlspecialchars($ta['nombre_area'])."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php } else { ?>
                            <input type="hidden" name="id_area" value="<?php echo $id_area; ?>">
                        <?php } ?>
                        <label class="form-label small fw-bold text-muted">Nombre del Nuevo Empleado</label>
                        <div class="input-group border rounded overflow-hidden">
                            <input type="text" name="nombre_empleado" class="form-control border-0 shadow-none px-3" required placeholder="Ej: Lic. Alejandro Pérez">
                            <button type="submit" class="btn rounded-0 px-4 text-white fw-bold" style="background-color: var(--guinda-base);">Dar de Alta</button>
                        </div>
                    </form>
                    <label class="form-label small fw-bold text-muted mb-2">Plantilla Activa</label>
                    <div style="max-height: 250px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                        <ul class="list-group list-group-flush border-0">
                            <?php
                            if ($is_global_admin) {
                                $stmt_list = $conn->query("SELECT p.*, a.nombre_area FROM personal p JOIN areas a ON p.id_area = a.id_area ORDER BY a.nombre_area ASC, p.nombre ASC");
                            } else {
                                $stmt_list = $conn->prepare("SELECT p.*, a.nombre_area FROM personal p JOIN areas a ON p.id_area = a.id_area WHERE p.id_area = :area ORDER BY p.nombre ASC");
                                $stmt_list->execute([':area' => $id_area]);
                            }
                            $empleados = $stmt_list->fetchAll();
                            if(count($empleados) == 0) {
                                echo "<li class='list-group-item small text-muted text-center py-4 bg-light border-0'>No hay personal operativo registrado.</li>";
                            } else {
                                $current_area = '';
                                foreach($empleados as $emp) {
                                    if ($is_global_admin && $current_area !== $emp['nombre_area']) {
                                        $current_area = $emp['nombre_area'];
                                        echo "<li class='list-group-item bg-light small fw-bold text-secondary border-bottom py-1 px-3'>".htmlspecialchars($current_area)."</li>";
                                    }
                                    echo "<li class='list-group-item d-flex justify-content-between align-items-center small bg-white text-dark py-2 px-3 border-bottom'>";
                                    echo "<span><i class='bi bi-person-fill text-secondary me-2'></i>" . htmlspecialchars($emp['nombre']) . "</span>";
                                    echo "<form action='guardar_personal.php' method='POST' onsubmit='return confirm(\"¿Baja de empleado?\");' class='m-0'>";
                                    echo "<input type='hidden' name='id_personal' value='".$emp['id_personal']."'>";
                                    echo "<input type='hidden' name='accion' value='baja'>";
                                    echo "<button type='submit' class='btn btn-link text-danger p-0 m-0 border-0 shadow-none'><i class='bi bi-trash3-fill fs-6'></i></button>";
                                    echo "</form>";
                                    echo "</li>";
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<div id="modalError" class="modal-error" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; justify-content: center; align-items: center;">
    <div class="modal-content text-center shadow-lg" style="background: #fff; padding: 30px; border-radius: 12px; border-top: 6px solid #e74c3c; width: 90%; max-width: 400px;">
        <h4 style="color: #e74c3c; margin-top: 0; font-weight: bold;"><i class="bi bi-exclamation-triangle-fill me-2"></i>Faltan datos prioritarios</h4>
        <p class="text-muted">Los siguientes campos son obligatorios:</p>
        <ul class="text-start text-secondary mb-4" style="font-size: 14px;">
            <li>Número de ingreso</li>
            <li>Autoridad (Enviado por)</li>
            <li>Asunto / Título</li>
            <li>Fecha del oficio</li>
            <li>Área / JUD a Turnar</li>
        </ul>
        <button type="button" class="btn btn-danger w-100 fw-bold" onclick="document.getElementById('modalError').style.display='none'">Entendido</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
    function validarCaptura(e) {
        const num = document.getElementById('form_num_ingreso').value.trim();
        const titulo = document.getElementById('form_titulo').value.trim();
        const autoridad = document.getElementById('form_enviado_por').value.trim();
        const fecha = document.getElementById('form_fecha_documento').value.trim();
        
        let checkedAreas = 0;
        const areas = document.querySelectorAll('.area-checkbox');
        if (areas.length > 0) {
            for (let i = 0; i < areas.length; i++) {
                if (areas[i].checked) checkedAreas++;
            }
        } else {
            checkedAreas = 1;
        }

        if (!num || !titulo || !autoridad || !fecha || checkedAreas === 0) {
            e.preventDefault();
            document.getElementById('modalError').style.display = 'flex';
            return false;
        }
        return true;
    }

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

    document.addEventListener('DOMContentLoaded', function() {
        const selectPrioridad = document.getElementById('form_prioridad');
        const inputDias = document.getElementById('form_dias_termino');
        const inputVencimiento = document.getElementById('form_fecha_vencimiento');
        const inputFechaDoc = document.getElementById('form_fecha_documento');
        
        const containersTermino = document.querySelectorAll('.termino-group');

        function calcularVencimiento() {
            if (selectPrioridad.value === 'Con término') {
                containersTermino.forEach(el => el.style.display = 'block');

                let dias = parseInt(inputDias.value);
                let fechaBaseVal = inputFechaDoc.value;

                if (!isNaN(dias) && dias > 0 && fechaBaseVal) {
                    let parts = fechaBaseVal.split('-');
                    let currentDate = new Date(parts[0], parts[1] - 1, parts[2]); 
                    let daysAdded = 0;
                    while (daysAdded < dias) {
                        currentDate.setDate(currentDate.getDate() + 1);
                        if (currentDate.getDay() !== 0 && currentDate.getDay() !== 6) {
                            daysAdded++;
                        }
                    }

                    let yyyy = currentDate.getFullYear();
                    let mm = String(currentDate.getMonth() + 1).padStart(2, '0');
                    let dd = String(currentDate.getDate()).padStart(2, '0');
                    
                    inputVencimiento.value = `${yyyy}-${mm}-${dd}`;
                } else {
                    inputVencimiento.value = '';
                }
            } else {
                containersTermino.forEach(el => el.style.display = 'none');
                inputDias.value = '';
                inputVencimiento.value = '';
            }
        }

        selectPrioridad.addEventListener('change', calcularVencimiento);
        inputDias.addEventListener('input', calcularVencimiento);
        inputFechaDoc.addEventListener('change', calcularVencimiento);
        
        calcularVencimiento();
    });

    function clonarFormulario(doc) {
        if(document.getElementById('form_titulo')) {
            document.getElementById('form_titulo').value = doc.titulo || '';
            document.getElementById('form_num_ingreso').value = doc.num_ingreso || '';
            document.getElementById('form_fecha_documento').value = doc.fecha_documento || '<?php echo date('Y-m-d'); ?>';
            document.getElementById('form_prioridad').value = doc.prioridad || 'Urgente';
            document.getElementById('form_trae_cach').value = doc.trae_cach || '';
            document.getElementById('form_es_dgaf').value = doc.es_dgaf || '';
            document.getElementById('form_folio_cjsl').value = doc.folio_cjsl || '';
            document.getElementById('form_enviado_por').value = doc.enviado_por || '';
            document.getElementById('form_tipo_documento').value = doc.tipo_documento || 'Volante';
            document.getElementById('form_instruccion').value = doc.instruccion || '';
            document.getElementById('form_capturista').value = doc.capturista || '';
            
            document.getElementById('form_prioridad').dispatchEvent(new Event('change'));
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    <?php if ($is_global_admin) { ?>
    document.addEventListener('DOMContentLoaded', function() {
        const buscador = document.getElementById('buscadorOficios');
        const resultados = document.getElementById('resultadosBusqueda');
        if(buscador) {
            buscador.addEventListener('input', function() {
                let query = this.value;
                if(query.length < 2) {
                    resultados.style.display = 'none';
                    return;
                }
                fetch(`buscar_oficio.php?q=${query}`)
                    .then(response => response.json())
                    .then(data => {
                        resultados.innerHTML = '';
                        if(data.length > 0) {
                            data.forEach(oficio => {
                                let li = document.createElement('li');
                                li.className = 'list-group-item list-group-item-action cursor-pointer';
                                li.style.cursor = 'pointer';
                                li.innerHTML = `<strong>${oficio.numero_oficio}</strong> - ${oficio.autoridad}`;
                                li.onclick = () => cargarOficio(oficio);
                                resultados.appendChild(li);
                            });
                            resultados.style.display = 'block';
                        } else {
                            resultados.style.display = 'none';
                        }
                    });
            });

            document.addEventListener('click', function(e) {
                if(e.target !== buscador) resultados.style.display = 'none';
            });
        }
    });

    function cargarOficio(oficio) {
        document.getElementById('oficio_id').value = oficio.id;
        document.getElementById('autoridad').value = oficio.autoridad;
        document.getElementById('numero_oficio').value = oficio.numero_oficio;
        document.getElementById('fecha_oficio').value = oficio.fecha_oficio;
        document.getElementById('asunto').value = oficio.asunto;
        document.getElementById('tituloFormulario').innerText = "Editando Oficio Oficial";
        document.getElementById('buscadorOficios').value = "";
        document.getElementById('btnDuplicar').style.display = "inline-block";
        document.getElementById('btnGuardar').innerText = "Actualizar Cambios";
    }

    function prepararDuplicado() {
        document.getElementById('oficio_id').value = "";
        document.getElementById('numero_oficio').value = "";
        document.getElementById('numero_oficio').focus();
        document.getElementById('tituloFormulario').innerText = "Nuevo Oficio (Duplicado)";
        document.getElementById('btnDuplicar').style.display = "none";
        document.getElementById('btnGuardar').innerText = "Guardar Nuevo Registro";
    }

    function limpiarFormulario() {
        document.getElementById('formOficios').reset();
        document.getElementById('oficio_id').value = "";
        document.getElementById('tituloFormulario').innerText = "Registrar Nuevo Oficio Oficial";
        document.getElementById('btnDuplicar').style.display = "none";
        document.getElementById('btnGuardar').innerText = "Guardar Registro";
    }
    <?php } ?>
</script>
</body>
</html>