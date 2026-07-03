<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'conexion.php';
    $db = new Conexion();
    $conn = $db->getConexion();

    try {
        $titulo = trim($_POST['titulo']);
        $num_ingreso = !empty($_POST['num_ingreso']) ? trim($_POST['num_ingreso']) : 'S/N';
        
        // ==========================================
        // VALIDACIÓN: NO REPETIR NÚMERO DE INGRESO
        // ==========================================
        if ($num_ingreso !== 'S/N') {
            $check_sql = "SELECT id_doc FROM documentos WHERE num_ingreso = :num LIMIT 1";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([':num' => $num_ingreso]);
            
            if ($check_stmt->rowCount() > 0) {
                // Si ya existe, lanzamos alerta y regresamos sin perder los datos del formulario
                echo "<script>
                    alert('¡Error! El número de oficio o trámite ($num_ingreso) ya se encuentra registrado en el sistema.');
                    window.history.back();
                </script>";
                exit;
            }
        }
        // ==========================================

        $tipo_documento = !empty($_POST['tipo_documento']) ? $_POST['tipo_documento'] : 'Volante';
        $prioridad = !empty($_POST['prioridad']) ? $_POST['prioridad'] : 'Urgente';
        $trae_cach = !empty($_POST['trae_cach']) ? trim($_POST['trae_cach']) : null;
        $es_dgaf = !empty($_POST['es_dgaf']) ? trim($_POST['es_dgaf']) : null;
        $folio_cjsl = !empty($_POST['folio_cjsl']) ? trim($_POST['folio_cjsl']) : null;
        $enviado_por = !empty($_POST['enviado_por']) ? trim($_POST['enviado_por']) : 'S/N';
        $instruccion = !empty($_POST['instruccion']) ? trim($_POST['instruccion']) : null;
        $capturista = !empty($_POST['capturista']) ? trim($_POST['capturista']) : $_SESSION['nombre'];
        $fecha_documento = !empty($_POST['fecha_documento']) ? $_POST['fecha_documento'] : date('Y-m-d');
        
        $operativo = !empty($_POST['operativo_inicial']) ? trim($_POST['operativo_inicial']) : null;
        $fecha_entrega = !empty($operativo) ? date('Y-m-d H:i:s') : null;

        $areas_asignadas = isset($_POST['id_area']) ? (array)$_POST['id_area'] : [];

        $nombre_archivo_final = 'oficio_default.pdf';
        if (isset($_FILES['documento_archivo']) && $_FILES['documento_archivo']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['documento_archivo']['tmp_name'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);

            if ($mime_type !== 'application/pdf') {
                throw new Exception("El archivo debe ser un PDF legítimo.");
            }

            $nombre_archivo_final = time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
            move_uploaded_file($file_tmp, 'uploads/' . $nombre_archivo_final);
        }

        $sql = "INSERT INTO documentos (titulo, num_ingreso, tipo_documento, prioridad, trae_cach, es_dgaf, folio_cjsl, enviado_por, capturista, id_area_asignada, ruta_documento, instruccion, operativo, fecha_entrega_operativo, fecha_documento, estado) 
                VALUES (:titulo, :num_ingreso, :tipo_documento, :prioridad, :trae_cach, :es_dgaf, :folio_cjsl, :enviado_por, :capturista, :id_area_asignada, :ruta_documento, :instruccion, :operativo, :fecha_entrega, :fecha_documento, 'Pendiente')";
        
        $stmt = $conn->prepare($sql);

        foreach ($areas_asignadas as $id_area_item) {
            $stmt->execute([
                ':titulo' => $titulo,
                ':num_ingreso' => $num_ingreso,
                ':tipo_documento' => $tipo_documento,
                ':prioridad' => $prioridad,
                ':trae_cach' => $trae_cach,
                ':es_dgaf' => $es_dgaf,
                ':folio_cjsl' => $folio_cjsl,
                ':enviado_por' => $enviado_por,
                ':capturista' => $capturista,
                ':id_area_asignada' => intval($id_area_item),
                ':ruta_documento' => $nombre_archivo_final,
                ':instruccion' => $instruccion,
                ':operativo' => $operativo,
                ':fecha_entrega' => $fecha_entrega,
                ':fecha_documento' => $fecha_documento
            ]);
        }

        header("Location: tablon.php");
        exit;

    } catch (Exception $e) {
        exit("Error de carga seguro: " . $e->getMessage());
    }
}
?>