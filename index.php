<?php
/**
 * Dashboard Principal
 * 
 * Página inicial do sistema protegida por autenticação
 */

// Proteger página - requer login
require_once __DIR__ . '/middleware/auth.php';
requerAutenticacao();

$usuario = getUsuarioLogado();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Gestão de Locações de Climatizadores">
    <title>Dashboard - Gestor Clima</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    
    <!-- Ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-wrapper">
        <!-- SIDEBAR -->
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        
        <!-- HEADER -->
        <?php require_once __DIR__ . '/includes/header.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Visão geral do sistema de locações</p>
            </div>

            <!-- ESTATÍSTICAS -->
            <div class="stats-grid">
                <div class="card-stat card-stat-clickable" id="card-finalizadas" onclick="toggleStatDetails('finalizadas')">
                    <div class="card-stat-icon primary">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="card-stat-content">
                        <div class="card-stat-value" id="total-finalizadas">0</div>
                        <div class="card-stat-label">Total de Locações Finalizadas</div>
                    </div>
                    <div class="card-stat-chevron"><i class="fas fa-chevron-down chevron"></i></div>
                </div>

                <div class="card-stat card-stat-clickable" id="card-reservas" onclick="toggleStatDetails('reservas')">
                    <div class="card-stat-icon success">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="card-stat-content">
                        <div class="card-stat-value" id="total-reservas">0</div>
                        <div class="card-stat-label">Total de Reservas</div>
                    </div>
                    <div class="card-stat-chevron"><i class="fas fa-chevron-down chevron"></i></div>
                </div>

                <div class="card-stat card-stat-clickable" id="card-confirmadas" onclick="toggleStatDetails('confirmadas')">
                    <div class="card-stat-icon warning">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="card-stat-content">
                        <div class="card-stat-value" id="locacoes-confirmadas">0</div>
                        <div class="card-stat-label">Locações Confirmadas</div>
                    </div>
                    <div class="card-stat-chevron"><i class="fas fa-chevron-down chevron"></i></div>
                </div>

                <div class="card-stat card-stat-clickable" id="card-receita" onclick="toggleStatDetails('receita')">
                    <div class="card-stat-icon danger">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="card-stat-content">
                        <div>
                            <div class="card-stat-value" id="receita-recebidos">R$ 0,00</div>
                            <div class="card-stat-subvalue" id="receita-a-receber">A receber: R$ 0,00</div>
                        </div>
                        <div class="card-stat-label">Receita do Mês</div>
                    </div>
                    <div class="card-stat-chevron"><i class="fas fa-chevron-down chevron"></i></div>
                </div>
            </div>
            <div id="stats-details-anchor">
                <div id="stats-details" class="stats-details" style="margin-top:16px;">
                    <!-- detalhes expandidos serão inseridos dinamicamente -->
                </div>
            </div>

            <!-- GRID DE CONTEÚDO -->
            <div class="content-grid">
                <!-- CALENDÁRIO -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt"></i> Calendário
                        </h3>
                    </div>
                    <div class="card-body">
                        <div id="calendar" style="max-width: 600px; height: 400px; margin: 0 auto;"></div>
                    </div>
                </div>

                <!-- Locações Confirmadas removidas do dashboard (módulo centralizado em outra view) -->

                <!-- Climatizadores Disponíveis removidos do dashboard (visual centralizado em outra view) -->
            </div>
        </main>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/app.js"></script>
    <script src="assets/js/auth.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script>
        /* Estilos adicionais para destacar o dia inteiro com cores por status */
        (function() {
            const style = document.createElement('style');
            style.innerHTML = `
                .fc-daygrid-day-frame.fc-day-status-ativa { background-color: #d1fae5; }
                .fc-daygrid-day-frame.fc-day-status-reserva { background-color: #fff7ed; }
                .fc-daygrid-day-frame.fc-day-status-finalizada { background-color: #f3f4f6; }
                /* Melhor contraste para o número do dia */
                .fc-daygrid-day-number { z-index: 2; position: relative; }
                /* Tooltip simples */
                .calendar-tooltip { position: fixed; background: rgba(0,0,0,0.88); color: #fff; padding: 8px 10px; border-radius: 6px; font-size: 13px; pointer-events: none; z-index: 9999; display: none; box-shadow: 0 6px 18px rgba(0,0,0,0.3); max-width: 320px; word-break: break-word; transition: opacity 0.12s ease; }
                .calendar-tooltip ul { margin: 0; padding: 0; list-style: none; }
                .calendar-tooltip li { margin: 0 0 6px 0; }
            `;
            document.head.appendChild(style);
        })();

        /**
         * Dashboard - Script específico
         */
        
        // Carrega estatísticas do dashboard
        async function carregarEstatisticas() {
            UI.showLoading();
            
            try {
                // Buscar contadores
                // Buscar contadores
                const [clientes, climatizadoresList, climatizadoresCount, locacoesStats, allLocacoesResp] = await Promise.all([
                    API.get(API_ENDPOINTS.clientes),
                    API.get(API_ENDPOINTS.climatizadores, { estatisticas: true }),
                    API.get(API_ENDPOINTS.climatizadores, { contar_disponiveis: true }),
                    API.get(API_ENDPOINTS.locacoes, { estatisticas: true }),
                    API.get(API_ENDPOINTS.locacoes) // obter todas para cálculos locais (contagens e recebimentos)
                ]);

                // Preparar dados locais
                const allLocacoes = (allLocacoesResp && allLocacoesResp.success && Array.isArray(allLocacoesResp.data)) ? allLocacoesResp.data : [];
                const now = new Date();

                // Total de locações finalizadas (todas)
                const totalFinalizadas = allLocacoes.filter(l => {
                    const status = (l.status || '').toString().trim();
                    if (status === 'Finalizada') return true;
                    // fallback: se data_fim já passou
                    if (l.data_fim) {
                        try { return (new Date(l.data_fim + 'T23:59:59') < now); } catch(e) { return false; }
                    }
                    return false;
                }).length;

                // Total de reservas (status Reserva OR data_inicio futura)
                const totalReservas = allLocacoes.filter(l => {
                    const status = (l.status || '').toString().trim();
                    if (status === 'Reserva') return true;
                    if (l.data_inicio) {
                        try { return (new Date(l.data_inicio + 'T00:00:00') > now); } catch(e) { return false; }
                    }
                    return false;
                }).length;

                // Total de locações confirmadas (atuais):
                // considera status persistido 'Confirmada'/'Ativa' OU
                // o status calculado por data (computed_status === 'Confirmada')
                const isConfirmadaAtual = (l) => {
                    const status = (l.status || '').toString().trim();
                    const computed = (l.computed_status || '').toString().trim();
                    return computed === 'Confirmada' || status === 'Confirmada' || status === 'Ativa';
                };

                const totalConfirmadas = allLocacoes.filter(isConfirmadaAtual).length;

                // Receita do mês: usar locacoesStats.receita_mes (valor_total para finalizadas no mês)
                const receitaMes = (locacoesStats && locacoesStats.success && locacoesStats.data && typeof locacoesStats.data.receita_mes !== 'undefined') ? Number(locacoesStats.data.receita_mes) : 0;

                // Calcular recebidos neste mês (somar valor_pago de locações finalizadas com data_fim neste mês/ano)
                let recebidos = 0;
                allLocacoes.forEach(l => {
                    try {
                        if (!l.data_fim) return;
                        const dt = new Date(l.data_fim + 'T00:00:00');
                        if (dt.getFullYear() === now.getFullYear() && dt.getMonth() === now.getMonth()) {
                            // considerar apenas finalizadas
                            const status = (l.status || '').toString().trim();
                            if (status === 'Finalizada' || dt < now) {
                                recebidos += Number(l.valor_pago || 0);
                            }
                        }
                    } catch (e) { /* ignore parsing errors */ }
                });
                const aReceber = Math.max(0, receitaMes - recebidos);

                // Atualizar cards de estatísticas
                document.getElementById('total-finalizadas').textContent = totalFinalizadas;
                document.getElementById('total-reservas').textContent = totalReservas;
                document.getElementById('locacoes-confirmadas').textContent = totalConfirmadas;
                document.getElementById('receita-recebidos').textContent = UI.formatMoney(recebidos || 0);
                document.getElementById('receita-a-receber').textContent = 'A receber: ' + UI.formatMoney(aReceber || 0);
            } catch (error) {
                console.error('Erro ao carregar estatísticas:', error);
            } finally {
                UI.hideLoading();
            }
        }
        
        // Carrega locações ativas
        async function carregarLocacoesAtivas() {
            try {
                const response = await API.get(API_ENDPOINTS.locacoes, { ativas: true });
                
                const tbody = document.querySelector('#locacoes-ativas-table tbody');
                tbody.innerHTML = '';
                
                if (response.success && response.data && response.data.length > 0) {
                    response.data.forEach(locacao => {
                        const tr = document.createElement('tr');
                        const statusLabel = mapStatusLabel(locacao.status);
                        tr.innerHTML = `
                            <td>${locacao.cliente_nome}</td>
                            <td>${locacao.climatizador_modelo}</td>
                            <td>${UI.formatDate(locacao.data_inicio)}</td>
                            <td>${UI.formatDate(locacao.data_fim)}</td>
                            <td>${UI.formatMoney(locacao.valor_total)}</td>
                            <td><span class="badge badge-success">${statusLabel}</span></td>
                        `;
                        tbody.appendChild(tr);
                    });
                    } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="empty-state">
                                <div class="empty-state-icon">📋</div>
                                
                                <div class="empty-state-text">Não há locações confirmadas no momento</div>
                            </td>
                        </tr>
                    `;
                }
            } catch (error) {
                console.error('Erro ao carregar locações ativas:', error);
            }
        }

        // Mapeia valores internos de status para rótulos amigáveis
        function mapStatusLabel(status) {
            if (!status) return '';
            try {
                const s = status.toString().trim();
                if (s.toLowerCase() === 'ativa' || s.toLowerCase() === 'confirmada') return 'Confirmado';
                return s;
            } catch (err) {
                return status;
            }
        }
        
        // Carrega climatizadores disponíveis
        async function carregarClimatizadoresDisponiveis() {
            try {
                const response = await API.get(API_ENDPOINTS.climatizadores, { disponiveis: true });
                
                const tbody = document.querySelector('#climatizadores-disponiveis-table tbody');
                tbody.innerHTML = '';
                
                if (response.success && response.data && response.data.length > 0) {
                    response.data.forEach(clim => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td><strong>${clim.codigo}</strong></td>
                            <td>${clim.modelo}</td>
                            <td>${clim.marca}</td>
                            <td>${clim.estoque}</td>
                            <td>${clim.disponivel}</td>
                            <td>${UI.formatMoney(clim.valor_diaria)}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                    } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="empty-state">
                                <div class="empty-state-icon">❄️</div>
                                <div class="empty-state-title">Nenhum climatizador disponível</div>
                                <div class="empty-state-text">Todos os climatizadores estão locados</div>
                            </td>
                        </tr>
                    `;
                }
            } catch (error) {
                console.error('Erro ao carregar climatizadores:', error);
            }
        }
        
        // Inicialização
        document.addEventListener('DOMContentLoaded', () => {
            carregarEstatisticas();
            
            // Atualizar a cada 30 segundos (não recarregar listas removidas do dashboard)
            setInterval(() => {
                carregarEstatisticas();
            }, 30000);
        });

        // Função utilitária global para escapar HTML (usada por painéis de detalhe)
        function escapeHtml(str) {
            if (!str && str !== 0) return '';
            return String(str).replace(/[&<>"']/g, function(m) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m]; });
        }

        // Helpers para normalizar e formatar datas de forma resiliente
        function __normalizeDate(val) {
            if (!val) return null;
            if (val instanceof Date) return val;
            try {
                const s = String(val).trim();
                if (!s) return null;
                // Adiciona 'T' quando vier no formato 'YYYY-MM-DD HH:MM:SS'
                const iso = s.includes('T') ? s : s.replace(' ', 'T');
                const d = new Date(iso);
                if (!isNaN(d)) return d;
                const d2 = new Date(s);
                if (!isNaN(d2)) return d2;
            } catch (_) { /* ignore */ }
            return null;
        }

        function formatDateSafe(val) {
            const d = __normalizeDate(val);
            if (!d) return '-';
            try {
                return new Intl.DateTimeFormat('pt-BR', { timeZone: 'America/Sao_Paulo' }).format(d);
            } catch (_) {
                return d.toLocaleDateString('pt-BR');
            }
        }

            // Toggle painel de detalhes dos cards (usa classe .open para animação)
        function toggleStatDetails(key) {
            const holder = document.getElementById('stats-details');
            if (!holder) return;
            const anchor = document.getElementById('stats-details-anchor');
            const isMobile = window.matchMedia('(max-width:600px)').matches;
            const activeCard = document.getElementById('card-' + key);

            // Fechar se já aberto com a mesma chave
            if (holder.classList.contains('open') && holder.dataset.open === key) {
                holder.classList.remove('open');
                holder.dataset.open = '';
                // remover estados visuais dos cards
                document.querySelectorAll('.card-stat').forEach(c=>c.classList.remove('active'));
                // aguardar transição antes de limpar o conteúdo e reposicionar
                setTimeout(() => {
                    holder.innerHTML = '';
                    if (isMobile && anchor && anchor !== holder.parentElement) {
                        anchor.appendChild(holder);
                        holder.classList.remove('anchored');
                    }
                }, 320);
                return;
            }

            // Abrir
            holder.dataset.open = key;
            // Se mobile, ancorar o painel logo abaixo do card ativo
            if (isMobile && activeCard) {
                activeCard.insertAdjacentElement('afterend', holder);
                holder.classList.add('anchored');
            } else {
                // garantir que o holder esteja no anchor padrão
                if (anchor && anchor !== holder.parentElement) {
                    anchor.appendChild(holder);
                    holder.classList.remove('anchored');
                }
            }
            holder.classList.add('open');
            // marcar card ativo (rotacionar chevron)
            document.querySelectorAll('.card-stat').forEach(c=>c.classList.remove('active'));
            if (activeCard) activeCard.classList.add('active');
            holder.innerHTML = '<div class="card"><div class="card-body">Carregando...</div></div>';
            // Preencher conteúdo conforme key
            (async function() {
                try {
                    const resp = await API.get(API_ENDPOINTS.locacoes);
                    const all = (resp && resp.success && Array.isArray(resp.data)) ? resp.data : [];
                    if (key === 'finalizadas') {
                        const items = all.filter(l => (l.status === 'Finalizada') || (l.data_fim && new Date(l.data_fim + 'T23:59:59') < new Date()));
                        holder.innerHTML = `<div class="card"><div class="card-body"><h4>Últimas locações finalizadas (${items.length})</h4><ul>` + items.slice(0,10).map(i => `<li>#${i.id} — ${escapeHtml(i.cliente_nome)} — ${formatDateSafe(i.data_fim)} — ${UI.formatMoney(i.valor_total||0)}</li>`).join('') + `</ul></div></div>`;
                    } else if (key === 'reservas') {
                        const items = all.filter(l => (l.status === 'Reserva') || (l.data_inicio && new Date(l.data_inicio + 'T00:00:00') > new Date()));
                        holder.innerHTML = `<div class="card"><div class="card-body"><h4>Próximas reservas (${items.length})</h4><ul>` + items.slice(0,10).map(i => `<li>#${i.id} — ${escapeHtml(i.cliente_nome)} — ${formatDateSafe(i.data_inicio)} — ${escapeHtml(i.climatizador_modelo||i.climatizador)}</li>`).join('') + `</ul></div></div>`;
                    } else if (key === 'confirmadas') {
                        const items = all.filter(l => {
                            const status = (l.status || '').toString().trim();
                            return status === 'Confirmada' || status === 'Ativa';
                        });
                        holder.innerHTML = `<div class="card"><div class="card-body"><h4>Locações Confirmadas (${items.length})</h4><ul>` + items.slice(0,10).map(i => `<li>#${i.id} — ${escapeHtml(i.cliente_nome)} — ${formatDateSafe(i.data_inicio)} → ${formatDateSafe(i.data_fim)}</li>`).join('') + `</ul></div></div>`;
                    } else if (key === 'receita') {
                        // mostrar resumo de recebido / a receber e os maiores itens do mês
                        const now = new Date();
                        const items = all.filter(l => {
                            if (!l.data_fim) return false;
                            const dt = new Date(l.data_fim + 'T00:00:00');
                            return dt.getFullYear() === now.getFullYear() && dt.getMonth() === now.getMonth();
                        });
                        const recebidos = items.reduce((s,i)=> s + Number(i.valor_pago||0), 0);
                        const total = items.reduce((s,i)=> s + Number(i.valor_total||0), 0);
                        const aReceber = Math.max(0, total - recebidos);
                        holder.innerHTML = `<div class="card"><div class="card-body"><h4>Receita - Resumo do Mês</h4><p><strong>Total (locações):</strong> ${UI.formatMoney(total)}</p><p><strong>Recebidos:</strong> ${UI.formatMoney(recebidos)}</p><p><strong>A receber:</strong> ${UI.formatMoney(aReceber)}</p><ul>` + items.sort((a,b)=> (b.valor_total||0)-(a.valor_total||0)).slice(0,10).map(i=>`<li>#${i.id} — ${escapeHtml(i.cliente_nome)} — ${UI.formatMoney(i.valor_total||0)}</li>`).join('') + `</ul></div></div>`;
                    }
                } catch (e) {
                    holder.innerHTML = `<div class="card"><div class="card-body">Erro ao carregar detalhes: ${e.message}</div></div>`;
                }
            })();
        }
        
        // Calendário
        document.addEventListener('DOMContentLoaded', function() {
            // Detectar dispositivo touch / mobile para evitar tooltips que quebram a UX em celulares
            // Declarar aqui antes da inicialização do calendário para evitar ReferenceError quando
            // callbacks (eventsSet) forem executados imediatamente.
            const isTouchDevice = (('ontouchstart' in window) || (navigator.maxTouchPoints && navigator.maxTouchPoints > 0) || (window.matchMedia && window.matchMedia('(pointer: coarse)').matches));
            // Tooltip element (só em desktop / devices não-touch)
            let tooltipEl = null;
            if (!isTouchDevice) {
                tooltipEl = document.createElement('div');
                tooltipEl.className = 'calendar-tooltip';
                document.body.appendChild(tooltipEl);
            }

            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt-br',
                height: 400,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth listMonth'
                },
                buttonText: {
                    today: 'Hoje',
                    month: 'Mês',
                    list: 'Lista'
                },
                events: async function(fetchInfo, successCallback, failureCallback) {
                    try {
                        const response = await API.get(API_ENDPOINTS.locacoes, { calendario: true });
                        if (response.success && response.data) {
                            // Guardar raw events para uso local (tooltips / dia)
                            window.__calendarRawEvents = response.data.map(evento => {
                                // Detectar se start/end possuem hora (YYYY-MM-DD HH:MM:SS)
                                const hasTime = (s) => s && s.match && /\d{2}:\d{2}:?\d{0,2}/.test(s);
                                const startRaw = evento.inicio || evento.start;
                                const endRaw = evento.fim || evento.end;
                                const allDay = !(hasTime(startRaw) || hasTime(endRaw));

                                return Object.assign({}, evento, {
                                    start: startRaw,
                                    end: endRaw || null,
                                    allDay: allDay
                                });
                            });
                            successCallback(window.__calendarRawEvents.map(evento => {
                                return {
                                    id: evento.id,
                                    title: evento.titulo || evento.title,
                                    start: evento.start,
                                    end: evento.end || null,
                                    backgroundColor: evento.cor || evento.color || undefined,
                                    borderColor: evento.cor || evento.color || undefined,
                                    allDay: evento.allDay
                                };
                            }));
                        } else {
                            failureCallback('Erro ao carregar eventos.');
                        }
                    } catch (error) {
                        console.error('Erro ao carregar eventos do calendário:', error);
                        failureCallback(error);
                    }
                }
                ,
                eventsSet: function(events) {
                    // Quando os eventos estão carregados/atualizados, colorir os dias
                    try {
                        updateDayCellStatuses(events || []);
                    } catch (err) {
                        console.error('Erro em eventsSet:', err);
                    }
                }
                ,
                eventClick: function(info) {
                    info.jsEvent && info.jsEvent.preventDefault();
                    const id = info.event.id;
                    if (id) {
                        abrirDetalhesLocacao(id);
                    }
                }
            });
            calendar.render();

            

            /**
             * Atualiza as células do dia com classes de status com base nos eventos
             */
            function updateDayCellStatuses(events) {
                // Preferir raw events retornados pela API (mantêm status e campos originais)
                const raw = window.__calendarRawEvents || events || [];
                // Normalizar eventos: garantir start/end Date
                const normalized = (raw || []).map(e => {
                    const s = e.start ? new Date(e.start) : (e.inicio ? new Date(e.inicio) : null);
                    const en = e.end ? new Date(e.end) : (e.fim ? new Date(e.fim) : null);
                    return Object.assign({}, e, { __start: s, __end: en });
                });

                // Iterar sobre cada célula visível
                const dayCells = document.querySelectorAll('.fc-daygrid-day');
                dayCells.forEach(cell => {
                    const date = cell.getAttribute('data-date');
                    if (!date) return;
                    // Remove classes
                    const frame = cell.querySelector('.fc-daygrid-day-frame');
                    if (!frame) return;
                    frame.classList.remove('fc-day-status-ativa', 'fc-day-status-reserva', 'fc-day-status-finalizada');

                    // Encontrar eventos que intersectam a data
                    const dStart = new Date(date + 'T00:00:00');
                    const dEnd = new Date(date + 'T23:59:59');
                    const eventsForDay = normalized.filter(ev => {
                        if (!ev.__start && !ev.__end) return false;
                        const evStart = ev.__start || ev.__end;
                        const evEnd = ev.__end || ev.__start;
                        if (!evStart || !evEnd) return false;
                        // Se end é igual a 00:00:00 por FullCalendar, considerar até o dia anterior
                        let endTime = evEnd;
                        // Comparar sobreposição
                        return (evStart <= dEnd && endTime >= dStart);
                    });

                    if (eventsForDay.length) {
                        // Priorizar Confirmada/Ativa > Reserva > Finalizada
                        let applied = false;
                        if (eventsForDay.some(e => {
                            const st = (e.extendedProps?.status || e.status || '').toLowerCase();
                            return st === 'confirmada' || st === 'ativa';
                        })) {
                            frame.classList.add('fc-day-status-ativa'); applied = true;
                        }
                        if (!applied && eventsForDay.some(e => (e.extendedProps && e.extendedProps.status && e.extendedProps.status.toLowerCase() === 'reserva') || (e.status && e.status.toLowerCase() === 'reserva'))) {
                            frame.classList.add('fc-day-status-reserva'); applied = true;
                        }
                        if (!applied && eventsForDay.some(e => (e.extendedProps && e.extendedProps.status && e.extendedProps.status.toLowerCase() === 'finalizada') || (e.status && e.status.toLowerCase() === 'finalizada'))) {
                            frame.classList.add('fc-day-status-finalizada'); applied = true;
                        }
                    }
                });

                // Attach hover/click handlers to day cells (for tooltips and click list)
                attachDayCellHandlers();
            }

            function attachDayCellHandlers() {
                const dayCells = document.querySelectorAll('.fc-daygrid-day');
                dayCells.forEach(cell => {
                    // Avoid duplicating handlers
                    if (cell._calendarHandlersAttached) return;
                    cell._calendarHandlersAttached = true;

                    // Tooltip behaviour: apenas em não-touch devices
                    if (!isTouchDevice) {
                        const TOOLTIP_DELAY = 0; // ms (0 = immediate)
                        cell.addEventListener('mouseenter', function(ev) {
                            const date = cell.getAttribute('data-date');
                            if (!date) return;
                            const events = getEventsForDate(date);
                            if (!events.length) return;
                            // schedule showing tooltip after a small delay
                            if (cell._tooltipTimer) clearTimeout(cell._tooltipTimer);
                            // store last mouse position and use it when showing tooltip to avoid large offset
                            const mousePos = cell._lastMouse || ev;
                            cell._tooltipTimer = setTimeout(() => {
                                const html = `<ul>` + events.map(e => `<li><strong>${escapeHtml(e.title || e.titulo || e.name || 'Locação')}</strong><br/><small>${formatEventRange(e)}</small></li>`).join('') + `</ul>`;
                                tooltipEl.innerHTML = html;
                                tooltipEl.style.opacity = '0';
                                tooltipEl.style.display = 'block';
                                // position near last known mouse, prefer above the cursor, smaller margin
                                const margin = 4;
                                // use client coordinates (viewport) since tooltip is fixed
                                const mx = (mousePos.clientX || ev.clientX);
                                const my = (mousePos.clientY || ev.clientY);
                                // compute top based on tooltip height after render
                                requestAnimationFrame(() => {
                                    const tRect = tooltipEl.getBoundingClientRect();
                                    let top;
                                    const above = my - tRect.height - margin;
                                    if (above > 8) {
                                        top = above;
                                    } else {
                                        top = my + margin;
                                    }
                                    // prefer to the right of cursor (small offset)
                                    let left = mx + 4;
                                    if (left + tRect.width > window.innerWidth - 8) {
                                        // fallback to left of cursor
                                        left = mx - tRect.width - 4;
                                    }
                                    if (left < 4) left = 4;
                                    tooltipEl.style.left = left + 'px';
                                    tooltipEl.style.top = top + 'px';
                                    tooltipEl.style.opacity = '1';
                                });
                            }, TOOLTIP_DELAY);
                        });
                        cell.addEventListener('mousemove', function(ev) {
                            // update last mouse pos and if tooltip visible, move it smoothly
                            cell._lastMouse = { clientX: ev.clientX, clientY: ev.clientY };
                            if (tooltipEl && tooltipEl.style.display === 'block') {
                                requestAnimationFrame(() => {
                                    const tRect = tooltipEl.getBoundingClientRect();
                                    const margin = 4;
                                    // place tooltip to the right of cursor when possible
                                    let left = ev.clientX + 4;
                                    if (left + tRect.width > window.innerWidth - 4) {
                                        left = ev.clientX - tRect.width - 4;
                                    }
                                    if (left < 4) left = 4;
                                    let preferTop = ev.clientY - tRect.height - margin;
                                    if (preferTop > 8) {
                                        tooltipEl.style.left = left + 'px';
                                        tooltipEl.style.top = preferTop + 'px';
                                    } else {
                                        tooltipEl.style.left = left + 'px';
                                        tooltipEl.style.top = ((ev.clientY) + margin) + 'px';
                                    }
                                });
                            }
                        });
                        cell.addEventListener('mouseleave', function() {
                            if (cell._tooltipTimer) { clearTimeout(cell._tooltipTimer); cell._tooltipTimer = null; }
                            if (tooltipEl) tooltipEl.style.display = 'none';
                        });
                    }
                    cell.addEventListener('click', function() {
                        const date = cell.getAttribute('data-date');
                        if (!date) return;
                        const events = getEventsForDate(date);
                        if (!events.length) return;
                        // Se só houver uma locação no dia, abrir diretamente o modal de detalhes
                        if (events.length === 1) {
                            const only = events[0];
                            // Se o objeto for raw com id, usar esse id
                            const id = only.id || only.ID || only.id_locacao || null;
                            if (id) {
                                abrirDetalhesLocacao(id);
                                return;
                            }
                        }
                        // Abrir modal com lista de locações do dia
                        criarModalDetalhes();
                        const overlay = document.getElementById('modal-locacao-overlay');
                        const modal = document.getElementById('modal-locacao-detalhes');
                        const body = document.getElementById('modal-locacao-body');
                        const tituloEl = document.getElementById('modal-locacao-titulo');
                        // Ativar overlay para centralizar modal
                        if (overlay) overlay.classList.add('active');
                        tituloEl.textContent = `Locações em ${date}`;
                        body.innerHTML = events.map(e => `
                            <div class="loc-item">
                                <p><strong>${escapeHtml(e.title || e.titulo || '')}</strong></p>
                                <p><small>${formatEventRange(e)}</small></p>
                                <p><a class="btn btn-secondary" href="#" data-id="${e.id}">Ver</a></p>
                            </div>
                        `).join('<hr/>');
                        // Delegation for 'Ver' buttons: abrir detalhes da locação
                        body.querySelectorAll('a[data-id]').forEach(a => {
                            a.addEventListener('click', function(ev) {
                                ev.preventDefault();
                                const id = this.getAttribute('data-id');
                                // Abrir detalhes (reaproveita o mesmo modal/overlay)
                                abrirDetalhesLocacao(id);
                            });
                        });
                    });
                });
            }

            function getEventsForDate(dateStr) {
                const all = window.__calendarRawEvents || [];
                const dStart = new Date(dateStr + 'T00:00:00');
                const dEnd = new Date(dateStr + 'T23:59:59');
                return all.filter(e => {
                    const s = e.start ? new Date(e.start) : null;
                    const en = e.end ? new Date(e.end) : null;
                    if (!s && !en) return false;
                    const start = s || en;
                    const end = en || s;
                    return (start <= dEnd && end >= dStart);
                });
            }

            function formatEventRange(e) {
                try {
                    const s = e.start ? (UI.formatDateTime ? UI.formatDateTime(e.start) : (new Date(e.start)).toLocaleString()) : '';
                    const en = e.end ? (UI.formatDateTime ? UI.formatDateTime(e.end) : (new Date(e.end)).toLocaleString()) : '';
                    return s && en ? `${s} — ${en}` : (s || en || 'Horário não informado');
                } catch (err) {
                    return '';
                }
            }

            // Helpers para links de telefone e maps
            function sanitizePhoneForWhatsApp(phone) {
                if (!phone) return null;
                let s = String(phone).replace(/\D/g, '');
                if (!s) return null;
                // Remover zeros à esquerda
                s = s.replace(/^0+/, '');
                // Se já contém código do país (55), manter; caso contrário prefixar 55
                if (s.startsWith('55')) return s;
                return '55' + s;
            }

            function formatPhoneDisplay(phone) {
                if (!phone) return '';
                const s = String(phone).replace(/\D/g, '');
                // Formatação simples: +CC (AA) NNNNN-NNNN ou (AA) NNNN-NNNN
                if (s.length > 11) {
                    // Possui código de país no começo
                    const ccLen = s.length - 11;
                    const cc = s.slice(0, ccLen);
                    const ddd = s.slice(ccLen, ccLen + 2);
                    const p1 = s.slice(ccLen + 2, ccLen + 7);
                    const p2 = s.slice(ccLen + 7);
                    return `+${cc} (${ddd}) ${p1}-${p2}`;
                }
                if (s.length === 11) {
                    const ddd = s.slice(0,2);
                    const p1 = s.slice(2,7);
                    const p2 = s.slice(7);
                    return `(${ddd}) ${p1}-${p2}`;
                }
                if (s.length === 10) {
                    const ddd = s.slice(0,2);
                    const p1 = s.slice(2,6);
                    const p2 = s.slice(6);
                    return `(${ddd}) ${p1}-${p2}`;
                }
                return phone;
            }

            function escapeHtml(str) {
                if (!str) return '';
                return str.replace(/[&<>\"']/g, function(m) { return {'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":"&#39;"}[m]; });
            }
            
            // Modal simples para detalhes de locação (usado pelo calendário)
            function criarModalDetalhes() {
                if (document.getElementById('modal-locacao-overlay')) return;
                const overlay = document.createElement('div');
                overlay.id = 'modal-locacao-overlay';
                overlay.className = 'modal-overlay';

                const modal = document.createElement('div');
                modal.id = 'modal-locacao-detalhes';
                modal.className = 'modal';
                modal.innerHTML = `
                    <div class="modal-header">
                        <h3 id="modal-locacao-titulo">Detalhes da Locação</h3>
                        <button class="modal-close" onclick="document.getElementById('modal-locacao-overlay').classList.remove('active')">✕</button>
                    </div>
                    <div class="modal-body">
                        <div id="modal-locacao-body">Carregando...</div>
                    </div>
                `;

                overlay.appendChild(modal);
                document.body.appendChild(overlay);
            }

            // Função para abrir modal com dados da locação (busca por id via API)
            async function abrirDetalhesLocacao(id) {
                criarModalDetalhes();
                const overlay = document.getElementById('modal-locacao-overlay');
                const modal = document.getElementById('modal-locacao-detalhes');
                const body = document.getElementById('modal-locacao-body');
                const tituloEl = document.getElementById('modal-locacao-titulo');
                body.innerHTML = 'Carregando...';
                tituloEl.textContent = 'Detalhes da Locação';
                
                if (overlay) overlay.classList.add('active');

                try {
                    const resp = await API.get(API_ENDPOINTS.locacoes, { id: id });
                    if (resp.success && resp.data) {
                        const l = resp.data;
                        tituloEl.textContent = `Locação #${l.id} — ${l.cliente_nome || ''}`;
                        const inicio = UI.formatDateTime ? UI.formatDateTime(l.data_inicio) : (l.data_inicio || '');
                        const fim = UI.formatDateTime ? UI.formatDateTime(l.data_fim) : (l.data_fim || '');
                        const climatizadorLabel = (l.climatizador_codigo ? (`${l.climatizador_codigo} — `) : '') + (l.climatizador_modelo || l.climatizador || '-');
                        const qty = (typeof l.quantidade_climatizadores !== 'undefined' && l.quantidade_climatizadores !== null) ? l.quantidade_climatizadores : (l.quantidade || 1);
                            const mapaHtml = l.local_evento ? `<a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(l.local_evento)}" target="_blank" rel="noopener noreferrer">${escapeHtml(l.local_evento)}</a>` : '-';
                            const phoneRaw = l.cliente_telefone || l.telefone || l.telefone_responsavel || l.cliente_telefone_principal || l.telefone_principal || l.telefone1 || l.telefone2 || null;
                            const phoneSan = sanitizePhoneForWhatsApp(phoneRaw);
                            const phoneDisplay = phoneRaw ? formatPhoneDisplay(phoneRaw) : '';
                            const phoneHtml = (phoneSan ? `<a href="https://wa.me/${phoneSan}" target="_blank" rel="noopener noreferrer">${escapeHtml(phoneDisplay || phoneRaw)}</a>` : (phoneRaw ? escapeHtml(phoneRaw) : '-'));

                            body.innerHTML = `
                                <p><strong>Cliente:</strong> ${l.cliente_nome || '-'}</p>
                                <p><strong>Local do Evento:</strong> ${mapaHtml}</p>
                                <p><strong>Climatizador:</strong> ${escapeHtml(climatizadorLabel)}</p>
                                <p><strong>Quantidade Alocada:</strong> ${qty}</p>
                                <p><strong>Telefone:</strong> ${phoneHtml}</p>
                                <p><strong>Início:</strong> ${inicio}</p>
                                <p><strong>Fim:</strong> ${fim}</p>
                                <p><strong>Valor Total:</strong> ${UI.formatMoney ? UI.formatMoney(l.valor_total || 0) : (l.valor_total || 0)}</p>
                                <p><strong>Status:</strong> ${mapStatusLabel(l.status) || ''}</p>
                                <p><strong>Observações:</strong> ${l.observacoes || '-'}</p>
                            `;
                        
                    } else {
                        body.innerHTML = '<p>Não foi possível carregar os detalhes.</p>';
                    }
                } catch (err) {
                    body.innerHTML = `<p>Erro ao carregar: ${err.message}</p>`;
                }
            }
        });
    </script>
</body>
</html>