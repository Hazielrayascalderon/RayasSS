<?php
session_start();
if (!isset($_SESSION['id_usuario']) || !in_array($_SESSION['rol'], ['Administrador', 'Coordinación'])) {
    header("Location: index.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['accion'])) {
    require_once 'conexion.php';
    $db = new Conexion();
    $conn = $db->getConexion();

    $id_doc = intval($_GET['id']);
    $accion = $_GET['accion'];

    if ($accion === 'aprobar') {
        $sql = "UPDATE documentos SET estado = 'Concluido', fecha_respuesta = NOW() WHERE id_doc = :id";
    } elseif ($accion === 'rechazar') {
        $sql = "UPDATE documentos SET estado = 'Rechazado' WHERE id_doc = :id";
    } else {
        header("Location: tablon.php");
        exit;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id_doc]);

    header("Location: tablon.php?msg=validation_success");
    exit;
}