<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { header("Location: index.php"); exit; }

$rol_clean = mb_strtolower(trim($_SESSION['rol'] ?? ''), 'UTF-8');
if ($rol_clean !== 'administrador') { header("Location: tablon.php"); exit; }

$carpeta_backups = __DIR__ . '/backups';
$archivo = isset($_GET['archivo']) ? basename($_GET['archivo']) : '';

// Solo permitir nombres con el patrón esperado, para evitar path traversal.
if (!preg_match('/^backup_[0-9_\-]+\.sql(\.gz)?$/', $archivo)) {
    http_response_code(400);
    die("Nombre de archivo no válido.");
}

$ruta = $carpeta_backups . '/' . $archivo;
if (!file_exists($ruta)) {
    http_response_code(404);
    die("El respaldo solicitado no existe.");
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $archivo . '"');
header('Content-Length: ' . filesize($ruta));
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($ruta);
exit;
