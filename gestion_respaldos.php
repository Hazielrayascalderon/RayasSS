<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

$rol = $_SESSION['rol'] ?? '';
$rol_clean = mb_strtolower(trim($rol), 'UTF-8');
if ($rol_clean !== 'administrador') {
    header("Location: tablon.php");
    exit;
}

$carpeta_backups = __DIR__ . '/backups';
$archivos = [];
if (is_dir($carpeta_backups)) {
    foreach (glob($carpeta_backups . '/backup_*.sql*') as $ruta) {
        $archivos[] = [
            'nombre' => basename($ruta),
            'tamano' => filesize($ruta),
            'fecha'  => filemtime($ruta),
        ];
    }
    usort($archivos, function ($a, $b) { return $b['fecha'] <=> $a['fecha']; });
}

function formatoTamano($bytes) {
    if ($bytes >= 1048576) { return round($bytes / 1048576, 2) . ' MB'; }
    if ($bytes >= 1024) { return round($bytes / 1024, 1) . ' KB'; }
    return $bytes . ' B';
}

$mensaje_ok = isset($_GET['ok']) && isset($_GET['archivo']) ? $_GET['archivo'] : null;
$mensaje_error = isset($_GET['error']) ? $_GET['error'] : null;

$url_base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$url_cron = rtrim($url_base, '/') . '/backup_db.php?token=CAMBIA_ESTE_TOKEN_2026';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respaldos de Base de Datos | Control Documental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" crossorigin="anonymous">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
        :root { --guinda-base: #9f2241; --guinda-oscuro: #6f1120; --surface-bg: #f4f6f8; }
        body { background-color: var(--surface-bg); font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-header-guinda { background: linear-gradient(90deg, var(--guinda-base) 0%, var(--guinda-oscuro) 100%); color: white; border-radius: 12px 12px 0 0 !important; }
        .table th { font-size: 0.7rem; letter-spacing: 0.6px; text-transform: uppercase; font-weight: 700; color: #4a5568; background-color: #edf2f7; }
        code.token-box { display: block; background: #1a202c; color: #9ae6b4; padding: 10px 14px; border-radius: 8px; word-break: break-all; font-size: 0.82rem; }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h4 class="fw-bold text-dark m-0"><i class="bi bi-hdd-fill me-2" style="color: var(--guinda-base);"></i>Respaldos de Base de Datos</h4>
        <a href="tablon.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver al Tablón</a>
    </div>

    <?php if ($mensaje_ok) { ?>
    <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>Respaldo generado correctamente: <strong><?php echo htmlspecialchars($mensaje_ok); ?></strong></div>
    <?php } ?>
    <?php if ($mensaje_error) { ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Error al generar el respaldo: <?php echo htmlspecialchars($mensaje_error); ?></div>
    <?php } ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h6 class="fw-bold mb-1">Generar respaldo ahora</h6>
                <p class="text-muted small mb-0">Crea un respaldo completo (estructura + datos) de la base de datos en este momento.</p>
            </div>
            <a href="backup_db.php" class="btn fw-bold text-white" style="background-color: var(--guinda-base);"><i class="bi bi-play-fill me-1"></i>Generar Respaldo</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-2"><i class="bi bi-alarm me-2"></i>Programar respaldos automáticos y periódicos</h6>
            <p class="text-muted small mb-2">
                Este hosting (InfinityFree) no permite ejecutar tareas programadas (cron) directamente desde PHP.
                Para automatizarlo, usa un servicio gratuito externo que visite esta URL en el horario que definas
                (por ejemplo, todos los días a las 3:00 AM):
            </p>
            <code class="token-box mb-2"><?php echo htmlspecialchars($url_cron); ?></code>
            <p class="text-muted small mt-2 mb-0">
                <strong>Pasos recomendados:</strong>
                1) Crea una cuenta gratuita en <strong>cron-job.org</strong> (o similar).
                2) Da de alta un nuevo "cron job" con la URL de arriba y la frecuencia que quieras (diaria, semanal, etc.).
                3) Antes de usarlo, cambia el token <code>CAMBIA_ESTE_TOKEN_2026</code> por uno propio y secreto en <code>backup_db.php</code> (constante <code>BACKUP_TOKEN</code>) y actualiza la URL aquí arriba también.
                Los respaldos con más de <?php echo defined('BACKUP_RETENCION_DIAS') ? BACKUP_RETENCION_DIAS : 30; ?> días se eliminan automáticamente para no llenar el espacio del hosting.
            </p>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header card-header-guinda py-3 border-0">
            <h6 class="m-0 fw-bold text-white"><i class="bi bi-archive-fill me-2"></i>Respaldos disponibles (<?php echo count($archivos); ?>)</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Archivo</th>
                        <th>Fecha</th>
                        <th>Tamaño</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($archivos) === 0) { ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Aún no hay respaldos generados.</td></tr>
                    <?php } ?>
                    <?php foreach ($archivos as $a) { ?>
                    <tr>
                        <td class="ps-4"><i class="bi bi-file-earmark-zip-fill me-2 text-secondary"></i><?php echo htmlspecialchars($a['nombre']); ?></td>
                        <td><?php echo date('d/m/Y H:i', $a['fecha']); ?></td>
                        <td><?php echo formatoTamano($a['tamano']); ?></td>
                        <td class="pe-4 text-end">
                            <a href="descargar_respaldo.php?archivo=<?php echo urlencode($a['nombre']); ?>" class="btn btn-sm btn-outline-dark"><i class="bi bi-download"></i></a>
                            <a href="eliminar_respaldo.php?archivo=<?php echo urlencode($a['nombre']); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar este respaldo? Esta acción no se puede deshacer.');"><i class="bi bi-trash-fill"></i></a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>
