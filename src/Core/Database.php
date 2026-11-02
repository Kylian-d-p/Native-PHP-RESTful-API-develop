<?php
class Database
{
    private static ?Database $instance = null;
    private ?PDO $conn = null;
    private const string CHARSET = 'utf8';
    private const string LOG_FILE_PATH = '../logs/error.log';

    private function __construct()
    {
        $host = $_ENV['DB_HOST'];
        $user = $_ENV['DB_USER'];
        $pass = $_ENV['DB_PASS'];
        $name = $_ENV['DB_NAME'];
        try {
            $dsn = "mysql:host=$host;dbname=$name;charset=" . self::CHARSET;
            $this->conn = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            logWithDate('DB connection failed', $e->getMessage());
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): ?PDO
    {
        return $this->conn;
    }
}