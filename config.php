<?php
class Database {
    private $host = "gokucheker.ceheeiow0knm.us-east-1.rds.amazonaws.com";
    private $dbname = "goku_checker";
    private $username = "admin";  // ← TU USUARIO REAL AQUÍ
    private $password = "gokucheker123";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname}",
                $this->username,
                $this->password
            );
            $this->conn->exec("SET NAMES utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch(PDOException $exception) {
            die("Error de conexión: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

session_start();
?>
