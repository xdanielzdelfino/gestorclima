<?php
// ...existing code...
?>
<script>
// envia orçamento via WhatsApp: usa controller específico para gerar link e número
async function enviarWhatsapp(id) {
    UI.showLoading();
    let orcamentoEndpoint = API_ENDPOINTS.locacoes;
    try {
        orcamentoEndpoint = orcamentoEndpoint.replace('LocacaoController.php', 'LocacaoControllerOrcamento.php');
    } catch (e) {
        console.warn('Não foi possível derivar endpoint de orçamento, usando API_BASE diretamente.');
    }
    try {
        const res = await API.get(orcamentoEndpoint, { orcamento: 1, id, json: 1 });
        if (res && res.success && res.pdfUrl && res.clienteWhatsapp) {
            let numero = (res.clienteWhatsapp || '').replace(/\D/g, '');
            if (!numero) return UI.showToast('Telefone do cliente inválido para envio pelo WhatsApp.', 'error');
            if (!numero.startsWith('55')) numero = '55' + numero;
            const texto = `Segue o orçamento da sua locação:\n${res.pdfUrl}`;
            const encoded = encodeURIComponent(texto);
            const mobileScheme = `whatsapp://send?phone=${numero}&text=${encoded}`;
            const webUrl = `https://api.whatsapp.com/send?phone=${numero}&text=${encoded}`;
            const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
            if (isMobile) {
                window.location.href = mobileScheme;
                setTimeout(() => window.open(webUrl, '_blank'), 700);
            } else {
                window.open(webUrl, '_blank');
            }
        } else {
            console.error('Resposta inválida ao preparar envio WhatsApp:', res);
            UI.showToast('Erro ao preparar envio para WhatsApp', 'error');
        }
    } catch (err) {
        console.error('Erro na requisição para preparar WhatsApp:', err);
        UI.showToast('Erro ao preparar envio para WhatsApp', 'error');
    } finally {
        UI.hideLoading();
    }
}
</script>
<!-- Botão de enviar orçamento pelo WhatsApp (exemplo de uso: preencher dinamicamente com clienteTelefone/pdfLink) -->
<button onclick="enviarWhatsapp(<?= isset($locacao_id) ? (int)$locacao_id : 0 ?>)" class="btn btn-success">Enviar pelo WhatsApp</button>