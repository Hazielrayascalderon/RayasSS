<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { header("Location: index.php"); exit; }

$rol_clean = mb_strtolower(trim($_SESSION['rol'] ?? ''), 'UTF-8');
if ($rol_clean !== 'administrador') { header("Location: tablon.php"); exit; }

$carpeta_backups = __DIR__ . '/backups';
$archivo = isset($_GET['archivo']) ? basename($_GET['archivo']) : '';

if (preg_match('/^backup_[0-9_\-]+\.sql(\.gz)?$/', $archivo)) {
    $ruta = $carpeta_backups . '/' . $archivo;
    if (file_exists($ruta)) {
        unlink($ruta);
    }
}

header("Location: gestion_respaldos.php");
exit;
