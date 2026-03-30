<?php
/**
 * Classe Database
 * 
 * Gerencia conexão com banco de dados usando PDO
 * Implementa Singleton Pattern para garantir única instância
 * 
 * @package GestorClima
 */

class Database {
    /**
     * Instância única da classe (Singleton)
     * @var Database|null
     */
    private static $instance = null;
    
    /**
     * Objeto PDO
     * @var PDO|null
     */
    private $connection = null;
    
    /**
     * Construtor privado para Singleton
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Previne clonagem da instância
     */
    private function __clone() {}
    
    /**
     * Previne unserialize da instância
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Obtém instância única da classe
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Estabelece conexão com banco de dados
     * 
     * @throws PDOException
     */
    private function connect() {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log("Erro de conexão: " . $e->getMessage());
            throw new Exception("Erro ao conectar ao banco de dados. Verifique as configurações.");
        }
    }
    
    /**
     * Retorna conexão PDO
     * 
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Prepara e executa query com prepared statements
     * 
     * @param string $sql Query SQL
     * @param array $params Parâmetros para bind
     * @return PDOStatement
     * @throws Exception
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erro na query: " . $e->getMessage() . " | SQL: " . $sql);
            if (defined('APP_DEBUG') && APP_DEBUG) {
                // Incluir mensagem do PDO na exceção para endpoints que mostrem debug
                throw new Exception("Erro ao executar operação no banco de dados: " . $e->getMessage());
            }
            throw new Exception("Erro ao executar operação no banco de dados.");
        }
    }
    
    /**
     * Retorna todos os registros
     * 
     * @param string $sql Query SQL
     * @param array $params Parâmetros
     * @return array
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Retorna um único registro
     * 
     * @param string $sql Query SQL
     * @param array $params Parâmetros
     * @return array|false
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Retorna ID do último insert
     * 
     * @return string
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    /**
     * Atalho para executar INSERT e retornar o ID inserido.
     * Compatibilidade com modelos que chamam `$db->insert()`.
     *
     * @param string $sql
     * @param array $params
     * @return string|false ID inserido ou false em falha
     */
    public function insert($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $this->lastInsertId();
    }
    
    /**
     * Inicia transação
     * 
     * @return bool
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Confirma transação
     * 
     * @return bool
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Reverte transação
     * 
     * @return bool
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    /**
     * Verifica se está em transação
     * 
     * @return bool
     */
    public function inTransaction() {
        return $this->connection->inTransaction();
    }
}
