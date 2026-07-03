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
        $id_doc = intval($_POST['id_doc']);
        $num_respuesta = !empty($_POST['num_respuesta']) ? trim($_POST['num_respuesta']) : 'RESP-2026-GEN';
        $comentarios_abogado = !empty($_POST['comentarios_abogado']) ? trim($_POST['comentarios_abogado']) : 'Asunto resuelto de conformidad.';
     
        $operativo = !empty($_POST['operativo']) && trim($_POST['operativo']) !== 'Jefatura' ? trim($_POST['operativo']) : 'Jefatura de Unidad Departamental';

        $nombre_respuesta_final = 'default_response.pdf';
        if (isset($_FILES['archivo_respuesta']) && $_FILES['archivo_respuesta']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['archivo_respuesta']['tmp_name'];
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);

            if ($mime_type !== 'application/pdf') {
                throw new Exception("El archivo debe ser un PDF legítimo.");
            }

            $nombre_respuesta_final = time() . '_resp_' . bin2hex(random_bytes(4)) . '.pdf';
            move_uploaded_file($file_tmp, 'uploads/' . $nombre_respuesta_final);
        }


        $sql = "UPDATE documentos SET 
                    operativo = :operativo, 
                    num_respuesta = :num_respuesta, 
                    archivo_respuesta = :archivo_respuesta, 
                    comentarios_abogado = :comentarios_abogado, 
                    estado = 'Resuelto_Para_Validar', 
                    fecha_respuesta = NOW() 
                WHERE id_doc = :id_doc";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':operativo' => $operativo,
            ':num_respuesta' => $num_respuesta,
            ':archivo_respuesta' => $nombre_respuesta_final,
            ':comentarios_abogado' => $comentarios_abogado,
            ':id_doc' => $id_doc
        ]);

        header("Location: tablon.php");
        exit;

    } catch (Exception $e) {
        echo "Error de liberación: " . $e->getMessage();
        exit;
    }
}