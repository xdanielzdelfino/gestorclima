/**
 * Gestor Clima - JavaScript Principal
 * Gerenciamento de API, UI e funcionalidades globais
 * 
 * @version 1.0.0
 */

// =====================================================
// CONFIGURAÇÕES GLOBAIS
// =====================================================

/**
 * Detecta automaticamente o caminho base da aplicação
 */
function getBasePath() {
    const path = window.location.pathname;
    
    // Se está em /views/, volta um nível
    if (path.includes('/views/')) {
        const basePath = path.substring(0, path.indexOf('/views/'));
        return window.location.origin + basePath + '/controllers/';
    }
    
    // Se está na raiz ou em outra pasta
    const segments = path.split('/').filter(s => s);
    segments.pop(); // Remove o arquivo atual
    
    const basePath = segments.length > 0 ? '/' + segments.join('/') : '';
    return window.location.origin + basePath + '/controllers/';
}

const API_BASE = getBasePath();

const API_ENDPOINTS = {
    clientes: `${API_BASE}ClienteController.php`,
    climatizadores: `${API_BASE}ClimatizadorController.php`,
    locacoes: `${API_BASE}LocacaoController.php`
};

// Log para debug
const DEBUG = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1');
function logDebug(...args) {
    if (DEBUG) console.log(...args);
}
function logError(...args) {
    if (DEBUG) console.error(...args);
}

logDebug('🔧 API_BASE configurado:', API_BASE);
logDebug('🔧 API_ENDPOINTS:', API_ENDPOINTS);

// =====================================================
// CLASSE API - Comunicação com Backend
// =====================================================
class API {
    /**
     * Requisição GET
     */
    static async get(endpoint, params = {}) {
        try {
            const queryString = new URLSearchParams(params).toString();
            const url = `${endpoint}${queryString ? '?' + queryString : ''}`;
            logDebug('🌐 GET:', url);
            const response = await fetch(url, {
                method: 'GET',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });
            logDebug('📡 Response status:', response.status, response.statusText);
            
            // Verificar se a resposta é JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                logError('❌ Resposta não é JSON:', text.substring(0, 200));
                throw new Error('Servidor retornou resposta inválida (não é JSON)');
            }
            
            const data = await response.json();
            logDebug('📦 Data:', data);
            
            return data;
        } catch (error) {
            logError('❌ Erro na requisição GET:', error);
            return { success: false, message: 'Erro de comunicação com servidor: ' + error.message };
        }
    }
    
    /**
     * Requisição POST
     */
    static async post(endpoint, data) {
        try {
            logDebug('🌐 POST:', endpoint, data);
            
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data),
                credentials: 'same-origin'
            });
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                logError('❌ Resposta não é JSON:', text.substring(0, 200));
                throw new Error('Servidor retornou resposta inválida');
            }
            
            const result = await response.json();
            logDebug('📦 Result:', result);
            
            return result;
        } catch (error) {
            logError('❌ Erro na requisição POST:', error);
            return { success: false, message: 'Erro de comunicação com servidor: ' + error.message };
        }
    }
    
    /**
     * Requisição PUT
     */
    static async put(endpoint, data) {
        try {
            const response = await fetch(endpoint, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            return await response.json();
        } catch (error) {
            console.error('Erro na requisição PUT:', error);
            return { success: false, message: 'Erro de comunicação com servidor' };
        }
    }
    
    /**
     * Requisição DELETE
     */
    static async delete(endpoint, data) {
        try {
            const response = await fetch(endpoint, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            return await response.json();
        } catch (error) {
            console.error('Erro na requisição DELETE:', error);
            return { success: false, message: 'Erro de comunicação com servidor' };
        }
    }
}

// =====================================================
// CLASSE UI - Gerenciamento de Interface
// =====================================================
class UI {
    /**
     * Mostra mensagem toast
     */
    static showToast(message, type = 'info') {
        // Remove toast anterior se existir
        const existingToast = document.querySelector('.toast');
        if (existingToast) {
            existingToast.remove();
        }
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()">✕</button>
        `;
        
        document.body.appendChild(toast);
        
        // Adiciona CSS se não existir
        if (!document.getElementById('toast-styles')) {
            const style = document.createElement('style');
            style.id = 'toast-styles';
            style.textContent = `
                .toast {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: white;
                    padding: 1rem 1.5rem;
                    border-radius: 12px;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    z-index: 10000;
                    animation: slideInRight 0.3s ease-out;
                    max-width: 400px;
                }
                .toast-success { border-left: 4px solid #10b981; }
                .toast-error { border-left: 4px solid #ef4444; }
                .toast-warning { border-left: 4px solid #f59e0b; }
                .toast-info { border-left: 4px solid #3b82f6; }
                .toast button {
                    background: none;
                    border: none;
                    font-size: 1.25rem;
                    cursor: pointer;
                    color: #64748b;
                }
            `;
            document.head.appendChild(style);
        }
        
        // Remove automaticamente após 5 segundos
        setTimeout(() => toast.remove(), 5000);
    }
    
    /**
     * Mostra loading overlay
     */
    static showLoading() {
        const loading = document.createElement('div');
        loading.className = 'loading-overlay';
        loading.id = 'loading-overlay';
        loading.innerHTML = '<div class="spinner"></div>';
        document.body.appendChild(loading);
    }
    
    /**
     * Esconde loading overlay
     */
    static hideLoading() {
        const loading = document.getElementById('loading-overlay');
        if (loading) {
            loading.remove();
        }
    }
    
    /**
     * Abre modal
     */
    static openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    /**
     * Fecha modal
     */
    static closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    /**
     * Confirma ação
     */
    static async confirm(message) {
        return confirm(message);
    }
    
    /**
     * Formata valor monetário
     */
    static formatMoney(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }
    
    /**
     * Formata data
     */
    static formatDate(date) {
        if (!date) return '';
        const d = new Date(date + 'T00:00:00');
        return d.toLocaleDateString('pt-BR');
    }

    /**
     * Formata data e hora no padrão pt-BR (dd/mm/yyyy HH:MM:SS)
     */
    static formatDateTime(dateTime) {
        if (!dateTime) return '';
        try {
            // Se a string vier sem o separador 'T', normalizar para Date
            const d = new Date(dateTime.indexOf('T') === -1 && dateTime.indexOf(' ') !== -1 ? dateTime.replace(' ', 'T') : dateTime);
            // Forçar formato com horas, minutos e segundos
            const parts = new Intl.DateTimeFormat('pt-BR', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit', second: '2-digit',
                hour12: false
            }).formatToParts(d);
            // Montar string dd/mm/yyyy HH:MM:SS
            const map = {};
            parts.forEach(p => map[p.type] = p.value);
            return `${map.day}/${map.month}/${map.year} ${map.hour}:${map.minute}:${map.second}`;
        } catch (err) {
            // fallback simples
            return new Date(dateTime).toLocaleString('pt-BR');
        }
    }
    
    /**
     * Converte data BR para MySQL
     */
    static dateToMySQL(dateStr) {
        if (!dateStr) return null;
        const parts = dateStr.split('/');
        if (parts.length === 3) {
            return `${parts[2]}-${parts[1]}-${parts[0]}`;
        }
        return dateStr;
    }
}

// =====================================================
// VALIDAÇÕES
// =====================================================
class Validator {
    /**
     * Valida email
     */
    static email(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    /**
     * Valida telefone
     */
    static telefone(telefone) {
        const cleaned = telefone.replace(/\D/g, '');
        return cleaned.length >= 10;
    }
    
    /**
     * Valida CPF
     */
    static cpf(cpf) {
        cpf = cpf.replace(/\D/g, '');
        if (cpf.length !== 11) return false;
        
        // Validação simples
        let sum = 0;
        for (let i = 0; i < 9; i++) {
            sum += parseInt(cpf.charAt(i)) * (10 - i);
        }
        let rev = 11 - (sum % 11);
        if (rev === 10 || rev === 11) rev = 0;
        if (rev !== parseInt(cpf.charAt(9))) return false;
        
        sum = 0;
        for (let i = 0; i < 10; i++) {
            sum += parseInt(cpf.charAt(i)) * (11 - i);
        }
        rev = 11 - (sum % 11);
        if (rev === 10 || rev === 11) rev = 0;
        if (rev !== parseInt(cpf.charAt(10))) return false;
        
        return true;
    }
    
    /**
     * Valida CNPJ
     */
    static cnpj(cnpj) {
        cnpj = cnpj.replace(/\D/g, '');
        return cnpj.length === 14;
    }
    
    /**
     * Valida campo obrigatório
     */
    static required(value) {
        return value !== null && value !== undefined && value.toString().trim() !== '';
    }
}

// =====================================================
// MÁSCARAS DE INPUT
// =====================================================
class InputMask {
    /**
     * Máscara de telefone
     */
    static telefone(input) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 10) {
                value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else {
                value = value.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
            }
            e.target.value = value;
        });
    }
    
    /**
     * Máscara de CPF
     */
    static cpf(input) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{0,2})/, '$1.$2.$3-$4');
            e.target.value = value;
        });
    }
    
    /**
     * Máscara de CNPJ
     */
    static cnpj(input) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2})/, '$1.$2.$3/$4-$5');
            e.target.value = value;
        });
    }
    
    /**
     * Máscara de CEP
     */
    static cep(input) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{5})(\d{0,3})/, '$1-$2');
            e.target.value = value;
        });
    }
    
    /**
     * Máscara de dinheiro
     */
    static money(input) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            value = (parseInt(value) / 100).toFixed(2);
            e.target.value = value;
        });
    }
}

// =====================================================
// SIDEBAR MOBILE
// =====================================================
function initSidebar() {
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
        
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
}

// =====================================================
// INICIALIZAÇÃO
// =====================================================
document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    
    // Ativar link da página atual no menu
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    document.querySelectorAll('.sidebar-nav-link').forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
    
    async function atualizarClimatizadoresDisponiveis() {
        try {
            const response = await API.get(API_ENDPOINTS.climatizadores, { contar_disponiveis: true });
            if (response.success && response.data && typeof response.data.total !== 'undefined') {
                const el = document.getElementById('climatizadores-disponiveis');
                if (el) {
                    el.textContent = response.data.total;
                }
            }
        } catch (e) {
            logError('Erro ao buscar climatizadores disponíveis:', e);
        }
    }
    // Chame essa função ao carregar o dashboard
    if (document.readyState !== 'loading') {
        atualizarClimatizadoresDisponiveis();
    } else {
        document.addEventListener('DOMContentLoaded', atualizarClimatizadoresDisponiveis);
    }
});

// Exportar para uso global
window.API = API;
window.UI = UI;
window.Validator = Validator;
window.InputMask = InputMask;
