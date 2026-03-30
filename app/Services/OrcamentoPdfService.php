<?php
/**
 * Serviço para geração de PDF de orçamento de locação de climatizadores
 * Utiliza mPDF para renderizar o template HTML
 * @author GitHub Copilot
 */
namespace App\Services;

// Carrega autoload do Composer para resolver dependências (ex: mPDF)
$autoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    error_log('[OrcamentoPDFService] vendor/autoload.php não encontrado em: ' . $autoload);
}

// Caso o autoload não esteja disponível, incluir implementação local do DocumentoPdfService
if (!class_exists('\App\\Services\\DocumentoPdfService')) {
    $docPath = __DIR__ . '/DocumentoPdfService.php';
    if (file_exists($docPath)) {
        require_once $docPath;
    }
}
use App\Services\DocumentoPdfService;

class OrcamentoPdfService
{
    /**
     * Gera o PDF do orçamento a partir dos dados da locação
     * @param array $dados Dados da locação
     * @return string Caminho do arquivo PDF gerado
     */
    public function gerar(array $dados): string
    {
        try {
            $template = __DIR__ . '/../../resources/views/orcamento_locacao_climatizadores.php';
            $docService = new DocumentoPdfService();
            return $docService->gerar($dados, $template, 'orcamento_');
        } catch (\Exception $e) {
            error_log('[OrcamentoPDFService] Erro ao gerar PDF: ' . $e->getMessage());
            return '';
        }
    }
}
