
<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'Administrador') {
    die("Acceso denegado.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    
    $db = new Conexion();
    $conn = $db->getConexion();
    $accion = $_POST['accion'];

    switch ($accion) {
        
        case 'crear':
            $nombre = trim($_POST['nombre']);
            $email = trim($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $rol = $_POST['rol'];
            $id_area = ($_POST['id_area'] == 'NULL') ? NULL : $_POST['id_area'];

            $sql = "INSERT INTO usuarios (nombre, email, password, rol, id_area) 
                    VALUES (:nombre, :email, :password, :rol, :id_area)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nombre' => $nombre,
                ':email' => $email,
                ':password' => $password,
                ':rol' => $rol,
                ':id_area' => $id_area
            ]);
            break;

        case 'editar':
            $id_usuario = $_POST['id_usuario'];
            $nombre = trim($_POST['nombre']);
            $email = trim($_POST['email']);
            $nueva_password = $_POST['password'];

            if (!empty($nueva_password)) {
                $password_hash = password_hash($nueva_password, PASSWORD_BCRYPT);
                $sql = "UPDATE usuarios SET nombre = :nombre, email = :email, password = :password WHERE id_usuario = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':email' => $email,
                    ':password' => $password_hash,
                    ':id' => $id_usuario
                ]);
            } else {
                $sql = "UPDATE usuarios SET nombre = :nombre, email = :email WHERE id_usuario = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':email' => $email,
                    ':id' => $id_usuario
                ]);
            }
            break;

        case 'eliminar':
            $id_usuario = $_POST['id_usuario'];
            
            if ($id_usuario != $_SESSION['id_usuario']) {
                $sql = "DELETE FROM usuarios WHERE id_usuario = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':id' => $id_usuario]);
            }
            break;
    }

    header("Location: crud.php");
    exit;

} else {
    header("Location: crud.php");
    exit;
}
?>