<?php
/**
 * Controller: LocacaoController
 * 
 * API REST para gerenciar locações
 * 
 * @package GestorClima\Controllers
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Locacao.php';

class LocacaoController {
    private $locacao;
    
    public function __construct() {
        $this->locacao = new Locacao();
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
     * GET - Listar ou buscar locações
     */
    private function handleGet() {
        // Atualização automática e interna dos status, sem depender de CRON.
        // Usa um "throttle" com arquivo em cache para executar no máximo a cada 5 minutos.
        $this->ensureStatusesUpToDate();
        // Buscar por ID
        if (isset($_GET['id'])) {
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                jsonResponse(false, 'ID inválido');
            }
            
            $locacao = $this->locacao->buscarPorId($id);
            if ($locacao) {
                jsonResponse(true, 'Locação encontrada', $locacao);
            } else {
                jsonResponse(false, 'Locação não encontrada');
            }
        }
        
        // Retornar eventos para o calendário
        if (isset($_GET['calendario'])) {
            // Retornamos todas as locações (pode ser filtrado no futuro) e mapeamos
            // para o formato esperado pelo frontend/FullCalendar.
            $all = $this->locacao->listarTodas();
            $events = [];
            foreach ($all as $l) {
                // Ignorar locações canceladas no calendário
                $status = isset($l['status']) ? trim($l['status']) : '';
                if (strtolower($status) === 'cancelada') continue;

                // Título: Cliente - Climatizador (fallback para ID)
                $titleParts = [];
                if (!empty($l['cliente_nome'])) $titleParts[] = $l['cliente_nome'];
                if (!empty($l['climatizador_modelo'])) $titleParts[] = $l['climatizador_modelo'];
                $title = !empty($titleParts) ? implode(' - ', $titleParts) : ('Locação #' . ($l['id'] ?? ''));

                // Construir start/end; aceita datas apenas ou data+hora se houver
                $start = $l['data_inicio'] ?? null;
                $end = $l['data_fim'] ?? null;
                if (!empty($l['hora_inicio'])) {
                    $start = trim(($start ?? '') . ' ' . $l['hora_inicio']);
                }
                if (!empty($l['hora_fim'])) {
                    $end = trim(($end ?? '') . ' ' . $l['hora_fim']);
                }

                // Mapeamento de cores por status
                switch (strtolower($status)) {
                    case 'ativa':
                        $color = '#10b981'; // verde
                        break;
                    case 'reserva':
                        $color = '#f59e0b'; // laranja
                        break;
                    case 'finalizada':
                        $color = '#6b7280'; // cinza
                        break;
                    default:
                        $color = '#2563eb'; // azul padrão
                }

                $events[] = [
                    'id' => $l['id'] ?? null,
                    'titulo' => $title,
                    'title' => $title,
                    'inicio' => $start,
                    'start' => $start,
                    'fim' => $end,
                    'end' => $end,
                    'cor' => $color,
                    'status' => $status
                ];
            }

            jsonResponse(true, 'Eventos do calendário', $events);
            return;
        }

        // Listar apenas ativas
        if (isset($_GET['ativas'])) {
            $locacoes = $this->locacao->listarAtivas();
            error_log('DEBUG listarAtivas SQL: ' . json_encode($locacoes));
            // Se não há resultados, tentar fallback: listar todas e filtrar pelo servidor
            if (empty($locacoes)) {
                $all = $this->locacao->listarTodas();
                error_log('DEBUG listarTodas SQL: ' . json_encode($all));
                $filtered = [];
                $now = new DateTime();
                foreach ($all as $l) {
                    // Ignorar locações canceladas explicitamente
                    $status = isset($l['status']) ? trim($l['status']) : '';
                    if (strtolower($status) === 'cancelada') {
                        continue;
                    }

                    // Se status é Ativa, mantém
                    if ($status === 'Ativa') {
                        $filtered[] = $l;
                        continue;
                    }

                    // Construir datetime de fim e considerar apenas se não for cancelada
                    $dataFim = $l['data_fim'] ?? null;
                    $horaFim = $l['hora_fim'] ?? '23:59:59';
                    if ($dataFim) {
                        try {
                            $dt = new DateTime($dataFim . ' ' . $horaFim);
                            if ($dt >= $now) {
                                $filtered[] = $l;
                            }
                        } catch (Exception $e) {
                            // ignorar formatação inválida
                        }
                    }
                }
                error_log('DEBUG filtered fallback: ' . json_encode($filtered));
                // Se ainda vazio, montar payload de debug com amostras para diagnóstico
                if (empty($filtered)) {
                    $samples = array_slice($all, 0, 10);
                    $debugSamples = [];
                    foreach ($samples as $s) {
                        $dataFim = $s['data_fim'] ?? null;
                        $horaFim = $s['hora_fim'] ?? null;
                        $computed = null;
                        $isActive = false;
                        // Se estiver cancelada, marca explicitamente como não ativa
                        if (isset($s['status']) && strtolower(trim($s['status'])) === 'cancelada') {
                            $isActive = false;
                        }
                        if ($dataFim) {
                            try {
                                $dt = new DateTime($dataFim . ' ' . ($horaFim ?: '23:59:59'));
                                $computed = $dt->format('Y-m-d H:i:s');
                                $isActive = $isActive || ($dt >= $now);
                            } catch (Exception $e) {
                                $computed = 'invalid';
                            }
                        }
                        $debugSamples[] = [
                            'id' => $s['id'] ?? null,
                            'status' => $s['status'] ?? null,
                            'data_fim' => $dataFim,
                            'hora_fim' => $horaFim,
                            'computed_end' => $computed,
                            'is_active_by_calc' => $isActive
                        ];
                    }

                    $debug = [
                        'total_in_db' => count($all),
                        'samples_returned' => count($samples),
                        'debug_samples' => $debugSamples
                    ];
                    error_log('DEBUG debugSamples: ' . json_encode($debug));
                    jsonResponse(true, 'Locações ativas (fallback - debug)', ['filtered' => $filtered, 'debug' => $debug]);
                    return;
                }
                jsonResponse(true, 'Locações ativas (fallback)', $filtered);
                return;
            }
            jsonResponse(true, 'Locações ativas', $locacoes);
        }
        
        // Listar apenas finalizadas
        if (isset($_GET['finalizadas'])) {
            $locacoes = $this->locacao->listarFinalizadas();
            jsonResponse(true, 'Locações finalizadas', $locacoes);
        }
        
        // Estatísticas
        if (isset($_GET['estatisticas'])) {
            $stats = $this->locacao->obterEstatisticas();
            jsonResponse(true, 'Estatísticas obtidas', $stats);
        }
        
        // Listar todas
        $locacoes = $this->locacao->listarTodas();
        jsonResponse(true, 'Locações listadas', $locacoes);
    }

    /**
     * Garante que os status de locações vencidas sejam atualizados periodicamente
     * sem depender de cron externo. Executa no máximo a cada 5 minutos.
     */
    private function ensureStatusesUpToDate() {
        try {
            $cacheDir = __DIR__ . '/../cache';
            if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
            $flagFile = $cacheDir . '/last_status_update.txt';
            $now = time();
            $last = 0;
            if (file_exists($flagFile)) {
                $raw = @file_get_contents($flagFile);
                $last = intval($raw ?: 0);
            }
            // só roda a atualização se passaram 5 minutos
            if (($now - $last) >= 300) {
                $updated = $this->locacao->atualizarStatusVencidas();
                @file_put_contents($flagFile, (string)$now, LOCK_EX);
                if ($updated > 0) {
                    error_log('[LocacaoController] Status auto-atualizado para ' . $updated . ' locação(ões).');
                }
            }
        } catch (\Throwable $e) {
            // Falhas aqui não devem quebrar a listagem; apenas logamos
            error_log('[LocacaoController] Falha ao auto-atualizar status: ' . $e->getMessage());
        }
    }
    
    /**
     * POST - Criar nova locação
     */
    private function handlePost() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            jsonResponse(false, 'Dados inválidos');
        }

    // Sanitização e conversão
    $this->locacao->cliente_id = filter_var($data['cliente_id'] ?? 0, FILTER_VALIDATE_INT);
    $this->locacao->climatizador_id = filter_var($data['climatizador_id'] ?? 0, FILTER_VALIDATE_INT);
    // Normalizar valores de datetime-local (navegador envia 'YYYY-MM-DDTHH:MM') para 'YYYY-MM-DD HH:MM:SS'
    $rawInicio = sanitize($data['data_inicio'] ?? '');
    $rawFim = sanitize($data['data_fim'] ?? '');
    $this->locacao->data_inicio = $this->normalizeDateTimeLocal($rawInicio);
    $this->locacao->data_fim = $this->normalizeDateTimeLocal($rawFim);
        $this->locacao->valor_diaria = filter_var($data['valor_diaria'] ?? 0, FILTER_VALIDATE_FLOAT);
        $this->locacao->quantidade_climatizadores = filter_var($data['quantidade_climatizadores'] ?? 1, FILTER_VALIDATE_INT);
        // Se o frontend enviou quantidade de dias manualmente, atribuir aqui para que o model respeite o override
        if (isset($data['quantidade_dias']) && $data['quantidade_dias'] !== '') {
            $qd = filter_var($data['quantidade_dias'], FILTER_VALIDATE_INT);
            $this->locacao->quantidade_dias = ($qd && $qd > 0) ? $qd : null;
        } else {
            $this->locacao->quantidade_dias = null; // será calculado automaticamente pelo model
        }
        $this->locacao->observacoes = sanitize($data['observacoes'] ?? '');
    $this->locacao->local_evento = sanitize($data['local_evento'] ?? '');
        // Responsável (campo novo, opcional)
        $this->locacao->responsavel = sanitize($data['responsavel'] ?? '');
        // Despesas acessórias (valor monetário)
        $this->locacao->despesas_acessorias = isset($data['despesas_acessorias']) ? floatval(str_replace(',', '.', $data['despesas_acessorias'])) : 0.0;
    // Tipo/descrição das despesas acessórias (opção selecionada)
    $this->locacao->despesas_acessorias_tipo = sanitize($data['despesas_acessorias_tipo'] ?? '');
        // Se o frontend enviou lista de climatizadores (multi), armazenar para persistência
        if (isset($data['climatizadores'])) {
            $this->locacao->climatizadores = (is_string($data['climatizadores']) ? $data['climatizadores'] : json_encode($data['climatizadores']));
            error_log('[LocacaoController] POST recebeu climatizadores: ' . $this->locacao->climatizadores);
        } else {
            error_log('[LocacaoController] POST NÃO recebeu campo climatizadores');
        }
        // Aplicar desconto flag (0/1)
        $this->locacao->aplicar_desconto = isset($data['aplicar_desconto']) ? (int)$data['aplicar_desconto'] : 0;

        // Buscar dados do climatizador selecionado (necessário para validar/descontar)
        $climatizador = $this->locacao->buscarClimatizadorPorId($this->locacao->climatizador_id);
        if (!$climatizador) {
            jsonResponse(false, 'Climatizador não encontrado');
        }

        // Se o frontend enviou aplicar_desconto como 1, usamos o desconto máximo do climatizador
        $aplicarDesconto = isset($data['aplicar_desconto']) ? (bool)$data['aplicar_desconto'] : false;
        $desconto = 0.0;
        if ($aplicarDesconto) {
            $desconto = floatval($climatizador['desconto_maximo'] ?? 0);
        } else {
            // permitir enviar desconto explícito se necessário, mas validar limite
            $descontoRaw = $data['desconto'] ?? 0;
            $desconto = ($descontoRaw === '' || $descontoRaw === null) ? 0.0 : floatval($descontoRaw);
        }

        // Validar desconto máximo permitido pelo climatizador
        $descontoMaximo = floatval($climatizador['desconto_maximo'] ?? 0);
        if ($desconto > $descontoMaximo) {
            jsonResponse(false, "Desconto aplicado ({$desconto}%) excede o máximo permitido ({$descontoMaximo}%) para o climatizador selecionado.");
        }

        $this->locacao->desconto = $desconto;

        try {
            // Debug: log dos valores recebidos e normalizados para investigação de perda de hora
            error_log('[LocacaoController] POST raw data_inicio: ' . var_export($rawInicio, true));
            error_log('[LocacaoController] POST raw data_fim: ' . var_export($rawFim, true));
            error_log('[LocacaoController] POST normalized data_inicio: ' . var_export($this->locacao->data_inicio, true));
            error_log('[LocacaoController] POST normalized data_fim: ' . var_export($this->locacao->data_fim, true));

            $id = $this->locacao->criar();
            if ($id) {
                jsonResponse(true, 'Locação criada com sucesso!', ['id' => $id]);
            } else {
                jsonResponse(false, 'Erro ao criar locação');
            }
        } catch (Exception $e) {
            jsonResponse(false, $e->getMessage());
        }
    }

    /**
     * Converte valores enviados por inputs datetime-local (ex.: '2025-10-23T14:30')
     * para formato MySQL DATETIME 'YYYY-MM-DD HH:MM:SS'.
     * Aceita também strings já em formato 'YYYY-MM-DD HH:MM[:SS]' e retorna null para valores vazios.
     *
     * @param string $val
     * @return string|null
     */
    private function normalizeDateTimeLocal($val) {
        $v = trim($val ?: '');
        if ($v === '') return null;
        // substituir 'T' por espaço
        $v = str_replace('T', ' ', $v);
        // se já tem segundos, tentar analisar; caso contrário acrescentar :00
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $v)) {
            $v .= ':00';
        }
        // Tentar criar DateTime para normalizar
        try {
            $dt = new DateTime($v);
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            // fallback: retornar valor original (sanitizado) sem alteração
            return $val;
        }
    }
    
    /**
     * PUT - Atualizar locação (finalizar ou cancelar)
     */
    private function handlePut() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['id'])) {
            jsonResponse(false, 'Dados inválidos');
        }
        
        $id = filter_var($data['id'], FILTER_VALIDATE_INT);
        
        if (!$id) {
            jsonResponse(false, 'ID inválido');
        }
        
        try {
            // Finalizar locação
            if (isset($data['acao']) && $data['acao'] === 'finalizar') {
                $dataDevolucao = sanitize($data['data_devolucao'] ?? null);
                if ($this->locacao->finalizar($id, $dataDevolucao)) {
                    jsonResponse(true, 'Locação finalizada com sucesso!');
                } else {
                    jsonResponse(false, 'Erro ao finalizar locação');
                }
            }
            
            // Cancelar locação
            if (isset($data['acao']) && $data['acao'] === 'cancelar') {
                if ($this->locacao->cancelar($id)) {
                    jsonResponse(true, 'Locação cancelada com sucesso!');
                } else {
                    jsonResponse(false, 'Erro ao cancelar locação');
                }
            }

            // Efetivar (ativar) locação
            if (isset($data['status']) && ($data['status'] === 'Confirmada' || $data['status'] === 'Ativa')) {
                if ($this->locacao->efetivar($id)) {
                    jsonResponse(true, 'Locação confirmada com sucesso!');
                } else {
                    jsonResponse(false, 'Erro ao confirmar locação');
                }
            }
            // Atualizar campos da locação (edição)
            // Se a requisição tiver campos de edição (cliente_id, climatizador_id, data_inicio etc), tratamos como update
            $updatableKeys = ['cliente_id','climatizador_id','data_inicio','data_fim','valor_diaria','quantidade_dias','quantidade_climatizadores','valor_total','observacoes','local_evento','desconto','aplicar_desconto','despesas_acessorias','despesas_acessorias_tipo','responsavel','climatizadores'];
            $hasUpdateFields = false;
            foreach ($updatableKeys as $k) { if (isset($data[$k])) { $hasUpdateFields = true; break; } }
            if ($hasUpdateFields) {
                // Prepara dados de atualização
                $updateData = [];
                foreach ($updatableKeys as $k) {
                    if (isset($data[$k])) {
                        // Normalizar datetimes quando enviados via datetime-local
                        if (in_array($k, ['data_inicio', 'data_fim'])) {
                            $updateData[$k] = $this->normalizeDateTimeLocal(sanitize($data[$k]));
                        } elseif ($k === 'climatizadores') {
                            // Se é array, serializar; se já é string JSON, manter
                            $updateData[$k] = is_string($data[$k]) ? $data[$k] : json_encode($data[$k]);
                        } else {
                            $updateData[$k] = $data[$k];
                        }
                    }
                }
                // Debug: log dos valores que serão enviados para atualização
                error_log('[LocacaoController] PUT updateData: ' . var_export($updateData, true));
                if ($this->locacao->atualizar($id, $updateData)) {
                    jsonResponse(true, 'Locação atualizada com sucesso!');
                } else {
                    jsonResponse(false, 'Erro ao atualizar locação');
                }
            }

            // Se nenhum dos caminhos acima foi executado, a ação é inválida
            jsonResponse(false, 'Ação inválida');
        } catch (Exception $e) {
            jsonResponse(false, $e->getMessage());
        }
    }
    
    /**
     * DELETE - Excluir locação (não implementado por questões de integridade)
     */
    private function handleDelete() {
        // Tentar obter ID de diferentes fontes: query string (?id=), PATH_INFO (/id) ou corpo JSON
        $id = null;

        // Query string
        if (isset($_GET['id'])) {
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        }

        // PATH_INFO (ex.: /LocacaoController.php/123)
        if (!$id && isset($_SERVER['PATH_INFO'])) {
            $parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
            if (count($parts) > 0 && is_numeric($parts[0])) {
                $id = intval($parts[0]);
            }
        }

        // Corpo JSON
        if (!$id) {
            $data = json_decode(file_get_contents('php://input'), true);
            if (is_array($data) && isset($data['id'])) {
                $id = filter_var($data['id'], FILTER_VALIDATE_INT);
            }
        }

        if (!$id) {
            jsonResponse(false, 'ID inválido para exclusão');
        }

        try {
            if ($this->locacao->excluir($id)) {
                jsonResponse(true, 'Locação excluída com sucesso');
            } else {
                jsonResponse(false, 'Erro ao excluir locação');
            }
        } catch (Exception $e) {
            jsonResponse(false, $e->getMessage());
        }
    }
}

// Executar controller
$controller = new LocacaoController();
$controller->handleRequest();
