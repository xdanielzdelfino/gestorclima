<?php
/**
 * Página de Clientes
 * 
 * Gerenciamento de clientes do sistema
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
    <title>Clientes - Gestor Clima</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    
    <!-- Ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Melhorias no design do formulário */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-control {
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 0.5rem;
            font-size: 1rem;
            width: 100%;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
            outline: none;
        }

        .form-label {
            font-weight: normal;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding-top: 1rem;
        }

        .required::after {
            content: "*";
            color: red;
            margin-left: 0.25rem;
        }

        /* Adicionar barra de scroll para formulários */
        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
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
    </style>
</head>
<body>
    <div class="app-wrapper">
        <!-- SIDEBAR -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- HEADER -->
        <?php require_once __DIR__ . '/../includes/header.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Clientes</h1>
                <p class="page-subtitle">Gerencie os clientes cadastrados</p>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="abrirModalCliente()">
                        <i class="fas fa-plus"></i> Novo Cliente
                    </button>
                </div>
            </div>

            <!-- TOOLBAR -->
            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="search-box">
                        <i class="search-icon fas fa-search"></i>
                        <input type="text" class="form-control" id="busca-cliente" placeholder="Buscar por nome, CPF/CNPJ...">
                    </div>
                </div>
                <div class="toolbar-right">
                    <span class="text-secondary">Total: <strong id="total-clientes">0</strong> clientes</span>
                </div>
            </div>

            <!-- TABELA DE CLIENTES -->
            <div class="card">
                <div class="card-body">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Telefone</th>
                                    <th>CPF/CNPJ</th>
                                    <th>Cidade</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="clientes-tbody">
                                <!-- Será preenchido via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL CLIENTE -->
    <div class="modal-overlay" id="modal-cliente">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-cliente-titulo">Novo Cliente</h3>
                <button class="modal-close" onclick="fecharModalCliente()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="form-cliente" onsubmit="salvarCliente(event)">
                <div class="modal-body">
                    <input type="hidden" id="cliente-id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required">Nome Completo</label>
                            <input type="text" class="form-control" id="cliente-nome" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="cliente-email">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required">Telefone</label>
                            <input type="text" class="form-control" id="cliente-telefone" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">CPF/CNPJ</label>
                            <input type="text" class="form-control" id="cliente-cpf-cnpj">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        
                    </div>

                    <div class="form-group">
                        <label class="form-label">Endereço</label>
                        <input type="text" class="form-control" id="cliente-endereco">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="cliente-cidade">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Estado</label>
                            <select class="form-control form-select" id="cliente-estado">
                                <option value="">Selecione...</option>
                                <option value="AC">AC</option>
                                <option value="AL">AL</option>
                                <option value="AP">AP</option>
                                <option value="AM">AM</option>
                                <option value="BA">BA</option>
                                <option value="CE">CE</option>
                                <option value="DF">DF</option>
                                <option value="ES">ES</option>
                                <option value="GO">GO</option>
                                <option value="MA">MA</option>
                                <option value="MT">MT</option>
                                <option value="MS">MS</option>
                                <option value="MG">MG</option>
                                <option value="PA">PA</option>
                                <option value="PB">PB</option>
                                <option value="PR">PR</option>
                                <option value="PE">PE</option>
                                <option value="PI">PI</option>
                                <option value="RJ">RJ</option>
                                <option value="RN">RN</option>
                                <option value="RS">RS</option>
                                <option value="RO">RO</option>
                                <option value="RR">RR</option>
                                <option value="SC">SC</option>
                                <option value="SP">SP</option>
                                <option value="SE">SE</option>
                                <option value="TO">TO</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">CEP</label>
                            <input type="text" class="form-control" id="cliente-cep">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" id="cliente-observacoes" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="fecharModalCliente()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/auth.js"></script>
    <script>
        let clientes = [];
        
        // Carregar todos os clientes
        async function carregarClientes() {
            UI.showLoading();
            
            try {
                const response = await API.get(API_ENDPOINTS.clientes);
                
                if (response.success) {
                    clientes = response.data || [];
                    renderizarClientes(clientes);
                    document.getElementById('total-clientes').textContent = clientes.length;
                } else {
                    UI.showToast(response.message, 'error');
                }
            } catch (error) {
                UI.showToast('Erro ao carregar clientes', 'error');
            } finally {
                UI.hideLoading();
            }
        }
        
        // Renderizar tabela de clientes
        function renderizarClientes(data) {
            const tbody = document.getElementById('clientes-tbody');
            tbody.innerHTML = '';
            
                if (data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="empty-state">
                            <div class="empty-state-icon">👥</div>
                            <div class="empty-state-title">Nenhum cliente encontrado</div>
                            <div class="empty-state-text">Clique em "Novo Cliente" para adicionar</div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            data.forEach(cliente => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${cliente.nome}</strong></td>
                    <td>${cliente.telefone}</td>
                    <td>${cliente.cpf_cnpj}</td>
                    <td>${cliente.cidade || '-'} / ${cliente.estado || '-'}</td>
                    <td>
                        <div class="table-actions">
                            <button class="btn btn-sm btn-primary" onclick="editarCliente(${cliente.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="excluirCliente(${cliente.id})" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
        
        // Abrir modal para novo cliente
        function abrirModalCliente() {
            document.getElementById('modal-cliente-titulo').textContent = 'Novo Cliente';
            document.getElementById('form-cliente').reset();
            document.getElementById('cliente-id').value = '';
            UI.openModal('modal-cliente');
        }
        
        // Fechar modal
        function fecharModalCliente() {
            UI.closeModal('modal-cliente');
        }
        
        // Editar cliente
        async function editarCliente(id) {
            const cliente = clientes.find(c => c.id == id);
            
            if (!cliente) return;
            
            document.getElementById('modal-cliente-titulo').textContent = 'Editar Cliente';
            document.getElementById('cliente-id').value = cliente.id;
            document.getElementById('cliente-nome').value = cliente.nome;
            document.getElementById('cliente-email').value = cliente.email || '';
            document.getElementById('cliente-telefone').value = cliente.telefone;
            document.getElementById('cliente-cpf-cnpj').value = cliente.cpf_cnpj;
            document.getElementById('cliente-endereco').value = cliente.endereco || '';
            document.getElementById('cliente-cidade').value = cliente.cidade || '';
            document.getElementById('cliente-estado').value = cliente.estado || '';
            document.getElementById('cliente-cep').value = cliente.cep || '';
            document.getElementById('cliente-observacoes').value = cliente.observacoes || '';
            
            UI.openModal('modal-cliente');
        }
        
        // Salvar cliente
        async function salvarCliente(e) {
            e.preventDefault();
            
            const id = document.getElementById('cliente-id').value;
            const dados = {
                nome: document.getElementById('cliente-nome').value,
                email: document.getElementById('cliente-email').value,
                telefone: document.getElementById('cliente-telefone').value,
                cpf_cnpj: document.getElementById('cliente-cpf-cnpj').value,
                endereco: document.getElementById('cliente-endereco').value,
                cidade: document.getElementById('cliente-cidade').value,
                estado: document.getElementById('cliente-estado').value,
                cep: document.getElementById('cliente-cep').value,
                observacoes: document.getElementById('cliente-observacoes').value
            };
            
            UI.showLoading();
            
            try {
                let response;
                
                if (id) {
                    // Atualizar
                    dados.id = id;
                    response = await API.put(API_ENDPOINTS.clientes, dados);
                } else {
                    // Criar
                    response = await API.post(API_ENDPOINTS.clientes, dados);
                }
                
                if (response.success) {
                    UI.showToast(response.message, 'success');
                    fecharModalCliente();
                    carregarClientes();
                } else {
                    UI.showToast(response.message, 'error');
                }
            } catch (error) {
                UI.showToast('Erro ao salvar cliente', 'error');
            } finally {
                UI.hideLoading();
            }
        }
        
        // Excluir cliente
        async function excluirCliente(id) {
            if (!await UI.confirm('Tem certeza que deseja excluir este cliente?')) {
                return;
            }
            
            UI.showLoading();
            
            try {
                const response = await API.delete(API_ENDPOINTS.clientes, { id });
                
                if (response.success) {
                    UI.showToast(response.message, 'success');
                    carregarClientes();
                } else {
                    UI.showToast(response.message, 'error');
                }
            } catch (error) {
                UI.showToast('Erro ao excluir cliente', 'error');
            } finally {
                UI.hideLoading();
            }
        }
        
        // Busca em tempo real
        document.getElementById('busca-cliente').addEventListener('input', (e) => {
            const termo = e.target.value.toLowerCase();
            
            if (termo === '') {
                renderizarClientes(clientes);
                return;
            }
            
            const filtrados = clientes.filter(c => 
                (c.nome || '').toLowerCase().includes(termo) ||
                (c.email || '').toLowerCase().includes(termo) ||
                (c.cpf_cnpj || '').includes(termo) ||
                (c.telefone || '').includes(termo)
            );
            
            renderizarClientes(filtrados);
        });
        
        // Máscaras para CPF/CNPJ, telefone e CEP
        document.getElementById('cliente-cpf-cnpj').addEventListener('input', function (e) {
            e.target.value = e.target.value.replace(/\D/g, '')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        });

        document.getElementById('cliente-telefone').addEventListener('input', function (e) {
            e.target.value = e.target.value.replace(/\D/g, '')
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{4,5})(\d{4})$/, '$1-$2');
        });

        document.getElementById('cliente-cep').addEventListener('input', function (e) {
            e.target.value = e.target.value.replace(/\D/g, '')
                .replace(/(\d{5})(\d{3})$/, '$1-$2');
        });

        // Preencher automaticamente por CNPJ público (via proxy BrasilAPI) quando o campo atingir 14 dígitos durante a digitação
        (function () {
            const input = document.getElementById('cliente-cpf-cnpj');
            const queried = new Set(); // evita consultas repetidas para o mesmo CNPJ

            input.addEventListener('input', async function (e) {
                const raw = e.target.value.replace(/\D/g, '');
                if (raw.length === 14 && !queried.has(raw)) {
                    queried.add(raw);
                    try {
                        const res = await API.get(API_ENDPOINTS.clientes, { lookup_cnpj: raw });
                        if (res && res.success && res.data) {
                            const c = res.data;
                            if (!document.getElementById('cliente-nome').value) document.getElementById('cliente-nome').value = c.nome || '';
                            if (!document.getElementById('cliente-email').value) document.getElementById('cliente-email').value = c.email || '';
                            if (!document.getElementById('cliente-telefone').value) document.getElementById('cliente-telefone').value = c.telefone || '';
                            if (!document.getElementById('cliente-endereco').value) document.getElementById('cliente-endereco').value = c.endereco || '';
                            if (!document.getElementById('cliente-cidade').value) document.getElementById('cliente-cidade').value = c.cidade || '';
                            if (!document.getElementById('cliente-estado').value) document.getElementById('cliente-estado').value = c.estado || '';
                            if (!document.getElementById('cliente-cep').value && c.cep) document.getElementById('cliente-cep').value = c.cep;
                        }
                    } catch (err) {
                        console.error('Erro ao consultar CNPJ público:', err);
                    }
                }
                // Limpar queried caso usuário apague para evitar não reconsultar um novo valor
                if (raw.length < 14) {
                    queried.clear();
                }
            });
        })();

        // Preencher endereço por CEP usando proxy para BrasilAPI (igual ao lookup CNPJ)
        (function () {
            const input = document.getElementById('cliente-cep');
            const queriedCep = new Set();

            input.addEventListener('blur', async function (e) {
                const cep = e.target.value.replace(/\D/g, '');
                if (!cep || cep.length !== 8 || queriedCep.has(cep)) return;

                queriedCep.add(cep);

                try {
                    // Usamos o proxy do backend para não expor a BrasilAPI diretamente no cliente
                    const res = await API.get(API_ENDPOINTS.clientes, { lookup_cep: cep });
                    if (res && res.success && res.data) {
                        const d = res.data;
                        if (!document.getElementById('cliente-endereco').value) document.getElementById('cliente-endereco').value = (d.endereco || '').trim();
                        if (!document.getElementById('cliente-cidade').value) document.getElementById('cliente-cidade').value = d.cidade || '';
                        if (!document.getElementById('cliente-estado').value) document.getElementById('cliente-estado').value = d.estado || '';
                        if (!document.getElementById('cliente-cep').value && d.cep) document.getElementById('cliente-cep').value = d.cep;
                    }
                } catch (err) {
                    console.error('Erro lookup CEP:', err);
                }
            });
        })();
        
        // Inicialização
        carregarClientes();
    </script>
</body>
</html>
