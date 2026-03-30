<?php
/**
 * Model: Climatizador
 * 
 * Gerencia operações CRUD da entidade Climatizador
 * 
 * @package GestorClima\Models
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

class Climatizador {
    private $db;
    private $table = 'climatizadores';

    public $id;
    public $codigo;
    public $modelo;
    public $marca;
    public $capacidade;
    public $tipo;
    public $descricao;
    public $valor_diaria;
    public $status;
    public $estoque; // Adicionado para gerenciar o estoque
    public $desconto_maximo; // Percentual de desconto máximo
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lista todos os climatizadores (exceto inativos)
     * 
     * @return array
     */
    public function listarTodos() {
    // Selecionar campos explicitamente para garantir presença de desconto_maximo
    $sql = "SELECT id, codigo, modelo, marca, capacidade, tipo, descricao, valor_diaria, status, COALESCE(estoque,0) as estoque, COALESCE(desconto_maximo,0) as desconto_maximo
        FROM {$this->table}
        WHERE status != 'Inativo'
        ORDER BY codigo ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Lista apenas climatizadores disponíveis
     * 
     * @return array
     */
    public function listarDisponiveis() {
        // Retorna modelos com estoque líquido positivo (estoque - locações confirmadas)
        $sql = "SELECT 
                    c.id,
                    c.codigo,
                    c.modelo,
                    c.marca,
                    c.capacidade,
                    c.valor_diaria,
                    c.desconto_maximo,
                    COALESCE(c.estoque, 0) as estoque,
                    GREATEST(COALESCE(c.estoque, 0) - COALESCE(a.active_count, 0), 0) as disponivel
                FROM {$this->table} c
                LEFT JOIN (
                    SELECT climatizador_id, COUNT(*) as active_count
                    FROM locacoes
                    WHERE status = 'Ativa'
                    GROUP BY climatizador_id
                ) a ON a.climatizador_id = c.id
                WHERE c.status != 'Inativo' AND (COALESCE(c.estoque, 0) - COALESCE(a.active_count, 0)) > 0
                ORDER BY c.modelo ASC";

        return $this->db->fetchAll($sql);
    }
    
    /**
     * Busca climatizador por ID
     * 
     * @param int $id ID do climatizador
     * @return array|false
     */
    public function buscarPorId($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }
    
    /**
     * Cria novo climatizador
     * 
     * @return int|false ID do climatizador criado ou false
     */
    public function criar() {
        // Validações
        if (!$this->validar()) {
            return false;
        }

        $sql = "INSERT INTO {$this->table} (codigo, modelo, marca, capacidade, tipo, descricao, valor_diaria, estoque, desconto_maximo) 
                VALUES (:codigo, :modelo, :marca, :capacidade, :tipo, :descricao, :valor_diaria, :estoque, :desconto_maximo)";

        $params = [
            'codigo' => $this->codigo,
            'modelo' => $this->modelo,
            'marca' => $this->marca,
            'capacidade' => $this->capacidade,
            'tipo' => $this->tipo,
            'descricao' => $this->descricao,
            'valor_diaria' => $this->valor_diaria,
            'estoque' => $this->estoque,
            'desconto_maximo' => $this->desconto_maximo
        ];

        return $this->db->insert($sql, $params);
    }
    
    /**
     * Atualiza climatizador existente
     * 
     * @return bool
     */
    public function atualizar() {
        // Validações
        if (!$this->validar()) {
            return false;
        }
        
        $sql = "UPDATE {$this->table} SET
                codigo = :codigo,
                modelo = :modelo,
                marca = :marca,
                capacidade = :capacidade,
                tipo = :tipo,
                descricao = :descricao,
                valor_diaria = :valor_diaria,
                status = :status,
                estoque = :estoque,
                desconto_maximo = :desconto_maximo
                WHERE id = :id";
        
        try {
            $this->db->query($sql, [
                'id' => $this->id,
                'codigo' => $this->codigo,
                'modelo' => $this->modelo,
                'marca' => $this->marca,
                'capacidade' => $this->capacidade,
                'tipo' => $this->tipo,
                'descricao' => $this->descricao,
                'valor_diaria' => $this->valor_diaria,
                'status' => $this->status,
                'estoque' => $this->estoque,
                'desconto_maximo' => $this->desconto_maximo
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao atualizar climatizador: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza apenas o status do climatizador
     * 
     * @param int $id ID do climatizador
     * @param string $novoStatus Novo status
     * @return bool
     */
    public function atualizarStatus($id, $novoStatus) {
        $statusPermitidos = ['Disponivel', 'Locado', 'Manutencao', 'Inativo'];
        
        if (!in_array($novoStatus, $statusPermitidos)) {
            throw new Exception("Status inválido.");
        }
        
        $sql = "UPDATE {$this->table} SET status = :status WHERE id = :id";
        
        try {
            $this->db->query($sql, [
                'id' => $id,
                'status' => $novoStatus
            ]);
            return true;
        } catch (Exception $e) {
            error_log("Erro ao atualizar status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Exclui climatizador (soft delete - marca como inativo)
     * 
     * @param int $id ID do climatizador
     * @return bool
     */
    public function excluir($id) {
        // Verifica se está em locação ativa
        if ($this->estaLocado($id)) {
            throw new Exception("Climatizador está em locação ativa e não pode ser excluído.");
        }
        
        $sql = "UPDATE {$this->table} SET status = 'Inativo' WHERE id = :id";
        
        try {
            $this->db->query($sql, ['id' => $id]);
            return true;
        } catch (Exception $e) {
            error_log("Erro ao excluir climatizador: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se climatizador está locado
     * 
     * @param int $id ID do climatizador
     * @return bool
     */
    private function estaLocado($id) {
        $sql = "SELECT COUNT(*) as total FROM locacoes 
                WHERE climatizador_id = :id AND status = 'Ativa'";
        $result = $this->db->fetchOne($sql, ['id' => $id]);
        return $result['total'] > 0;
    }
    
    /**
     * Busca climatizadores por termo
     * 
     * @param string $termo Termo de busca
     * @return array
     */
    public function buscar($termo) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE status != 'Inativo' AND (
                    codigo LIKE :termo OR 
                    modelo LIKE :termo OR 
                    marca LIKE :termo OR
                    capacidade LIKE :termo
                )
                ORDER BY modelo ASC";
        
        $termoLike = "%{$termo}%";
        return $this->db->fetchAll($sql, ['termo' => $termoLike]);
    }
    
    /**
     * Valida dados do climatizador
     * 
     * @return bool
     * @throws Exception
     */
    private function validar() {
        if (empty($this->codigo)) {
            throw new Exception("Código é obrigatório.");
        }
        
        if (empty($this->modelo)) {
            throw new Exception("Modelo é obrigatório.");
        }
        
        if (empty($this->marca)) {
            throw new Exception("Marca é obrigatória.");
        }
        
        if (empty($this->valor_diaria) || $this->valor_diaria <= 0) {
            throw new Exception("Valor da diária deve ser maior que zero.");
        }
        
        // Verifica duplicidade de código
        if ($this->codigoExiste()) {
            throw new Exception("Código já cadastrado.");
        }
        
        return true;
    }
    
    /**
     * Verifica se código já existe
     * 
     * @return bool
     */
    private function codigoExiste() {
        $sql = "SELECT id FROM {$this->table} WHERE codigo = :codigo AND id != :id";
        $result = $this->db->fetchOne($sql, [
            'codigo' => $this->codigo,
            'id' => $this->id ?? 0
        ]);
        return $result !== false;
    }
    
    /**
     * Conta climatizadores por status
     * 
     * @return array
     */
    public function contarPorStatus() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Disponivel' THEN 1 ELSE 0 END) as disponiveis,
                    SUM(CASE WHEN status = 'Locado' THEN 1 ELSE 0 END) as locados,
                    SUM(CASE WHEN status = 'Manutencao' THEN 1 ELSE 0 END) as manutencao
                FROM {$this->table}
                WHERE status != 'Inativo'";
        
        return $this->db->fetchOne($sql);
    }
    
    /**
     * Conta climatizadores disponíveis em estoque
     * 
     * @return int
     */
    public function contarDisponiveis() {
        // Calcula unidades disponíveis em estoque por modelo menos as unidades atualmente locadas (status = 'Ativa')
        $sql = "SELECT COALESCE(SUM(GREATEST(c.estoque - IFNULL(a.active_count, 0), 0)), 0) as total
                FROM {$this->table} c
                LEFT JOIN (
                    SELECT climatizador_id, COUNT(*) as active_count
                    FROM locacoes
                    WHERE status = 'Ativa'
                    GROUP BY climatizador_id
                ) a ON a.climatizador_id = c.id
                WHERE c.status != 'Inativo'";

        $result = $this->db->fetchOne($sql);
        return isset($result['total']) ? (int)$result['total'] : 0;
    }
}
