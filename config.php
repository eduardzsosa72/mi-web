<?php
class Database {
    private $host = "gokucheker.ceheeiow0knm.us-east-1.rds.amazonaws.com";
    private $dbname = "goku_checker";
    private $username = "Admin";
    private $password = "gokucheker123";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->dbname, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Error de conexión: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

session_start();
?>