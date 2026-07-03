<?php

header('Content-Type: application/json');


$host = 'sql302.infinityfree.com';
$db   = 'if0_42072977_expedientes';
$user = 'if0_42072977';
$pass = 'SrvyDZB9hzL';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $busqueda = isset($_GET['q']) ? $_GET['q'] : '';

    if (strlen($busqueda) >= 2) {
      
        $stmt = $pdo->prepare("SELECT id, autoridad, asunto, numero_oficio, fecha_oficio 
                               FROM oficios 
                               WHERE numero_oficio LIKE :q OR autoridad LIKE :q 
                               LIMIT 5");
        
        $termino = "%" . $busqueda . "%";
        $stmt->bindParam(':q', $termino);
        $stmt->execute();

        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($resultados);
    } else {
        echo json_encode([]);
    }

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>