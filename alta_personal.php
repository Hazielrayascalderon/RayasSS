<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

$rol = trim($_SESSION['rol'] ?? '');
$rol_clean = mb_strtolower($rol, 'UTF-8');
$es_coordinacion = in_array($rol_clean, ['coordinación', 'coordinacion']);
$es_direccion_general = in_array($rol_clean, ['dirección general', 'direccion general']);
$is_global_admin = in_array($rol_clean, ['administrador', 'coordinación', 'coordinacion', 'dirección general', 'direccion general']);

if (!$es_coordinacion && !$es_direccion_general) {
    header("Location: tablon.php");
    exit;
}

require_once 'conexion.php';
$db = new Conexion();
$conn = $db->getConexion();

$areas = $conn->query("SELECT * FROM areas ORDER BY nombre_area ASC")->fetchAll(PDO::FETCH_ASSOC);
$id_area_preseleccionada = isset($_GET['id_area']) ? intval($_GET['id_area']) : 0;
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dar de Alta Personal | Control Documental</title>
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
        .card-custom { border-top: 4px solid var(--guinda-base); border-radius: 14px; }
        .btn-guinda { background-color: var(--guinda-base); color: white; font-weight: 600; border: none; }
        .btn-guinda:hover { background-color: var(--guinda-oscuro); color: white; }
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

<script>
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

<div class="container py-2 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">

            <div class="mb-3">
                <a href="empleados_tareas.php" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left me-1"></i>Volver a Tareas</a>
            </div>

            <?php if ($error === 'nombre_vacio') { ?>
                <div class="alert alert-danger border-0 shadow-sm py-2 mb-3"><i class="bi bi-exclamation-triangle-fill me-1"></i> El nombre completo es obligatorio.</div>
            <?php } elseif ($error === 'area_invalida') { ?>
                <div class="alert alert-danger border-0 shadow-sm py-2 mb-3"><i class="bi bi-exclamation-triangle-fill me-1"></i> Selecciona un área/JUD válida.</div>
            <?php } ?>

            <div class="card shadow-sm border-0 card-custom">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-person-plus-fill me-2" style="color: var(--guinda-base);"></i>Dar de Alta Personal Operativo</h5>
                    <p class="text-muted small mb-4">Registra un nuevo analista dictaminador dentro de una Jefatura de Unidad Departamental (JUD).</p>

                    <form method="POST" action="guardar_personal.php">
                        <input type="hidden" name="accion" value="alta">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Nombre Completo *</label>
                            <input type="text" name="nombre_empleado" class="form-control" placeholder="Ej. Pérez Gómez Juan" required autofocus>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-secondary">Área / JUD *</label>
                            <select name="id_area" class="form-select" required>
                                <option value="">-- Selecciona un área --</option>
                                <?php foreach ($areas as $area) { ?>
                                    <option value="<?php echo intval($area['id_area']); ?>" <?php echo ($id_area_preseleccionada === intval($area['id_area'])) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($area['nombre_area']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="empleados_tareas.php" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-guinda shadow-sm"><i class="bi bi-check-lg me-1"></i>Guardar Personal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
