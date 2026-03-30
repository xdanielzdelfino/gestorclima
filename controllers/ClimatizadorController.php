<?php
/**
 * Controller: ClimatizadorController
 * 
 * API REST para gerenciar climatizadores
 * 
 * @package GestorClima\Controllers
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Climatizador.php';

class ClimatizadorController {
    private $climatizador;
    
    public function __construct() {
        $this->climatizador = new Climatizador();
    }
    
    /**
     * Roteia requisição baseada no método HTTP
     */
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        try {
            switch ($method) {
                case 'GET':
                    $this->handleGet();
                    break;
                case 'POST':
                    $this->handlePost();
                    break;
                case 'PUT':
                    $this->handlePut();
                    break;
                case 'DELETE':
                    $this->handleDelete();
                    break;
                default:
                    jsonResponse(false, 'Método não suportado');
            }
        } catch (Exception $e) {
            jsonResponse(false, $e->getMessage());
        }
    }
    
    /**
     * GET - Listar ou buscar climatizadores
     */
    private function handleGet() {
        // Buscar por ID
        if (isset($_GET['id'])) {
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                jsonResponse(false, 'ID inválido');
            }
            
            $climatizador = $this->climatizador->buscarPorId($id);
            if ($climatizador) {
                jsonResponse(true, 'Climatizador encontrado', $climatizador);
            } else {
                jsonResponse(false, 'Climatizador não encontrado');
            }
        }
        
        // Listar apenas disponíveis
        if (isset($_GET['disponiveis'])) {
            $climatizadores = $this->climatizador->listarDisponiveis();
            jsonResponse(true, 'Climatizadores disponíveis', $climatizadores);
        }
        
        // Buscar por termo
        if (isset($_GET['buscar'])) {
            $termo = sanitize($_GET['buscar']);
            $climatizadores = $this->climatizador->buscar($termo);
            jsonResponse(true, 'Busca realizada', $climatizadores);
        }
        
        // Estatísticas por status
        if (isset($_GET['estatisticas'])) {
            $stats = $this->climatizador->contarPorStatus();
            jsonResponse(true, 'Estatísticas obtidas', $stats);
        }
        
        // Contar disponíveis
        if (isset($_GET['contar_disponiveis'])) {
            $total = $this->climatizador->contarDisponiveis();
            jsonResponse(true, 'Total de climatizadores disponíveis', ['total' => $total]);
        }
        
        // Listar todos
        $climatizadores = $this->climatizador->listarTodos();
        jsonResponse(true, 'Climatizadores listados', $climatizadores);
    }
    
    /**
     * POST - Criar novo climatizador
     */
    private function handlePost() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            jsonResponse(false, 'Dados inválidos');
        }
        
        // Sanitização
        $this->climatizador->codigo = sanitize($data['codigo'] ?? '');
        $this->climatizador->modelo = sanitize($data['modelo'] ?? '');
        $this->climatizador->marca = sanitize($data['marca'] ?? '');
        $this->climatizador->capacidade = sanitize($data['capacidade'] ?? '');
        $this->climatizador->tipo = sanitize($data['tipo'] ?? 'Portatil');
        $this->climatizador->descricao = sanitize($data['descricao'] ?? '');
        $this->climatizador->valor_diaria = filter_var($data['valor_diaria'] ?? 0, FILTER_VALIDATE_FLOAT);
    $this->climatizador->desconto_maximo = filter_var($data['desconto_maximo'] ?? 0, FILTER_VALIDATE_FLOAT);
        $this->climatizador->status = sanitize($data['status'] ?? 'Disponivel');
        $this->climatizador->estoque = filter_var($data['estoque'] ?? 0, FILTER_VALIDATE_INT); // Processa o estoque
        
        try {
            $id = $this->climatizador->criar();
            if ($id) {
                jsonResponse(true, 'Climatizador cadastrado com sucesso!', ['id' => $id]);
            } else {
                jsonResponse(false, 'Erro ao cadastrar climatizador');
            }
        } catch (Exception $e) {
            jsonResponse(false, $e->getMessage());
        }
    }
    
    /**
     * PUT - Atualizar climatizador existente ou status
     */
    private function handlePut() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['id'])) {
            jsonResponse(false, 'Dados inválidos');
        }
        
        $id = filter_var($data['id'], FILTER_VALIDATE_INT);
        
        // Atualizar apenas status
        if (isset($data['status_only']) && $data['status_only'] === true) {
            try {
                if ($this->climatizador->atualizarStatus($id, sanitize($data['status']))) {
                    jsonResponse(true, 'Status atualizado com sucesso!');
                } else {
                    jsonResponse(false, 'Erro ao atualizar status');
                }
            } catch (Exception $e) {
                jsonResponse(false, $e->getMessage());
            }
            return;
        }
        
        // Atualizar todos os dados
        $this->climatizador->id = $id;
        $this->climatizador->codigo = sanitize($data['codigo'] ?? '');
        $this->climatizador->modelo = sanitize($data['modelo'] ?? '');
        $this->climatizador->marca = sanitize($data['marca'] ?? '');
        $this->climatizador->capacidade = sanitize($data['capacidade'] ?? '');
        $this->climatizador->tipo = sanitize($data['tipo'] ?? 'Portatil');
        $this->climatizador->descricao = sanitize($data['descricao'] ?? '');
        $this->climatizador->valor_diaria = filter_var($data['valor_diaria'] ?? 0, FILTER_VALIDATE_FLOAT);
    $this->climatizador->desconto_maximo = filter_var($data['desconto_maximo'] ?? 0, FILTER_VALIDATE_FLOAT);
        $this->climatizador->status = sanitize($data['status'] ?? 'Disponivel');
        $this->climatizador->estoque = filter_var($data['estoque'] ?? 0, FILTER_VALIDATE_INT); // Processa o estoque
        
        try {
            if ($this->climatizador->atualizar()) {
                jsonResponse(true, 'Climatizador atualizado com sucesso!');
            } else {
                jsonResponse(false, 'Erro ao atualizar climatizador');
            }
        } catch (Exception $e) {
            jsonResponse(false, $e->getMessage());
        }
    }
    
    /**
     * DELETE - Excluir climatizador
     */
    private function handleDelete() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['id'])) {
            jsonResponse(false, 'ID não informado');
        }
        
        $id = filter_var($data['id'], FILTER_VALIDATE_INT);
        
        if (!$id) {
            jsonResponse(false, 'ID inválido');
        }
        
        try {
            if ($this->climatizador->excluir($id)) {
                jsonResponse(true, 'Climatizador excluído com sucesso!');
            } else {
                jsonResponse(false, 'Erro ao excluir climatizador');
            }
        } catch (Exception $e) {
            jsonResponse(false, $e->getMessage());
        }
    }
}

// Executar controller
$controller = new ClimatizadorController();
$controller->handleRequest();
