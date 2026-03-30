<?php
/**
 * Controller: Relatório de Locações em PDF
 * Gera um relatório detalhado (período, filtros) reutilizando DocumentoPdfService
 * Rota: GET /controllers/RelatorioLocacoesController.php?relatorio=1&inicio=YYYY-MM-DD&fim=YYYY-MM-DD[&status=Ativa|Reserva|Finalizada|Cancelada][&cliente_id=ID][&json=1]
 */

// Carrega configurações e acesso ao banco
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../app/Services/DocumentoPdfService.php';

use App\Services\DocumentoPdfService;

class RelatorioLocacoesController
{
    public function handle(): void
    {
        try {
            $wantJson = isset($_GET['json']) && ($_GET['json'] == '1' || $_GET['json'] === 'true');
            // Parametrização básica
            $inicio = isset($_GET['inicio']) ? trim($_GET['inicio']) : null;
            $fim    = isset($_GET['fim']) ? trim($_GET['fim']) : null;
            $status = isset($_GET['status']) ? trim($_GET['status']) : '';
            $clienteId = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;

            if (!$inicio || !$fim) {
                http_response_code(400);
                if ($wantJson) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Informe início e fim (YYYY-MM-DD).']);
                } else {
                    echo 'Parâmetros inválidos. Informe inicio e fim (YYYY-MM-DD).';
                }
                return;
            }

            // Monta filtro SQL de forma segura
            $db = Database::getInstance();
            $where = [ 'l.data_inicio >= :inicio', 'l.data_fim <= :fim' ];
            $params = [ 'inicio' => $inicio . ' 00:00:00', 'fim' => $fim . ' 23:59:59' ];
            if (!empty($status)) {
                $where[] = 'l.status = :status';
                $params['status'] = $status;
            }
            if ($clienteId > 0) {
                $where[] = 'l.cliente_id = :cliente_id';
                $params['cliente_id'] = $clienteId;
            }
            $whereSql = implode(' AND ', $where);

            $sql = "SELECT l.*, c.nome AS cliente_nome, c.telefone AS cliente_telefone,
                           cl.modelo AS climatizador_modelo, cl.marca AS climatizador_marca, cl.capacidade AS climatizador_capacidade
                    FROM locacoes l
                    INNER JOIN clientes c ON c.id = l.cliente_id
                    INNER JOIN climatizadores cl ON cl.id = l.climatizador_id
                    WHERE $whereSql
                    ORDER BY l.data_inicio ASC";

            $registros = $db->fetchAll($sql, $params) ?: [];

            // Estatísticas para rodapé
            $totalLoc = count($registros);
            $somaTotal = 0.0; $porStatus = [];
            foreach ($registros as $r) {
                $somaTotal += (float)($r['valor_total'] ?? 0);
                $st = $r['status'] ?? '—';
                if (!isset($porStatus[$st])) $porStatus[$st] = 0;
                $porStatus[$st]++;
            }

            // Dados para o template
            $dados = [
                'periodo_inicio' => $inicio,
                'periodo_fim'    => $fim,
                'status'         => $status,
                'cliente_id'     => $clienteId,
                'registros'      => $registros,
                'total_locacoes' => $totalLoc,
                'soma_total'     => $somaTotal,
                'por_status'     => $porStatus,
                'gerado_em'      => date('d/m/Y H:i'),
            ];

            // Gera PDF a partir do template
            $template = realpath(__DIR__ . '/../resources/views/relatorio_locacoes.php') ?: __DIR__ . '/../resources/views/relatorio_locacoes.php';
            $service = new DocumentoPdfService();
            $pdfTmpPath = $service->gerar($dados, $template, 'relatorio_locacoes_');
            if (!$pdfTmpPath || !file_exists($pdfTmpPath)) {
                http_response_code(500);
                if ($wantJson) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Falha ao gerar relatório PDF.']);
                } else {
                    echo 'Falha ao gerar relatório PDF.';
                }
                return;
            }

            // Mesmo fluxo: se json=1, publica e retorna URL; senão, faz stream inline
            $isJson = isset($_GET['json']) && ($_GET['json'] == '1' || $_GET['json'] === 'true');
            $fileBase = sprintf('relatorio_locacoes_%s_%s.pdf', preg_replace('/[^0-9]/', '', $inicio . '_' . $fim), uniqid());
            if ($isJson) {
                $publicDir = realpath(__DIR__ . '/../assets/pdfs');
                if (!$publicDir) { $publicDir = __DIR__ . '/../assets/pdfs'; if (!is_dir($publicDir)) mkdir($publicDir, 0755, true); }
                $publicPath = $publicDir . '/' . $fileBase;
                if (!@rename($pdfTmpPath, $publicPath)) { @copy($pdfTmpPath, $publicPath); }

                // Index + URL assinada via DownloadPdf.php
                $token = bin2hex(random_bytes(16));
                $indexFile = __DIR__ . '/../assets/pdfs/index.json';
                $index = file_exists($indexFile) ? (json_decode(file_get_contents($indexFile), true) ?: []) : [];
                $index[$token] = [ 'file' => $fileBase, 'created_at' => time(), 'type' => 'relatorio' ];
                file_put_contents($indexFile, json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
                $basePath = dirname($_SERVER['SCRIPT_NAME']);
                $basePath = rtrim(str_replace('controllers', '', $basePath), '/');
                $pdfUrl = $scheme . '://' . $host . $basePath . '/controllers/DownloadPdf.php?token=' . $token;
                header('Content-Type: application/json');
                echo json_encode([ 'success' => true, 'pdfUrl' => $pdfUrl, 'fileName' => $fileBase ]);
                return;
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $fileBase . '"');
            readfile($pdfTmpPath);
            @unlink($pdfTmpPath);
        } catch (Exception $e) {
            error_log('[RelatorioPDF] Erro: ' . $e->getMessage());
            http_response_code(500);
            if (isset($_GET['json']) && ($_GET['json'] == '1' || $_GET['json'] === 'true')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Erro interno ao gerar relatório.']);
            } else {
                echo 'Erro interno ao gerar relatório.';
            }
        }
    }
}

// Roteamento simples
if (isset($_GET['relatorio'])) {
    $c = new RelatorioLocacoesController();
    $c->handle();
    exit;
}

?>
