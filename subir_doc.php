<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

require_once 'conexion.php';
$db = new Conexion();
$conn = $db->getConexion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $capturista = trim($_POST['capturista'] ?? '');
    $fecha_documento = trim($_POST['fecha_documento'] ?? date('Y-m-d'));
    $num_ingreso = trim($_POST['num_ingreso'] ?? '');
    $enviado_por = trim($_POST['enviado_por'] ?? '');
    $titulo = trim($_POST['titulo'] ?? '');
    $tipo_documento = trim($_POST['tipo_documento'] ?? 'Volante');
    $prioridad = trim($_POST['prioridad'] ?? 'Sin término');
    $instruccion = trim($_POST['instruccion'] ?? '');
    $areas_seleccionadas = $_POST['id_area'] ?? [];

    if (empty($areas_seleccionadas)) {
        die("Error: Debe seleccionar al menos un área a turnar.");
    }

    // 1. Manejo del archivo subido
    $nombre_guardado = null;

    if (isset($_FILES['documento_archivo']) && $_FILES['documento_archivo']['error'] === UPLOAD_ERR_OK) {
        $directorio_destino = __DIR__ . '/uploads/';

        if (!file_exists($directorio_destino)) {
            mkdir($directorio_destino, 0777, true);
        }

        $nombre_original = basename($_FILES['documento_archivo']['name']);
        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            // Nombre único para evitar sobreescrituras
            $nombre_guardado = time() . '_' . uniqid() . '.pdf';
            $ruta_completa = $directorio_destino . $nombre_guardado;

            if (!move_uploaded_file($_FILES['documento_archivo']['tmp_name'], $ruta_completa)) {
                die("Error al guardar el archivo físico en la carpeta uploads/.");
            }
        } else {
            die("Error: Únicamente se permiten archivos en formato PDF.");
        }
    } else {
        die("Error en la subida del archivo. Código de error PHP: " . ($_FILES['documento_archivo']['error'] ?? 'No enviado'));
    }

    // 2. Inserción en la base de datos
    try {
        $conn->beginTransaction();

        $sql = "INSERT INTO documentos 
                (capturista, fecha_documento, num_ingreso, enviado_por, titulo, tipo_documento, prioridad, instruccion, id_area_asignada, documento_archivo, estado) 
                VALUES 
                (:capturista, :fecha_documento, :num_ingreso, :enviado_por, :titulo, :tipo_documento, :prioridad, :instruccion, :id_area, :documento_archivo, 'Pendiente')";

        $stmt = $conn->prepare($sql);

        foreach ($areas_seleccionadas as $id_area) {
            $stmt->execute([
                ':capturista'        => $capturista,
                ':fecha_documento'   => $fecha_documento,
                ':num_ingreso'       => $num_ingreso,
                ':enviado_por'       => $enviado_por,
                ':titulo'            => $titulo,
                ':tipo_documento'    => $tipo_documento,
                ':prioridad'        => $prioridad,
                ':instruccion'       => $instruccion,
                ':id_area'           => intval($id_area),
                ':documento_archivo' => $nombre_guardado,
            ]);
        }

        $conn->commit();
        header("Location: tablon.php?msg=exito");
        exit;

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error SQL en subir_doc.php: " . $e->getMessage());
        die("Error de BD al registrar el documento: " . $e->getMessage());
    }
}