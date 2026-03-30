<?php
/**
 * Arquivo de Configuração Principal
 * 
 * Define constantes e configurações globais do sistema
 * 
 * @package GestorClima
 * @author Sistema de Gestão de Climatizadores
 * @version 1.0.0
 */

// Prevenir acesso direto
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));

// =====================================================
// Configurações do Banco de Dados
// =====================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestorclima_db');
define('DB_USER', 'db_usuario');
define('DB_PASS', 'db_senha');
define('DB_CHARSET', 'utf8mb4');

// =====================================================
// Configurações da Aplicação
// =====================================================
define('APP_NAME', 'Gestor Clima');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/gestorclimaa');

// Depuração da aplicação (quando true, mensagens de erro do DB serão expostas em endpoints de debug)
define('APP_DEBUG', true);

// =====================================================
// Configurações de Timezone e Locale
// =====================================================
date_default_timezone_set('America/Sao_Paulo');
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');

// =====================================================
// Configurações de Erro (Desenvolvimento)
// =====================================================
// ATENÇÃO: Em produção, altere para:
// error_reporting(0);
// ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/php-errors.log');

// =====================================================
// Configurações de Sessão
// =====================================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Alterar para 1 em HTTPS
}

// =====================================================
// Headers de Segurança
// =====================================================
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// =====================================================
// Autoload de Classes (PSR-4)
// =====================================================
spl_autoload_register(function ($class) {
    $paths = [
        BASE_PATH . '/models/' . $class . '.php',
        BASE_PATH . '/controllers/' . $class . '.php',
        BASE_PATH . '/config/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// =====================================================
// Funções Auxiliares Globais
// =====================================================

/**
 * Sanitiza entrada do usuário
 * 
 * @param mixed $data Dados a serem sanitizados
 * @return mixed Dados sanitizados
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida email
 * 
 * @param string $email Email a ser validado
 * @return bool
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Formata valor monetário
 * 
 * @param float $valor Valor a ser formatado
 * @return string Valor formatado
 */
function formatarValor($valor) {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

/**
 * Formata data brasileira
 * 
 * @param string $data Data no formato Y-m-d
 * @return string Data formatada dd/mm/yyyy
 */
function formatarData($data) {
    if (empty($data)) return '';
    $timestamp = strtotime($data);
    return date('d/m/Y', $timestamp);
}

/**
 * Converte data brasileira para MySQL
 * 
 * @param string $data Data no formato dd/mm/yyyy
 * @return string Data no formato Y-m-d
 */
function dataParaMySQL($data) {
    if (empty($data)) return null;
    $partes = explode('/', $data);
    if (count($partes) === 3) {
        return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
    }
    return null;
}

/**
 * Retorna resposta JSON
 * 
 * @param array|bool $data Array de dados ou booleano para compatibilidade
 * @param int|string $statusCode Código HTTP de status ou mensagem (para compatibilidade)
 * @param mixed $deprecated Parâmetro legado (ignorado)
 * @return void
 */
function jsonResponse($data, $statusCode = 200, $deprecated = null) {
    // Limpar qualquer output anterior
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Compatibilidade com formato antigo: jsonResponse(true, "mensagem", $dados)
    if (is_bool($data)) {
        // Formato antigo detectado
        $responseData = [
            'success' => $data,
            'message' => is_string($statusCode) ? $statusCode : '',
            'data' => $deprecated
        ];
        $httpCode = 200;
    } else {
        // Formato novo: jsonResponse(['sucesso' => true, ...], 200)
        $responseData = $data;
        $httpCode = is_int($statusCode) ? $statusCode : 200;
    }
    
    // Definir código de status HTTP
    http_response_code($httpCode);
    
    // Definir cabeçalhos
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    
    // Enviar JSON
    echo json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Gera token CSRF
 * 
 * @return string Token gerado
 */
function gerarTokenCSRF() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida token CSRF
 * 
 * @param string $token Token a ser validado
 * @return bool
 */
function validarTokenCSRF($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
