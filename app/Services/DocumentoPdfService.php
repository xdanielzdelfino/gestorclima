<?php
/**
 * Serviço genérico para geração de documentos PDF a partir de templates PHP.
 * Reutiliza a mesma ideia do OrcamentoPdfService, mas permite passar o caminho do template.
 */
namespace App\Services;

$autoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    error_log('[DocumentoPdfService] vendor/autoload.php não encontrado: ' . $autoload);
}

use Mpdf\Mpdf;

class DocumentoPdfService
{
    /**
     * Gera um PDF a partir de um template PHP e dados.
     * @param array $dados Dados que serão disponibilizados para o template
     * @param string $templatePath Caminho absoluto para o template PHP que renderiza o HTML
     * @param string $filePrefix Prefixo do arquivo temporário gerado
     * @return string Caminho do arquivo PDF gerado (temp) ou '' em caso de erro
     */
    public function gerar(array $dados, string $templatePath, string $filePrefix = 'documento_'): string
    {
        try {
            if (!file_exists($templatePath)) {
                error_log('[DocumentoPdfService] Template não encontrado: ' . $templatePath);
                return '';
            }

            // Renderiza o template em escopo isolado
            $html = $this->renderTemplate($templatePath, $dados);

            // Instancia o mPDF e força fonte padrão para Arial (se disponível no sistema/substituído pelo renderer)
            // Desativa temporariamente a exibição de erros/warnings para evitar que mensagens do mPDF poluam a saída HTML/PDF
            $prevDisplayErrors = ini_get('display_errors');
            $prevErrorReporting = error_reporting();
            ini_set('display_errors', '0');
            error_reporting($prevErrorReporting & ~E_WARNING & ~E_NOTICE);
            try {
                $mpdf = new Mpdf([ 'mode' => 'utf-8', 'format' => 'A4', 'default_font' => 'Arial' ]);
                $mpdf->WriteHTML($html);
                // Salva o PDF em arquivo temporário
                $tmpDir = sys_get_temp_dir();
                $filePath = rtrim($tmpDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filePrefix . uniqid() . '.pdf';
                $mpdf->Output($filePath, \Mpdf\Output\Destination::FILE);
            } finally {
                // Restaurar configurações de erro
                ini_set('display_errors', $prevDisplayErrors);
                error_reporting($prevErrorReporting);
            }
            return $filePath;
        } catch (\Exception $e) {
            error_log('[DocumentoPdfService] Erro ao gerar PDF: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Renderiza um template PHP em uma string, com variáveis fornecidas.
     * Usa EXTR_SKIP para evitar sobrescrever variáveis locais por segurança.
     * @param string $templatePath Caminho absoluto do template
     * @param array $vars Variáveis que serão extraídas no template
     * @return string HTML renderizado
     */
    private function renderTemplate(string $templatePath, array $vars): string
    {
        // Limpar opcache deste arquivo específico para evitar cache de versão antiga
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($templatePath, true);
        }
        ob_start();
        // proteger o escopo do template
        $safeVars = $vars;
        extract($safeVars, EXTR_SKIP);
        include $templatePath;
        return ob_get_clean();
    }
}
