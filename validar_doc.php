<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

$rol_clean = mb_strtolower(trim($_SESSION['rol'] ?? ''), 'UTF-8');
$puede_validar = in_array($rol_clean, ['administrador', 'coordinación', 'coordinacion']);

if (!$puede_validar) {
    header("Location: tablon.php");
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

    $return = $_GET['return'] ?? '';
    if ($return === 'tareas') {
        $destino = 'empleados_tareas.php?msg=validation_success';
    } elseif ($return === 'acuse') {
        $destino = 'imprimir_acuse.php?id=' . $id_doc . '&msg=validation_success';
    } else {
        $destino = 'tablon.php?msg=validation_success';
    }
    header("Location: " . $destino);
    exit;
} else {
    header("Location: tablon.php");
    exit;
}
