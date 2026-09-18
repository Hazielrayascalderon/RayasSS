<?php
ob_start();
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new Conexion();
        $conn = $db->getConexion();

        $oficio_id     = !empty($_POST['oficio_id']) ? intval($_POST['oficio_id']) : null;
        $autoridad     = trim($_POST['autoridad'] ?? '');
        $numero_oficio = trim($_POST['numero_oficio'] ?? '');
        $fecha_oficio  = $_POST['fecha_oficio'] ?? date('Y-m-d');
        $asunto        = trim($_POST['asunto'] ?? '');

        if (empty($autoridad) || empty($numero_oficio) || empty($asunto)) {
            die("Error: Faltan campos obligatorios por llenar.");
        }

        if ($oficio_id) {
            $sql = "UPDATE oficios SET autoridad = :aut, numero_oficio = :num, fecha_oficio = :fec, asunto = :asu WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':id', $oficio_id, PDO::PARAM_INT);
        } else {
            $sql = "INSERT INTO oficios (autoridad, numero_oficio, fecha_oficio, asunto) VALUES (:aut, :num, :fec, :asu)";
            $stmt = $conn->prepare($sql);
        }

        $stmt->bindValue(':aut', $autoridad, PDO::PARAM_STR);
        $stmt->bindValue(':num', $numero_oficio, PDO::PARAM_STR);
        $stmt->bindValue(':fec', $fecha_oficio, PDO::PARAM_STR);
        $stmt->bindValue(':asu', $asunto, PDO::PARAM_STR);

        $stmt->execute();

        header("Location: tablon.php?msg=oficio_exito");
        exit;

    } catch (Exception $e) {
        die("Error en base de datos: " . $e->getMessage());
    }
} else {
    header("Location: tablon.php");
    exit;
}
?>