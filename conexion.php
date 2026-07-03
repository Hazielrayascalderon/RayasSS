
<?php
class Conexion {
    private $host = "sql302.infinityfree.com";
    private $db_name = "if0_42072977_expedientes";
    private $username = "if0_42072977";
    private $password = "SrvyDZB9hzL";
    public $conn;

    public function getConexion() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=3306;dbname=" . $this->db_name . ";charset=utf8mb4", 
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            die("Error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
?>