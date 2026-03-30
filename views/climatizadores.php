<?php
/**
 * Página de Climatizadores
 * 
 * Gerenciamento de climatizadores do sistema
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
    <title>Climatizadores - Gestor Clima</title>
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Ajustar o campo de busca para evitar sobreposição da lupa e texto grande */
        .search-box {
            position: relative;
        }

        .search-box .form-control {
            padding-left: 2rem;
            font-size: 0.875rem;
        }

        .search-box .search-icon {
            position: absolute;
            left: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: #aaa;
        }

        /* slider styles moved to assets/css/components.css */
    </style>
</head>
<body>
    <div class="app-wrapper">
        <!-- SIDEBAR -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- HEADER -->
        <?php require_once __DIR__ . '/../includes/header.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Climatizadores</h1>
                <p class="page-subtitle">Gerencie os equipamentos</p>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="abrirModal()"><i class="fas fa-plus"></i> Novo Climatizador</button>
                    <a href="manutencoes.php" class="btn btn-secondary"><i class="fas fa-tools"></i> Gerenciar Manutenções</a>
                </div>
            </div>

            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="search-box">
                        <i class="search-icon fas fa-search"></i>
                        <input type="text" class="form-control" id="busca" placeholder="Buscar por código, modelo, marca...">
                    </div>
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
                                    <th>Código</th>
                                    <th>Modelo</th>
                                    <th>Marca</th>
                                    <th>Capacidade</th>
                                    <th>Tipo</th>
                                    <th>Diária</th>
                                    <th>Estoque</th>
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
                <h3 class="modal-title" id="modal-titulo">Novo Climatizador</h3>
                <button class="modal-close" onclick="fecharModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="form" onsubmit="salvar(event)">
                <div class="modal-body">
                    <input type="hidden" id="id">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required">Código</label>
                            <input type="text" class="form-control" id="codigo" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Modelo</label>
                            <input type="text" class="form-control" id="modelo" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required">Marca</label>
                            <input type="text" class="form-control" id="marca" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Capacidade</label>
                            <input type="text" class="form-control" id="capacidade" placeholder="Ex: 12.000 BTU">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tipo</label>
                            <select class="form-control form-select" id="tipo">
                                <option value="Portatil">Portátil</option>
                                <option value="Split">Split</option>
                                <option value="Janela">Janela</option>
                                <option value="Central">Central</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Valor Diária (R$)</label>
                            <input type="number" step="0.01" class="form-control" id="valor_diaria" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required">Estoque</label>
                            <input type="number" class="form-control" id="estoque" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Desconto Máximo (%)</label>
                        <input type="number" class="form-control" id="desconto_maximo" min="0" max="100" step="0.1" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Foto (opcional)</label>
                        <input type="file" class="form-control" id="foto" accept="image/*">
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
                const res = await API.get(API_ENDPOINTS.climatizadores);
                if (res.success) {
                    dados = res.data || [];
                    renderizar(dados);
                    document.getElementById('total').textContent = dados.length;
                }
            } finally {
                UI.hideLoading();
            }
        }
        
        function renderizar(data) {
            const tbody = document.getElementById('tbody');
            tbody.innerHTML = '';
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="empty-state"><div class="empty-state-icon">❄️</div><div class="empty-state-title">Nenhum climatizador encontrado</div></td></tr>';
                return;
            }
            data.forEach(item => {
                const statusClass = {
                    'Disponivel': 'success',
                    'Locado': 'warning',
                    'Manutencao': 'info',
                    'Inativo': 'secondary'
                }[item.status] || 'secondary';
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${item.codigo}</strong></td>
                    <td>${item.modelo}</td>
                    <td>${item.marca}</td>
                    <td>${item.capacidade}</td>
                    <td>${item.tipo}</td>
                    <td>${UI.formatMoney(item.valor_diaria)}</td>
                    <td>${item.estoque || 0}</td>
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
            document.getElementById('modal-titulo').textContent = 'Novo Climatizador';
            document.getElementById('form').reset();
            document.getElementById('id').value = '';
            UI.openModal('modal');
        }
        
        function fecharModal() {
            UI.closeModal('modal');
        }
        
        async function editar(id) {
            const item = dados.find(c => c.id == id);
            if (!item) return;
            document.getElementById('modal-titulo').textContent = 'Editar Climatizador';
            document.getElementById('id').value = item.id;
            document.getElementById('codigo').value = item.codigo;
            document.getElementById('modelo').value = item.modelo;
            document.getElementById('marca').value = item.marca;
            document.getElementById('capacidade').value = item.capacidade;
            document.getElementById('tipo').value = item.tipo;
            document.getElementById('valor_diaria').value = item.valor_diaria;
            document.getElementById('descricao').value = item.descricao || '';
            document.getElementById('estoque').value = item.estoque;
            document.getElementById('desconto_maximo').value = item.desconto_maximo;
            // limpar campo de foto ao editar (upload opcional separado)
            const fotoInput = document.getElementById('foto');
            if (fotoInput) fotoInput.value = '';
            UI.openModal('modal');
        }
        
        async function salvar(e) {
            e.preventDefault();
            const id = document.getElementById('id').value;
            const dados = {
                codigo: document.getElementById('codigo').value,
                modelo: document.getElementById('modelo').value,
                marca: document.getElementById('marca').value,
                capacidade: document.getElementById('capacidade').value,
                tipo: document.getElementById('tipo').value,
                valor_diaria: document.getElementById('valor_diaria').value,
                descricao: document.getElementById('descricao').value,
                estoque: document.getElementById('estoque').value,
                desconto_maximo: document.getElementById('desconto_maximo').value // Novo campo
            };
            UI.showLoading();
            try {
                const res = id ? await API.put(API_ENDPOINTS.climatizadores, {...dados, id}) : await API.post(API_ENDPOINTS.climatizadores, dados);
                if (res.success) {
                    // obter id retornado (novo ou existente)
                    const newId = id || (res.data && res.data.id) || null;
                    // se tiver arquivo selecionado, enviar para o endpoint de upload
                    const fotoEl = document.getElementById('foto');
                    if (fotoEl && fotoEl.files && fotoEl.files.length > 0 && newId) {
                        try {
                            const file = fotoEl.files[0];
                            const uploadUrl = API_BASE + 'ClimatizadorUpload.php';
                            const form = new FormData();
                            form.append('foto', file);
                            form.append('id', newId);
                            const resp = await fetch(uploadUrl, { method: 'POST', body: form, credentials: 'same-origin' });
                            const result = await resp.json();
                            if (result.success) {
                                UI.showToast('Foto enviada com sucesso', 'success');
                            } else {
                                UI.showToast('Upload falhou: ' + (result.message || 'Erro'), 'warning');
                            }
                        } catch (err) {
                            UI.showToast('Erro no upload da foto: ' + err.message, 'warning');
                        }
                    }

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
                const res = await API.delete(API_ENDPOINTS.climatizadores, {id});
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
        
        document.getElementById('busca').addEventListener('input', (e) => {
            const termo = e.target.value.toLowerCase();
            if (termo === '') {
                renderizar(dados);
                return;
            }
            const filtrados = dados.filter(c => 
                c.codigo.toLowerCase().includes(termo) ||
                c.modelo.toLowerCase().includes(termo) ||
                c.marca.toLowerCase().includes(termo)
            );
            renderizar(filtrados);
        });
        
        carregar();
    </script>
    <script>
        // Verificar se o elemento com ID 'email' existe antes de remover o atributo
        const emailField = document.getElementById('email');
        if (emailField) {
            emailField.removeAttribute('required');
        }
    </script>
</body>
</html>
