<?php
/**
 * Controller de Autenticação
 * 
 * Gerencia login, logout e verificação de sessão
 * 
 * @package GestorClima
 * @subpackage Controllers
 * @author Sistema de Gestão de Climatizadores
 * @version 1.0.0
 */

// Iniciar sessão se ainda não iniciou
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController {
    
    private $usuarioModel;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->usuarioModel = new Usuario();
    }
    
    /**
     * Processar requisições
     */
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'POST':
                $this->handlePost();
                break;
            case 'GET':
                $this->handleGet();
                break;
            case 'DELETE':
                $this->handleDelete();
                break;
            default:
                jsonResponse(['erro' => 'Método não permitido'], 405);
        }
    }
    
    /**
     * Processar GET (verificar sessão, obter usuário logado)
     */
    private function handleGet() {
        // Verificar sessão
        if (isset($_GET['verificar'])) {
            jsonResponse([
                'autenticado' => $this->isAutenticado(),
                'usuario' => $_SESSION['usuario'] ?? null
            ]);
            return;
        }
        
        // Obter usuário logado
        if ($this->isAutenticado()) {
            jsonResponse([
                'sucesso' => true,
                'usuario' => $_SESSION['usuario']
            ]);
        } else {
            jsonResponse(['erro' => 'Não autenticado'], 401);
        }
    }
    
    /**
     * Processar POST (login)
     */
    private function handlePost() {
        try {
            $dados = json_decode(file_get_contents('php://input'), true);
            
            // Validar dados
            if (empty($dados['email']) || empty($dados['senha'])) {
                jsonResponse(['erro' => 'Email e senha são obrigatórios'], 400);
                return;
            }
            
            // Tentar autenticar
            $usuario = $this->usuarioModel->autenticar(
                $dados['email'],
                $dados['senha']
            );
            
            if (!$usuario) {
                jsonResponse(['erro' => 'Email ou senha incorretos'], 401);
                return;
            }
            
            // Criar sessão
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario'] = [
                'id' => $usuario['id'],
                'nome' => $usuario['nome'],
                'email' => $usuario['email'],
                'nivel' => $usuario['nivel']
            ];
            $_SESSION['login_time'] = time();
            
            // Regenerar ID da sessão por segurança
            session_regenerate_id(true);
            
            jsonResponse([
                'sucesso' => true,
                'mensagem' => 'Login realizado com sucesso',
                'usuario' => $_SESSION['usuario']
            ]);
            
        } catch (Exception $e) {
            jsonResponse(['erro' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Processar DELETE (logout)
     */
    private function handleDelete() {
        try {
            // Registrar logout
            if (isset($_SESSION['usuario_id'])) {
                $this->registrarLogout($_SESSION['usuario_id']);
            }
            
            // Destruir sessão
            $_SESSION = [];
            
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            session_destroy();
            
            jsonResponse([
                'sucesso' => true,
                'mensagem' => 'Logout realizado com sucesso'
            ]);
            
        } catch (Exception $e) {
            jsonResponse(['erro' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Verificar se usuário está autenticado
     * 
     * @return bool True se autenticado
     */
    public function isAutenticado() {
        return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
    }
    
    /**
     * Verificar se usuário tem permissão
     * 
     * @param string|array $nivel Nível(is) permitido(s)
     * @return bool True se tem permissão
     */
    public function temPermissao($nivel) {
        if (!$this->isAutenticado()) {
            return false;
        }
        
        $nivelUsuario = $_SESSION['usuario']['nivel'] ?? '';
        
        // Admin tem acesso a tudo
        if ($nivelUsuario === 'admin') {
            return true;
        }
        
        if (is_array($nivel)) {
            return in_array($nivelUsuario, $nivel);
        }
        
        return $nivelUsuario === $nivel;
    }
    
    /**
     * Middleware - Requer autenticação
     * Redireciona para login se não autenticado
     */
    public function requerAutenticacao() {
        if (!$this->isAutenticado()) {
            // Se for requisição AJAX
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                jsonResponse(['erro' => 'Não autenticado'], 401);
                exit;
            }
            
            // Redirecionar para login
            header('Location: /login.php');
            exit;
        }
        
        // Verificar timeout de sessão (30 minutos)
        if (isset($_SESSION['login_time'])) {
            $tempoDecorrido = time() - $_SESSION['login_time'];
            
            if ($tempoDecorrido > 1800) { // 30 minutos
                session_destroy();
                header('Location: /login.php?timeout=1');
                exit;
            }
            
            // Renovar tempo de sessão
            $_SESSION['login_time'] = time();
        }
    }
    
    /**
     * Registrar logout no banco
     * 
     * @param int $usuarioId ID do usuário
     */
    private function registrarLogout($usuarioId) {
        try {
            $db = Database::getInstance()->getConnection();
            
            $query = "INSERT INTO logs_acesso (usuario_id, acao, ip_address, user_agent) 
                      VALUES (:usuario_id, 'logout', :ip, :user_agent)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
            $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Erro ao registrar logout: " . $e->getMessage());
        }
    }
}

// Se acessado diretamente, processar requisição
if (basename($_SERVER['PHP_SELF']) === 'AuthController.php') {
    // Limpar qualquer output anterior
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Iniciar buffer de saída
    ob_start();
    
    try {
        $controller = new AuthController();
        $controller->handleRequest();
    } catch (Exception $e) {
        // Limpar buffer em caso de erro
        ob_end_clean();
        
        jsonResponse([
            'erro' => $e->getMessage(),
            'arquivo' => $e->getFile(),
            'linha' => $e->getLine()
        ], 500);
    }
    
    // Enviar buffer
    ob_end_flush();
}
