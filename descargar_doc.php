<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['tipo'])) {
    require_once 'conexion.php';
    $db = new Conexion();
    $conn = $db->getConexion();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id_doc = intval($_GET['id']);
    $tipo = $_GET['tipo'];

    try {
        $sql = "SELECT ruta_documento, archivo_respuesta FROM documentos WHERE id_doc = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id_doc]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($doc) {
            $archivo = '';
            if ($tipo === 'ingreso') {
                $archivo = $doc['ruta_documento'];
            } elseif ($tipo === 'respuesta') {
                $archivo = $doc['archivo_respuesta'];
            }

            $ruta_completa = 'uploads/' . $archivo;

            if (!empty($archivo) && file_exists($ruta_completa)) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . $archivo . '"');
                header('Content-Transfer-Encoding: binary');
                header('Accept-Ranges: bytes');
                @readfile($ruta_completa);
                exit;
            } else {
                echo "El archivo físico no se encuentra en la carpeta del servidor.";
            }
        } else {
            echo "No se encontró el registro del expediente en el sistema.";
        }
    } catch (PDOException $e) {
        echo "Error en la base de datos: " . $e->getMessage();
        exit;
    }
} else {
    header("Location: tablon.php");
    exit;
}