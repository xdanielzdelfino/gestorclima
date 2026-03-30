<!-- Modal para visualização de PDF de orçamento ou contrato (implementado com o sistema de modais do projeto) -->
<div class="modal-overlay" id="modalOrcamentoOverlay">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalPdfTitulo">Visualizar Orçamento</h3>
            <button class="modal-close" id="modalOrcamentoClose"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <object id="objOrcamentoPdf" type="application/pdf" data="" width="100%" height="500px">
                <p>Seu navegador não suporta visualização de PDF. <a id="downloadOrcamentoPdf" href="#" target="_blank">Clique aqui para baixar o PDF</a>.</p>
            </object>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="btnAbrirNovaAba">Abrir em Nova Aba</button>
            <a class="btn btn-primary" id="btnBaixarPdf" href="#" download>Baixar PDF</a>
        </div>
    </div>
</div>

<script>
    // Função para atualizar o título do modal dinamicamente
    function setModalPdfTitulo(titulo) {
        const tituloEl = document.getElementById('modalPdfTitulo');
        if (tituloEl) tituloEl.textContent = titulo;
    }

    document.getElementById('modalOrcamentoClose').addEventListener('click', () => {
        const overlay = document.getElementById('modalOrcamentoOverlay');
        const obj = document.getElementById('objOrcamentoPdf');
        const download = document.getElementById('downloadOrcamentoPdf');
        if (overlay) overlay.classList.remove('active');
        if (obj) obj.data = '';
        if (download) download.href = '#';
    });

    document.getElementById('btnAbrirNovaAba').addEventListener('click', () => {
        const obj = document.getElementById('objOrcamentoPdf');
        if (obj && obj.data) {
            window.open(obj.data, '_blank');
        } else {
            alert('Nenhum PDF carregado para abrir.');
        }
    });

    document.getElementById('btnBaixarPdf').addEventListener('click', (e) => {
        const download = document.getElementById('downloadOrcamentoPdf');
        if (!download || download.href === '#') {
            e.preventDefault();
            alert('Nenhum PDF disponível para download.');
        }
    });
</script>
