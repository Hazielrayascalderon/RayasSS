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

    $rol_sesion = trim($_SESSION['rol'] ?? '');
    $rol_clean = mb_strtolower($rol_sesion, 'UTF-8');
    $id_area_sesion = $_SESSION['id_area'];
    $accion = $_POST['accion'] ?? '';

    // Roles que pueden elegir a qué JUD/área asignar al personal (y por lo tanto
    // los únicos autorizados para dar de alta personal nuevo).
    $es_admin = ($rol_clean === 'administrador');
    $es_coordinacion = in_array($rol_clean, ['coordinación', 'coordinacion']);
    $es_direccion_general = in_array($rol_clean, ['dirección general', 'direccion general']);
    $puede_elegir_area = ($es_admin || $es_coordinacion || $es_direccion_general);

    try {
        if ($accion === 'alta') {
            // Solo Administrador, Coordinación y Dirección General pueden dar de alta personal.
            if (!$puede_elegir_area) {
                throw new Exception("No tienes autorización para dar de alta personal. Esta acción está reservada a Coordinación y Dirección General.");
            }

            $nombre_empleado = trim($_POST['nombre_empleado'] ?? '');
            $id_area_destino = intval($_POST['id_area'] ?? 0);

            if (empty($nombre_empleado) || empty($id_area_destino)) {
                throw new Exception("Datos incompletos para procesar el alta.");
            }

            $check_area = $conn->prepare("SELECT id_area FROM areas WHERE id_area = :id");
            $check_area->execute([':id' => $id_area_destino]);
            if ($check_area->rowCount() === 0) {
                throw new Exception("El área seleccionada no es válida.");
            }

            $sql = "INSERT INTO personal (nombre, id_area) VALUES (:nombre, :id_area)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nombre' => $nombre_empleado,
                ':id_area' => $id_area_destino
            ]);

            header("Location: empleados_tareas.php?area_auditoria=" . $id_area_destino . "&msg=personal_alta");
            exit;
        }
        elseif ($accion === 'baja') {
            $id_personal = intval($_POST['id_personal'] ?? 0);

            if (empty($id_personal)) {
                throw new Exception("Identificador de personal no válido.");
            }

            if (!$puede_elegir_area) {
                // Un JUD solo puede dar de baja personal de su propia área.
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

            header("Location: empleados_tareas.php?msg=personal_baja");
            exit;
        } else {
            throw new Exception("Acción no reconocida.");
        }

    } catch (Exception $e) {
        exit("Error de privilegios en plantilla: " . htmlspecialchars($e->getMessage()));
    }
} else {
    header("Location: empleados_tareas.php");
    exit;
}
