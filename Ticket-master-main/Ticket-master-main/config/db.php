<?php

class Database {

    private $host = "sql302.infinityfree.com";
    private $dbname = "if0_42412327_db";
    private $username = "if0_42412327";
    private $password = "Zsq0Wnj8TX";

    public function connect() {

        return new PDO(
            "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
            $this->username,
            $this->password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
}

$database = new Database();
$pdo = $database->connect();

/* Fetch admin details */
$stmt = $pdo->query("SELECT whatsapp, telegram, email FROM admins LIMIT 1");
$admin = $stmt->fetch();

$whatsapp = $admin['whatsapp'] ?? '';
$telegram = $admin['telegram'] ?? '';
$email = $admin['email'] ?? '';

?>
