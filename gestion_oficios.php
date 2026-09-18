<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

require_once 'conexion.php';
$db = new Conexion();
$conn = $db->getConexion();

$rol = $_SESSION['rol'] ?? '';
$rol_clean = mb_strtolower(trim($rol), 'UTF-8');
$is_global_admin = in_array($rol_clean, ['administrador', 'coordinación', 'coordinacion', 'dirección general', 'direccion general']);

// Obtener la lista de todos los oficios registrados
$sql = "SELECT * FROM oficios ORDER BY id DESC";
$oficios = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Oficios | Control Documental</title>
    <link rel="icon" href="rayas.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
        :root {
            --guinda-base: #9f2241;
            --guinda-oscuro: #6f1120; 
            --guinda-oro: #bc955c;
            --oro-claro: #ddc9a3;
            --surface-bg: #f4f6f8; 
            --text-main: #2d3748;
        }
        body { background-color: var(--surface-bg); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); }
        .navbar-custom { 
            background: linear-gradient(135deg, var(--guinda-oscuro) 0%, var(--guinda-base) 100%);
            border-bottom: 4px solid var(--guinda-oro);
        }
        .logo-esquina { height: 45px; object-fit: contain; border-radius: 6px; background: rgba(255,255,255,0.1); padding: 2px; }
        .nav-link { color: rgba(255, 255, 255, 0.8) !important; font-weight: 500; font-size: 0.9rem; padding: 0.5rem 0.8rem !important; }
        .nav-link:hover { color: var(--guinda-oro) !important; }
        .nav-link.active { color: #ffffff !important; font-weight: 700; background-color: rgba(255, 255, 255, 0.15); border-bottom: 2px solid var(--guinda-oro); }
        .btn-salir { background-color: rgba(255, 255, 255, 0.1); color: white; border: 1px solid rgba(255,255,255,0.2); transition: all 0.2s; }
        .btn-salir:hover { background-color: #dc2626; border-color: #dc2626; color: white !important; }
        .btn-guinda { background-color: var(--guinda-base); color: white; font-weight: 600; }
        .btn-guinda:hover { background-color: var(--guinda-oscuro); color: white; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom mb-4 sticky-top py-2">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2 me-lg-4" href="tablon.php">
            <img src="rayas.png" alt="Logo" class="logo-esquina">
            <div class="d-flex flex-column lh-1">
                <span class="fw-bold fs-6 text-white">Control Documental</span>
                <span class="fw-medium" style="color: var(--oro-claro); font-size: 0.65rem; text-transform: uppercase;">Gestión Interna</span>
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
                    <li class="nav-item"><a class="nav-link active" href="gestion_oficios.php"><i class="fas fa-file-signature me-1.5"></i>Gestión Oficios</a></li>
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
                        <span class="small fw-bold text-white"><?php echo htmlspecialchars($_SESSION['nombre'] ?? ''); ?></span>
                        <span style="font-size: 0.65rem; color: var(--guinda-oro); font-weight: 700; text-transform: uppercase;"><?php echo htmlspecialchars($rol); ?></span>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-salir rounded p-2 ms-1 d-flex align-items-center justify-content-center" title="Cerrar Sesión" style="width: 36px; height: 36px;">
                    <i class="bi bi-power fs-5"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container px-4 pb-5">
    <div class="row g-4">
        <!-- Formulario de Registro -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0" style="border-top: 4px solid var(--guinda-oro) !important;">
                <div class="card-header bg-white pt-3 border-0">
                    <h5 class="fw-bold m-0" style="color: var(--guinda-base);" id="formTitulo">Registrar Nuevo Oficio</h5>
                </div>
                <div class="card-body">
                    <form action="guardar_oficio.php" method="POST" id="formOficios">
                        <input type="hidden" name="oficio_id" id="oficio_id" value="">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Autoridad Emisora *</label>
                            <input type="text" class="form-control" name="autoridad" id="autoridad" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Número de Oficio *</label>
                            <input type="text" class="form-control" name="numero_oficio" id="numero_oficio" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Fecha del Oficio *</label>
                            <input type="date" class="form-control" name="fecha_oficio" id="fecha_oficio" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Asunto / Temática *</label>
                            <textarea class="form-control" name="asunto" id="asunto" rows="3" required></textarea>
                        </div>

                        <div class="d-flex justify-content-between pt-2">
                            <button type="button" class="btn btn-outline-secondary" onclick="limpiar()"><i class="fas fa-eraser me-1"></i> Limpiar</button>
                            <button type="submit" class="btn text-white fw-bold" style="background-color: var(--guinda-oro);">
                                <i class="fas fa-save me-1"></i> Guardar Registro
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabla con todos los registros existentes -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header text-white fw-bold py-3" style="background: linear-gradient(90deg, var(--guinda-base), var(--guinda-oscuro));">
                    <i class="fas fa-list me-2"></i> Catálogo de Oficios Registrados
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Oficio / Emisor</th>
                                <th>Asunto</th>
                                <th>Fecha</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($oficios)) { ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No hay oficios registrados.</td></tr>
                            <?php } else { 
                                foreach ($oficios as $o) { ?>
                                <tr>
                                    <td class="fw-bold">#<?php echo $o['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($o['numero_oficio']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($o['autoridad']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($o['asunto']); ?></td>
                                    <td><?php echo date("d/m/Y", strtotime($o['fecha_oficio'])); ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" onclick='editar(<?php echo json_encode($o); ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
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

<script>
    function editar(oficio) {
        document.getElementById('oficio_id').value = oficio.id;
        document.getElementById('autoridad').value = oficio.autoridad;
        document.getElementById('numero_oficio').value = oficio.numero_oficio;
        document.getElementById('fecha_oficio').value = oficio.fecha_oficio;
        document.getElementById('asunto').value = oficio.asunto;
        document.getElementById('formTitulo').innerText = "Editar Oficio #" + oficio.id;
    }

    function limpiar() {
        document.getElementById('formOficios').reset();
        document.getElementById('oficio_id').value = "";
        document.getElementById('formTitulo').innerText = "Registrar Nuevo Oficio";
    }

function inicializarReloj() {
    const ahora = new Date();
    const opciones = { weekday: 'short', day: 'numeric', month: 'short' };
    const fecha = ahora.toLocaleDateString('es-MX', opciones).replace('.', '');
    const hora = String(ahora.getHours()).padStart(2, '0') + ':' + String(ahora.getMinutes()).padStart(2, '0');
    const banner = document.getElementById('reloj-banner');
    if (banner) {
        banner.innerHTML = `<i class="bi bi-calendar-event me-2"></i> <span class="text-capitalize me-2">${fecha}</span> <span class="mx-1 opacity-50">|</span> <i class="bi bi-clock ms-2 me-1"></i> ${hora}`;
    }
}
setInterval(inicializarReloj, 1000);
inicializarReloj();
</script>
</body>
</html>