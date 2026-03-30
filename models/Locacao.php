<?php
/**
 * Model: Locacao
 * 
 * Gerencia operações CRUD da entidade Locação
 * 
 * @package GestorClima\Models
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

class Locacao {
    private $db;
    private $table = 'locacoes';
    
    // Propriedades
    public $id;
    public $cliente_id;
    public $climatizador_id;
    public $data_inicio;
    public $data_fim;
    public $data_devolucao_real;
    public $valor_diaria;
    public $quantidade_dias;
    public $valor_total;
    public $valor_pago;
    public $quantidade_climatizadores = 1;
    public $desconto = 0; // percentual
    public $aplicar_desconto = 0; // 0/1
    public $despesas_acessorias = 0.0; // valor monetário
    public $despesas_acessorias_tipo = ''; // label/descrição da despesa acessória selecionada
    public $status;
    public $observacoes;
    public $local_evento;
    public $responsavel;
    public $climatizadores; // JSON/texto com a lista de climatizadores (compatibilidade com novo frontend)
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Cache local para colunas existentes na tabela (evita múltiplas consultas)
     * @var array
     */
    private static $existingColumns = null;

    /**
     * Verifica se a tabela de locações possui a coluna informada
     * @param string $col
     * @return bool
     */
    private function tableHasColumn($col) {
        if (self::$existingColumns === null) {
            try {
                $sql = "SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = :db AND table_name = :table";
                $rows = $this->db->fetchAll($sql, ['db' => DB_NAME, 'table' => $this->table]);
                $cols = [];
                foreach ($rows as $r) { $cols[] = $r['COLUMN_NAME']; }
                self::$existingColumns = $cols;
            } catch (Exception $e) {
                // Em caso de falha, assumir que coluna não existe para manter compatibilidade
                self::$existingColumns = [];
            }
        }
        return in_array($col, self::$existingColumns, true);
    }

    /**
     * Tenta adicionar uma coluna VARCHAR(255) NULL na tabela caso não exista.
     * Retorna true se a coluna existe ou foi criada com sucesso.
     * @param string $col
     * @return bool
     */
    private function ensureColumnExists($col) {
        if ($this->tableHasColumn($col)) return true;
        try {
            $sql = "ALTER TABLE {$this->table} ADD COLUMN `{$col}` VARCHAR(255) NULL DEFAULT NULL";
            $this->db->query($sql);
            // invalidar cache e recarregar
            self::$existingColumns = null;
            return $this->tableHasColumn($col);
        } catch (Exception $e) {
            error_log('Falha ao criar coluna ' . $col . ' em ' . $this->table . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Garante que uma coluna do tipo TEXT exista na tabela (cria se necessário).
     * @param string $col
     * @return bool
     */
    private function ensureTextColumnExists($col) {
        if ($this->tableHasColumn($col)) return true;
        try {
            $sql = "ALTER TABLE {$this->table} ADD COLUMN `{$col}` TEXT NULL";
            $this->db->query($sql);
            self::$existingColumns = null;
            return $this->tableHasColumn($col);
        } catch (Exception $e) {
            error_log('Falha ao criar coluna TEXT ' . $col . ' em ' . $this->table . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica se uma coluna permite NULL (information_schema).
     *
     * @param string $col
     * @return bool
     */
    private function isColumnNullable($col) {
        try {
            $sql = "SELECT IS_NULLABLE FROM information_schema.columns WHERE table_schema = :db AND table_name = :table AND column_name = :col LIMIT 1";
            $row = $this->db->fetchOne($sql, ['db' => DB_NAME, 'table' => $this->table, 'col' => $col]);
            if (!$row || !isset($row['IS_NULLABLE'])) return false;
            return strtoupper(trim((string)$row['IS_NULLABLE'])) === 'YES';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Garante que uma coluna DATETIME aceite NULL (para datas opcionais).
     *
     * @param string $col
     * @return bool
     */
    private function ensureDateColumnNullable($col) {
        if (!$this->tableHasColumn($col)) return false;
        if ($this->isColumnNullable($col)) return true;
        try {
            $sql = "ALTER TABLE {$this->table} MODIFY `{$col}` DATETIME NULL DEFAULT NULL";
            $this->db->query($sql);
            // invalidar cache (colunas mudaram de definição)
            self::$existingColumns = null;
            return $this->isColumnNullable($col);
        } catch (Exception $e) {
            error_log('Falha ao tornar coluna ' . $col . ' anulável em ' . $this->table . ': ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lista todas as locações com informações de cliente e climatizador
     * 
     * @return array
     */
    public function listarTodas() {
        // Computa o status com base nas datas (Reserva / Ativa / Finalizada)
        $sql = "SELECT 
                    l.*,
                    CASE 
                        WHEN NOW() < l.data_inicio THEN 'Reserva'
                        WHEN NOW() BETWEEN l.data_inicio AND l.data_fim THEN 'Confirmada'
                        ELSE 'Finalizada'
                    END AS computed_status,
                    c.nome as cliente_nome,
                    c.telefone as cliente_telefone,
                    cl.codigo as climatizador_codigo,
                    cl.modelo as climatizador_modelo,
                    cl.marca as climatizador_marca
                FROM {$this->table} l
                INNER JOIN clientes c ON l.cliente_id = c.id
                INNER JOIN climatizadores cl ON l.climatizador_id = cl.id
                ORDER BY l.data_inicio DESC";
        
        $rows = $this->db->fetchAll($sql);
        // Normalizar campos e decodificar possível JSON de climatizadores
        foreach ($rows as &$r) {
            // tentar decodificar campo 'climatizadores' quando existir e for string
            if (isset($r['climatizadores']) && is_string($r['climatizadores'])) {
                $decoded = json_decode($r['climatizadores'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $r['climatizadores'] = $decoded;
                }
            }
            // Se já for array, garantir consistência das chaves e calcular valor_total baseado nele
            if (isset($r['climatizadores']) && is_array($r['climatizadores']) && count($r['climatizadores']) > 0) {
                $perDaySubtotal = 0.0;
                foreach ($r['climatizadores'] as $it) {
                    $qtd = isset($it['quantidade']) ? intval($it['quantidade']) : (isset($it['qtd']) ? intval($it['qtd']) : 1);
                    $vd = isset($it['valor_diaria']) ? floatval($it['valor_diaria']) : (isset($it['valor_unitario']) ? floatval($it['valor_unitario']) : 0.0);
                    $perDaySubtotal += ($vd * $qtd);
                }
                $dias = isset($r['quantidade_dias']) && intval($r['quantidade_dias']) > 0 ? intval($r['quantidade_dias']) : 1;
                $descontoPercent = isset($r['desconto']) ? floatval($r['desconto']) : 0.0;
                $despesas = isset($r['despesas_acessorias']) ? floatval($r['despesas_acessorias']) : 0.0;
                $total = ($perDaySubtotal * $dias) - (($perDaySubtotal) * $descontoPercent / 100) + $despesas;
                // Garantir número com duas casas
                $r['valor_total'] = round($total, 2);
            } else {
                // fallback: garantir tipo numérico
                if (isset($r['valor_total'])) $r['valor_total'] = floatval($r['valor_total']);
            }
        }
        unset($r);
        return $rows;
    }
    
    /**
     * Lista locações ativas/confirmadas
     * 
     * @return array
     */
    public function listarAtivas() {
        // Retornar locações cujo período contém NOW() (consideradas Confirmadas)
        $sql = "SELECT 
                    l.*,
                    CASE 
                        WHEN NOW() < l.data_inicio THEN 'Reserva'
                        WHEN NOW() BETWEEN l.data_inicio AND l.data_fim THEN 'Confirmada'
                        ELSE 'Finalizada'
                    END AS computed_status,
                    c.nome as cliente_nome,
                    c.telefone as cliente_telefone,
                    cl.codigo as climatizador_codigo,
                    cl.modelo as climatizador_modelo,
                    cl.marca as climatizador_marca
                FROM {$this->table} l
                INNER JOIN clientes c ON l.cliente_id = c.id
                INNER JOIN climatizadores cl ON l.climatizador_id = cl.id
                WHERE (NOW() BETWEEN l.data_inicio AND l.data_fim) AND l.status != 'Cancelada'
                ORDER BY l.data_fim ASC";
        $rows = $this->db->fetchAll($sql);
        // aplicar mesma normalização usada em listarTodas
        foreach ($rows as &$r) {
            if (isset($r['climatizadores']) && is_string($r['climatizadores'])) {
                $decoded = json_decode($r['climatizadores'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $r['climatizadores'] = $decoded;
                }
            }
            if (isset($r['climatizadores']) && is_array($r['climatizadores']) && count($r['climatizadores']) > 0) {
                $perDaySubtotal = 0.0;
                foreach ($r['climatizadores'] as $it) {
                    $qtd = isset($it['quantidade']) ? intval($it['quantidade']) : (isset($it['qtd']) ? intval($it['qtd']) : 1);
                    $vd = isset($it['valor_diaria']) ? floatval($it['valor_diaria']) : (isset($it['valor_unitario']) ? floatval($it['valor_unitario']) : 0.0);
                    $perDaySubtotal += ($vd * $qtd);
                }
                $dias = isset($r['quantidade_dias']) && intval($r['quantidade_dias']) > 0 ? intval($r['quantidade_dias']) : 1;
                $descontoPercent = isset($r['desconto']) ? floatval($r['desconto']) : 0.0;
                $despesas = isset($r['despesas_acessorias']) ? floatval($r['despesas_acessorias']) : 0.0;
                $total = ($perDaySubtotal * $dias) - (($perDaySubtotal) * $descontoPercent / 100) + $despesas;
                $r['valor_total'] = round($total, 2);
            } else {
                if (isset($r['valor_total'])) $r['valor_total'] = floatval($r['valor_total']);
            }
        }
        unset($r);
        return $rows;
    }

    /**
     * Atualiza todas as locações vencidas (data_fim < NOW()) para status 'Finalizada'
     * Retorna número de linhas afetadas.
     * Utiliza uma única query UPDATE para que triggers de banco (se existir) sejam disparadas.
     *
     * @return int
     */
    public function atualizarStatusVencidas() {
        $sql = "UPDATE {$this->table} SET status = 'Finalizada', data_devolucao_real = data_fim WHERE status IN ('Ativa','Confirmada') AND data_fim < NOW()";
        try {
            $stmt = $this->db->query($sql);
            // rowCount indica quantas linhas foram atualizadas
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log('Erro ao atualizar status de locações vencidas: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Lista locações finalizadas
     * 
     * @return array
     */
    public function listarFinalizadas() {
        // Finalizadas são aquelas cujo data_fim já passou ou explicitamente marcadas como Finalizada
        $sql = "SELECT 
                    l.*,
                    CASE 
                        WHEN NOW() < l.data_inicio THEN 'Reserva'
                        WHEN NOW() BETWEEN l.data_inicio AND l.data_fim THEN 'Confirmada'
                        ELSE 'Finalizada'
                    END AS computed_status,
                    c.nome as cliente_nome,
                    c.telefone as cliente_telefone,
                    cl.codigo as climatizador_codigo,
                    cl.modelo as climatizador_modelo,
                    cl.marca as climatizador_marca
                FROM {$this->table} l
                INNER JOIN clientes c ON l.cliente_id = c.id
                INNER JOIN climatizadores cl ON l.climatizador_id = cl.id
                WHERE (NOW() > l.data_fim) OR l.status = 'Finalizada'
                ORDER BY COALESCE(l.data_devolucao_real, l.data_fim) DESC";
        $rows = $this->db->fetchAll($sql);
        foreach ($rows as &$r) {
            if (isset($r['climatizadores']) && is_string($r['climatizadores'])) {
                $decoded = json_decode($r['climatizadores'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $r['climatizadores'] = $decoded;
                }
            }
            if (isset($r['climatizadores']) && is_array($r['climatizadores']) && count($r['climatizadores']) > 0) {
                $perDaySubtotal = 0.0;
                foreach ($r['climatizadores'] as $it) {
                    $qtd = isset($it['quantidade']) ? intval($it['quantidade']) : (isset($it['qtd']) ? intval($it['qtd']) : 1);
                    $vd = isset($it['valor_diaria']) ? floatval($it['valor_diaria']) : (isset($it['valor_unitario']) ? floatval($it['valor_unitario']) : 0.0);
                    $perDaySubtotal += ($vd * $qtd);
                }
                $dias = isset($r['quantidade_dias']) && intval($r['quantidade_dias']) > 0 ? intval($r['quantidade_dias']) : 1;
                $descontoPercent = isset($r['desconto']) ? floatval($r['desconto']) : 0.0;
                $despesas = isset($r['despesas_acessorias']) ? floatval($r['despesas_acessorias']) : 0.0;
                $total = ($perDaySubtotal * $dias) - (($perDaySubtotal) * $descontoPercent / 100) + $despesas;
                $r['valor_total'] = round($total, 2);
            } else {
                if (isset($r['valor_total'])) $r['valor_total'] = floatval($r['valor_total']);
            }
        }
        unset($r);
        return $rows;
    }

    /**
     * Busca uma locação pelo ID
     * 
     * @param int $id ID da locação
     * @return array|false
     */
    public function buscarPorId($id) {
        $sql = "SELECT 
                    l.*,
                    CASE 
                        WHEN NOW() < l.data_inicio THEN 'Reserva'
                        WHEN NOW() BETWEEN l.data_inicio AND l.data_fim THEN 'Confirmada'
                        ELSE 'Finalizada'
                    END AS computed_status,
                    c.nome as cliente_nome,
                    c.email as cliente_email,
                    c.cpf_cnpj as cliente_cpf_cnpj,
                    c.telefone as cliente_telefone,
                    c.endereco as cliente_endereco,
                    cl.codigo as climatizador_codigo,
                    cl.modelo as climatizador_modelo,
                    cl.marca as climatizador_marca,
                    cl.capacidade as climatizador_capacidade
                FROM {$this->table} l
                INNER JOIN clientes c ON l.cliente_id = c.id
                INNER JOIN climatizadores cl ON l.climatizador_id = cl.id
                WHERE l.id = :id";
        $row = $this->db->fetchOne($sql, ['id' => $id]);
        // Decodificar JSON de climatizadores, se existir
        if ($row && isset($row['climatizadores']) && is_string($row['climatizadores'])) {
            $decoded = json_decode($row['climatizadores'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $row['climatizadores'] = $decoded;
            }
        }
        // Fallback para registros legados: se não houver "climatizadores" válido, construir um item único
        if ($row && (!isset($row['climatizadores']) || !is_array($row['climatizadores']) || count($row['climatizadores']) === 0)) {
            $descricao = null;
            if (!empty($row['climatizador_marca']) || !empty($row['climatizador_modelo'])) {
                $descricao = trim(($row['climatizador_marca'] ?? '') . ' ' . ($row['climatizador_modelo'] ?? ''));
            }
            if (empty($descricao)) {
                $descricao = 'Climatizador #' . ($row['climatizador_id'] ?? '');
            }
            $row['climatizadores'] = [[
                'climatizador_id' => (int)($row['climatizador_id'] ?? 0),
                'descricao' => $descricao,
                'quantidade' => (int)($row['quantidade_climatizadores'] ?? 1),
                'valor_diaria' => (float)($row['valor_diaria'] ?? 0),
                'quantidade_dias' => (int)($row['quantidade_dias'] ?? 1),
            ]];
        }
        return $row;
    }

    /**
     * Cria uma nova locação
     * 
     * @return int|false ID da locação criada ou false em caso de falha
     */
    public function criar() {
        if (!$this->validar()) return false;
        // Datas são opcionais. Quando vazias, garantir que o schema aceite NULL.
        if (empty($this->data_inicio)) {
            $this->ensureDateColumnNullable('data_inicio');
        }
        if (empty($this->data_fim)) {
            $this->ensureDateColumnNullable('data_fim');
        }
        $this->calcularValores();
        // Construir INSERT dinamicamente conforme colunas existentes (compatibilidade com esquemas antigos)
        $candidates = [
            'cliente_id','climatizador_id','data_inicio','data_fim','valor_diaria',
            'quantidade_dias','quantidade_climatizadores','desconto','aplicar_desconto','valor_total','valor_pago','status','observacoes','local_evento','despesas_acessorias','despesas_acessorias_tipo','responsavel','climatizadores'
        ];

        // Garantir tentativa de criar colunas opcionais se valores estiverem presentes
        if (!empty($this->despesas_acessorias_tipo) && !$this->tableHasColumn('despesas_acessorias_tipo')) {
            try { $this->ensureColumnExists('despesas_acessorias_tipo'); } catch (Exception $e) { }
        }
        if (!empty($this->climatizadores) && !$this->tableHasColumn('climatizadores')) {
            try { $this->ensureTextColumnExists('climatizadores'); } catch (Exception $e) { }
        }

        $fields = [];
        $placeholders = [];
        $params = [];
        foreach ($candidates as $col) {
            if ($this->tableHasColumn($col)) {
                $fields[] = "`{$col}`";
                $placeholders[] = ":{$col}";
                // mapear valor do objeto para o param (usar null quando vazio)
                $val = null;
                switch ($col) {
                    case 'cliente_id': $val = $this->cliente_id; break;
                    case 'climatizador_id': $val = $this->climatizador_id; break;
                    case 'data_inicio': $val = $this->data_inicio; break;
                    case 'data_fim': $val = $this->data_fim; break;
                    case 'valor_diaria': $val = $this->valor_diaria; break;
                    case 'quantidade_dias': $val = $this->quantidade_dias; break;
                    case 'quantidade_climatizadores': $val = $this->quantidade_climatizadores; break;
                    case 'desconto': $val = $this->desconto; break;
                    case 'aplicar_desconto': $val = $this->aplicar_desconto ? 1 : 0; break;
                    case 'valor_total': $val = $this->valor_total; break;
                    case 'valor_pago': $val = $this->valor_pago ?? 0; break;
                    case 'status': $val = 'Reserva'; break;
                    case 'observacoes': $val = $this->observacoes; break;
                    case 'local_evento': $val = $this->local_evento; break;
                    case 'despesas_acessorias': $val = isset($this->despesas_acessorias) ? $this->despesas_acessorias : 0.0; break;
                    case 'despesas_acessorias_tipo': $val = isset($this->despesas_acessorias_tipo) ? $this->despesas_acessorias_tipo : null; break;
                    case 'responsavel': $val = isset($this->responsavel) ? $this->responsavel : null; break;
                    case 'climatizadores': $val = isset($this->climatizadores) ? (is_string($this->climatizadores) ? $this->climatizadores : json_encode($this->climatizadores)) : null; break;
                    default: $val = null;
                }
                $params[$col] = $val;
            }
        }

        if (empty($fields)) return false;

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', array_map(function($p){ return ':' . trim($p, ':'); }, $placeholders)) . ")";

        try {
            $this->db->beginTransaction();
            $this->db->query($sql, $params);
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erro ao criar locação: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Finaliza uma locação
     * 
     * @param int $id ID da locação
     * @param string|null $dataDevolucao Data de devolução
     * @return bool
     * @throws Exception
     */
    public function finalizar($id, $dataDevolucao = null) {
        $locacao = $this->buscarPorId($id);
        if (!$locacao || !in_array(($locacao['status'] ?? ''), ['Confirmada','Ativa'], true)) {
            throw new Exception('Locação não encontrada ou já finalizada.');
        }
        $dataDevolucao = $dataDevolucao ?? date('Y-m-d');
        $sql = "UPDATE {$this->table} SET 
                status = 'Finalizada',
                data_devolucao_real = :data_devolucao
                WHERE id = :id";
        try {
            $this->db->beginTransaction();
            $this->db->query($sql, [ 'id' => $id, 'data_devolucao' => $dataDevolucao ]);
            $this->atualizarStatusClimatizador($locacao['climatizador_id'], 'Disponivel');
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erro ao finalizar locação: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Efetiva uma locação, mudando seu status para 'Ativa'
     * 
     * @param int $id ID da locação
     * @return bool
     * @throws Exception
     */
    public function efetivar($id) {
        $locacao = $this->buscarPorId($id);
        if (!$locacao) throw new Exception('Locação não encontrada');
        if (isset($locacao['status']) && ($locacao['status'] === 'Confirmada' || $locacao['status'] === 'Ativa')) throw new Exception('Locação já está confirmada');
        $sql = "UPDATE {$this->table} SET status = 'Confirmada' WHERE id = :id";
        try {
            $this->db->beginTransaction();
            $this->db->query($sql, ['id' => $id]);
            $this->atualizarStatusClimatizador($locacao['climatizador_id'], 'Locado');
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erro ao efetivar locação: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Atualiza os dados de uma locação
     * 
     * @param int $id ID da locação
     * @param array $data Dados a serem atualizados
     * @return bool
     */
    public function atualizar($id, array $data) {
        if (empty($id) || empty($data)) {
            error_log("Atualização falhou: ID ou dados vazios.");
            return false;
        }
        error_log("Atualizando locação ID: $id com os dados: " . json_encode($data));
    $allowed = ['cliente_id','climatizador_id','data_inicio','data_fim','valor_diaria','quantidade_dias','quantidade_climatizadores','valor_total','observacoes','local_evento','desconto','aplicar_desconto','despesas_acessorias','despesas_acessorias_tipo','responsavel','climatizadores'];
        // Se o payload contém despesas_acessorias_tipo, tentar criar a coluna automaticamente
        if (array_key_exists('despesas_acessorias_tipo', $data) && !$this->tableHasColumn('despesas_acessorias_tipo')) {
            try { $this->ensureColumnExists('despesas_acessorias_tipo'); } catch (
                Exception $e) { /* ignore */ }
        }
        // Se o payload contém 'climatizadores' (JSON/text), tentar criar coluna TEXT automaticamente
        if (array_key_exists('climatizadores', $data) && !$this->tableHasColumn('climatizadores')) {
            try { $this->ensureTextColumnExists('climatizadores'); } catch (Exception $e) { /* ignore */ }
        }

        // Se datas forem limpas (null/vazio), garantir que o schema aceite NULL.
        if (array_key_exists('data_inicio', $data) && empty($data['data_inicio'])) {
            $this->ensureDateColumnNullable('data_inicio');
        }
        if (array_key_exists('data_fim', $data) && empty($data['data_fim'])) {
            $this->ensureDateColumnNullable('data_fim');
        }

        // Filtrar allowed para colunas que existam na tabela (compatibilidade)
        $setParts = [];
        $params = ['id' => $id];
        foreach ($allowed as $field) {
            // ignorar campos que não existem fisicamente no banco
            if (!$this->tableHasColumn($field)) continue;
            if (array_key_exists($field, $data)) {
                $setParts[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }
        if (empty($setParts)) return false;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE id = :id";
        try {
            $this->db->query($sql, $params);
            return true;
        } catch (Exception $e) {
            error_log('Erro ao atualizar locacao: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancela uma locação, mudando seu status para 'Cancelada'
     * 
     * @param int $id ID da locação
     * @return bool
     * @throws Exception
     */
    public function cancelar($id) {
        $locacao = $this->buscarPorId($id);
        if (!$locacao) throw new Exception('Locação não encontrada.');
        $currentStatus = $locacao['status'] ?? null;
        if (!in_array($currentStatus, ['Confirmada','Ativa','Reserva'], true)) throw new Exception("Só é possível cancelar locações em status 'Reserva' ou 'Confirmada'.");
        $sql = "UPDATE {$this->table} SET status = 'Cancelada' WHERE id = :id";
        try {
            $this->db->beginTransaction();
            $this->db->query($sql, ['id' => $id]);
            if (in_array($currentStatus, ['Confirmada','Ativa'], true)) {
                $this->atualizarStatusClimatizador($locacao['climatizador_id'], 'Disponivel');
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erro ao cancelar locação: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Atualiza o status de um climatizador
     * 
     * @param int $climatizadorId ID do climatizador
     * @param string $status Novo status
     */
    private function atualizarStatusClimatizador($climatizadorId, $status) {
        $sql = "UPDATE climatizadores SET status = :status WHERE id = :id";
        $this->db->query($sql, [ 'id' => $climatizadorId, 'status' => $status ]);
    }

    /**
     * Calcula os valores da locação (quantidade de dias, valor total, etc.)
     */
    private function calcularValores() {
        $hasInicio = !empty($this->data_inicio);
        $hasFim = !empty($this->data_fim);

        // Se o usuário informou manualmente 'quantidade_dias' (>0), respeitar esse valor.
        // Caso contrário, calcular automaticamente a partir do intervalo de datas (quando ambas existirem).
        if (isset($this->quantidade_dias) && intval($this->quantidade_dias) > 0) {
            $this->quantidade_dias = intval($this->quantidade_dias);
        } elseif ($hasInicio && $hasFim) {
            $dataInicio = new DateTime($this->data_inicio);
            $dataFim = new DateTime($this->data_fim);
            $diff = $dataInicio->diff($dataFim);
            // total de horas aproximado (dias * 24 + horas restantes)
            $intervaloHoras = ($diff->days * 24) + $diff->h;
            // Se o intervalo total em horas for menor ou igual a 24, considerar 1 diária
            $this->quantidade_dias = ($intervaloHoras <= 24) ? 1 : ceil($intervaloHoras / 24);
        } else {
            // Sem datas: usar 1 dia como fallback
            $this->quantidade_dias = 1;
        }

        $dias = max(1, intval($this->quantidade_dias ?? 1));
        $subtotal = $dias * $this->valor_diaria * ($this->quantidade_climatizadores ?? 1);
        $descontoPercent = $this->desconto ?? 0;
        if ($descontoPercent > 0) {
            $this->valor_total = $subtotal - ($subtotal * $descontoPercent / 100);
        } else {
            $this->valor_total = $subtotal;
        }
        // Incluir despesas acessórias no valor total
        $despesas = isset($this->despesas_acessorias) ? floatval($this->despesas_acessorias) : 0.0;
        $this->valor_total = $this->valor_total + $despesas;
    }

    /**
     * Valida os dados da locação antes de criar ou atualizar
     * 
     * @return bool
     * @throws Exception
     */
    private function validar() {
        if (empty($this->cliente_id)) throw new Exception('Cliente é obrigatório.');
        if (empty($this->climatizador_id)) throw new Exception('Climatizador é obrigatório.');
        // Datas são opcionais. Quando informadas, validar consistência.
        if (!empty($this->data_inicio) && strtotime($this->data_inicio) === false) {
            throw new Exception('Data de início inválida.');
        }
        if (!empty($this->data_fim) && strtotime($this->data_fim) === false) {
            throw new Exception('Data de fim inválida.');
        }
        if (!empty($this->data_inicio) && !empty($this->data_fim)) {
            if (strtotime($this->data_fim) < strtotime($this->data_inicio)) {
                throw new Exception('Data de fim deve ser posterior à data de início.');
            }
        }
        if (!$this->climatizadorDisponivel()) throw new Exception('Climatizador não está disponível para locação.');
        return true;
    }

    /**
     * Verifica se climatizador está disponível
     * 
     * @return bool
     */
    private function climatizadorDisponivel() {
        // Checa disponibilidade baseada em estoque menos locações ativas
        $sql = "SELECT 
                    c.status,
                    COALESCE(c.estoque, 0) as estoque,
                    COALESCE(a.active_count, 0) as active_count,
                    GREATEST(COALESCE(c.estoque, 0) - COALESCE(a.active_count, 0), 0) as disponivel
                FROM climatizadores c
                LEFT JOIN (
                    SELECT climatizador_id, COUNT(*) as active_count
                    FROM locacoes
                    WHERE status != 'Cancelada' AND (NOW() BETWEEN data_inicio AND data_fim)
                    GROUP BY climatizador_id
                ) a ON a.climatizador_id = c.id
                WHERE c.id = :id";

        $result = $this->db->fetchOne($sql, ['id' => $this->climatizador_id]);
        if (!$result) return false;
        // Se estiver marcado como Inativo, não permite locação
        if (isset($result['status']) && strtolower(trim($result['status'])) === 'inativo') {
            return false;
        }
        return isset($result['disponivel']) && intval($result['disponivel']) > 0;
    }
    
    /**
     * Obtém estatísticas do dashboard
     * 
     * @return array
     */
    public function obterEstatisticas() {
        // Contar locações confirmadas/ativas considerando status e data_fim
        $sql = "SELECT 
                    SUM(CASE WHEN status IN ('Ativa','Confirmada') AND data_fim >= NOW() THEN 1 ELSE 0 END) as locacoes_ativas,
                    COALESCE(SUM(CASE WHEN status IN ('Ativa','Confirmada') AND data_fim >= NOW() THEN valor_total ELSE 0 END), 0) as receita_ativa,
                    COALESCE(SUM(CASE 
                        WHEN status = 'Finalizada' AND MONTH(data_fim) = MONTH(NOW()) AND YEAR(data_fim) = YEAR(NOW()) THEN valor_total
                        ELSE 0 END), 0) as receita_mes
                FROM {$this->table}";
        
        return $this->db->fetchOne($sql);
    }

    /**
     * Exclui uma locação (remoção física)
     *
     * @param int $id ID da locação
     * @return bool
     * @throws Exception
     */
    public function excluir($id) {
        $locacao = $this->buscarPorId($id);

        if (!$locacao) {
            throw new Exception("Locação não encontrada.");
        }

        try {
            $this->db->beginTransaction();

            // Atualiza status do climatizador para "Disponivel" caso esteja locado
            if (isset($locacao['climatizador_id'])) {
                $this->atualizarStatusClimatizador($locacao['climatizador_id'], 'Disponivel');
            }

            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            $this->db->query($sql, ['id' => $id]);

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erro ao excluir locação: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Busca detalhes de um climatizador pelo ID
     * 
     * @param int $climatizadorId ID do climatizador
     * @return array|false
     */
    public function buscarClimatizadorPorId($climatizadorId) {
        $sql = "SELECT 
                    id, 
                    codigo, 
                    modelo, 
                    marca, 
                    capacidade, 
                    desconto_maximo
                FROM climatizadores
                WHERE id = :id";

        return $this->db->fetchOne($sql, ['id' => $climatizadorId]);
    }
}
