<?php
/**
 * Adiciona endpoint para geração de orçamento em PDF
 * POST /locacao/orcamento
 * Body: { id: int }
 */
require_once __DIR__ . '/../app/Services/OrcamentoPdfService.php';
require_once __DIR__ . '/../models/Locacao.php';
require_once __DIR__ . '/../models/Climatizador.php';

class LocacaoControllerOrcamento {
    public function handleOrcamento() {
        error_log('[OrcamentoPDF] Endpoint chamado - iniciando processamento');
        try {
            $id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
            if (!$id) {
                error_log('[OrcamentoPDF] ID inválido ou não informado');
                http_response_code(400);
                echo 'ID da locação inválido.';
                exit;
            }
            $locacaoModel = new Locacao();
            $locacao = $locacaoModel->buscarPorId($id);
            if (!$locacao) {
                error_log('[OrcamentoPDF] Locação não encontrada para ID: ' . $id);
                http_response_code(404);
                echo 'Locação não encontrada.';
                exit;
            }
            // Monta dados para o template
            // Usa caminho absoluto para o logo para que o mPDF consiga localizar a imagem corretamente
            $logoAbsolute = realpath(__DIR__ . '/../assets/images/logo.png');
            if (!$logoAbsolute) {
                // fallback para caminho relativo caso realpath falhe
                $logoAbsolute = __DIR__ . '/../assets/images/logo.png';
            }
            // Formatar datas para padrão BR com hora quando possível
            $formatarDataHora = function($rawDate, $rawTime = null) {
                if (empty($rawDate)) return '';
                $candidate = $rawDate;
                if (!empty($rawTime)) {
                    $candidate = trim($rawDate . ' ' . $rawTime);
                }
                try {
                    $dt = new DateTime($candidate);
                    return $dt->format('d/m/Y H:i');
                } catch (Exception $e) {
                    // fallback: retornar raw
                    return $candidate;
                }
            };

            // Calcular valores intermediários com clareza
            // Suporte a multi-climatizadores: se o registro de locação trouxer o campo 'climatizadores' (array),
            // construímos os itens a partir dele. Caso contrário, mantemos o comportamento legado (um único item).
            error_log('[OrcamentoPDF] Campo climatizadores recebido do banco: ' . json_encode($locacao['climatizadores'] ?? null));
            $perDaySubtotal = 0.0;
            $itens = [];
            if (!empty($locacao['climatizadores']) && is_array($locacao['climatizadores'])) {
                error_log('[OrcamentoPDF] Processando ' . count($locacao['climatizadores']) . ' climatizadores do array');
                foreach ($locacao['climatizadores'] as $it) {
                    $qtd = isset($it['qtd']) ? intval($it['qtd']) : (isset($it['quantidade']) ? intval($it['quantidade']) : 1);
                    $vd = isset($it['valor_diaria']) ? floatval($it['valor_diaria']) : (isset($it['valor_unitario']) ? floatval($it['valor_unitario']) : 0.0);
                    // Se item referencia um climatizador cadastrado, separar descrição curta (nome/modelo)
                    // da descrição longa (características técnicas) vinda do cadastro.
                    $climatizadorId = $it['id'] ?? $it['climatizador_id'] ?? null;
                    $descricaoCurta = '';
                    $caracteristicas = '';
                    if (!empty($climatizadorId)) {
                        try {
                            $clModel = new Climatizador();
                            $clData = $clModel->buscarPorId($climatizadorId);
                            if (is_array($clData)) {
                                    // descrição curta: usar apenas o campo `modelo` do cadastro
                                    $descricaoCurta = trim($clData['modelo'] ?? '');
                                // descrição completa (características técnicas)
                                $caracteristicas = trim($clData['descricao'] ?? '');
                            }
                        } catch (Exception $e) {
                            error_log('[OrcamentoPDF] Falha ao buscar climatizador ID ' . $climatizadorId . ': ' . $e->getMessage());
                            $descricaoCurta = '';
                            $caracteristicas = '';
                        }
                        // Não aceitar descrição manual para o campo curto: usar somente `modelo`
                    } else {
                        // item não referencia um climatizador cadastrado: manter comportamento legado
                        $descricaoCurta = trim(($it['descricao'] ?? $it['modelo'] ?? $it['codigo'] ?? ''));
                    }
                    $itemTotal = $vd * $qtd; // subtotal por dia deste item
                    $itens[] = [
                        'descricao' => $descricaoCurta,
                        'caracteristicas' => $caracteristicas,
                        'quantidade' => $qtd,
                        'valor_unitario' => $vd,
                        'quantidade_dias' => 1,
                        'valor_total' => $itemTotal,
                        'climatizador_id' => $it['id'] ?? $it['climatizador_id'] ?? null, // preservar ID para matching de imagem
                    ];
                    $perDaySubtotal += $itemTotal;
                }
            } else {
                // comportamento anterior (um único modelo)
                $perDaySubtotal = (($locacao['valor_diaria'] ?? 0) * ($locacao['quantidade_climatizadores'] ?? 1));
                $itens = [
                    [
                        'descricao' => trim(($locacao['climatizador_modelo'] ?? '') . ' ' . ($locacao['climatizador_capacidade'] ?? '')),
                        'quantidade' => $locacao['quantidade_climatizadores'] ?? 1,
                        'valor_unitario' => $locacao['valor_diaria'],
                        'quantidade_dias' => 1,
                        'valor_total' => ($locacao['valor_diaria'] ?? 0) * ($locacao['quantidade_climatizadores'] ?? 1),
                    ]
                ];
            }

            $subtotal = $perDaySubtotal; // exibido no PDF como "Subtotal" (por dia)
            $desconto_percent = $locacao['desconto'] ?? 0;
            // desconto aplicado sobre o subtotal por dia (se desconto for percentual)
            $desconto_valor = ($desconto_percent / 100) * $perDaySubtotal;
            $despesas_acessorias_val = isset($locacao['despesas_acessorias']) ? (float)$locacao['despesas_acessorias'] : 0;
            // Total final considera subtotal por dia vezes número de dias
            $total_calculado = ($perDaySubtotal * ($locacao['quantidade_dias'] ?? 1)) - $desconto_valor + $despesas_acessorias_val;

            // --- detectar automaticamente imagem do climatizador ANTES de montar $dados ---
            error_log('[OrcamentoPDF] Total de itens para detectar imagem: ' . count($itens));
            $imgDir = realpath(__DIR__ . '/../assets/images/climatizadores') ?: __DIR__ . '/../assets/images/climatizadores';
            foreach ($itens as $idx => &$item) {
                error_log('[OrcamentoPDF] Processando item #' . ($idx+1) . ': ' . ($item['descricao'] ?? 'sem descrição'));
                $possibleNames = [];
                if (!empty($item['descricao'])) {
                    $desc = strtoupper(trim($item['descricao']));
                    $words = preg_split('/[\s_]+/', $desc);
                    $uniqueWords = [];
                    $seen = [];
                    foreach ($words as $w) {
                        $w = trim($w);
                        if ($w === '') continue;
                        $key = strtolower($w);
                        if (!isset($seen[$key])) {
                            $uniqueWords[] = $w;
                            $seen[$key] = true;
                        }
                    }
                    $normalized = implode(' ', $uniqueWords);
                    $slug = preg_replace('/[^A-Z0-9]+/', '_', $normalized);
                    $slug = trim($slug, '_');
                    $possibleNames[] = $slug;
                    $slugOrig = preg_replace('/[^A-Z0-9]+/', '_', $desc);
                    $slugOrig = trim($slugOrig, '_');
                    if ($slugOrig !== $slug) {
                        $possibleNames[] = $slugOrig;
                    }
                }
                if (!empty($item['climatizador_id'])) {
                    $possibleNames[] = $item['climatizador_id'];
                }
                if (!empty($locacao['climatizador_codigo'])) $possibleNames[] = $locacao['climatizador_codigo'];
                if (!empty($locacao['climatizador_id'])) $possibleNames[] = $locacao['climatizador_id'];
                
                $imgFound = '';
                foreach ($possibleNames as $pn) {
                    if (empty($pn)) continue;
                    $candidates = [ 
                        $imgDir . DIRECTORY_SEPARATOR . $pn . '.png', 
                        $imgDir . DIRECTORY_SEPARATOR . $pn . '.jpg', 
                        $imgDir . DIRECTORY_SEPARATOR . $pn . '.jpeg' 
                    ];
                    foreach ($candidates as $c) {
                        if (file_exists($c)) { $imgFound = $c; break 2; }
                    }
                }
                if ($imgFound === '') {
                    $files = @scandir($imgDir) ?: [];
                    $foundFlag = false;
                    foreach ($possibleNames as $pn) {
                        if ($foundFlag) break;
                        if (empty($pn)) continue;
                        $tokens = array_filter(explode('_', $pn), function($t) { return strlen($t) >= 3; });
                        foreach ($files as $f) {
                            if (in_array($f, ['.', '..'])) continue;
                            $fUpper = strtoupper($f);
                            if (stripos($fUpper, $pn) !== false) {
                                $candidatePath = $imgDir . DIRECTORY_SEPARATOR . $f;
                                if (is_file($candidatePath)) { $imgFound = $candidatePath; $foundFlag = true; break; }
                            }
                            $matchCount = 0;
                            foreach ($tokens as $tok) {
                                if (stripos($fUpper, $tok) !== false) $matchCount++;
                            }
                            if ($matchCount > 0 && $matchCount >= ceil(count($tokens) * 0.6)) {
                                $candidatePath = $imgDir . DIRECTORY_SEPARATOR . $f;
                                if (is_file($candidatePath)) { $imgFound = $candidatePath; $foundFlag = true; break; }
                            }
                        }
                    }
                }
                if ($imgFound !== '') {
                    $item['foto_path'] = $imgFound;
                    error_log('[OrcamentoPDF] Imagem encontrada para item "' . ($item['descricao'] ?? '') . '": ' . $imgFound);
                    error_log('[OrcamentoPDF] file_exists verificado: ' . ($imgFound && file_exists($imgFound) ? 'SIM' : 'NAO'));
                } else {
                    error_log('[OrcamentoPDF] Nenhuma imagem encontrada para item "' . ($item['descricao'] ?? '') . '" em: ' . $imgDir);
                    error_log('[OrcamentoPDF] Tentou: ' . implode(', ', array_filter($possibleNames)));
                }
            }
            unset($item);
            // --- fim da detecção automática de imagem ---

            $dados = [
                'logoPath' => $logoAbsolute,
                'cliente' => $locacao['cliente_nome'],
                'cliente_cpf_cnpj' => $locacao['cliente_cpf_cnpj'] ?? $locacao['cpf_cnpj'] ?? '',
                // data do orçamento com hora (formato BR)
                'dataOrcamento' => date('d/m/Y H:i'),
                'contato' => $locacao['cliente_email'] ?? '',
                'telefone' => $locacao['cliente_telefone'],
                'endereco' => $locacao['cliente_endereco'] ?? '',
                'responsavel' => $locacao['responsavel'] ?? $locacao['contato'] ?? $locacao['cliente_responsavel'] ?? '',
                'data_inicio' => $formatarDataHora($locacao['data_inicio'] ?? null, $locacao['hora_inicio'] ?? null),
                'data_fim' => $formatarDataHora($locacao['data_fim'] ?? null, $locacao['hora_fim'] ?? null),
                'quantidade_dias' => $locacao['quantidade_dias'] ?? ($locacao['quantidade_dias'] ?? 1),
                'local_evento' => $locacao['local_evento'] ?? '',
                // 'itens' pode conter vários modelos (suporte multi-climatizador) ou apenas um para compatibilidade
                'itens' => $itens,
                // Compatibilidade com o template de contrato que usa variáveis antigas
                'obj_quantidade' => (count($itens) > 0) ? array_sum(array_column($itens, 'quantidade')) : ($locacao['quantidade_climatizadores'] ?? 1),
                'obj_descricao' => (count($itens) === 1) ? ($itens[0]['descricao'] ?? '') : (count($itens) > 1 ? implode(' + ', array_map(function($i){return $i['descricao'];}, $itens)) : trim(($locacao['climatizador_modelo'] ?? '') . ' ' . ($locacao['climatizador_capacidade'] ?? ''))),
                'valor_unitario' => (count($itens) === 1) ? ($itens[0]['valor_unitario'] ?? ($locacao['valor_diaria'] ?? 0)) : ($locacao['valor_diaria'] ?? 0),
                // Calcular subtotal, desconto real e despesas acessórias (usando valores pré-calculados)
                'subtotal' => $subtotal,
                'desconto_percent' => $desconto_percent,
                'desconto' => $desconto_valor,
                'despesas_acessorias' => $despesas_acessorias_val,
                // total recalculado: subtotal - desconto + despesas
                'total' => $total_calculado,
                'observacoes' => $locacao['observacoes'],
                // preservar label/tipo das despesas acessórias quando disponível
                'despesas_acessorias_tipo' => $locacao['despesas_acessorias_tipo'] ?? ($locacao['despesasTipo'] ?? ($locacao['despesas_label'] ?? '')),
                'empresa' => 'Imperial Clima',
                'empresaContato' => 'contato@imperialclima.com.br',
                'clienteTelefone' => $locacao['cliente_telefone'],
                // pdfLink será preenchido após geração
            ];
            // Gera o PDF usando o serviço. Suporta tipo=contrato para gerar contrato em vez do orçamento
            $tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'orcamento';
            error_log('[OrcamentoPDF] Iniciando geração de PDF tipo=' . $tipo . ' para locação ID: ' . $id);

            if ($tipo === 'contrato') {
                $template = realpath(__DIR__ . '/../resources/views/contrato_locacao_climatizadores.php') ?: __DIR__ . '/../resources/views/contrato_locacao_climatizadores.php';
                $docService = new \App\Services\DocumentoPdfService();
                $pdfTmpPath = $docService->gerar($dados, $template, 'contrato_');
            } else {
                $pdfService = new \App\Services\OrcamentoPdfService();
                $pdfTmpPath = $pdfService->gerar($dados);
            }
            error_log('[OrcamentoPDF] PDF temporário gerado em: ' . $pdfTmpPath);

            // Preparar nome de arquivo amigável para exportação/download
            $clienteNome = $locacao['cliente_nome'] ?? 'cliente';
            $clienteSafe = preg_replace('/[^A-Za-z0-9\-_]/', '_', trim($clienteNome));
            $prefix = ($tipo === 'contrato') ? 'contrato' : 'orcamento';
            $generatedFileName = sprintf('%s_%s_%s_%s.pdf', $prefix, $clienteSafe, $id, uniqid());

            // Se a requisição pedir JSON, movemos o PDF para pasta pública e retornamos URL + telefone
            $isJson = isset($_GET['json']) && ($_GET['json'] == '1' || $_GET['json'] === 'true');
            if ($isJson) {
                $publicDir = realpath(__DIR__ . '/../assets/pdfs');
                if (!$publicDir) {
                    $publicDir = __DIR__ . '/../assets/pdfs';
                    if (!is_dir($publicDir)) {
                        mkdir($publicDir, 0755, true);
                    }
                }
                $fileName = $generatedFileName;
                $publicPath = $publicDir . '/' . $fileName;
                if (!@rename($pdfTmpPath, $publicPath)) {
                    // fallback para copy + unlink
                    if (!@copy($pdfTmpPath, $publicPath)) {
                        error_log('[OrcamentoPDF] Falha ao mover/copy PDF para pasta pública: ' . $publicPath);
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Falha ao gerar PDF']);
                        exit;
                    }
                }

                // Constroi URL de download assinada (não expõe caminho real dos arquivos)
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
                $basePath = dirname($_SERVER['SCRIPT_NAME']);
                $basePath = rtrim(str_replace('controllers', '', $basePath), '/');

                // Criar token persistente (UUID-like) e registrar no índice para links permanentes
                $token = bin2hex(random_bytes(16));
                $indexFile = __DIR__ . '/../assets/pdfs/index.json';
                $index = [];
                if (file_exists($indexFile)) {
                    $raw = file_get_contents($indexFile);
                    $index = json_decode($raw, true) ?: [];
                }
                $index[$token] = [
                    'file' => $fileName,
                    'locacao_id' => $id,
                    'created_at' => time()
                ];
                // gravar índice usando LOCK_EX para reduzir risco de corrupção por concorrência
                file_put_contents($indexFile, json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
                $pdfUrl = $scheme . '://' . $host . $basePath . '/controllers/DownloadPdf.php?token=' . $token;

                header('Content-Type: application/json');
                // Normaliza telefone para formato internacional (adiciona código do país 55 quando apropriado)
                $rawPhone = $locacao['cliente_telefone'] ?? '';
                $digits = preg_replace('/\D/', '', $rawPhone);
                // remove zeros à esquerda comuns (ex: 0...)
                $digits = preg_replace('/^0+/', '', $digits);
                $clienteWhatsapp = '';
                if ($digits === '') {
                    $clienteWhatsapp = '';
                } else {
                    if (strpos($digits, '55') === 0) {
                        $clienteWhatsapp = $digits;
                    } elseif (strlen($digits) === 11 || strlen($digits) === 10) {
                        // Número local BR com DDD (10 ou 11 dígitos) -> prefixa 55
                        $clienteWhatsapp = '55' . $digits;
                    } elseif (strlen($digits) > 11) {
                        // Já tem código do país diferente de 55; assume correto
                        $clienteWhatsapp = $digits;
                    } else {
                        // Fallback: prefixar 55
                        $clienteWhatsapp = '55' . $digits;
                    }
                }
                error_log('[OrcamentoPDF] PDF público: ' . $pdfUrl);
                error_log('[OrcamentoPDF] clienteWhatsapp normalizado: ' . $clienteWhatsapp);
                echo json_encode([
                    'success' => true,
                    'pdfUrl' => $pdfUrl,
                    'fileName' => $fileName,
                    'clienteWhatsapp' => $clienteWhatsapp
                ]);
                exit;
            }

            // Comportamento antigo: stream do PDF inline (sem JSON)
            $pdfPath = $pdfTmpPath;
            if (!file_exists($pdfPath) || filesize($pdfPath) < 1000) {
                error_log('[OrcamentoPDF] Falha ao gerar PDF para locação ID: ' . $id . ' - caminho: ' . $pdfPath);
                http_response_code(500);
                echo 'Falha ao gerar PDF. Verifique os logs do servidor.';
                exit;
            }
            header('Content-Type: application/pdf');
            // Ao fazer stream direto, informar um nome de arquivo mais amigável
            $streamName = $generatedFileName ?? ('orcamento_locacao_' . $id . '.pdf');
            header('Content-Disposition: inline; filename="' . $streamName . '"');
            readfile($pdfPath);
            // remover arquivo gerado temporariamente
            @unlink($pdfPath);
            exit;
        } catch (Exception $e) {
            error_log('[OrcamentoPDF] Erro: ' . $e->getMessage());
            http_response_code(500);
            echo 'Erro interno ao gerar orçamento PDF.';
            exit;
        }
    }
}
// Roteamento para endpoint de orçamento
if (isset($_GET['orcamento'])) {
    $controller = new LocacaoControllerOrcamento();
    $controller->handleOrcamento();
    exit;
}
