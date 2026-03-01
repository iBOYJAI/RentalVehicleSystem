<?php
/**
 * Database Configuration File
 * Handles MySQL database connection using PDO
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'rvms_db';
    private $username = 'root';
    private $password = '';
    private $conn;

    /**
     * Get database connection
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
        
        return $this->conn;
    }
}

// Global database instance
function getDB() {
    $database = new Database();
    return $database->getConnection();
}

