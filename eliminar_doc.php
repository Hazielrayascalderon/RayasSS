<?php
session_start();
if (!isset($_SESSION['id_usuario']) || !in_array($_SESSION['rol'], ['Administrador', 'Coordinación'])) {
    exit("Acceso denegado.");
}

if (isset($_GET['id'])) {
    require_once 'conexion.php';
    $db = new Conexion();
    $conn = $db->getConexion();

    $id_doc = intval($_GET['id']);

    $stmt = $conn->prepare("SELECT ruta_documento, archivo_respuesta FROM documentos WHERE id_doc = :id");
    $stmt->execute([':id' => $id_doc]);
    $doc = $stmt->fetch();

    if ($doc) {
        if (!empty($doc['ruta_documento']) && file_exists('uploads/' . $doc['ruta_documento'])) {
            @unlink('uploads/' . $doc['ruta_documento']);
        }
        if (!empty($doc['archivo_respuesta']) && file_exists('uploads/' . $doc['archivo_respuesta'])) {
            @unlink('uploads/' . $doc['archivo_respuesta']);
        }

        $stmt_del = $conn->prepare("DELETE FROM documentos WHERE id_doc = :id");
        $stmt_del->execute([':id' => $id_doc]);
    }

    header("Location: crud_global.php?msg=deleted");
    exit;
}