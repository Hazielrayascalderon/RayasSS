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

    $rol_sesion = $_SESSION['rol'];
    $id_area_sesion = $_SESSION['id_area'];
    $accion = $_POST['accion'] ?? '';

    $is_global_admin = in_array($rol_sesion, ['Administrador', 'Coordinación']);

    try {
        if ($accion === 'alta') {
            $nombre_empleado = trim($_POST['nombre_empleado']);
            $id_area_destino = $is_global_admin ? intval($_POST['id_area']) : intval($id_area_sesion);

            if (empty($nombre_empleado) || empty($id_area_destino)) {
                throw new Exception("Datos incompletos para procesar el alta.");
            }

            $sql = "INSERT INTO personal (nombre, id_area) VALUES (:nombre, :id_area)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nombre' => $nombre_empleado,
                ':id_area' => $id_area_destino
            ]);
        } 
        elseif ($accion === 'baja') {
            $id_personal = intval($_POST['id_personal']);

            if (empty($id_personal)) {
                throw new Exception("Identificador de personal no válido.");
            }

            if (!$is_global_admin) {
                $stmt_check = $conn->prepare("SELECT id_area FROM personal WHERE id_personal = :id");
                $stmt_check->execute([':id' => $id_personal]);
                $emp = $stmt_check->fetch();
                if (!$emp || intval($emp['id_area']) !== intval($id_area_sesion)) {
                    throw new Exception("No tienes autorización para dar de baja a personal de otra JUD.");
                }
            }

            $sql = "DELETE FROM personal WHERE id_personal = :id_personal";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id_personal' => $id_personal]);
        }

        header("Location: tablon.php");
        exit;

    } catch (Exception $e) {
        exit("Error de privilegios en plantilla: " . $e->getMessage());
    }
}