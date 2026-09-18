<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

require_once 'conexion.php';
$db = new Conexion();
$conn = $db->getConexion();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'ingreso';

if ($id <= 0) {
    die("ID de documento no válido.");
}

try {
    $stmt = $conn->prepare("SELECT * FROM documentos WHERE id_doc = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        die("El registro del documento no existe en la base de datos.");
    }

    // Identificar el campo del archivo de forma dinámica según la base de datos
    $nombre_archivo = '';
    
    if ($tipo === 'respuesta') {
        $posibles_campos = ['archivo_respuesta', 'documento_respuesta', 'pdf_respuesta', 'respuesta_pdf'];
    } else {
        $posibles_campos = ['documento_archivo', 'archivo', 'ruta_pdf', 'pdf', 'pdf_ingreso', 'archivo_ingreso'];
    }

    foreach ($posibles_campos as $campo) {
        if (!empty($doc[$campo])) {
            $nombre_archivo = $doc[$campo];
            break;
        }
    }

    if (empty($nombre_archivo)) {
        die("No hay ningún archivo asociado a este registro en la base de datos.");
    }

    // Limpiar el nombre del archivo (remover slashes iniciales si los tiene)
    $nombre_limpio = ltrim(basename($nombre_archivo), '/\\');

    // Directorios donde el script buscará el archivo físico
    $directorios_busqueda = [
        __DIR__ . '/',
        __DIR__ . '/uploads/',
        __DIR__ . '/documentos/',
        __DIR__ . '/archivos/'
    ];

    $ruta_final = null;

    // 1. Probar ruta directa si ya incluye subcarpeta
    if (file_exists(__DIR__ . '/' . $nombre_archivo)) {
        $ruta_final = __DIR__ . '/' . $nombre_archivo;
    } else {
        // 2. Probar en los directorios conocidos
        foreach ($directorios_busqueda as $dir) {
            if (file_exists($dir . $nombre_limpio)) {
                $ruta_final = $dir . $nombre_limpio;
                break;
            }
        }
    }

    if (!$ruta_final || !file_exists($ruta_final)) {
        // Depuración clara para identificar dónde falló
        die("El archivo físico ('" . htmlspecialchars($nombre_limpio) . "') no se encuentra en las carpetas del servidor.");
    }

    // Enviar encabezados para visualizar o descargar el PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($ruta_final) . '"');
    header('Content-Length: ' . filesize($ruta_final));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    readfile($ruta_final);
    exit;

} catch (PDOException $e) {
    error_log("Error en descargar_doc.php: " . $e->getMessage());
    die("Error al consultar la base de datos.");
}