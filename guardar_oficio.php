<?php


$host = 'sql302.infinityfree.com';
$db   = 'if0_42072977_expedientes';
$user = 'if0_42072977';
$pass = 'SrvyDZB9hzL';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  
    $oficio_id     = $_POST['oficio_id'] ?? null;
    $autoridad     = $_POST['autoridad'] ?? '';
    $asunto        = $_POST['asunto'] ?? '';
    $numero_oficio = $_POST['numero_oficio'] ?? '';
    $fecha_oficio  = $_POST['fecha_oficio'] ?? '';

   
    if (empty($oficio_id)) {
        $stmt = $pdo->prepare("INSERT INTO oficios (autoridad, asunto, numero_oficio, fecha_oficio) 
                               VALUES (:autoridad, :asunto, :numero_oficio, :fecha_oficio)");
    } 
    // Si hay un ID, entonces estamos editando un registro existente
    else {
        $stmt = $pdo->prepare("UPDATE oficios 
                               SET autoridad = :autoridad, 
                                   asunto = :asunto, 
                                   numero_oficio = :numero_oficio, 
                                   fecha_oficio = :fecha_oficio 
                               WHERE id = :id");
        $stmt->bindParam(':id', $oficio_id);
    }


    $stmt->bindParam(':autoridad', $autoridad);
    $stmt->bindParam(':asunto', $asunto);
    $stmt->bindParam(':numero_oficio', $numero_oficio);
    $stmt->bindParam(':fecha_oficio', $fecha_oficio);

    $stmt->execute();

   
    header("Location: index.php?mensaje=guardado_exito");
    exit();

} catch (PDOException $e) {
    die("Error al guardar el registro: " . $e->getMessage());
}
?>