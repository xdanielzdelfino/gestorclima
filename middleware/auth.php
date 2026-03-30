<?php
/**
 * Middleware de Autenticação
 * 
 * Protege páginas que requerem login
 * Inclua este arquivo no topo de cada página protegida:
 * require_once __DIR__ . '/middleware/auth.php';
 * 
 * @package GestorClima
 * @version 1.0.0
 */

// Iniciar sessão se ainda não iniciou
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/AuthController.php';

/**
 * Função para requerer autenticação
 * Redireciona para login se não autenticado
 * 
 * @return void
 */
function requerAutenticacao() {
    // Verificar se está autenticado
    if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
        // Redirecionar para login
        header('Location: ' . getBaseUrl() . 'login.php');
        exit;
    }
    
    // Verificar timeout de sessão (30 minutos)
    $tempoMaximo = 30 * 60; // 30 minutos em segundos
    
    if (isset($_SESSION['login_time'])) {
        $tempoDecorrido = time() - $_SESSION['login_time'];
        
        if ($tempoDecorrido > $tempoMaximo) {
            // Sessão expirada
            session_destroy();
            header('Location: ' . getBaseUrl() . 'login.php?timeout=1');
            exit;
        }
        
        // Renovar tempo de sessão
        $_SESSION['login_time'] = time();
    }
}

/**
 * Obter URL base do sistema
 * 
 * @return string URL base
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    // Remover /views se existir
    $path = str_replace('/views', '', $path);
    
    return $protocol . '://' . $host . $path . '/';
}

/**
 * Função helper para obter usuário logado
 * 
 * @return array|null Dados do usuário ou null
 */
function getUsuarioLogado() {
    return $_SESSION['usuario'] ?? null;
}

/**
 * Função helper para verificar permissão
 * 
 * @param string|array $nivel Nível(is) permitido(s)
 * @return bool True se tem permissão
 */
function temPermissao($nivel) {
    global $auth;
    return $auth->temPermissao($nivel);
}

/**
 * Função helper para verificar se é admin
 * 
 * @return bool True se é admin
 */
function isAdmin() {
    $usuario = getUsuarioLogado();
    return $usuario && $usuario['nivel'] === 'admin';
}

/**
 * Função helper para verificar se é operador ou superior
 * 
 * @return bool True se é operador ou admin
 */
function isOperador() {
    $usuario = getUsuarioLogado();
    return $usuario && in_array($usuario['nivel'], ['admin', 'operador']);
}
