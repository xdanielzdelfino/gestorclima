<?php
/**
 * Página de Manutenções (placeholder)
 *
 * Exibe uma mensagem simples indicando que a funcionalidade está em desenvolvimento.
 *
 * @package GestorClima
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
    <title>Manutenções - Em Desenvolvimento</title>
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dev-page { display:flex; align-items:center; justify-content:center; min-height:60vh; flex-direction:column; gap:12px; }
        .dev-card { background:#fff; padding:28px; border-radius:12px; box-shadow:0 8px 30px rgba(2,6,23,0.08); max-width:720px; width:92%; text-align:center; }
        .dev-title { font-size:22px; font-weight:700; color:#052a48; }
        .dev-sub { color:#5f7385; }
        .btn-return { margin-top:12px; }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <?php require_once __DIR__ . '/../includes/header.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Gerenciar Manutenções</h1>
                <p class="page-subtitle">Área destinada ao controle de manutenções dos climatizadores</p>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="dev-page">
                        <div class="dev-card">
                            <div class="dev-title"><i class="fas fa-tools"></i> Em desenvolvimento</div>
                            <div class="dev-sub">Estamos trabalhando nesta funcionalidade. Voltaremos em breve com a tela de gerenciamento de manutenções.</div>
                            <div class="btn-return">
                                <a class="btn btn-outline" href="climatizadores.php"><i class="fas fa-arrow-left"></i> Voltar para Climatizadores</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/auth.js"></script>
</body>
</html>
<?php
/**
 * Página de Manutenções
 * 
 * Gerenciamento de manutenções dos climatizadores
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
    <title>Manutenções - Gestor Clima</title>
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-wrapper">
        <!-- SIDEBAR -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- HEADER -->
        <?php require_once __DIR__ . '/../includes/header.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Manutenções</h1>
                <p class="page-subtitle">Gerencie as manutenções dos climatizadores</p>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="abrirModal()"><i class="fas fa-plus"></i> Nova Manutenção</button>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Climatizador</th>
                                    <th>Descrição</th>
                                    <th>Data</th>
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
                <h3 class="modal-title" id="modal-titulo">Nova Manutenção</h3>
                <button class="modal-close" onclick="fecharModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="form" onsubmit="salvar(event)">
                <div class="modal-body">
                    <input type="hidden" id="id">
                    <div class="form-group">
                        <label class="form-label required">Climatizador</label>
                        <select class="form-control form-select" id="climatizador" required>
                            <!-- Opções serão carregadas dinamicamente -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Descrição</label>
                        <textarea class="form-control" id="descricao" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Data</label>
                        <input type="date" class="form-control" id="data" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Status</label>
                        <select class="form-control form-select" id="status" required>
                            <option value="Pendente">Pendente</option>
                            <option value="Concluída">Concluída</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/auth.js"></script>
    <script>
        let dados = [];

        async function carregar() {
            UI.showLoading();
            try {
                const res = await API.get(API_ENDPOINTS.manutencoes);
                if (res.success) {
                    dados = res.data || [];
                    renderizar(dados);
                }
            } finally {
                UI.hideLoading();
            }
        }

        function renderizar(data) {
            const tbody = document.getElementById('tbody');
            tbody.innerHTML = '';
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state"><div class="empty-state-icon">🔧</div><div class="empty-state-title">Nenhuma manutenção encontrada</div></td></tr>';
                return;
            }
            data.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.id}</td>
                    <td>${item.climatizador}</td>
                    <td>${item.descricao}</td>
                    <td>${item.data}</td>
                    <td>${item.status}</td>
                    <td>
                        <div class="table-actions">
                            <button class="btn btn-sm btn-primary" onclick="editar(${item.id})"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="excluir(${item.id})"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function abrirModal() {
            document.getElementById('modal-titulo').textContent = 'Nova Manutenção';
            document.getElementById('form').reset();
            document.getElementById('id').value = '';
            UI.openModal('modal');
        }

        function fecharModal() {
            UI.closeModal('modal');
        }

        async function editar(id) {
            const item = dados.find(m => m.id == id);
            if (!item) return;
            document.getElementById('modal-titulo').textContent = 'Editar Manutenção';
            document.getElementById('id').value = item.id;
            document.getElementById('climatizador').value = item.climatizador;
            document.getElementById('descricao').value = item.descricao;
            document.getElementById('data').value = item.data;
            document.getElementById('status').value = item.status;
            UI.openModal('modal');
        }

        async function salvar(e) {
            e.preventDefault();
            const id = document.getElementById('id').value;
            const dados = {
                climatizador: document.getElementById('climatizador').value,
                descricao: document.getElementById('descricao').value,
                data: document.getElementById('data').value,
                status: document.getElementById('status').value
            };
            UI.showLoading();
            try {
                const res = id ? await API.put(API_ENDPOINTS.manutencoes, {...dados, id}) : await API.post(API_ENDPOINTS.manutencoes, dados);
                if (res.success) {
                    UI.showToast(res.message, 'success');
                    fecharModal();
                    carregar();
                } else {
                    UI.showToast(res.message, 'error');
                }
            } finally {
                UI.hideLoading();
            }
        }

        async function excluir(id) {
            if (!await UI.confirm('Tem certeza?')) return;
            UI.showLoading();
            try {
                const res = await API.delete(API_ENDPOINTS.manutencoes, {id});
                if (res.success) {
                    UI.showToast(res.message, 'success');
                    carregar();
                } else {
                    UI.showToast(res.message, 'error');
                }
            } finally {
                UI.hideLoading();
            }
        }

        async function carregarClimatizadores() {
            const select = document.getElementById('climatizador');
            select.innerHTML = '<option value="">Carregando...</option>';
            try {
                const res = await API.get(API_ENDPOINTS.climatizadores);
                if (res.success) {
                    const climatizadores = res.data || [];
                    select.innerHTML = climatizadores.map(c => `<option value="${c.id}">${c.modelo} (${c.codigo})</option>`).join('');
                } else {
                    select.innerHTML = '<option value="">Erro ao carregar</option>';
                }
            } catch (error) {
                select.innerHTML = '<option value="">Erro ao carregar</option>';
            }
        }

        carregar();
        carregarClimatizadores();
    </script>
</body>
</html>