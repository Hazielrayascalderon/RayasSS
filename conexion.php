<?php
class Conexion {
    private $host = "sql312.infinityfree.com";
    private $db_name = "if0_42850069_expedientes";
    private $username = "if0_42850069";
    private $password = "qoGDbJnRwS4m";
    private $port = "3306";
    private $conn;

    public function getConexion() {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            
            $opciones = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $opciones);
            return $this->conn;

        } catch (PDOException $e) {
            error_log("Error de conexión SQL: " . $e->getMessage());
            throw new Exception("No se pudo conectar a la base de datos.");
        }
    }
}
?>