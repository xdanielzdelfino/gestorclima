<?php
/**
 * Controller: ClienteController
 * 
 * API REST para gerenciar clientes
 * 
 * @package GestorClima\Controllers
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Cliente.php';

class ClienteController {
    private $cliente;
    
    public function __construct() {
        $this->cliente = new Cliente();
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
     * GET - Listar ou buscar clientes
     */
    private function handleGet() {
        // Buscar por ID
        if (isset($_GET['id'])) {
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                jsonResponse(false, 'ID inválido');
            }
            
            $cliente = $this->cliente->buscarPorId($id);
            if ($cliente) {
                jsonResponse(true, 'Cliente encontrado', $cliente);
            } else {
                jsonResponse(false, 'Cliente não encontrado');
            }
        }
        
        // Buscar por termo
        if (isset($_GET['buscar'])) {
            $termo = sanitize($_GET['buscar']);
            $clientes = $this->cliente->buscar($termo);
            jsonResponse(true, 'Busca realizada', $clientes);
        }

        // Buscar por CPF/CNPJ exato
        if (isset($_GET['cpf_cnpj'])) {
            $cpf = sanitize($_GET['cpf_cnpj']);
            $cliente = $this->cliente->buscarPorCpfCnpj($cpf);
            if ($cliente) {
                jsonResponse(true, 'Cliente encontrado', $cliente);
            } else {
                jsonResponse(false, 'Cliente não encontrado');
            }
        }

        // Lookup público por CNPJ (proxy) usando BrasilAPI
        if (isset($_GET['lookup_cnpj'])) {
            $cnpj = preg_replace('/\D/', '', $_GET['lookup_cnpj']);
            if (strlen($cnpj) !== 14) {
                jsonResponse(false, 'CNPJ inválido');
            }

            // Cache file-based (TTL 24h)
            $cacheDir = __DIR__ . '/../cache';
            if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
            $cacheFile = $cacheDir . '/cnpj_' . $cnpj . '.json';
            $ttl = 24 * 60 * 60; // 24 horas

            // Retornar do cache se disponível e válido
            if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $ttl)) {
                $json = file_get_contents($cacheFile);
                $data = json_decode($json, true);
            } else {
                // Chamada externa sem expor a API no cliente (proxy)
                $url = "https://brasilapi.com.br/api/cnpj/v1/{$cnpj}";
                try {
                    $opts = [
                        "http" => [
                            "timeout" => 5,
                            "header" => "User-Agent: GestorClima/1.0\r\n"
                        ]
                    ];
                    $context = stream_context_create($opts);
                    $json = @file_get_contents($url, false, $context);
                    if ($json === false) {
                        jsonResponse(false, 'Erro ao consultar serviço externo');
                    }

                    $data = json_decode($json, true);
                    if (!$data || isset($data['message'])) {
                        jsonResponse(false, 'CNPJ não encontrado ou serviço indisponível');
                    }

                    // Gravar cache (silencioso em caso de falha)
                    @file_put_contents($cacheFile, json_encode($data));
                } catch (Exception $e) {
                    jsonResponse(false, 'Erro na consulta externa: ' . $e->getMessage());
                }
            }

            // Mapear campos úteis para preenchimento do formulário
            $mapped = [];
            $mapped['nome'] = $data['razao_social'] ?? $data['nome'] ?? $data['nome_empresarial'] ?? '';
            $mapped['fantasia'] = $data['nome_fantasia'] ?? $data['fantasia'] ?? '';
            $mapped['telefone'] = $data['ddd_telefone_1'] ?? $data['telefone'] ?? ($data['telefone_principal'] ?? '');
            $mapped['email'] = $data['email'] ?? '';

            // Endereço - compor de partes conhecidas
            $logradouro = $data['logradouro'] ?? ($data['street'] ?? '');
            $numero = $data['numero'] ?? ($data['number'] ?? '');
            $bairro = $data['bairro'] ?? ($data['neighborhood'] ?? '');
            $cidade = $data['municipio'] ?? ($data['city'] ?? '');
            $uf = $data['uf'] ?? ($data['state'] ?? '');
            $cep = $data['cep'] ?? ($data['postal_code'] ?? '');

            $mapped['endereco'] = trim("{$logradouro} {$numero} {$bairro}");
            $mapped['cidade'] = $cidade;
            $mapped['estado'] = $uf;
            $mapped['cep'] = preg_replace('/\D/', '', $cep);

            // Retornar raw para depuração caso necessário
            $mapped['raw'] = $data;

            jsonResponse(true, 'Consulta CNPJ realizada', $mapped);
        }

        // Lookup público por CEP (proxy) usando BrasilAPI
        if (isset($_GET['lookup_cep'])) {
            $cep = preg_replace('/\D/', '', $_GET['lookup_cep']);
            if (strlen($cep) !== 8) {
                jsonResponse(false, 'CEP inválido');
            }

            // Cache file-based (TTL 7 dias)
            $cacheDir = __DIR__ . '/../cache';
            if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
            $cacheFile = $cacheDir . '/cep_' . $cep . '.json';
            $ttl = 7 * 24 * 60 * 60; // 7 dias

            if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $ttl)) {
                $json = file_get_contents($cacheFile);
                $data = json_decode($json, true);
            } else {
                $url = "https://brasilapi.com.br/api/cep/v2/{$cep}";
                try {
                    $opts = [
                        "http" => [
                            "timeout" => 5,
                            "header" => "User-Agent: GestorClima/1.0\r\n"
                        ]
                    ];
                    $context = stream_context_create($opts);
                    $json = @file_get_contents($url, false, $context);
                    if ($json === false) {
                        jsonResponse(false, 'Erro ao consultar serviço externo');
                    }

                    $data = json_decode($json, true);
                    if (!$data || isset($data['message'])) {
                        jsonResponse(false, 'CEP não encontrado ou serviço indisponível');
                    }

                    @file_put_contents($cacheFile, json_encode($data));
                } catch (Exception $e) {
                    jsonResponse(false, 'Erro na consulta externa: ' . $e->getMessage());
                }
            }

            // Mapear campos úteis
            $mapped = [];
            $street = $data['street'] ?? ($data['logradouro'] ?? '');
            $neighborhood = $data['neighborhood'] ?? ($data['bairro'] ?? '');
            $city = $data['city'] ?? ($data['localidade'] ?? '');
            $state = $data['state'] ?? ($data['uf'] ?? '');
            $postal = $data['cep'] ?? ($data['postal_code'] ?? '');

            $mapped['endereco'] = trim("{$street} {$neighborhood}");
            $mapped['cidade'] = $city;
            $mapped['estado'] = $state;
            $mapped['cep'] = preg_replace('/\D/', '', $postal);
            $mapped['raw'] = $data;

            jsonResponse(true, 'Consulta CEP realizada', $mapped);
        }
        
        // Listar todos
        $clientes = $this->cliente->listarTodos();
        jsonResponse(true, 'Clientes listados', $clientes);
    }
    
    /**
     * POST - Criar novo cliente
     */
    private function handlePost() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            jsonResponse(false, 'Dados inválidos');
        }
        
        // Sanitização
        $this->cliente->nome = sanitize($data['nome'] ?? '');
        $this->cliente->email = sanitize($data['email'] ?? '');
        $this->cliente->telefone = sanitize($data['telefone'] ?? '');
        $this->cliente->cpf_cnpj = sanitize($data['cpf_cnpj'] ?? '');
        $this->cliente->endereco = sanitize($data['endereco'] ?? '');
        $this->cliente->cidade = sanitize($data['cidade'] ?? '');
        $this->cliente->estado = sanitize($data['estado'] ?? '');
        $this->cliente->cep = sanitize($data['cep'] ?? '');
        $this->cliente->observacoes = sanitize($data['observacoes'] ?? '');
        
        try {
            $id = $this->cliente->criar();
            if ($id) {
                jsonResponse(true, 'Cliente cadastrado com sucesso!', ['id' => $id]);
            } else {
                jsonResponse(false, 'Erro ao cadastrar cliente');
            }
        } catch (Exception $e) {
            jsonResponse(false, $e->getMessage());
        }
    }
    
    /**
     * PUT - Atualizar cliente existente
     */
    private function handlePut() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['id'])) {
            jsonResponse(false, 'Dados inválidos');
        }
        
        $this->cliente->id = filter_var($data['id'], FILTER_VALIDATE_INT);
        $this->cliente->nome = sanitize($data['nome'] ?? '');
        $this->cliente->email = sanitize($data['email'] ?? '');
        $this->cliente->telefone = sanitize($data['telefone'] ?? '');
        $this->cliente->cpf_cnpj = sanitize($data['cpf_cnpj'] ?? '');
        $this->cliente->endereco = sanitize($data['endereco'] ?? '');
        $this->cliente->cidade = sanitize($data['cidade'] ?? '');
        $this->cliente->estado = sanitize($data['estado'] ?? '');
        $this->cliente->cep = sanitize($data['cep'] ?? '');
        $this->cliente->observacoes = sanitize($data['observacoes'] ?? '');
        
        try {
            if ($this->cliente->atualizar()) {
                jsonResponse(true, 'Cliente atualizado com sucesso!');
            } else {
                jsonResponse(false, 'Erro ao atualizar cliente');
            }
        } catch (Exception $e) {
            jsonResponse(false, $e->getMessage());
        }
    }
    
    /**
     * DELETE - Excluir cliente
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
            if ($this->cliente->excluir($id)) {
                jsonResponse(true, 'Cliente excluído com sucesso!');
            } else {
                jsonResponse(false, 'Erro ao excluir cliente');
            }
        } catch (Exception $e) {
            jsonResponse(false, $e->getMessage());
        }
    }
}

// Executar controller
$controller = new ClienteController();
$controller->handleRequest();
