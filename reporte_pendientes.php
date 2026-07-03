<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'JUD') {
    die("Acceso no autorizado.");
}

require_once 'conexion.php';
$db = new Conexion();
$conn = $db->getConexion();

$id_area = $_SESSION['id_area'];
$nombre_jud = $_SESSION['nombre'];

$meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
$mes_actual = $meses[date('n') - 1];
$anio_actual = date('Y');

try {
    $sql = "SELECT * FROM documentos 
            WHERE id_area_asignada = :area 
            AND estado = 'Pendiente' 
            AND MONTH(fecha_ingreso) = MONTH(CURRENT_DATE()) 
            AND YEAR(fecha_ingreso) = YEAR(CURRENT_DATE()) 
            ORDER BY id_doc DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([':area' => $id_area]);
    $pendientes = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al generar el reporte: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte_Pendientes_<?php echo $mes_actual . "_" . $anio_actual; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        body { background-color: #fff; font-family: 'Arial', sans-serif; color: #333; }
        .encabezado-reporte { border-bottom: 3px solid #6f1120; padding-bottom: 15px; margin-bottom: 30px; }
        .titulo-dependencia { color: #4a0812; font-size: 20px; font-weight: bold; text-transform: uppercase; }
        .tabla-reporte thead { background-color: #6f1120 !important; color: #fff !important; }
        .tabla-reporte th { font-size: 12px; text-transform: uppercase; padding: 10px; }
        .tabla-reporte td { font-size: 11px; padding: 10px; }
        .seccion-firmas { margin-top: 80px; }
        .linea-firma { border-top: 1px solid #333; width: 250px; margin: 0 auto; padding-top: 5px; font-size: 12px; font-weight: bold; }
        @media print {
            .no-print { display: none; }
            body { -webkit-print-color-adjust: exact; }
            .tabla-reporte thead { background-color: #6f1120 !important; color: #fff !important; }
        }
    </style>
</head>
<body>

<div class="container my-4">
    <div class="no-print text-end mb-3">
        <button onclick="window.print();" class="btn btn-sm btn-dark"><i class="bi bi-printer"></i> Imprimir / Guardar PDF</button>
        <button onclick="window.close();" class="btn btn-sm btn-secondary">Cerrar</button>
    </div>

    <div class="encabezado-reporte row align-items-center">
        <div class="col-8">
            <div class="titulo-dependencia">Control y Gestión Documental</div>
            <div class="text-muted small">Reporte Mensual de Expedientes Pendientes</div>
            <div class="fw-bold text-secondary mt-1 small"><?php echo htmlspecialchars($nombre_jud); ?></div>
        </div>
        <div class="col-4 text-end">
            <div class="badge bg-dark p-2 font-monospace">MES: <?php echo strtoupper($mes_actual) . " " . $anio_actual; ?></div>
            <div class="text-muted mt-1" style="font-size: 10px;">Fecha de emisión: <?php echo date('d/m/Y H:i'); ?></div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <table class="table table-bordered table-striped align-middle tabla-reporte">
                <thead>
                    <tr>
                        <th style="width: 8%;">Folio</th>
                        <th style="width: 15%;">Fecha Ingreso</th>
                        <th style="width: 30%;">Asunto / Título</th>
                        <th style="width: 47%;">Detalles del Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pendientes) == 0) { ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted fw-bold">No se cuentan con expedientes pendientes en el mes de <?php echo $mes_actual; ?>.</td>
                        </tr>
                    <?php } else { 
                        foreach ($pendientes as $row) { ?>
                        <tr>
                            <td class="fw-bold text-center">#<?php echo $row['id_doc']; ?></td>
                            <td class="text-center"><?php echo date('d/m/Y', strtotime($row['fecha_ingreso'])); ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($row['titulo']); ?></td>
                            <td class="text-muted" style="white-space: pre-line;"><?php echo htmlspecialchars($row['descripcion']); ?></td>
                        </tr>
                    <?php } } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row seccion-firmas text-center">
        <div class="col-6 offset-3">
            <div class="mb-5 text-muted small">Atentamente,</div>
            <div class="linea-firma">
                <?php echo htmlspecialchars($nombre_jud); ?><br>
                <span class="text-muted fw-normal" style="font-size: 10px;">Titular de la Unidad Administrativa</span>
            </div>
        </div>
    </div>
</div>

<script>
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 300);
    };
</script>
</body>
</html>


