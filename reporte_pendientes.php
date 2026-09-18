<?php
session_start();

// 1. Verificar sesión activa
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

// 2. Control de rol flexible (comprueba por nombre o por ID)
$rol_usuario = $_SESSION['rol'] ?? $_SESSION['tipo_usuario'] ?? '';
$id_rol = $_SESSION['id_rol'] ?? null;

$es_admin = (
    strcasecmp($rol_usuario, 'Administrador') === 0 || 
    strcasecmp($rol_usuario, 'Admin') === 0 || 
    $id_rol == 1
);

if (!$es_admin) {
    die("Acceso denegado: Se requieren permisos de Administrador.");
}

require_once 'conexion.php';
$db = new Conexion();
$conn = $db->getConexion();

// Consulta para traer los pendientes de todas las áreas
try {
    $sql = "SELECT d.*, a.nombre_area 
            FROM documentos d 
            LEFT JOIN areas a ON d.id_area_asignada = a.id_area 
            WHERE d.estado = 'Pendiente' 
            ORDER BY d.fecha_documento DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al consultar pendientes: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Pendientes - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Tablón de Pendientes General</h2>
        <a href="tablon.php" class="btn btn-secondary">Volver al Tablón</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Ingreso</th>
                            <th>Título / Asunto</th>
                            <th>Área Asignada</th>
                            <th>Prioridad</th>
                            <th>Archivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pendientes) > 0): ?>
                            <?php foreach ($pendientes as $doc): ?>
                                <tr>
                                    <td><?= htmlspecialchars($doc['id_doc']) ?></td>
                                    <td><?= htmlspecialchars($doc['num_ingreso']) ?></td>
                                    <td><?= htmlspecialchars($doc['titulo']) ?></td>
                                    <td><?= htmlspecialchars($doc['nombre_area'] ?? 'Sin Área') ?></td>
                                    <td>
                                        <span class="badge bg-<?= $doc['prioridad'] === 'Urgente' ? 'danger' : 'warning' ?>">
                                            <?= htmlspecialchars($doc['prioridad']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="descargar_doc.php?id=<?= $doc['id_doc'] ?>&tipo=ingreso" 
                                           class="btn btn-sm btn-outline-danger" target="_blank">
                                            Ver PDF
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No hay documentos pendientes registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
