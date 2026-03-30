/**
 * Script de Autenticação Global
 * 
 * Funções comuns de autenticação para todas as páginas
 * 
 * @package GestorClima
 * @version 1.0.0
 */

/**
 * Detecta o caminho base automaticamente
 */
function getAuthBasePath() {
    const path = window.location.pathname;
    
    // Se está em /views/, volta um nível
    if (path.includes('/views/')) {
        return '../';
    }
    
    return './';
}

/**
 * Função de logout
 */
async function fazerLogout() {
    if (!confirm('Deseja realmente sair do sistema?')) {
        return;
    }
    
    const basePath = getAuthBasePath();
    
    try {
        console.log('🚪 Fazendo logout...');
        
        const response = await fetch(basePath + 'controllers/AuthController.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        });
        
        console.log('📡 Logout response:', response.status);
        
        // Sempre redirecionar para login, mesmo em caso de erro
        window.location.href = basePath + 'login.php';
        
    } catch (error) {
        console.error('❌ Erro ao fazer logout:', error);
        // Redirecionar mesmo em caso de erro
        window.location.href = basePath + 'login.php';
    }
}

/**
 * Verificar sessão automaticamente a cada 5 minutos
 */
function verificarSessao() {
    const basePath = getAuthBasePath();
    
    setInterval(async () => {
        try {
            const response = await fetch(basePath + 'controllers/AuthController.php?verificar=1', {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (!data.autenticado) {
                console.warn('⚠️ Sessão expirada, redirecionando...');
                window.location.href = basePath + 'login.php?timeout=1';
            }
        } catch (error) {
            console.error('❌ Erro ao verificar sessão:', error);
        }
    }, 5 * 60 * 1000); // 5 minutos
}

// Iniciar verificação de sessão ao carregar a página
document.addEventListener('DOMContentLoaded', () => {
    verificarSessao();
    
    console.log('✅ Sistema de autenticação inicializado');
});
