<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    exit("Acceso denegado");
}

require_once 'conexion.php';
$db = new Conexion();
$conn = $db->getConexion();

$id_area = $_SESSION['id_area'];
$rol = $_SESSION['rol'];
$is_admin = in_array($rol, ['Administrador', 'Direccion General', 'Coordinación']);

// Query que recalcula el rendimiento real del personal operativo de la JUD sacando los resueltos
$sql = "SELECT nombre, 
        (SELECT COUNT(*) FROM documentos WHERE operativo = personal.nombre AND estado != 'Resuelto_Para_Validar') as total_pendientes,
        (SELECT COUNT(*) FROM documentos WHERE operativo = personal.nombre AND estado = 'Resuelto_Para_Validar') as total_concluidos
        FROM personal";

if (!$is_admin) {
    $sql .= " WHERE id_area = :area";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':area' => $id_area]);
    $rendimiento = $stmt->fetchAll();
} else {
    $rendimiento = $conn->query($sql)->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Métricas de Rendimiento Departamental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f8fafc; font-family: sans-serif; padding:30px; }
        .card-metrica { background:#fff; border-radius:12px; border:1px solid #e2e8f0; padding:20px; margin-bottom:15px; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="text-danger">●</i> Rendimiento Técnico del Personal Operativo (Actualizado)</h4>
        <button class="btn btn-sm btn-dark" onclick="window.print();">Imprimir Gráfica Interna</button>
    </div>
    
    <div class="row">
        <?php foreach ($rendimiento as $r) { 
            $tot = $r['total_pendientes'] + $r['total_concluidos'];
            $pct = $tot > 0 ? round(($r['total_concluidos'] / $tot) * 100) : 0;
            $color = $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger');
        ?>
            <div class="col-md-6">
                <div class="card-metrica shadow-sm">
                    <div class="d-flex justify-content-between font-weight-bold mb-1">
                        <strong><?php echo htmlspecialchars($r['nombre']); ?></strong>
                        <span class="badge bg-secondary"><?php echo $pct; ?>% Eficiencia</span>
                    </div>
                    <div class="text-muted small mb-2">
                        Pendientes activos: <span class="text-danger fw-bold"><?php echo $r['total_pendientes']; ?></span> | 
                        Concluidos: <span class="text-success fw-bold"><?php echo $r['total_concluidos']; ?></span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar <?php echo $color; ?>" style="width: <?php echo $pct; ?>%"></div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
</body>
</html>