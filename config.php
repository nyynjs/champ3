<?php
// config.php - Konfiguracja bazy danych
class Database {
    private $host = 'sql.10.svpj.link';
    private $dbname = 'db_112106';
    private $username = 'db_112106t';
    private $password = 'wG7UrOmwlYX6ZCqv';
    private $pdo;
    
    public function connect() {
        if ($this->pdo === null) {
            try {
                $this->pdo = new PDO(
                    "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
                
                // Test connection
                $this->pdo->query('SELECT 1');
                
            } catch (PDOException $e) {
                error_log('Database connection error: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
                exit;
            }
        }
        return $this->pdo;
    }
    
    public function getConnectionInfo() {
        return [
            'host' => $this->host,
            'database' => $this->dbname,
            'username' => $this->username
        ];
    }
}

// Włącz wyświetlanie błędów dla debugowania (usuń w produkcji)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');
?>