<?php
/**
 * Página de Locações
 * 
 * Gerenciamento de locações do sistema
 * 
 * @package GestorClima
 * @version 1.0.0
 */

// Requer autenticação
require_once __DIR__ . '/../middleware/auth.php';
requerAutenticacao();

// Obter dados do usuário logado
$usuario = getUsuarioLogado();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locações - Gestor Clima</title>
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Adicionar barra de scroll para formulários */
        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        /* Ajustar alinhamento dos botões de salvar e cancelar */
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding-top: 1rem;
        }

        /* slider styles moved to assets/css/components.css */
        /* Estilos para o popover do WhatsApp e do botão de Anexar */
        /* Aplicamos os mesmos estilos também para #attach-popover para que o menu de anexos
           reutilize exatamente o mesmo visual do popover do WhatsApp. */
        #whatsapp-popover, #attach-popover {
            position: absolute;
            z-index: 9999;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(2,6,23,0.12);
            padding: 6px;
            min-width: 220px;
            max-width: 92vw;
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
            transform: translateY(6px) scale(0.98);
            opacity: 0;
            transition: transform 180ms cubic-bezier(.2,.9,.2,1), opacity 160ms ease;
        }
        #whatsapp-popover.show, #attach-popover.show { transform: translateY(0) scale(1); opacity: 1; }
        #whatsapp-popover .wp-close, #attach-popover .wp-close {
            position: absolute; top: 6px; right: 8px; width:28px; height:28px; border-radius:6px; display:flex;align-items:center;justify-content:center; cursor:pointer; color:#6b7b8a;
        }
        /* Responsivo: em telas pequenas, usar bottom-sheet */
        @media (max-width: 640px) {
            #whatsapp-popover, #attach-popover {
                position: fixed !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                top: auto !important;
                min-width: 100% !important;
                border-radius: 12px 12px 0 0;
                padding: 12px 16px;
                box-shadow: 0 -8px 24px rgba(2,6,23,0.12);
                display: flex;
                flex-direction: column;
            }
            #whatsapp-popover:before, #attach-popover:before { display: none; }
            #whatsapp-popover .wp-option, #attach-popover .wp-option { padding: 12px 10px; }
        }
        /* Arrow for popover when it opens downward (default) */
        #whatsapp-popover.popover-down:before, #attach-popover.popover-down:before {
            content: '';
            position: absolute;
            top: -8px;
            left: 18px;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 8px solid #fff;
            filter: drop-shadow(0 -1px 0 rgba(0,0,0,0.02));
        }
        /* Estilos para o componente multi-climatizador */
        #multi-clim-wrapper .controls { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        #multi-clim-wrapper .controls select.form-select { min-width:160px; flex:1 1 220px; }
        #multi-clim-wrapper .controls input.form-control { flex:0 0 auto; }
        #multi-clim-wrapper .controls input#climatizador_qtd { width:92px; }
        #multi-clim-wrapper .controls input#valor_diaria { width:140px; }
        @media (max-width: 640px) {
            #multi-clim-wrapper .controls > * { flex: 1 1 100%; width:100% !important; }
            #multi-clim-wrapper .controls { gap:6px; }
        }
        /* Responsividade adicional para listagem e menu de ações */
        @media (max-width: 640px) {
            .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .table { width: 100%; min-width: 720px; }
            .table td, .table th { white-space: nowrap; }
            /* Transformar actions-menu em bottom-sheet no mobile */
            .actions-menu {
                position: fixed !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                top: auto !important;
                width: 100% !important;
                max-width: 100% !important;
                border-radius: 12px 12px 0 0 !important;
                box-shadow: 0 -8px 24px rgba(0,0,0,0.14) !important;
                padding: 12px 10px !important;
            }
            .actions-menu .actions-item { padding: 14px 12px !important; font-size: 15px; }
        }
        /* Arrow for popover when it opens upward: use :after to place below the popover */
        #whatsapp-popover.popover-up:after, #attach-popover.popover-up:after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 18px;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-top: 8px solid #fff;
            filter: drop-shadow(0 1px 0 rgba(0,0,0,0.02));
        }
        #whatsapp-popover .wp-option, #attach-popover .wp-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 140ms ease;
            user-select: none;
        }
        /* Evitar deslocar layout ao passar o mouse */
        #whatsapp-popover .wp-option:hover, #attach-popover .wp-option:hover { background: #f6f9fb; }
        #whatsapp-popover .wp-option .wp-icon, #attach-popover .wp-option .wp-icon {
            width: 40px; height: 40px; border-radius: 9px; display:flex; align-items:center; justify-content:center; color: #fff; font-size:18px;
        }
        #whatsapp-popover .wp-option.orc .wp-icon, #attach-popover .wp-option.orc .wp-icon { background: #25D366; }
        #whatsapp-popover .wp-option.contrato .wp-icon, #attach-popover .wp-option.contrato .wp-icon { background: #0d6efd; }
    #whatsapp-popover .wp-label, #attach-popover .wp-label { font-size: 14px; color: #042a48; font-weight: 700; }
    #whatsapp-popover .wp-sub, #attach-popover .wp-sub { font-size: 12px; color: #5f7385; margin-top:2px }
        /* Dropdown profissional para ações da tabela */
        .actions-dropdown-wrapper .btn { position:relative; }
        .actions-menu { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Arial; }
        .actions-menu.popover-up { transform-origin: bottom left; }
        .actions-menu.popover-down { transform-origin: top left; }
        .actions-menu.popover-up { box-shadow: 0 -6px 18px rgba(0,0,0,.08); }
        .actions-menu.popover-down { box-shadow: 0 6px 18px rgba(0,0,0,.08); }
        /* small arrow adjustments */
        .actions-menu.popover-up:after, .actions-menu.popover-down:before {
            content: '';
            position: absolute;
            left: 18px;
            border-left: 7px solid transparent;
            border-right: 7px solid transparent;
        }
        .actions-menu.popover-down:before {
            top: -8px;
            border-bottom: 8px solid #fff;
            filter: drop-shadow(0 -1px 0 rgba(0,0,0,0.03));
        }
        .actions-menu.popover-up:after {
            bottom: -8px;
            border-top: 8px solid #fff;
            filter: drop-shadow(0 1px 0 rgba(0,0,0,0.03));
        }
        .actions-menu .actions-item { display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:6px; font-size:13px; cursor:pointer; transition:background .15s ease, transform .12s ease; }
        .actions-menu .actions-item i { width:18px; text-align:center; }
        .actions-menu .actions-item:hover { background:#f5f8fa; transform:translateX(2px); }
        .actions-menu .actions-item:active { background:#e8edf1; }
        @media (max-width: 640px){
            .actions-dropdown-wrapper { position:static !important; }
            .actions-menu { position:fixed !important; left:0 !important; right:0 !important; bottom:0 !important; top:auto !important; border-radius:14px 14px 0 0; box-shadow:0 -4px 18px rgba(0,0,0,.18); padding:14px 16px; }
            .actions-menu .actions-item { padding:14px 12px; font-size:14px; }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
</head>
<body>
    <div class="app-wrapper">
        <!-- SIDEBAR -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- HEADER -->
        <?php require_once __DIR__ . '/../includes/header.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Locações</h1>
                <p class="page-subtitle">Gerencie as locações de climatizadores</p>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="abrirModal()"><i class="fas fa-plus"></i> Nova Locação</button>
                    <button class="btn btn-outline" style="margin-left:8px;" onclick="abrirModalRelatorio()"><i class="fas fa-chart-bar"></i> Relatório</button>
                </div>
            </div>
    
                <!-- Modal pequeno para upload de contrato assinado -->
                <div class="modal-overlay" id="modal-upload-contrato">
                    <div class="modal" style="max-width: 480px; width: 95%;">
                        <div class="modal-header">
                            <h3 class="modal-title">Anexar Contrato Assinado</h3>
                            <button class="modal-close" onclick="fecharModalUploadContrato()"><i class="fas fa-times"></i></button>
                        </div>
                        <form id="form-upload-contrato" onsubmit="uploadContrato(event)" enctype="multipart/form-data">
                            <div class="modal-body">
                                <input type="hidden" id="upload-contrato-locacao-id" name="locacao_id" value="">
                                <div class="form-group">
                                    <label class="form-label required">Arquivo (PDF ou imagem)</label>
                                    <input type="file" class="form-control" id="upload-contrato-file" name="signed_contract" accept=".pdf,image/*" required>
                                    <small class="text-secondary">Tamanho máximo sugerido: 10 MB. Formatos aceitos: PDF, PNG, JPG.</small>
                                </div>
                                    <hr>
                                    <div id="upload-contrato-list" style="margin-top:8px; font-size:13px;"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" onclick="fecharModalUploadContrato()">Cancelar</button>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Anexar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal pequeno para gerar relatório de locações -->
                <div class="modal-overlay" id="modal-relatorio">
                    <div class="modal" style="max-width: 520px; width: 95%;">
                        <div class="modal-header">
                            <h3 class="modal-title">Gerar Relatório de Locações</h3>
                            <button class="modal-close" onclick="fecharModalRelatorio()"><i class="fas fa-times"></i></button>
                        </div>
                        <form id="form-relatorio" onsubmit="gerarRelatorio(event)">
                            <div class="modal-body">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label required">Início</label>
                                        <input type="date" class="form-control" id="relatorio_inicio" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label required">Fim</label>
                                        <input type="date" class="form-control" id="relatorio_fim" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Status</label>
                                        <select class="form-control form-select" id="relatorio_status">
                                            <option value="">Todos</option>
                                            <option>Reserva</option>
                                            <option>Confirmada</option>
                                            <option>Finalizada</option>
                                            <option>Cancelada</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Cliente</label>
                                        <select class="form-control form-select" id="relatorio_cliente">
                                            <option value="">Todos</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" onclick="fecharModalRelatorio()">Cancelar</button>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Gerar PDF</button>
                            </div>
                        </form>
                    </div>
                </div>

            <div class="toolbar">
                <div class="toolbar-left">
                    <button class="btn btn-sm btn-outline" onclick="filtrarPorStatus('todas')">Todas</button>
                    <button class="btn btn-sm btn-success" onclick="filtrarPorStatus('Confirmada')">Confirmadas</button>
                    <button class="btn btn-sm btn-secondary" onclick="filtrarPorStatus('Finalizada')">Finalizadas</button>
                    <button class="btn btn-sm btn-warning" onclick="filtrarPorStatus('Reserva')" style="margin-left:6px;">Reservas</button>
                    <button class="btn btn-sm btn-danger" onclick="filtrarPorStatus('Cancelada')" style="margin-left:6px;">Canceladas</button>
                </div>
                <div class="toolbar-right">
                    <span class="text-secondary">Total: <strong id="total">0</strong></span>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Climatizador</th>
                                    <th>Início</th>
                                    <th>Fim</th>
                                    <th>Dias</th>
                                    <th>Valor Total</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal-overlay" id="modal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Nova Locação</h3>
                <button class="modal-close" onclick="fecharModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="form" onsubmit="salvar(event)">
                <input type="hidden" id="locacao_id">
                <div class="modal-body">
                    <div class="form-row">
                            <div class="form-group">
                                <label class="form-label required">Cliente</label>
                                <select class="form-control form-select" id="cliente_id" required>
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                        </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Data e Hora Início</label>
                            <input type="text" class="form-control" id="data_hora_inicio" onchange="calcularValores()" placeholder="DD/MM/AAAA HH:MM">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Data e Hora Fim</label>
                            <input type="text" class="form-control" id="data_hora_fim" onchange="calcularValores()" placeholder="DD/MM/AAAA HH:MM">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Quantidade Dias</label>
                            <input type="number" class="form-control" id="quantidade_dias" min="1" value="1" required onchange="calcularValores()">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required">Climatizador</label>
                            <!-- Componente multi-climatizador: selecionar modelo + quantidade + valor + Adicionar -->
                            <div id="multi-clim-wrapper">
                                <div class="controls">
                                    <select id="climatizador_select" class="form-control form-select">
                                        <option value="">Selecione...</option>
                                    </select>
                                    <input type="number" id="climatizador_qtd" class="form-control" style="width:92px;" min="1" value="1" title="Quantidade para este modelo">
                                    <input type="number" step="0.01" class="form-control" id="valor_diaria" placeholder="Valor diário (R$)" title="Valor diário para este modelo">
                                    <button type="button" class="btn btn-secondary" id="add-climatizador-btn">Adicionar</button>
                                </div>
                                <div id="climatizadores_list" style="margin-top:8px;font-size:14px;"></div>
                                <input type="hidden" id="climatizadores_json" name="climatizadores_json" value="">
                                <input type="hidden" id="quantidade_climatizadores" value="1">
                                <!-- campo original mantido (hidden) para compatibilidade com backend atual -->
                                <select class="form-control form-select" id="climatizador_id" style="display:none;" onchange="atualizarValorDiaria()">
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- Campo 'Quantidade de Climatizadores' removido: agora mantido como hidden sincronizado com os itens adicionados -->
                    <div class="form-group">
                        <label class="form-label">Aplicar Desconto</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="aplicar_desconto" onchange="toggleDesconto()">
                            <label class="form-check-label" for="aplicar_desconto">Deseja aplicar desconto?</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Despesas acessórias</label>
                        <!-- Campo de valor (permanece para entrada monetária) -->
                        <input type="text" inputmode="decimal" class="form-control" id="despesas_acessorias" value="">
                        <!-- Opções de tipo (radio) para descrever a despesa acessória selecionada -->
                        <div style="margin-top:8px; font-size:13px;">
                            <label style="display:block;"><input type="radio" name="despesas_acessorias_tipo" value="Despesas acessórias (transporte, instalação e suporte)" checked> Despesas acessórias (transporte, instalação e suporte)</label>
                            <label style="display:block;"><input type="radio" name="despesas_acessorias_tipo" value="Despesas acessórias (transporte, instalação e acompanhamento técnico)"> Despesas acessórias (transporte, instalação e acompanhamento técnico)</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valor Total (R$)</label>
                        <input type="number" step="0.01" class="form-control" id="valor_total" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Local do Evento</label>
                        <input type="text" class="form-control" id="local_evento">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Responsável</label>
                        <input type="text" class="form-control" id="responsavel" placeholder="Nome do responsável (opcional)">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" rows="3" placeholder="Observações adicionais que aparecerão no contrato (opcional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                    <!-- Botão de Gerar Orçamento removido por solicitação -->
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/auth.js"></script>
    <script>
        const usuario = <?php echo json_encode($usuario); ?>;
    </script>
    <script>
        // Flag para habilitar logs de depuração específicos desta view
        const DEBUG_LOCACOES = false; // altere para true durante depuração local

        function dbgLog(...args) { if (DEBUG_LOCACOES) console.log.apply(console, args); }
        function dbgDebug(...args) { if (DEBUG_LOCACOES) console.debug.apply(console, args); }
        function dbgWarn(...args) { if (DEBUG_LOCACOES) console.warn.apply(console, args); }

        // Pequena utilitária para escapar HTML (usada localmente nesta view)
        function escapeHtml(str) {
            if (str === null || typeof str === 'undefined') return '';
            return String(str).replace(/[&<>"']/g, function(m) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m]; });
        }

        /**
         * Formata número para moeda BR: 1234.56 -> R$ 1.234,56
         */
        /**
         * Formata número para moeda BR: 1234.56 -> 'R$ 1.234,56' ou '1.234,56' se includeSymbol=false
         * @param {number|string} value
         * @param {boolean} includeSymbol
         */
        function formatCurrencyBR(value, includeSymbol = true) {
            const num = Number(value) || 0;
            try {
                const formatted = num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                return includeSymbol ? ('R$ ' + formatted) : formatted;
            } catch (e) {
                const formatted = num.toFixed(2).replace('.', ',');
                return includeSymbol ? ('R$ ' + formatted) : formatted;
            }
        }

        // Simplified parser: accepts numbers with optional thousand separators and comma or dot decimal
        function parseCurrency(v) {
            if (v === undefined || v === null) return 0;
            if (typeof v === 'number') return v;
            let s = String(v).trim();
            s = s.replace(/^R\$\s?/, '').replace(/\s/g, '');
            // remove all non digits, comma or dot
            s = s.replace(/[^0-9\,\.\-]/g, '');
            // if has comma, treat as decimal separator
            if (s.indexOf(',') !== -1) s = s.replace(/\./g, '').replace(/,/g, '.');
            // otherwise keep dots (they may be decimal points) but remove lone thousand dots (e.g. 1.234 -> 1234)
            else s = s.replace(/\.(?=\d{3}(?:\.|$))/g, '');
            const n = parseFloat(s);
            return isNaN(n) ? 0 : n;
        }

        let locacoes = [];
        let clientes = [];
        let climatizadores = [];
        let filtroAtual = 'todas';
        
        // Preencher o campo de seleção de clientes
        function preencherClientes() {
            const clienteSelect = document.getElementById('cliente_id');
            clienteSelect.innerHTML = '<option value="">Selecione...</option>';
            clientes.forEach(cliente => {
                const option = document.createElement('option');
                option.value = cliente.id;
                option.textContent = cliente.nome;
                clienteSelect.appendChild(option);
            });
        }

        async function carregar() {
            UI.showLoading();
            try {
                const [resLoc, resCli, resClim] = await Promise.all([
                    API.get(API_ENDPOINTS.locacoes),
                    API.get(API_ENDPOINTS.clientes),
                    API.get(API_ENDPOINTS.climatizadores)
                ]);

                dbgLog('Resposta da API - Locações:', resLoc);
                dbgLog('Resposta da API - Clientes:', resCli);
                dbgLog('Resposta da API - Climatizadores:', resClim);

                if (!resLoc.success) {
                    UI.showToast('Erro ao carregar locações: ' + resLoc.message, 'error');
                    return;
                }
                if (!resCli.success) {
                    UI.showToast('Erro ao carregar clientes: ' + resCli.message, 'error');
                    return;
                }
                if (!resClim.success) {
                    UI.showToast('Erro ao carregar climatizadores: ' + resClim.message, 'error');
                    return;
                }

                locacoes = resLoc.data || [];
                clientes = resCli.data || [];
                climatizadores = resClim.data || [];
                // Normalizar campos que podem vir como string/ausentes
                climatizadores = (resClim.data || []).map(c => ({
                    ...c,
                    valor_diaria: (c.valor_diaria !== undefined) ? (typeof c.valor_diaria === 'number' ? c.valor_diaria : parseFloat(String(c.valor_diaria).replace(',', '.')) || 0) : 0,
                    desconto_maximo: (c.desconto_maximo !== undefined) ? (parseFloat(String(c.desconto_maximo).replace(',', '.')) || 0) : 0,
                    // Normaliza status para comparações consistentes (trim + lowercase)
                    status: (c.status || '').toString().trim()
                }));

                dbgDebug('Climatizadores normalizados:', climatizadores);

                preencherClientes();
                // Preencher select de clientes no modal de relatório
                const relCli = document.getElementById('relatorio_cliente');
                if (relCli) {
                    relCli.innerHTML = '<option value="">Todos</option>';
                    (clientes || []).forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id; opt.textContent = c.nome;
                        relCli.appendChild(opt);
                    });
                }
                renderizar(locacoes);
                dbgLog('Locações carregadas:', locacoes);
            } catch (error) {
                console.error('Erro inesperado ao carregar dados:', error);
                UI.showToast('Erro inesperado ao carregar dados', 'error');
            } finally {
                UI.hideLoading();
            }
        }
        
        function formatarDataBR(isoDate) {
            if (!isoDate) return '—';
            // aceita formatos YYYY-MM-DD ou YYYY-MM-DDTHH:MM:SS
            const d = new Date(isoDate);
            if (isNaN(d.getTime())) return isoDate; // se não for uma data válida, retorna original
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            return `${day}/${month}/${year}`;
        }

        /**
         * Retorna true se a locação já finalizou comparando data_fim + hora_fim com o momento atual.
         * item espera campos: data_fim (YYYY-MM-DD) e hora_fim (HH:MM ou HH:MM:SS) — ambos opcionais.
         */
        function isLocacaoFinalizada(item) {
            if (!item || !item.data_fim) return false;
            const dataFim = item.data_fim;
            const horaFim = item.hora_fim || '23:59:59';
            // Constrói string ISO compatível
            const iso = `${dataFim}T${horaFim}`;
            const fim = new Date(iso);
            if (isNaN(fim.getTime())) {
                // Tentar sem 'T' (algumas APIs retornam com espaço)
                const alt = new Date(`${dataFim} ${horaFim}`);
                if (isNaN(alt.getTime())) return false;
                return alt.getTime() <= Date.now();
            }
            return fim.getTime() <= Date.now();
        }

        function renderizar(data) {
            const tbody = document.getElementById('tbody');
            tbody.innerHTML = '';
            document.getElementById('total').textContent = data.length;
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="empty-state"><div class="empty-state-icon">📄</div><div class="empty-state-title">Nenhuma locação encontrada</div></td></tr>';
                return;
            }
            data.forEach(item => {
                const tr = document.createElement('tr');
                // Determinar se já finalizou pela data/hora
                const finalizada = isLocacaoFinalizada(item);
                let badgeClass = 'default';
                let badgeText = '—';
                const status = (item.status || '').toString().trim();
                if (finalizada || status === 'Finalizada') {
                    badgeClass = 'secondary';
                    badgeText = 'Finalizada';
                } else if (status === 'Reserva') {
                    badgeClass = 'warning';
                    badgeText = 'Reserva';
                } else if (status === 'Confirmada' || status === 'Ativa') {
                    badgeClass = 'success';
                    badgeText = 'Confirmada';
                } else if (status === 'Cancelada') {
                    badgeClass = 'danger';
                    badgeText = 'Cancelada';
                } else if (status) {
                    badgeClass = 'info';
                    badgeText = status;
                }

                // botão de editar mostrado apenas quando locação não foi efetivada/confirmada
                const canEdit = (!finalizada && item.status !== 'Confirmada' && item.status !== 'Ativa' && item.status !== 'Cancelada');
                const editBtn = canEdit ? `<button class="btn btn-sm btn-outline" title="Editar Locação" onclick="abrirModalEdicao(${item.id})"><i class="fas fa-edit"></i></button>` : '';
                // montar string de climatizadores suportando múltiplos modelos (se enviados pelo backend)
                let climDisplay = '—';
                if (Array.isArray(item.climatizadores) && item.climatizadores.length > 0) {
                    try {
                        climDisplay = item.climatizadores.map(it => {
                            const desc = it.descricao || it.modelo || it.codigo || 'Climatizador';
                            const qtd = Number(it.quantidade || it.qtd || 1) || 1;
                            return desc + (qtd > 1 ? (' x' + qtd) : '');
                        }).join(' + ');
                    } catch (e) { climDisplay = '—'; }
                } else {
                    climDisplay = (item.climatizador_modelo ? (item.climatizador_modelo + (item.climatizador_capacidade ? ' ' + item.climatizador_capacidade : '')) : (item.climatizador_codigo || '—'));
                }

                // calcular valor total de forma consistente com o backend quando houver múltiplos itens
                let totalNum = 0;
                if (Array.isArray(item.climatizadores) && item.climatizadores.length > 0) {
                    const dias = Number(item.quantidade_dias) || 1;
                    item.climatizadores.forEach(it => {
                        const qtd = Number(it.quantidade || it.qtd || 1) || 1;
                        const vd = Number(it.valor_diaria || it.valor_unitario || 0) || 0;
                        totalNum += vd * qtd * dias;
                    });
                    const desconto = Number(item.desconto || 0);
                    if (desconto > 0) totalNum = totalNum - (totalNum * desconto / 100);
                    totalNum += Number(item.despesas_acessorias || 0) || 0;
                } else {
                    totalNum = Number(item.valor_total || 0);
                }

                tr.innerHTML = `
                    <td>${item.cliente_nome}</td>
                    <td>${escapeHtml(climDisplay)}</td>
                    <td>${formatarDataBR(item.data_inicio)}</td>
                    <td>${formatarDataBR(item.data_fim)}</td>
                    <td>${item.quantidade_dias}</td>
                        <td>${formatCurrencyBR(totalNum)}</td>
                        <td><span class="badge badge-${badgeClass}">${badgeText || '—'}</span></td>
                    <td>
                        <div class="actions-dropdown-wrapper" style="position:relative;">
                            <button class="btn btn-sm btn-primary" title="Ações" onclick="toggleActionsMenu(this, ${item.id})">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="actions-menu" data-id="${item.id}" style="display:none; position:absolute; z-index:1000; top:100%; left:0; background:#fff; border:1px solid #ddd; box-shadow:0 4px 14px rgba(0,0,0,.1); border-radius:8px; padding:6px; min-width:200px;">
                                <div class="actions-item" onclick="efetivarLocacao(${item.id})"><i class="fas fa-check text-primary"></i> Efetivar Locação</div>
                                <div class="actions-item" onclick="visualizarOrcamento(${item.id})"><i class="fas fa-file-pdf text-info"></i> Ver Orçamento</div>
                                <div class="actions-item" onclick="visualizarContrato(${item.id})"><i class="fas fa-file-contract text-primary"></i> Ver Contrato</div>
                                <div class="actions-item" onclick="showAttachOptions(event, ${item.id})"><i class="fas fa-upload text-secondary"></i> Anexos / Contratos</div>
                                <div class="actions-item" onclick="showWhatsappOptions(event, ${item.id})"><i class="fab fa-whatsapp text-success"></i> Enviar WhatsApp</div>
                                ${canEdit ? `<div class="actions-item" onclick="abrirModalEdicao(${item.id})"><i class=\"fas fa-edit text-warning\"></i> Editar</div>` : ''}
                                <div class="actions-item" onclick="cancelarLocacao(${item.id})"><i class="fas fa-ban text-danger"></i> Cancelar</div>
                            </div>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
        
        function filtrarPorStatus(status) {
            filtroAtual = status;
            if (status === 'todas') {
                renderizar(locacoes);
            } else if (status === 'Finalizada') {
                // Incluir locações cujo status seja explicitamente 'Finalizada' OU cuja data/hora de fim já passou
                const filtradas = locacoes.filter(l => (l.status === 'Finalizada') || isLocacaoFinalizada(l));
                renderizar(filtradas);
            } else {
                const filtradas = locacoes.filter(l => l.status === status);
                renderizar(filtradas);
            }
        }
        
        async function abrirModal() {
            // Se clientes não estiverem carregados, buscar os dados
            if (clientes.length === 0) {
                await carregar();
            }

            document.getElementById('form').reset();
            // limpar locacao_id quando criando nova
            const locIdEl = document.getElementById('locacao_id');
            if (locIdEl) locIdEl.value = '';
            // reset multi-climatizadores
            _modalClimatizadores = [];
            try { renderClimatizadoresList(); } catch(e) {}
            // Inicializar despesas acessórias formatado
            try { const despEl = document.getElementById('despesas_acessorias'); if (despEl) despEl.value = formatCurrencyBR(0, false); } catch (e) {}
            const modalTitle = document.querySelector('.modal-title');
            if (modalTitle) modalTitle.textContent = 'Nova Locação';
            // Reset do estado de desconto ao abrir o modal
            const aplicarCheckbox = document.getElementById('aplicar_desconto');
            if (aplicarCheckbox) aplicarCheckbox.checked = false;

            // Preencher select de clientes
            const selectCliente = document.getElementById('cliente_id');
            selectCliente.innerHTML = '<option value="">Selecione...</option>';
            clientes.forEach(c => {
                selectCliente.innerHTML += `<option value="${c.id}">${c.nome}</option>`;
            });

            // Buscar climatizadores disponíveis diretamente do backend (garante campo 'disponivel')
            const selectClim = document.getElementById('climatizador_id');
            selectClim.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Selecione...';
            selectClim.appendChild(placeholder);

            try {
                const res = await API.get(API_ENDPOINTS.climatizadores, { disponiveis: 1 });
                if (res && res.success && Array.isArray(res.data) && res.data.length > 0) {
                    // Normalizar recebido
                    res.data.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.setAttribute('data-valor', c.valor_diaria || 0);
                        opt.setAttribute('data-desconto-maximo', c.desconto_maximo || 0);
                        const disponText = (c.disponivel !== undefined) ? `dispon: ${c.disponivel}` : '';
                        opt.textContent = `${c.modelo} - ${c.marca} (estoque: ${c.estoque || 0} ${disponText ? '/ ' + disponText : ''})`;
                        selectClim.appendChild(opt);
                    });
                } else {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.disabled = true;
                    opt.textContent = 'Nenhum climatizador disponível no momento';
                    selectClim.appendChild(opt);
                }
            } catch (e) {
                console.error('Erro ao buscar climatizadores disponíveis:', e);
                const opt = document.createElement('option');
                opt.value = '';
                opt.disabled = true;
                opt.textContent = 'Erro ao carregar climatizadores';
                selectClim.appendChild(opt);
            }
            
            UI.openModal('modal');

            // Garantir listeners para recalcular valores quando inputs mudarem
            attachListenersModal();
        }

        // Dropdown de ações: abrir/fechar
        function toggleActionsMenu(button, id) {
            const wrapper = button.closest('.actions-dropdown-wrapper');
            if (!wrapper) return;
            // Tentar obter o menu preferencialmente dentro do wrapper; se foi movido para body, localizar pelo data-orig-parent-id
            let menu = wrapper.querySelector('.actions-menu');
            if (!menu) {
                const wrapperId = wrapper.dataset._actionsWrapperId || '';
                if (wrapperId) {
                    menu = document.querySelector('.actions-menu[data-orig-parent-id="' + wrapperId + '"]');
                }
            }
            // fallback para qualquer menu disponível
            if (!menu) menu = document.querySelector('.actions-menu');
            if (!menu) return;
            const open = menu.getAttribute && menu.getAttribute('data-open') === '1';
            // Fechar todos os menus abertos (remover instâncias movidas para body)
            document.querySelectorAll('.actions-menu[data-open="1"]').forEach(m => {
                closeActionsMenu(m);
            });
            if (!open) {
                // marcar como aberto
                menu.setAttribute('data-open', '1');
                // preservar referência do parent original para restaurar depois
                const assignId = wrapper.dataset._actionsWrapperId || Math.random().toString(36).slice(2);
                menu.setAttribute('data-orig-parent-id', assignId);
                wrapper.dataset._actionsWrapperId = assignId;
                menu.dataset._origParent = assignId;
                // mover para body para evitar clipping por overflow
                document.body.appendChild(menu);
                menu.style.display = 'block';
                // usar posicionamento fixo para ficar relativo à viewport
                menu.style.position = 'fixed';
                menu.style.zIndex = 20000;
                positionActionsMenu(wrapper, menu, button);
                // garantir que o menu será fechado ao trocar de rolagem ou redimensionamento
                window.addEventListener('resize', onGlobalCloseActions);
                window.addEventListener('scroll', onGlobalCloseActions, true);
            }
        }

        function positionActionsMenu(wrapper, menu, button){
            // Posiciona o menu de ações no viewport com base nas coordenadas do botão
            menu.classList.remove('popover-up', 'popover-down');
            // Medir usando display block temporário se estiver oculto
            const wasHidden = (menu.style.display === 'none' || getComputedStyle(menu).display === 'none');
            if (wasHidden) {
                menu.style.visibility = 'hidden';
                menu.style.display = 'block';
            }
            const menuRect = menu.getBoundingClientRect();
            const menuW = menuRect.width;
            const menuH = menuRect.height;
            const btnRect = button.getBoundingClientRect();

            const spaceBelow = window.innerHeight - btnRect.bottom;
            const spaceAbove = btnRect.top;
            const spaceRight = window.innerWidth - btnRect.left;

            // Preferir posicionar abaixo do botão alinhado à esquerda do botão, se couber
            let top = 0, left = 0;
            // Mobile: usar comportamento de bottom-sheet quando a viewport for pequena
            if (window.innerWidth <= 640) {
                // aplicar estilo de bottom sheet
                menu.style.position = 'fixed';
                menu.style.left = '0px';
                menu.style.right = '0px';
                menu.style.bottom = '0px';
                menu.style.top = 'auto';
                menu.style.width = '100%';
                menu.style.maxWidth = '100%';
                menu.style.borderRadius = '12px 12px 0 0';
                menu.classList.remove('popover-up','popover-down');
                menu.classList.add('popover-up');
                return;
            }
            if (spaceBelow >= menuH) {
                top = btnRect.bottom + 6; // pequeno offset
            } else {
                // abrir acima
                top = Math.max(6, btnRect.top - menuH - 6);
            }

            // Tentar alinhar ao lado esquerdo do botão; se não couber à direita, ajustar para não estourar
            left = btnRect.left;
            if (left + menuW > window.innerWidth - 12) {
                left = Math.max(8, window.innerWidth - menuW - 12);
            }
            if (left < 8) left = 8;

            menu.style.top = Math.round(top) + 'px';
            menu.style.left = Math.round(left) + 'px';
            // adicionar classes para estilo (opcional)
            if (spaceBelow >= menuH) menu.classList.add('popover-down'); else menu.classList.add('popover-up');

            if (wasHidden) {
                // não esconder aqui - o chamador controla visibilidade
                menu.style.visibility = '';
            }
        }

        function closeActionsMenu(menu) {
            try {
                menu.removeAttribute('data-open');
                // restaurar para o wrapper original (se existir)
                const origWrapper = document.querySelector('.actions-dropdown-wrapper');
                // preferimos apenas esconder e re-anexar ao último wrapper se possível
                menu.style.display = 'none';
                menu.style.position = '';
                menu.style.top = '';
                menu.style.left = '';
                menu.classList.remove('popover-up', 'popover-down');
            } catch (e) { /* ignore */ }
        }

        function onGlobalCloseActions() {
            document.querySelectorAll('.actions-menu[data-open="1"]').forEach(m => closeActionsMenu(m));
            window.removeEventListener('resize', onGlobalCloseActions);
            window.removeEventListener('scroll', onGlobalCloseActions, true);
        }

        // Fechar ao clicar fora do menu ou do botão (compatível com menus movidos para body)
        document.addEventListener('click', function(e){
            const inMenu = !!e.target.closest('.actions-menu');
            const inWrapper = !!e.target.closest('.actions-dropdown-wrapper');
            if (!inMenu && !inWrapper) {
                document.querySelectorAll('.actions-menu[data-open="1"]').forEach(m => {
                    try { m.style.display = 'none'; m.removeAttribute('data-open'); m.style.position = ''; } catch (err) {}
                });
            }
        });

        // Fechar ao rolar
        window.addEventListener('scroll', () => {
            document.querySelectorAll('.actions-menu').forEach(m => m.style.display = 'none');
        }, true);
        
        function fecharModal() {
            UI.closeModal('modal');
        }
        
        function atualizarValorDiaria() {
            const climatizadorId = document.getElementById('climatizador_id').value;
            const climatizador = climatizadores.find(c => c.id == climatizadorId);

            if (climatizador) {
                // Garantir que valor_diaria seja numérico antes de chamar toFixed
                let valor = parseFloat(climatizador.valor_diaria);
                if (isNaN(valor)) {
                    valor = parseFloat(String(climatizador.valor_diaria).replace(',', '.')) || 0;
                }
                const vdEl = document.getElementById('valor_diaria');
                // Só sobrescrever o valor se o usuário não tiver editado manualmente
                if (vdEl && !vdEl.dataset.userEdited) vdEl.value = valor.toFixed(2);
                // Atualiza label com desconto máximo (informativo)
                const label = document.getElementById('desconto_maximo_label');
                if (label) label.textContent = `(Máximo: ${climatizador.desconto_maximo ?? 0}%)`;
                calcularValores();
            }
        }
        
        function toggleDesconto() {
            calcularValores();
        }

        /* ---------- Multi-climatizador UI helpers ---------- */
        // Lista em memória para o modal (array de {id, modelo, qtd, valor_diaria})
        let _modalClimatizadores = [];

        function renderClimatizadoresList() {
            const holder = document.getElementById('climatizadores_list');
            const hidden = document.getElementById('climatizadores_json');
            const hiddenSelect = document.getElementById('climatizador_id');
            if (!holder || !hidden) return;
            if (_modalClimatizadores.length === 0) {
                holder.innerHTML = '<div class="text-secondary">Nenhum climatizador adicionado. Use o seletor acima para incluir modelos.</div>';
                // Restaurar campo original vazio
                if (hiddenSelect) hiddenSelect.value = '';
            } else {
                holder.innerHTML = _modalClimatizadores.map((it, idx) => `
                    <div class="chip" data-idx="${idx}" style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-radius:10px;background:#f7fbfd;border:1px solid rgba(2,6,23,0.03);margin-bottom:6px;">
                        <div style="display:flex;gap:12px;align-items:center;">
                            <div style="font-weight:600;min-width:180px;">${escapeHtml(it.modelo)}</div>
                            <div style="color:#6b7280;">Qtd: ${it.qtd}</div>
                            <div style="color:#6b7280;">R$ ${Number(it.valor_diaria).toFixed(2)}</div>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline" onclick="removeClimatizador(${idx})">Remover</button>
                        </div>
                    </div>
                `).join('');
                // manter compatibilidade: setar climatizador_id como o primeiro item
                if (hiddenSelect) hiddenSelect.value = _modalClimatizadores[0].id;
            }
            // serializar
            hidden.value = JSON.stringify(_modalClimatizadores);
            // atualizar quantidade compatível (hidden) com a soma das quantidades do componente
            try {
                const qHidden = document.getElementById('quantidade_climatizadores');
                if (qHidden) qHidden.value = (_modalClimatizadores.reduce((s,i) => s + (Number(i.qtd)||0), 0) || 1);
            } catch(e) {}
            calcularValores();
        }

        function addClimatizadorFromSelect() {
            const sel = document.getElementById('climatizador_select');
            const qtdEl = document.getElementById('climatizador_qtd');
            if (!sel) return;
            const id = sel.value;
            if (!id) { UI.showToast('Selecione um climatizador para adicionar', 'warning'); return; }
            const qtd = Math.max(1, parseInt(qtdEl.value) || 1);
            const c = climatizadores.find(x => String(x.id) == String(id));
            if (!c) { UI.showToast('Climatizador selecionado não encontrado', 'error'); return; }
            // se já existe, apenas incrementar quantidade
            const existing = _modalClimatizadores.find(m => String(m.id) === String(id));
            if (existing) {
                existing.qtd = Number(existing.qtd) + qtd;
            } else {
                // permitir que o usuário informe um valor diário específico ao adicionar; usar fallback para o valor do catálogo
                const vdEl = document.getElementById('valor_diaria');
                let vd = 0;
                try { vd = parseFloat(vdEl && vdEl.value ? String(vdEl.value).replace(',', '.') : '') || parseFloat(c.valor_diaria) || 0; } catch(e) { vd = parseFloat(c.valor_diaria) || 0; }
                _modalClimatizadores.push({ id: c.id, modelo: c.modelo || (c.codigo || '—'), qtd: qtd, valor_diaria: Number(vd) });
            }
            // reset quantidade para 1
            if (qtdEl) qtdEl.value = '1';
            renderClimatizadoresList();
        }

        function removeClimatizador(idx) {
            if (idx < 0 || idx >= _modalClimatizadores.length) return;
            _modalClimatizadores.splice(idx, 1);
            renderClimatizadoresList();
        }

        function populateClimatizadorSelects() {
            // preencher tanto o select visível quanto o hidden original (compatibilidade)
            const vis = document.getElementById('climatizador_select');
            const hidden = document.getElementById('climatizador_id');
            if (!vis || !hidden) return;
            // Se o select hidden já foi populado (ex.: com disponibilidade específica), apenas clonar as opções
            if (hidden.options && hidden.options.length > 1) {
                vis.innerHTML = '<option value="">Selecione...</option>';
                Array.from(hidden.options).forEach(opt => {
                    // evitar clonar placeholder vazio
                    if (!opt.value) return;
                    const clone = opt.cloneNode(true);
                    vis.appendChild(clone);
                });
                return;
            }

            // Caso contrário, popular a partir do array global `climatizadores`
            vis.innerHTML = '<option value="">Selecione...</option>';
            hidden.innerHTML = '<option value="">Selecione...</option>';
            (climatizadores || []).forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                const disponText = (c.disponivel !== undefined) ? `dispon: ${c.disponivel}` : '';
                opt.textContent = `${c.modelo} - ${c.marca} (estoque: ${c.estoque || 0}${disponText ? ' / ' + disponText : ''})`;
                vis.appendChild(opt);
                const opt2 = opt.cloneNode(true);
                opt2.setAttribute('data-valor', c.valor_diaria || 0);
                opt2.setAttribute('data-desconto-maximo', c.desconto_maximo || 0);
                hidden.appendChild(opt2);
            });
        }


        function calcularValores() {
            dbgDebug('calcularValores - entrando');
            const quantidadeDias = parseInt(document.getElementById('quantidade_dias').value) || 1;
            const aplicarDesconto = document.getElementById('aplicar_desconto').checked;

            // Se houver itens adicionados no componente multi-climatizador, calcular com base neles
            const hidden = document.getElementById('climatizadores_json');
            let valorTotal = 0;
            if (hidden && hidden.value) {
                try {
                    const items = JSON.parse(hidden.value) || [];
                    if (Array.isArray(items) && items.length > 0) {
                        items.forEach(it => {
                            const qtd = Number(it.qtd) || 1;
                            let vd = Number(it.valor_diaria || 0) || 0;
                            let itemTotal = vd * quantidadeDias * qtd;
                            // aplicar desconto por item quando habilitado (usa desconto_maximo do climatizador real se existir)
                            if (aplicarDesconto) {
                                const cReal = climatizadores.find(c => String(c.id) === String(it.id));
                                const descontoMaximo = cReal ? (parseFloat(cReal.desconto_maximo) || 0) : 0;
                                if (descontoMaximo > 0) itemTotal = itemTotal - (itemTotal * descontoMaximo / 100);
                            }
                            valorTotal += itemTotal;
                        });
                    } else {
                        // fallback para comportamento antigo: usar campo valor_diaria * quantidade_climatizadores
                        const valorDiaria = parseFloat(document.getElementById('valor_diaria').value) || 0;
                        const quantidadeClimatizadores = parseInt(document.getElementById('quantidade_climatizadores').value) || 1;
                        valorTotal = valorDiaria * quantidadeDias * quantidadeClimatizadores;
                    }
                } catch (e) {
                    dbgWarn('Erro ao parsear climatizadores_json:', e);
                }
            } else {
                // comportamento anterior (um único modelo)
                const valorDiaria = parseFloat(document.getElementById('valor_diaria').value) || 0;
                const quantidadeClimatizadores = parseInt(document.getElementById('quantidade_climatizadores').value) || 1;
                const climatizadorId = document.getElementById('climatizador_id') ? document.getElementById('climatizador_id').value : '';
                valorTotal = valorDiaria * quantidadeDias * quantidadeClimatizadores;
                if (aplicarDesconto && climatizadorId) {
                    const climatizador = climatizadores.find(c => c.id == climatizadorId);
                    if (climatizador) {
                        const descontoMaximo = parseFloat(climatizador.desconto_maximo) || 0;
                        if (descontoMaximo > 0) {
                            valorTotal -= (valorTotal * descontoMaximo / 100);
                        }
                    }
                }
            }

            // Incluir despesas acessórias no total exibido
            const despEl = document.getElementById('despesas_acessorias');
            const despesas = despEl ? parseCurrency(despEl.value) : 0;
            const valorTotalEl = document.getElementById('valor_total');
            if (valorTotalEl) {
                const totalComDespesas = valorTotal + (despesas || 0);
                valorTotalEl.value = totalComDespesas.toFixed(2);
            }
            dbgDebug('calcularValores - valorTotal final=', valorTotal);
        }

        /**
         * As datas de início/fim são opcionais.
         * Alguns fluxos (ex.: Flatpickr altInput / cache de HTML) podem deixar um asterisco visual de obrigatório.
         * Esta função força o estado visual e remove atributos/classe de obrigatório das datas.
         */
        function ensureDateFieldsAreOptionalUi() {
            const ids = ['data_hora_inicio', 'data_hora_fim'];
            ids.forEach((id) => {
                const el = document.getElementById(id);
                if (!el) return;

                // atributo HTML
                try { el.removeAttribute('required'); } catch (e) {}

                // Flatpickr pode criar altInput (o campo visível)
                try {
                    if (el._flatpickr && el._flatpickr.altInput) {
                        el._flatpickr.altInput.removeAttribute('required');
                    }
                } catch (e) {}

                // remover marcação visual no label
                try {
                    const group = el.closest('.form-group');
                    const label = group ? group.querySelector('.form-label') : null;
                    if (label) label.classList.remove('required');
                } catch (e) {}
            });
        }
        
        // gerador simples removido para evitar duplicação — função completa abaixo

    function attachListenersModal() {
            // Evita múltiplos listeners: remove antes
            const fields = ['climatizador_id', 'quantidade_dias', 'quantidade_climatizadores', 'aplicar_desconto'];
            fields.forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                // Remover event listeners antigos usando cloneNode, preservando estado
                const newEl = el.cloneNode(true);
                try {
                    const tag = (el.tagName || '').toUpperCase();
                    if (tag === 'INPUT') {
                        const type = (el.type || '').toLowerCase();
                        if (type === 'checkbox' || type === 'radio') {
                            newEl.checked = el.checked;
                        }
                        // sempre preservar o value atual
                        newEl.value = el.value;
                    } else if (tag === 'SELECT') {
                        newEl.value = el.value;
                    } else if (tag === 'TEXTAREA') {
                        newEl.value = el.value;
                    }
                    // preservar flags/data-attributes úteis
                    if (el.dataset && el.dataset._listenerAttached) newEl.dataset._listenerAttached = el.dataset._listenerAttached;
                } catch (e) {
                    dbgWarn('Falha ao preservar estado do elemento ao clonar:', e);
                }
                el.parentNode.replaceChild(newEl, el);
                // Adicionar listener adequado
                if (id === 'climatizador_id') {
                    newEl.addEventListener('change', () => { atualizarValorDiaria(); });
                } else if (id === 'aplicar_desconto') {
                    // Quando o usuário alternar o checkbox, mostrar/ocultar container e recalcular
                    newEl.addEventListener('change', () => {
                        const descontoContainer = document.getElementById('desconto_container');
                        if (descontoContainer) descontoContainer.style.display = newEl.checked ? 'block' : 'none';
                        calcularValores();
                    });
                } else {
                    newEl.addEventListener('input', () => { calcularValores(); });
                }
            });

            // Listener específico para o campo valor_diaria: marcar se o usuário editou manualmente
            const vd = document.getElementById('valor_diaria');
            if (vd) {
                // Quando o usuário digitar, marcar a flag e recalcular
                vd.addEventListener('input', function() {
                    this.dataset.userEdited = '1';
                    calcularValores();
                });
                // Se desejar limpar a flag ao focar com tecla Ctrl (ex.: Ctrl+Z para reset), permitir double-click para reset
                vd.addEventListener('dblclick', function() {
                    delete this.dataset.userEdited;
                    atualizarValorDiaria(); // reaplica padrão do climatizador
                    calcularValores();
                });
            }
            // Para campos de data não clonamos (quebra o Flatpickr). Apenas adicionamos listeners.
            ['data_hora_inicio', 'data_hora_fim'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    // Remover listeners duplicados de forma segura: usar uma flag no dataset
                    if (!el.dataset._listenerAttached) {
                        el.addEventListener('change', () => calcularValores());
                        el.dataset._listenerAttached = '1';
                    }
                }
            });

            // Re-inicializar Flatpickr para garantir que, se o input foi substituído em algum fluxo anterior, o widget exista.
            try {
                if (typeof flatpickr === 'function') {
                    // Recreate with same options used na inicialização
                    flatpickr('#data_hora_inicio', { enableTime: true, altInput: true, altFormat: 'd/m/Y H:i', dateFormat: 'Y-m-d H:i', time_24hr: true, locale: 'pt' });
                    flatpickr('#data_hora_fim', { enableTime: true, altInput: true, altFormat: 'd/m/Y H:i', dateFormat: 'Y-m-d H:i', time_24hr: true, locale: 'pt' });
                }
                } catch (e) {
                dbgWarn('Não foi possível re-inicializar Flatpickr:', e);
            }
            // listeners para componente multi-climatizador
            const addBtn = document.getElementById('add-climatizador-btn');
            if (addBtn) {
                addBtn.removeEventListener('click', addClimatizadorFromSelect);
                addBtn.addEventListener('click', addClimatizadorFromSelect);
            }
            // Sincronizar select visível com o select oculto e atualizar valor ao mudar
            const visSelect = document.getElementById('climatizador_select');
            const hiddenSelect = document.getElementById('climatizador_id');
            if (visSelect && hiddenSelect) {
                // remover listener antigo se existir
                try { visSelect.removeEventListener('change', visSelect._syncHandler || (()=>{})); } catch(e) {}
                visSelect._syncHandler = function(e) {
                    // copiar valor para o select oculto (compatibilidade) e atualizar o valor diário
                    try {
                        hiddenSelect.value = this.value;
                    } catch (err) { /* ignore */ }
                    // marcar que não foi editado pelo usuário para permitir override
                    const vdEl = document.getElementById('valor_diaria');
                    if (vdEl) delete vdEl.dataset.userEdited;
                    try { atualizarValorDiaria(); } catch (err) { dbgWarn('Falha ao atualizar valor ao mudar select visível:', err); }
                };
                visSelect.addEventListener('change', visSelect._syncHandler);
            }
            // popular selects visíveis com os dados carregados
            populateClimatizadorSelects();
                // Formatação para despesas_acessorias (input text)
                const despEl = document.getElementById('despesas_acessorias');
                if (despEl) {
                    if (!despEl.dataset._listenersAttached) {
                        // aceitar apenas dígitos, vírgula, ponto, sinal negativo
                        despEl.addEventListener('input', (ev) => {
                            const cleaned = ev.target.value.replace(/[^0-9\,\.\-]/g, '');
                            if (cleaned !== ev.target.value) ev.target.value = cleaned;
                            calcularValores();
                        });

                        // ao focar, mostrar valor numérico sem símbolo, sem selecionar automaticamente
                        despEl.addEventListener('focus', (ev) => {
                            const n = parseCurrency(ev.target.value);
                            ev.target.value = (n || n === 0) ? String(n) : '';
                        });

                        // ao perder foco, formatar para moeda BR sem símbolo dentro do input
                        despEl.addEventListener('blur', (ev) => {
                            const n = parseCurrency(ev.target.value);
                            ev.target.value = formatCurrencyBR(n, false);
                            calcularValores();
                        });

                        despEl.dataset._listenersAttached = '1';
                    }
                    // inicializar exibindo 0 formatado (sem símbolo) caso vazio
                    try { if (!String(despEl.value).trim()) despEl.value = formatCurrencyBR(0, false); else despEl.value = formatCurrencyBR(parseCurrency(despEl.value), false); } catch (e) {}
                }
        }
        
        async function salvar(e) {
            e.preventDefault();
            // Garantir recálculo antes de enviar
            calcularValores();

            const dados = {
                cliente_id: document.getElementById('cliente_id').value,
                // mantém compatibilidade: envia climatizador_id como primeiro item caso existam múltiplos
                climatizador_id: (function(){ try{ const h=document.getElementById('climatizadores_json'); if(h && h.value){ const arr=JSON.parse(h.value); if(Array.isArray(arr)&&arr.length>0) return arr[0].id; } return document.getElementById('climatizador_id').value;}catch(e){return document.getElementById('climatizador_id').value}})(),
                data_inicio: document.getElementById('data_hora_inicio').value,
                data_fim: document.getElementById('data_hora_fim').value,
                valor_diaria: document.getElementById('valor_diaria').value,
                responsavel: document.getElementById('responsavel') ? document.getElementById('responsavel').value : '',
                quantidade_dias: document.getElementById('quantidade_dias').value,
                quantidade_climatizadores: document.getElementById('quantidade_climatizadores').value,
                valor_total: document.getElementById('valor_total').value,
                aplicar_desconto: document.getElementById('aplicar_desconto').checked ? 1 : 0,
                despesas_acessorias: document.getElementById('despesas_acessorias') ? parseCurrency(document.getElementById('despesas_acessorias').value) : 0,
                despesas_acessorias_tipo: (document.querySelector('input[name="despesas_acessorias_tipo"]:checked') ? document.querySelector('input[name="despesas_acessorias_tipo"]:checked').value : ''),
                observacoes: document.getElementById('observacoes') ? document.getElementById('observacoes').value : '',
                local_evento: document.getElementById('local_evento').value || ''
            };
            // incluir lista completa de climatizadores (compatível com backend futuro)
            try { const h = document.getElementById('climatizadores_json'); if (h && h.value) dados.climatizadores = JSON.parse(h.value); } catch(e) { dados.climatizadores = []; }
            // ajustar quantidade total de climatizadores para compatibilidade
            try { const h = document.getElementById('climatizadores_json'); if (h && h.value) { const arr = JSON.parse(h.value)||[]; dados.quantidade_climatizadores = arr.reduce((s,i)=> s + (Number(i.qtd)||0), 0) || document.getElementById('quantidade_climatizadores').value; } } catch(e) {}
            
            UI.showLoading();
            try {
                const locacaoId = document.getElementById('locacao_id').value;
                let res;
                if (locacaoId) {
                    // edição
                    dados.id = locacaoId;
                    res = await API.put(API_ENDPOINTS.locacoes, dados);
                } else {
                    // criação
                    res = await API.post(API_ENDPOINTS.locacoes, dados);
                }

                if (res && res.success) {
                    UI.showToast(res.message, 'success');
                    fecharModal();
                    carregar();
                } else {
                    UI.showToast(res ? res.message : 'Erro desconhecido', 'error');
                }
            } finally {
                UI.hideLoading();
            }
        }
        
        async function efetivarLocacao(id) {
            const ok = confirm('Deseja realmente confirmar esta locação? Esta ação marcará o equipamento como locado.');
            if (!ok) return;
            UI.showLoading();
            try {
                const res = await API.put(API_ENDPOINTS.locacoes, { id, status: 'Confirmada' });
                if (res && res.success) {
                    UI.showToast('Locação confirmada!', 'success');
                    carregar();
                } else {
                    UI.showToast(res ? res.message : 'Erro ao efetivar locação', 'error');
                }
            } catch (e) {
                console.error('Erro ao efetivar locação:', e);
                UI.showToast('Erro ao efetivar locação', 'error');
            } finally {
                UI.hideLoading();
            }
        }

        async function cancelarLocacao(id) {
            const ok = confirm('Deseja realmente cancelar esta locação? Esta ação marcará a locação como "Cancelada".');
            if (!ok) return;
            UI.showLoading();
            try {
                const res = await API.put(API_ENDPOINTS.locacoes, { id: id, acao: 'cancelar' });
                if (res && res.success) {
                    UI.showToast('Locação cancelada com sucesso.', 'success');
                    carregar();
                } else {
                    UI.showToast(res.message || 'Erro ao cancelar locação', 'error');
                }
            } catch (e) {
                UI.showToast('Erro de comunicação ao cancelar locação', 'error');
            } finally {
                UI.hideLoading();
            }
        }
        
        async function visualizarOrcamento(id) {
            try {
                const url = `${window.location.origin}/controllers/LocacaoControllerOrcamento.php?orcamento&id=${encodeURIComponent(id)}&t=${Date.now()}`;
                const overlay = document.getElementById('modalOrcamentoOverlay');
                const obj = document.getElementById('objOrcamentoPdf');
                const download = document.getElementById('downloadOrcamentoPdf');
                const btnBaixar = document.getElementById('btnBaixarPdf');
                if (!overlay || !obj || !download || !btnBaixar) {
                    console.error('Elementos do modal não encontrados.');
                    return;
                }
                if (typeof setModalPdfTitulo === 'function') setModalPdfTitulo('Visualizar Orçamento');
                if (overlay.parentElement !== document.body) {
                    document.body.appendChild(overlay);
                }
                obj.data = '';
                download.href = '#';
                btnBaixar.href = '#';
                obj.data = url;
                download.href = url;
                btnBaixar.href = url;
                overlay.classList.add('active');
            } catch (err) {
                console.error('Erro ao visualizar orçamento:', err);
                alert('Erro ao carregar o orçamento. Verifique os logs do console.');
            }
        }
        
        // Visualizar contrato usando o mesmo modal (aponta para controller com tipo=contrato)
        async function visualizarContrato(id) {
            try {
                const url = `${window.location.origin}/controllers/LocacaoControllerOrcamento.php?orcamento&id=${encodeURIComponent(id)}&tipo=contrato&t=${Date.now()}`;
                const overlay = document.getElementById('modalOrcamentoOverlay');
                const obj = document.getElementById('objOrcamentoPdf');
                const download = document.getElementById('downloadOrcamentoPdf');
                const btnBaixar = document.getElementById('btnBaixarPdf');
                if (!overlay || !obj || !download || !btnBaixar) return console.error('Elementos do modal não encontrados.');
                if (typeof setModalPdfTitulo === 'function') setModalPdfTitulo('Visualizar Contrato');
                if (overlay.parentElement !== document.body) document.body.appendChild(overlay);
                obj.data = url;
                download.href = url;
                btnBaixar.href = url;
                overlay.classList.add('active');
            } catch (err) {
                console.error('Erro ao visualizar contrato:', err);
                alert('Erro ao carregar o contrato. Verifique os logs do console.');
            }
        }

        // Gera contrato a partir do formulário atual (sem salvar manualmente)
        async function gerarContrato() {
            // Recalcular e montar dados — reusa lógica de gerarOrcamento para salvar antes
            calcularValores();
            const dados = {
                cliente_id: document.getElementById('cliente_id').value,
                climatizador_id: document.getElementById('climatizador_id').value,
                data_inicio: document.getElementById('data_hora_inicio').value,
                data_fim: document.getElementById('data_hora_fim').value,
                quantidade_climatizadores: document.getElementById('quantidade_climatizadores').value,
                quantidade_dias: document.getElementById('quantidade_dias').value,
                valor_diaria: document.getElementById('valor_diaria').value,
                responsavel: document.getElementById('responsavel') ? document.getElementById('responsavel').value : '',
                valor_total: document.getElementById('valor_total').value,
                local_evento: document.getElementById('local_evento').value || '',
                despesas_acessorias: document.getElementById('despesas_acessorias') ? parseCurrency(document.getElementById('despesas_acessorias').value) : 0,
                observacoes: document.getElementById('observacoes') ? document.getElementById('observacoes').value : '',
                aplicar_desconto: document.getElementById('aplicar_desconto').checked ? 1 : 0,
                status: 'Reserva'
            };

            // incluir climatizadores JSON se houver
            try { const h = document.getElementById('climatizadores_json'); if (h && h.value) dados.climatizadores = JSON.parse(h.value); } catch(e) { dados.climatizadores = []; }
            try { if (dados.climatizadores && Array.isArray(dados.climatizadores) && dados.climatizadores.length>0) dados.quantidade_climatizadores = dados.climatizadores.reduce((s,i)=> s + (Number(i.qtd)||0), 0); } catch(e) {}

            // Salvar locação antes de gerar
            UI.showLoading();
            try {
                const locacaoId = document.getElementById('locacao_id').value;
                let res;
                if (locacaoId) {
                    dados.id = locacaoId;
                    res = await API.put(API_ENDPOINTS.locacoes, dados);
                } else {
                    res = await API.post(API_ENDPOINTS.locacoes, dados);
                }

                if (!res || !res.success) {
                    UI.showToast(res ? res.message : 'Erro ao salvar locação', 'error');
                    return;
                }

                const idToUse = locacaoId || (res.data && res.data.id) || res.id || res.dataId || null;
                const finalId = idToUse || res.data?.id || res.id || (locacaoId || null);

                // Chamar endpoint para gerar contrato em JSON e obter pdfUrl
                const orcEndpoint = API_ENDPOINTS.locacoes.replace('LocacaoController.php', 'LocacaoControllerOrcamento.php');
                const jsonRes = await API.get(orcEndpoint, { orcamento: 1, id: finalId, tipo: 'contrato', json: 1 });
                if (jsonRes && jsonRes.success && jsonRes.pdfUrl) {
                    window.open(jsonRes.pdfUrl, '_blank');
                    UI.showToast('Contrato gerado com sucesso.', 'success');
                    fecharModal();
                    carregar();
                } else {
                    UI.showToast('Erro ao gerar contrato PDF', 'error');
                }
            } catch (err) {
                console.error('Erro ao gerar contrato:', err);
                UI.showToast('Erro ao gerar contrato', 'error');
            } finally {
                UI.hideLoading();
            }
        }
        
        // Exibe um menu popover com duas opções: Enviar Orçamento e Enviar Contrato
        function showWhatsappOptions(event, id) {
            event.stopPropagation();
            // Remove qualquer popover existente
            const existing = document.getElementById('whatsapp-popover');
            if (existing) existing.remove();

            const pop = document.createElement('div');
            pop.id = 'whatsapp-popover';

            const buildOption = (type, label, sub, iconClass) => {
                const opt = document.createElement('div');
                opt.className = 'wp-option ' + type;
                const icon = document.createElement('div');
                icon.className = 'wp-icon';
                icon.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i>`;
                const labels = document.createElement('div');
                labels.style.display = 'flex';
                labels.style.flexDirection = 'column';
                const lab = document.createElement('div'); lab.className = 'wp-label'; lab.textContent = label;
                const subl = document.createElement('div'); subl.className = 'wp-sub'; subl.textContent = sub;
                labels.appendChild(lab); labels.appendChild(subl);
                opt.appendChild(icon); opt.appendChild(labels);
                opt.addEventListener('click', () => { enviarWhatsappTipo(id, type); pop.remove(); });
                return opt;
            };

            const closeBtn = document.createElement('div');
            closeBtn.className = 'wp-close';
            closeBtn.innerHTML = '<i class="fas fa-times"></i>';
            closeBtn.addEventListener('click', (e) => { e.stopPropagation(); pop.remove(); });
            pop.appendChild(closeBtn);
            const orcOpt = buildOption('orc', 'Enviar Orçamento', 'Gera o PDF de orçamento e abre no WhatsApp', 'fas fa-file-invoice');
            const contrOpt = buildOption('contrato', 'Enviar Contrato', 'Gera o PDF de contrato e abre no WhatsApp', 'fas fa-file-contract');
            pop.appendChild(orcOpt);
            pop.appendChild(contrOpt);

            document.body.appendChild(pop);
            // posiciona próximo ao botão clicado
            const rect = event.currentTarget.getBoundingClientRect();
            const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
            if (viewportWidth <= 640) {
                // Bottom-sheet full width on small screens
                pop.style.position = 'fixed';
                pop.style.left = '0px';
                pop.style.right = '0px';
                pop.style.bottom = '0px';
                pop.style.top = 'auto';
                pop.classList.remove('popover-up','popover-down');
                pop.classList.add('popover-down');
            } else {
                // Temporariamente mostra invisível para medir tamanho
                pop.style.visibility = 'hidden';
                pop.classList.remove('popover-up','popover-down');
                // força render
                void pop.offsetHeight;
                const popHeight = pop.offsetHeight || 120;
                const popWidth = pop.offsetWidth || 220;
                const spaceAbove = rect.top; // pixels above the button
                const spaceBelow = (window.innerHeight || document.documentElement.clientHeight) - rect.bottom;
                let left = Math.max(8, rect.left + window.scrollX - 12);
                let calcTop;
                // preferir abrir para cima
                if (spaceAbove >= popHeight + 12) {
                    calcTop = rect.top + window.scrollY - popHeight - 8;
                    pop.style.top = calcTop + 'px';
                    pop.classList.add('popover-up');
                } else {
                    calcTop = rect.bottom + window.scrollY + 8;
                    pop.style.top = calcTop + 'px';
                    pop.classList.add('popover-down');
                }
                // ajustar para não vazar horizontalmente
                if (left + popWidth > window.innerWidth - 8) {
                    left = Math.max(8, window.innerWidth - popWidth - 8);
                }
                pop.style.left = left + 'px';
                pop.style.visibility = 'visible';
                pop.style.minWidth = popWidth + 'px';
            }

            // animar mostrando
            requestAnimationFrame(() => pop.classList.add('show'));
            // remove ao clicar fora
            const onDocClick = (ev) => { if (!pop.contains(ev.target) && ev.target !== event.currentTarget) { pop.remove(); document.removeEventListener('click', onDocClick); document.removeEventListener('keydown', onEsc); } };
            const onEsc = (ev) => { if (ev.key === 'Escape') { pop.remove(); document.removeEventListener('click', onDocClick); document.removeEventListener('keydown', onEsc); } };
            setTimeout(() => document.addEventListener('click', onDocClick), 50);
            document.addEventListener('keydown', onEsc);
        }

        // Envia via WhatsApp; type = 'orcamento' ou 'contrato'
        function enviarWhatsappTipo(id, type) {
            UI.showLoading();
            let orcamentoEndpoint = API_ENDPOINTS.locacoes;
            try { orcamentoEndpoint = orcamentoEndpoint.replace('LocacaoController.php', 'LocacaoControllerOrcamento.php'); } catch (e) { console.warn('Não foi possível derivar endpoint de orçamento, usando API_BASE diretamente.'); }
            // solicita o PDF correspondente
            API.get(orcamentoEndpoint, { orcamento: 1, id, tipo: (type === 'contrato' ? 'contrato' : 'orcamento'), json: 1 }).then(res => {
                if (res && res.success && res.pdfUrl && res.clienteWhatsapp) {
                    let numero = res.clienteWhatsapp.replace(/\D/g, '');
                    if (!numero) { UI.showToast('Telefone do cliente inválido para envio pelo WhatsApp.', 'error'); return; }
                    if (!numero.startsWith('55')) numero = '55' + numero;
                    const label = (type === 'contrato') ? 'contrato' : 'orçamento';
                    const texto = `Segue o ${label} da sua locação:\n${res.pdfUrl}`;
                    const encoded = encodeURIComponent(texto);
                    const mobileScheme = `whatsapp://send?phone=${numero}&text=${encoded}`;
                    const webUrl = `https://api.whatsapp.com/send?phone=${numero}&text=${encoded}`;
                    const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
                    try {
                        if (isMobile) { window.location.href = mobileScheme; setTimeout(() => window.open(webUrl, '_blank'), 700); }
                        else { window.open(webUrl, '_blank'); }
                    } catch (e) { console.error('Erro ao abrir WhatsApp link:', e); window.open(webUrl, '_blank'); }
                } else { console.error('Resposta inválida ao preparar envio WhatsApp:', res); UI.showToast('Erro ao preparar envio para WhatsApp', 'error'); }
            }).catch(err => { console.error('Erro na requisição para preparar WhatsApp:', err); UI.showToast('Erro ao preparar envio para WhatsApp', 'error'); })
            .finally(() => UI.hideLoading());
        }

        // Abrir modal de upload de contrato assinado
        function abrirModalUploadContrato(locacaoId) {
            const el = document.getElementById('upload-contrato-locacao-id');
            const fileEl = document.getElementById('upload-contrato-file');
            if (el) el.value = locacaoId;
            if (fileEl) fileEl.value = '';
            UI.openModal('modal-upload-contrato');
            carregarAnexosParaModal(locacaoId);
        }

        function fecharModalUploadContrato() {
            UI.closeModal('modal-upload-contrato');
        }

        // Relatório: abrir/fechar modal
        function abrirModalRelatorio() {
            try {
                const ini = document.getElementById('relatorio_inicio');
                const fim = document.getElementById('relatorio_fim');
                const today = new Date();
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                if (ini && !ini.value) ini.value = firstDay.toISOString().slice(0,10);
                if (fim && !fim.value) fim.value = today.toISOString().slice(0,10);
            } catch (e) {}
            UI.openModal('modal-relatorio');
        }
        function fecharModalRelatorio() { UI.closeModal('modal-relatorio'); }

        async function gerarRelatorio(e) {
            e.preventDefault();
            const inicio = document.getElementById('relatorio_inicio').value;
            const fim = document.getElementById('relatorio_fim').value;
            const status = document.getElementById('relatorio_status').value;
            const cliente = document.getElementById('relatorio_cliente').value;
            if (!inicio || !fim) { UI.showToast('Informe o período', 'warning'); return; }

            UI.showLoading();
            try {
                const base = `${window.location.origin}/controllers/RelatorioLocacoesController.php`;
                const params = new URLSearchParams({ relatorio: 1, inicio, fim, json: 1 });
                if (status) params.append('status', status);
                if (cliente) params.append('cliente_id', cliente);
                const url = `${base}?${params.toString()}&t=${Date.now()}`;

                const resp = await fetch(url, { credentials: 'same-origin' });
                const data = await resp.json().catch(() => null);
                if (!data || !data.success || !data.pdfUrl) { UI.showToast('Falha ao gerar relatório', 'error'); return; }

                // Abrir no mesmo visualizador
                const overlay = document.getElementById('modalOrcamentoOverlay');
                const obj = document.getElementById('objOrcamentoPdf');
                const download = document.getElementById('downloadOrcamentoPdf');
                const btnBaixar = document.getElementById('btnBaixarPdf');
                if (overlay && obj && download && btnBaixar) {
                    if (overlay.parentElement !== document.body) document.body.appendChild(overlay);
                    obj.data = data.pdfUrl;
                    download.href = data.pdfUrl;
                    btnBaixar.href = data.pdfUrl;
                    overlay.classList.add('active');
                } else {
                    // fallback: abrir em nova aba
                    window.open(data.pdfUrl, '_blank');
                }
                fecharModalRelatorio();
            } catch (err) {
                console.error('Erro ao gerar relatório:', err);
                UI.showToast('Erro ao gerar relatório', 'error');
            } finally {
                UI.hideLoading();
            }
        }

        async function uploadContrato(e) {
            e.preventDefault();
            const locacaoId = document.getElementById('upload-contrato-locacao-id').value;
            const fileInput = document.getElementById('upload-contrato-file');
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                UI.showToast('Selecione um arquivo para enviar', 'warning');
                return;
            }

            const file = fileInput.files[0];
            // Tamanho limite (10 MB)
            if (file.size > 10 * 1024 * 1024) {
                UI.showToast('Arquivo muito grande. Máx 10 MB.', 'error');
                return;
            }

            const form = new FormData();
            form.append('locacao_id', locacaoId);
            form.append('signed_contract', file);

            UI.showLoading();
            try {
                const resp = await fetch(`${API_BASE}UploadContratoController.php`, {
                    method: 'POST',
                    body: form,
                    credentials: 'same-origin'
                });

                const contentType = resp.headers.get('content-type') || '';
                if (contentType.indexOf('application/json') === -1) {
                    const txt = await resp.text();
                    console.error('Resposta inesperada:', txt);
                    UI.showToast('Erro ao enviar arquivo', 'error');
                    return;
                }

                const data = await resp.json();
                if (data.success) {
                    UI.showToast(data.message || 'Arquivo anexado com sucesso', 'success');
                    // atualizar lista de anexos no modal
                    carregarAnexosParaModal(locacaoId);
                } else {
                    UI.showToast(data.message || 'Falha ao anexar arquivo', 'error');
                }
            } catch (err) {
                console.error('Erro upload contrato:', err);
                UI.showToast('Erro ao enviar arquivo', 'error');
            } finally {
                UI.hideLoading();
            }
        }

        // Similar ao popover do WhatsApp: opções para Anexar ou Visualizar
        function showAttachOptions(event, id) {
            const existing = document.getElementById('attach-popover');
            if (existing) existing.remove();

            const pop = document.createElement('div');
            pop.id = 'attach-popover';
            pop.className = 'wp-popover';

            // buildOption agora aceita um argumento opcional `typeClass` para aplicar
            // as mesmas classes usadas pelo popover do WhatsApp (ex.: 'orc' ou 'contrato')
            // isso faz com que os ícones recebam o fundo colorido definido no CSS.
            const buildOption = (label, sub, iconClass, onClick, typeClass) => {
                const opt = document.createElement('div');
                // aplicar a classe de estilo (ex.: 'orc' ou 'contrato') quando fornecida
                opt.className = 'wp-option ' + (typeClass ? typeClass : 'attach');
                const icon = document.createElement('div'); icon.className = 'wp-icon'; icon.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i>`;
                const labels = document.createElement('div'); labels.style.display = 'flex'; labels.style.flexDirection = 'column';
                const lab = document.createElement('div'); lab.className = 'wp-label'; lab.textContent = label;
                const subl = document.createElement('div'); subl.className = 'wp-sub'; subl.textContent = sub;
                labels.appendChild(lab); labels.appendChild(subl);
                opt.appendChild(icon); opt.appendChild(labels);
                opt.addEventListener('click', () => { onClick(); pop.remove(); });
                return opt;
            };

            const closeBtn = document.createElement('div'); closeBtn.className = 'wp-close'; closeBtn.innerHTML = '<i class="fas fa-times"></i>'; closeBtn.addEventListener('click', () => pop.remove());
            pop.appendChild(closeBtn);

            // Usar 'contrato' (azul) para Anexar e 'orc' (verde) para Visualizar — combina com o estilo existente
            const anexar = buildOption('Anexar Contrato', 'Fazer upload do contrato assinado', 'fas fa-upload', () => abrirModalUploadContrato(id), 'contrato');
            const visualizar = buildOption('Visualizar Anexos', 'Abrir lista de contratos anexados', 'fas fa-eye', () => { visualizarAnexos(id); }, 'orc');
            pop.appendChild(anexar); pop.appendChild(visualizar);

            document.body.appendChild(pop);
            // position near button
            const rect = event.currentTarget.getBoundingClientRect();
            pop.style.position = 'absolute';
            let left = Math.max(8, rect.left + window.scrollX - 12);
            const popWidth = 220;
            if (left + popWidth > window.innerWidth - 8) left = Math.max(8, window.innerWidth - popWidth - 8);
            const top = rect.top + window.scrollY - 8 -  (pop.offsetHeight || 80);
            pop.style.left = left + 'px';
            pop.style.top = (top > 0 ? top : rect.bottom + window.scrollY + 8) + 'px';
            requestAnimationFrame(() => pop.classList.add('show'));
            const onDocClick = (ev) => { if (!pop.contains(ev.target) && ev.target !== event.currentTarget) { pop.remove(); document.removeEventListener('click', onDocClick); } };
            setTimeout(() => document.addEventListener('click', onDocClick), 50);
        }

        // Carrega lista de anexos do index.json e renderiza no modal
        async function carregarAnexosParaModal(locacaoId) {
            const listEl = document.getElementById('upload-contrato-list');
            if (!listEl) return;
            listEl.innerHTML = '<em>Carregando anexos...</em>';
            try {
                const assetsBase = API_BASE.replace(/controllers\/?$/, '');
                const url = assetsBase + 'assets/uploads/contratos/index.json?t=' + Date.now();
                const resp = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
                if (!resp.ok) { listEl.innerHTML = '<div class="text-secondary">Nenhum anexo encontrado.</div>'; return; }
                const idx = await resp.json();
                const arr = idx && idx[locacaoId] ? idx[locacaoId] : [];
                if (!arr || arr.length === 0) { listEl.innerHTML = '<div class="text-secondary">Nenhum anexo encontrado para esta locação.</div>'; return; }
                const ul = document.createElement('ul'); ul.style.listStyle = 'none'; ul.style.padding = '0'; ul.style.margin = '0';
                arr.slice().reverse().forEach(a => {
                    const li = document.createElement('li'); li.style.marginBottom = '6px';
                    const link = document.createElement('a'); link.href = assetsBase + 'assets/uploads/contratos/' + encodeURIComponent(a.file); link.target = '_blank'; link.textContent = a.original_name + ' (' + (new Date(a.uploaded_at * 1000)).toLocaleString() + ')';
                    li.appendChild(link);
                    ul.appendChild(li);
                });
                listEl.innerHTML = '';
                listEl.appendChild(ul);
            } catch (err) {
                console.error('Erro ao carregar index de anexos:', err);
                listEl.innerHTML = '<div class="text-secondary">Erro ao carregar anexos.</div>';
            }
        }

        // Visualizar anexos usando o mesmo modal do orçamento/contrato
        async function visualizarAnexos(locacaoId) {
            UI.showLoading();
            try {
                const assetsBase = API_BASE.replace(/controllers\/?$/, '');
                const url = assetsBase + 'assets/uploads/contratos/index.json?t=' + Date.now();
                const resp = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
                if (!resp.ok) { UI.showToast('Nenhum anexo encontrado.', 'warning'); return; }
                const idx = await resp.json();
                const arr = idx && idx[locacaoId] ? idx[locacaoId] : [];
                if (!arr || arr.length === 0) { UI.showToast('Nenhum anexo encontrado para esta locação.', 'warning'); return; }
                // pegar o último anexo (mais recente)
                const a = arr[arr.length - 1];
                const fileUrl = assetsBase + 'assets/uploads/contratos/' + encodeURIComponent(a.file);

                const overlay = document.getElementById('modalOrcamentoOverlay');
                const obj = document.getElementById('objOrcamentoPdf');
                const download = document.getElementById('downloadOrcamentoPdf');
                const btnBaixar = document.getElementById('btnBaixarPdf');
                if (!overlay || !obj || !download || !btnBaixar) return UI.showToast('Visualizador não encontrado.', 'error');

                if (overlay.parentElement !== document.body) document.body.appendChild(overlay);
                obj.data = fileUrl;
                download.href = fileUrl;
                btnBaixar.href = fileUrl;
                overlay.classList.add('active');
            } catch (err) {
                console.error('Erro ao visualizar anexos:', err);
                UI.showToast('Erro ao carregar anexos.', 'error');
            } finally {
                UI.hideLoading();
            }
        }
        
        // Mantemos os atributos originais do formulário — não alteramos campos globais.
        
        // Impedir que a tecla Enter salve o formulário
        document.getElementById('form').addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const formElements = Array.from(this.elements);
                const currentIndex = formElements.indexOf(document.activeElement);
                if (currentIndex >= 0 && currentIndex < formElements.length - 1) {
                    formElements[currentIndex + 1].focus();
                }
            }
        });
        
        // Função para abrir o modal de edição (preenche somente campos existentes no formulário atual)
        async function abrirModalEdicao(locacaoId) {
            dbgLog('Abrindo modal de edição para locação ID:', locacaoId);
            const locacao = locacoes.find(l => l.id === locacaoId);
            if (!locacao) {
                console.error('Locação não encontrada para o ID:', locacaoId);
                return;
            }

            const mapping = {
                cliente_id: 'cliente_id',
                climatizador_id: 'climatizador_id',
                data_inicio: 'data_hora_inicio',
                data_fim: 'data_hora_fim',
                quantidade_climatizadores: 'quantidade_climatizadores',
                quantidade_dias: 'quantidade_dias',
                valor_diaria: 'valor_diaria',
                valor_total: 'valor_total',
                observacoes: 'observacoes',
                local_evento: 'local_evento', // Adicionado para preencher o campo Local do Evento
                responsavel: 'responsavel' // Preencher campo Responsável ao editar
            };

            Object.keys(mapping).forEach(src => {
                const targetId = mapping[src];
                const elemento = document.getElementById(targetId);
                if (elemento && locacao[src] !== undefined) {
                    elemento.value = locacao[src];
                    // Se estivermos preenchendo o campo 'valor_diaria' por edição,
                    // marcar que o usuário "editou" este campo para evitar que
                    // a função atualizarValorDiaria() sobrescreva com o padrão do climatizador.
                    if (src === 'valor_diaria' && elemento.dataset) {
                        elemento.dataset.userEdited = '1';
                    }
                }
            });

            // Preencher despesas_acessorias de forma formatada, se presente
            try {
                const despEl = document.getElementById('despesas_acessorias');
                if (despEl) {
                    const rawDesp = locacao.despesas_acessorias !== undefined ? locacao.despesas_acessorias : (locacao.despesas ? locacao.despesas : 0);
                    despEl.value = formatCurrencyBR(parseCurrency(rawDesp), false);
                }
            } catch (e) { dbgWarn('Erro ao preencher despesas_acessorias no modal:', e); }

            // Preencher seleção do tipo de despesas acessórias (se existir)
            try {
                const tipo = locacao.despesas_acessorias_tipo || locacao.despesasTipo || locacao.despesas_label || '';
                if (tipo) {
                    const sel = document.querySelector('input[name="despesas_acessorias_tipo"][value="' + tipo + '"]');
                    if (sel) sel.checked = true;
                }
            } catch (e) { dbgWarn('Erro ao preencher tipo de despesas no modal:', e); }

            // Preencher locacao_id oculto e ajustar título
            const locIdEl = document.getElementById('locacao_id');
            if (locIdEl) locIdEl.value = locacao.id;
            const modalTitle = document.querySelector('.modal-title');
            if (modalTitle) modalTitle.textContent = 'Editar Locação';

            // Garantir que select de climatizadores seja populado (pode estar vazio se nunca abrimos modal de criar)
            const selectClim = document.getElementById('climatizador_id');
            try {
                // Se não temos a lista local de climatizadores, buscar do backend (sem filtro de disponiveis)
                if ((!climatizadores || climatizadores.length === 0) && typeof API !== 'undefined') {
                    dbgLog('Buscar climatizadores para popular select (edição)');
                    try {
                        const resAll = await API.get(API_ENDPOINTS.climatizadores);
                        if (resAll && resAll.success && Array.isArray(resAll.data)) {
                            climatizadores = resAll.data;
                        }
                    } catch (e) {
                        dbgWarn('Erro ao buscar climatizadores no editar:', e);
                    }
                }

                // Popular os selects visíveis/hidden usando a função existente
                try { populateClimatizadorSelects(); } catch (e) { dbgWarn('populateClimatizadorSelects falhou:', e); }

                if (selectClim) {
                    // se a opção não existir, adiciona temporariamente mantendo o formato antigo (modelo + capacidade quando disponível)
                    let opt = Array.from(selectClim.options).find(o => o.value == locacao.climatizador_id);
                    if (!opt && locacao.climatizador_id) {
                        opt = document.createElement('option');
                        opt.value = locacao.climatizador_id;
                        const modeloTexto = (locacao.climatizador_modelo ? locacao.climatizador_modelo : ('Climatizador ' + locacao.climatizador_id));
                        const capacidadeTexto = locacao.climatizador_capacidade ? (' ' + locacao.climatizador_capacidade) : '';
                        opt.textContent = modeloTexto + capacidadeTexto;
                        selectClim.appendChild(opt);
                    }
                    if (locacao.climatizador_id) selectClim.value = locacao.climatizador_id;
                }
            } catch (e) {
                dbgWarn('Erro ao garantir selectClim preenchido:', e);
            }

            // Se a locação trouxer um array 'climatizadores' (novo formato), popular o componente
            try {
                const listField = locacao.climatizadores || locacao.climatizadores_json || null;
                if (listField && Array.isArray(listField) && listField.length > 0) {
                    // Priorizar 'descricao' (se existir), depois 'modelo' e 'nome' para manter o texto exibido como antes
                    _modalClimatizadores = listField.map(it => ({ id: it.id, modelo: (it.descricao || it.modelo || it.nome || it.codigo || ''), qtd: Number(it.qtd || it.quantidade || 1), valor_diaria: Number(it.valor_diaria || it.vd || 0) }));
                    // garantir que selects já estejam populados antes de renderizar
                    try { populateClimatizadorSelects(); } catch(e) {}
                    renderClimatizadoresList();
                } else {
                    _modalClimatizadores = [];
                    try { document.getElementById('climatizadores_json').value = ''; } catch(e) {}
                }
            } catch(e) { dbgWarn('Erro ao popular climatizadores do registro:', e); }

            // --- Tratamento do checkbox e do container de desconto ---
            // Função utilitária para interpretar valores boolean-like que a API pode retornar
            function parseBooleanLike(v) {
                if (v === true || v === 1) return true;
                if (v === false || v === 0) return false;
                if (typeof v === 'string') {
                    const s = v.trim().toLowerCase();
                    if (s === '1' || s === 'true' || s === 'sim' || s === 'yes') return true;
                    if (s === '0' || s === 'false' || s === 'nao' || s === 'não' || s === 'no') return false;
                }
                return false;
            }

            const aplicarEl = document.getElementById('aplicar_desconto');

            // Determinar valor salvo de desconto olhando para vários possíveis nomes de campo
            const rawAplicar = (locacao.aplicar_desconto !== undefined) ? locacao.aplicar_desconto :
                (locacao.aplicarDesconto !== undefined ? locacao.aplicarDesconto :
                (locacao.desconto_aplicado !== undefined ? locacao.desconto_aplicado : undefined));

            // Também olhar para possíveis campos de valor de desconto — se houver desconto > 0, entendemos que foi aplicado
            const rawDescontoCandidate = (locacao.desconto !== undefined) ? locacao.desconto :
                (locacao.valor_desconto !== undefined ? locacao.valor_desconto :
                (locacao.desconto_percentual !== undefined ? locacao.desconto_percentual :
                (locacao.percentual_desconto !== undefined ? locacao.percentual_desconto : undefined)));
            let rawDescontoNum = undefined;
            if (rawDescontoCandidate !== undefined && rawDescontoCandidate !== null && rawDescontoCandidate !== '') {
                const tmp = (typeof rawDescontoCandidate === 'string') ? rawDescontoCandidate.replace(',', '.') : rawDescontoCandidate;
                const parsed = parseFloat(tmp);
                if (!isNaN(parsed)) rawDescontoNum = parsed;
            }

            // Aplicado é true se campo explícito indicar true OR se existir um desconto numérico > 0
            const aplicadoFromField = parseBooleanLike(rawAplicar);
            const aplicado = aplicadoFromField || (rawDescontoNum !== undefined && rawDescontoNum > 0);
            dbgLog('Detecção aplicar_desconto - rawField:', rawAplicar, '->', aplicadoFromField, 'rawDescontoCandidate:', rawDescontoCandidate, '->', rawDescontoNum, '=> aplicado(final):', aplicado, 'locacao:', locacao);

            if (aplicarEl) {
                aplicarEl.checked = aplicado;
            }

            // Nota: campo de desconto removido do formulário — o checkbox controla aplicação do desconto máximo do climatizador.

            // garantir que label/max do desconto esteja atualizado para o climatizador selecionado
            try { atualizarDescontoMaximo(); } catch (e) { /* ignore se função não disponível */ }

            // Preencher campos de data/hora respeitando Flatpickr (se inicializado)
            // input[type=datetime-local] espera valor no formato "YYYY-MM-DDTHH:MM"
            function toInputDatetimeValue(isoOrDateTime) {
                // aceita formatos: 'YYYY-MM-DD' ou 'YYYY-MM-DDTHH:MM:SS' ou 'YYYY-MM-DDTHH:MM'
                if (!isoOrDateTime) return '';
                const hasT = isoOrDateTime.indexOf('T') !== -1;
                if (hasT) {
                    const parts = isoOrDateTime.split('T');
                    const date = parts[0];
                    const time = (parts[1] || '').slice(0,5);
                    return `${date}T${time || '00:00'}`;
                }
                // se só data (YYYY-MM-DD), assume horário 00:00
                return `${isoOrDateTime}T00:00`;
            }

            // Função utilitária para setar valor no input ou no flatpickr associado
            function setDateTimeOnField(selectorOrEl, valueIsoLike) {
                const el = (typeof selectorOrEl === 'string') ? document.querySelector(selectorOrEl) : selectorOrEl;
                if (!el) return false;
                const inputValue = toInputDatetimeValue(valueIsoLike);
                // Se flatpickr está presente e foi inicializado naquele input, use setDate
                if (el._flatpickr && typeof el._flatpickr.setDate === 'function') {
                    // setDate aceita Date ou string; o segundo argumento false evita formatação adicional
                    el._flatpickr.setDate(inputValue, false);
                    return true;
                }
                // fallback: atribuir diretamente ao value (input datetime-local)
                el.value = inputValue;
                return true;
            }

            if (locacao.data_inicio) {
                const ok = setDateTimeOnField('#data_hora_inicio', locacao.data_inicio);
                if (!ok) dbgWarn('Não foi possível atribuir data_hora_inicio');
            }

            if (locacao.data_fim) {
                const ok2 = setDateTimeOnField('#data_hora_fim', locacao.data_fim);
                if (!ok2) dbgWarn('Não foi possível atribuir data_hora_fim');
            }

            // Atualizar valor_da_diaria e cálculo
            atualizarValorDiaria();
            // Garantir listeners e comportamento do componente multi-climatizador também ao editar
            try { attachListenersModal(); } catch (e) { dbgWarn('attachListenersModal falhou ao abrir edição:', e); }
            UI.openModal('modal');
        }
        
        async function gerarOrcamento() {

            // Garantir que as datas estejam como opcionais também na UI
            ensureDateFieldsAreOptionalUi();
            // Recalcular e montar dados
            calcularValores();
            const dados = {
                cliente_id: document.getElementById('cliente_id').value,
                climatizador_id: document.getElementById('climatizador_id').value,
                data_inicio: document.getElementById('data_hora_inicio').value,
                data_fim: document.getElementById('data_hora_fim').value,
                quantidade_climatizadores: document.getElementById('quantidade_climatizadores').value,
                quantidade_dias: document.getElementById('quantidade_dias').value,
                valor_diaria: document.getElementById('valor_diaria').value,
                valor_total: document.getElementById('valor_total').value,
                local_evento: document.getElementById('local_evento').value || '',
                despesas_acessorias: document.getElementById('despesas_acessorias') ? parseCurrency(document.getElementById('despesas_acessorias').value) : 0,
                observacoes: document.getElementById('observacoes') ? document.getElementById('observacoes').value : '',
                aplicar_desconto: document.getElementById('aplicar_desconto').checked ? 1 : 0,
                status: 'Reserva'
            };

            // Salvar (criar ou atualizar) antes de gerar PDF para garantir que controller encontre os dados
            UI.showLoading();
            try {
                const locacaoId = document.getElementById('locacao_id').value;
                let res;
                if (locacaoId) {
                    dados.id = locacaoId;

            // Após (re)inicializar o Flatpickr, garantir que as datas permaneçam opcionais.
            ensureDateFieldsAreOptionalUi();
                    res = await API.put(API_ENDPOINTS.locacoes, dados);
                } else {
                    res = await API.post(API_ENDPOINTS.locacoes, dados);
                }

                if (!res || !res.success) {
                    UI.showToast(res ? res.message : 'Erro ao salvar locação', 'error');
                    return;
                }

                const idToUse = locacaoId || (res.data && res.data.id) || res.id || res.dataId || null;
                const finalId = idToUse || res.data?.id || res.id || (locacaoId || null);

                // Chamar endpoint de orçamento em modo JSON para obter pdfUrl
                const orcEndpoint = API_ENDPOINTS.locacoes.replace('LocacaoController.php', 'LocacaoControllerOrcamento.php');
                const jsonRes = await API.get(orcEndpoint, { orcamento: 1, id: finalId, json: 1 });
                if (jsonRes && jsonRes.success && jsonRes.pdfUrl) {
                    // Abrir PDF em nova aba
                    window.open(jsonRes.pdfUrl, '_blank');
                    UI.showToast('Orçamento gerado com sucesso.', 'success');
                    fecharModal();
                    carregar();
                } else {
                    UI.showToast('Erro ao gerar orçamento PDF', 'error');
                }
            } catch (err) {
                console.error('Erro ao gerar orçamento:', err);
                UI.showToast('Erro ao gerar orçamento', 'error');
            } finally {
                UI.hideLoading();
            }
        }
        
        carregar();
    </script>
<?php require_once __DIR__ . '/partials/modal_pdf.php'; ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            try {
                const overlay = document.getElementById('modalOrcamentoOverlay');
                if (overlay && overlay.parentElement !== document.body) {
                    document.body.appendChild(overlay);
                }
            } catch (e) {
                console.warn('Erro ao mover modal para body no DOMContentLoaded', e);
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Inicializar Flatpickr nos campos de data e hora com idioma pt-br
            // Usamos altInput para mostrar no padrão d/m/Y H:i e manter o valor real em ISO no input oculto
            flatpickr('#data_hora_inicio', {
                enableTime: true,
                altInput: true,
                altFormat: 'd/m/Y H:i',
                dateFormat: 'Y-m-d H:i',
                time_24hr: true,
                locale: 'pt',
                onChange: function() {
                    if (typeof calcularValores === 'function') calcularValores();
                }
            });

            flatpickr('#data_hora_fim', {
                enableTime: true,
                altInput: true,
                altFormat: 'd/m/Y H:i',
                dateFormat: 'Y-m-d H:i',
                time_24hr: true,
                locale: 'pt',
                onChange: function() {
                    if (typeof calcularValores === 'function') calcularValores();
                }
            });
        });
    </script>
    <!-- Função atualizarDescontoMaximo removida; campo de desconto não existe mais no formulário. -->
</body>
</html>
