<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'thesis_system';
        $this->username = getenv('DB_USER') ?: 'thesis_admin';
        $this->password = getenv('DB_PASS') ?: 'securepassword123';
    }

    public function getPDO() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4", 
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
        
        return $this->conn;
    }

    public function backupDatabase($backupPath) {
        try {
            $command = "mysqldump --host={$this->host} --user={$this->username} --password={$this->password} {$this->db_name} > $backupPath";
            system($command, $output);
            
            if ($output !== 0) {
                throw new Exception("Database backup failed");
            }
            return true;
        } catch (Exception $e) {
            error_log("Backup error: " . $e->getMessage());
            return false;
        }
    }
}
?>
