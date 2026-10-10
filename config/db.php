<?php
// config/db.php - Database Connection Configuration
class Database {
    private $host = "sql201.infinityfree.com"; // Replace with your InfinityFree MySQL Hostname
    private $db_name = "if0_43132881_Arbitrage"; // Replace with your InfinityFree DB Name
    private $username = "if0_43132881";          // Replace with your InfinityFree DB Username
    private $password = "tcaAmxPccHqx3";        // Replace with your InfinityFree DB Password
    private $conn = null;

    public function connect() {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log("Database Connection Failure: " . $e->getMessage());
            throw new Exception("Unable to connect to the database. Please verify your credentials in config/db.php.");
        }

        return $this->conn;
    }
}
?>
