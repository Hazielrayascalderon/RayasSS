<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

// Solo Coordinación y Administrador General pueden editar folios desde el CRUD Global
$rol_clean_check = mb_strtolower(trim($_SESSION['rol'] ?? ''), 'UTF-8');
if (!in_array($rol_clean_check, ['administrador', 'coordinación', 'coordinacion'])) {
    header("Location: crud_global.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'conexion.php';
    $db = new Conexion();
    $conn = $db->getConexion();

    try {
        $id_doc = intval($_POST['id_doc']);

        $stmt_current = $conn->prepare("SELECT * FROM documentos WHERE id_doc = :id");
        $stmt_current->execute([':id' => $id_doc]);
        $current = $stmt_current->fetch();

        if (!$current) {
            throw new Exception("El expediente no existe.");
        }

        $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : $current['titulo'];
        $num_ingreso = isset($_POST['num_ingreso']) ? trim($_POST['num_ingreso']) : $current['num_ingreso'];
        $fecha_documento = isset($_POST['fecha_documento']) ? $_POST['fecha_documento'] : $current['fecha_documento'];
        $prioridad = isset($_POST['prioridad']) ? $_POST['prioridad'] : $current['prioridad'];
        $tipo_documento = isset($_POST['tipo_documento']) ? $_POST['tipo_documento'] : $current['tipo_documento'];
        $trae_cach = isset($_POST['trae_cach']) ? trim($_POST['trae_cach']) : $current['trae_cach'];
        $es_dgaf = isset($_POST['es_dgaf']) ? trim($_POST['es_dgaf']) : $current['es_dgaf'];
        $folio_cjsl = isset($_POST['folio_cjsl']) ? trim($_POST['folio_cjsl']) : $current['folio_cjsl'];
        $enviado_por = isset($_POST['enviado_por']) ? trim($_POST['enviado_por']) : $current['enviado_por'];
        $capturista = isset($_POST['capturista']) ? trim($_POST['capturista']) : $current['capturista'];
        $id_area_asignada = isset($_POST['id_area_asignada']) ? intval($_POST['id_area_asignada']) : $current['id_area_asignada'];
        $instruccion = isset($_POST['instruccion']) ? trim($_POST['instruccion']) : $current['instruccion'];
        $operativo = isset($_POST['operativo']) ? trim($_POST['operativo']) : $current['operativo'];
        $fecha_entrega_operativo = isset($_POST['fecha_entrega_operativo']) ? $_POST['fecha_entrega_operativo'] : $current['fecha_entrega_operativo'];
        $num_respuesta = isset($_POST['num_respuesta']) ? trim($_POST['num_respuesta']) : $current['num_respuesta'];
        $fecha_respuesta = isset($_POST['fecha_respuesta']) ? $_POST['fecha_respuesta'] : $current['fecha_respuesta'];
        $comentarios_abogado = isset($_POST['comentarios_abogado']) ? trim($_POST['comentarios_abogado']) : $current['comentarios_abogado'];
        $estado = isset($_POST['estado']) ? $_POST['estado'] : $current['estado'];

        if ($fecha_documento === '') $fecha_documento = null;
        if ($fecha_entrega_operativo === '') $fecha_entrega_operativo = null;
        if ($fecha_respuesta === '') $fecha_respuesta = null;

        $ruta_documento = $current['ruta_documento'];
        if (isset($_FILES['replace_entrada']) && $_FILES['replace_entrada']['error'] === UPLOAD_ERR_OK && $_FILES['replace_entrada']['size'] > 0) {
            if (!empty($ruta_documento) && file_exists('uploads/' . $ruta_documento)) {
                @unlink('uploads/' . $ruta_documento);
            }
            $ruta_documento = time() . '_ent_' . bin2hex(random_bytes(2)) . '.pdf';
            move_uploaded_file($_FILES['replace_entrada']['tmp_name'], 'uploads/' . $ruta_documento);
        }

        $archivo_respuesta = $current['archivo_respuesta'];
        if (isset($_FILES['replace_respuesta']) && $_FILES['replace_respuesta']['error'] === UPLOAD_ERR_OK && $_FILES['replace_respuesta']['size'] > 0) {
            if (!empty($archivo_respuesta) && file_exists('uploads/' . $archivo_respuesta)) {
                @unlink('uploads/' . $archivo_respuesta);
            }
            $archivo_respuesta = time() . '_resp_' . bin2hex(random_bytes(2)) . '.pdf';
            move_uploaded_file($_FILES['replace_respuesta']['tmp_name'], 'uploads/' . $archivo_respuesta);
        }

        $sql = "UPDATE documentos SET 
                    titulo = :titulo, num_ingreso = :num_ingreso, fecha_documento = :fecha_documento, 
                    prioridad = :prioridad, tipo_documento = :tipo_documento, trae_cach = :trae_cach, 
                    es_dgaf = :es_dgaf, folio_cjsl = :folio_cjsl, enviado_por = :enviado_por, 
                    capturista = :capturista, id_area_asignada = :id_area_asignada, ruta_documento = :ruta_documento, 
                    instruccion = :instruccion, operativo = :operativo, fecha_entrega_operativo = :fecha_entrega_operativo, 
                    num_respuesta = :num_respuesta, fecha_respuesta = :fecha_respuesta, archivo_respuesta = :archivo_respuesta, 
                    comentarios_abogado = :comentarios_abogado, estado = :estado 
                WHERE id_doc = :id_doc";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':titulo' => $titulo, ':num_ingreso' => $num_ingreso, ':fecha_documento' => $fecha_documento,
            ':prioridad' => $prioridad, ':tipo_documento' => $tipo_documento, ':trae_cach' => $trae_cach,
            ':es_dgaf' => $es_dgaf, ':folio_cjsl' => $folio_cjsl, ':enviado_por' => $enviado_por,
            ':capturista' => $capturista, ':id_area_asignada' => $id_area_asignada, ':ruta_documento' => $ruta_documento,
            ':instruccion' => $instruccion, ':operativo' => $operativo, ':fecha_entrega_operativo' => $fecha_entrega_operativo,
            ':num_respuesta' => $num_respuesta, ':fecha_respuesta' => $fecha_respuesta, ':archivo_respuesta' => $archivo_respuesta,
            ':comentarios_abogado' => $comentarios_abogado, ':estado' => $estado, ':id_doc' => $id_doc
        ]);

        if (isset($_POST['titulo'])) {
            header("Location: crud_global.php?msg=updated");
        } else {
            header("Location: tablon.php?msg=updated");
        }
        exit;

    } catch (Exception $e) {
        exit("Error técnico: " . $e->getMessage());
    }
}