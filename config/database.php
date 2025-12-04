<?php
session_start();

// Include functions - correct the path
require_once __DIR__ . '/../includes/functions.php';

class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "career";
    public $conn;

    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname}",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
}

// Create global database instance
$db = new Database();
?>