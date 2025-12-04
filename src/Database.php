<?php

class Database {
    private $host = "localhost";
    private $db_name = "memory_game";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Crée la table si besoin
            $this->ensureSchema($this->conn);

        } catch(PDOException $e) {
            // Cas base inexistante
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                try {
                    $tmp = new PDO("mysql:host=" . $this->host, $this->username, $this->password);
                    $tmp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $tmp->exec(
                        "CREATE DATABASE IF NOT EXISTS `" . $this->db_name . "` " .
                        "CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
                    );

                    // Reconnexion sur la nouvelle base
                    $this->conn = new PDO(
                        "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                        $this->username,
                        $this->password
                    );
                    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->ensureSchema($this->conn);

                } catch (PDOException $e2) {
                    echo "Connection error (creating DB): " . $e2->getMessage();
                }
            } else {
                echo "Connection error: " . $e->getMessage();
            }
        }

        return $this->conn;
    }

    /**
     * Crée la table scores si elle n'existe pas
     */
    private function ensureSchema(PDO $conn) {
        $sql = "CREATE TABLE IF NOT EXISTS `scores` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            player_name VARCHAR(100) NOT NULL,
            score INT NOT NULL,
            attempts INT NOT NULL,
            time_seconds INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        try {
            $conn->exec($sql);
        } catch (PDOException $e) {
            echo "Schema creation warning: " . $e->getMessage();
        }
    }
}
