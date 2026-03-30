<?php
/**
 * Model: Cliente
 * 
 * Gerencia operações CRUD da entidade Cliente
 * Segue princípios SOLID e Clean Code
 * 
 * @package GestorClima\Models
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

class Cliente {
    private $db;
    private $table = 'clientes';
    
    // Propriedades
    public $id;
    public $nome;
    public $email;
    public $telefone;
    public $cpf_cnpj;
    public $endereco;
    public $cidade;
    public $estado;
    public $cep;
    public $observacoes;
    public $ativo;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lista todos os clientes ativos
     * 
     * @return array
     */
    public function listarTodos() {
        $sql = "SELECT * FROM {$this->table} WHERE ativo = 1 ORDER BY nome ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Busca cliente por ID
     * 
     * @param int $id ID do cliente
     * @return array|false
     */
    public function buscarPorId($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }
    
    /**
     * Cria novo cliente
     * 
     * @return int|false ID do cliente criado ou false
     */
    public function criar() {
        // Validações
        if (!$this->validar()) {
            return false;
        }
        
        $sql = "INSERT INTO {$this->table} 
                (nome, email, telefone, cpf_cnpj, endereco, cidade, estado, cep, observacoes, ativo)
                VALUES 
                (:nome, :email, :telefone, :cpf_cnpj, :endereco, :cidade, :estado, :cep, :observacoes, :ativo)";
        
        try {
            // Se email estiver vazio, enviar NULL para banco (permite múltiplos NULLs em índices UNIQUE)
            $emailParam = !empty($this->email) ? $this->email : null;

            $this->db->query($sql, [
                'nome' => $this->nome,
                'email' => $emailParam,
                'telefone' => $this->telefone,
                'cpf_cnpj' => $this->cpf_cnpj,
                'endereco' => $this->endereco,
                'cidade' => $this->cidade,
                'estado' => $this->estado,
                'cep' => $this->cep,
                'observacoes' => $this->observacoes,
                'ativo' => $this->ativo ?? 1
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Erro ao criar cliente: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza cliente existente
     * 
     * @return bool
     */
    public function atualizar() {
        // Validações
        if (!$this->validar()) {
            return false;
        }
        
        $sql = "UPDATE {$this->table} SET
                nome = :nome,
                email = :email,
                telefone = :telefone,
                cpf_cnpj = :cpf_cnpj,
                endereco = :endereco,
                cidade = :cidade,
                estado = :estado,
                cep = :cep,
                observacoes = :observacoes
                WHERE id = :id";
        
        try {
            // Se email estiver vazio, enviar NULL para banco
            $emailParam = !empty($this->email) ? $this->email : null;

            $this->db->query($sql, [
                'id' => $this->id,
                'nome' => $this->nome,
                'email' => $emailParam,
                'telefone' => $this->telefone,
                'cpf_cnpj' => $this->cpf_cnpj,
                'endereco' => $this->endereco,
                'cidade' => $this->cidade,
                'estado' => $this->estado,
                'cep' => $this->cep,
                'observacoes' => $this->observacoes
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao atualizar cliente: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Exclui cliente (soft delete)
     * 
     * @param int $id ID do cliente
     * @return bool
     */
    public function excluir($id) {
        // Verifica se tem locações ativas
        if ($this->temLocacoesAtivas($id)) {
            throw new Exception("Cliente possui locações ativas e não pode ser excluído.");
        }
        
        $sql = "UPDATE {$this->table} SET ativo = 0 WHERE id = :id";
        
        try {
            $this->db->query($sql, ['id' => $id]);
            return true;
        } catch (Exception $e) {
            error_log("Erro ao excluir cliente: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se cliente tem locações ativas
     * 
     * @param int $id ID do cliente
     * @return bool
     */
    private function temLocacoesAtivas($id) {
        $sql = "SELECT COUNT(*) as total FROM locacoes 
                WHERE cliente_id = :id AND status = 'Ativa'";
        $result = $this->db->fetchOne($sql, ['id' => $id]);
        return $result['total'] > 0;
    }
    
    /**
     * Busca clientes por termo de pesquisa
     * 
     * @param string $termo Termo de busca
     * @return array
     */
    public function buscar($termo) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE ativo = 1 AND (
                    nome LIKE :termo OR 
                    email LIKE :termo OR 
                    cpf_cnpj LIKE :termo OR
                    telefone LIKE :termo
                )
                ORDER BY nome ASC";
        
        $termoLike = "%{$termo}%";
        return $this->db->fetchAll($sql, ['termo' => $termoLike]);
    }

    /**
     * Busca cliente por CPF ou CNPJ (exato)
     *
     * @param string $cpf_cnpj
     * @return array|false
     */
    public function buscarPorCpfCnpj($cpf_cnpj) {
        $sql = "SELECT * FROM {$this->table} WHERE cpf_cnpj = :cpf_cnpj AND ativo = 1 LIMIT 1";
        return $this->db->fetchOne($sql, ['cpf_cnpj' => $cpf_cnpj]);
    }
    
    /**
     * Valida dados do cliente
     * 
     * @return bool
     * @throws Exception
     */
    private function validar() {
        if (empty($this->nome)) {
            throw new Exception("Nome é obrigatório.");
        }
        
        // Email é opcional — validar apenas se fornecido
        if (!empty($this->email) && !validarEmail($this->email)) {
            throw new Exception("Email inválido.");
        }
        
        if (empty($this->telefone)) {
            throw new Exception("Telefone é obrigatório.");
        }
        
        // CPF/CNPJ agora é opcional
        // Verifica duplicidade de email apenas se informado
        if (!empty($this->email) && $this->emailExiste()) {
            throw new Exception("Email já cadastrado.");
        }
        // Só verifica duplicidade de CPF/CNPJ se informado
        if (!empty($this->cpf_cnpj) && $this->cpfCnpjExiste()) {
            throw new Exception("CPF/CNPJ já cadastrado.");
        }
        
        return true;
    }
    
    /**
     * Verifica se email já existe
     * 
     * @return bool
     */
    private function emailExiste() {
        $sql = "SELECT id FROM {$this->table} WHERE email = :email AND id != :id";
        $result = $this->db->fetchOne($sql, [
            'email' => $this->email,
            'id' => $this->id ?? 0
        ]);
        return $result !== false;
    }
    
    /**
     * Verifica se CPF/CNPJ já existe
     * 
     * @return bool
     */
    private function cpfCnpjExiste() {
        $sql = "SELECT id FROM {$this->table} WHERE cpf_cnpj = :cpf_cnpj AND id != :id";
        $result = $this->db->fetchOne($sql, [
            'cpf_cnpj' => $this->cpf_cnpj,
            'id' => $this->id ?? 0
        ]);
        return $result !== false;
    }
    
    /**
     * Conta total de clientes ativos
     * 
     * @return int
     */
    public function contarTotal() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE ativo = 1";
        $result = $this->db->fetchOne($sql);
        return (int) $result['total'];
    }
}
