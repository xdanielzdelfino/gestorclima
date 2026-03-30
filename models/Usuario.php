<?php
/**
 * Model de Usuário
 * 
 * Gerencia operações CRUD e autenticação de usuários
 * 
 * @package GestorClima
 * @subpackage Models
 * @author Sistema de Gestão de Climatizadores
 * @version 1.0.0
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

class Usuario {
    
    private $db;
    private $table = 'usuarios';
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Listar todos os usuários
     * 
     * @return array Array de usuários
     */
    public function listarTodos() {
        try {
            $query = "SELECT 
                        id, nome, email, nivel, ativo, 
                        ultimo_acesso, criado_em, atualizado_em
                      FROM {$this->table}
                      ORDER BY nome ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erro ao listar usuários: " . $e->getMessage());
            throw new Exception("Erro ao listar usuários");
        }
    }
    
    /**
     * Buscar usuário por ID
     * 
     * @param int $id ID do usuário
     * @return array|false Dados do usuário ou false
     */
    public function buscarPorId($id) {
        try {
            $query = "SELECT 
                        id, nome, email, nivel, ativo, 
                        ultimo_acesso, criado_em, atualizado_em
                      FROM {$this->table}
                      WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar usuário: " . $e->getMessage());
            throw new Exception("Erro ao buscar usuário");
        }
    }
    
    /**
     * Buscar usuário por email
     * 
     * @param string $email Email do usuário
     * @return array|false Dados do usuário ou false
     */
    public function buscarPorEmail($email) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE email = :email";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar usuário por email: " . $e->getMessage());
            throw new Exception("Erro ao buscar usuário");
        }
    }
    
    /**
     * Autenticar usuário
     * 
     * @param string $email Email do usuário
     * @param string $senha Senha em texto plano
     * @return array|false Dados do usuário ou false
     */
    public function autenticar($email, $senha) {
        try {
            $usuario = $this->buscarPorEmail($email);
            
            if (!$usuario) {
                $this->registrarTentativaFalha($email);
                return false;
            }
            
            // Verificar se está ativo
            if ($usuario['ativo'] != 1) {
                return false;
            }
            
            // Verificar senha
            if (!password_verify($senha, $usuario['senha'])) {
                $this->registrarTentativaFalha($email, $usuario['id']);
                return false;
            }
            
            // Atualizar último acesso
            $this->atualizarUltimoAcesso($usuario['id']);
            
            // Registrar log de sucesso
            $this->registrarLogin($usuario['id']);
            
            // Remover senha do retorno
            unset($usuario['senha']);
            
            return $usuario;
            
        } catch (PDOException $e) {
            error_log("Erro ao autenticar usuário: " . $e->getMessage());
            throw new Exception("Erro ao autenticar");
        }
    }
    
    /**
     * Criar novo usuário
     * 
     * @param array $dados Dados do usuário
     * @return int ID do usuário criado
     */
    public function criar($dados) {
        try {
            // Validar dados
            $this->validar($dados);
            
            // Verificar se email já existe
            if ($this->emailExiste($dados['email'])) {
                throw new Exception("Email já cadastrado");
            }
            
            // Criptografar senha
            $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);
            
            $query = "INSERT INTO {$this->table} 
                      (nome, email, senha, nivel, ativo) 
                      VALUES 
                      (:nome, :email, :senha, :nivel, :ativo)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nome', $dados['nome']);
            $stmt->bindParam(':email', $dados['email']);
            $stmt->bindParam(':senha', $senhaHash);
            $stmt->bindParam(':nivel', $dados['nivel']);
            $stmt->bindValue(':ativo', $dados['ativo'] ?? 1, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $this->db->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Erro ao criar usuário: " . $e->getMessage());
            throw new Exception("Erro ao criar usuário");
        }
    }
    
    /**
     * Atualizar usuário
     * 
     * @param int $id ID do usuário
     * @param array $dados Dados para atualizar
     * @return bool Sucesso da operação
     */
    public function atualizar($id, $dados) {
        try {
            // Verificar se usuário existe
            if (!$this->buscarPorId($id)) {
                throw new Exception("Usuário não encontrado");
            }
            
            // Verificar se email já existe em outro usuário
            if (isset($dados['email']) && $this->emailExiste($dados['email'], $id)) {
                throw new Exception("Email já cadastrado");
            }
            
            $campos = [];
            $valores = [];
            
            // Campos permitidos para atualização
            $camposPermitidos = ['nome', 'email', 'nivel', 'ativo'];
            
            foreach ($camposPermitidos as $campo) {
                if (isset($dados[$campo])) {
                    $campos[] = "$campo = :$campo";
                    $valores[$campo] = $dados[$campo];
                }
            }
            
            // Se foi enviada nova senha
            if (!empty($dados['senha'])) {
                $campos[] = "senha = :senha";
                $valores['senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
            }
            
            if (empty($campos)) {
                throw new Exception("Nenhum dado para atualizar");
            }
            
            $query = "UPDATE {$this->table} SET " . implode(', ', $campos) . " WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            foreach ($valores as $campo => $valor) {
                $stmt->bindValue(":$campo", $valor);
            }
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Erro ao atualizar usuário: " . $e->getMessage());
            throw new Exception("Erro ao atualizar usuário");
        }
    }
    
    /**
     * Excluir usuário
     * 
     * @param int $id ID do usuário
     * @return bool Sucesso da operação
     */
    public function excluir($id) {
        try {
            // Verificar se existe
            if (!$this->buscarPorId($id)) {
                throw new Exception("Usuário não encontrado");
            }
            
            // Não permitir excluir o último admin
            if ($this->isUltimoAdmin($id)) {
                throw new Exception("Não é possível excluir o último administrador");
            }
            
            $query = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Erro ao excluir usuário: " . $e->getMessage());
            throw new Exception("Erro ao excluir usuário");
        }
    }
    
    /**
     * Alterar senha do usuário
     * 
     * @param int $id ID do usuário
     * @param string $senhaAtual Senha atual
     * @param string $novaSenha Nova senha
     * @return bool Sucesso da operação
     */
    public function alterarSenha($id, $senhaAtual, $novaSenha) {
        try {
            $usuario = $this->buscarPorEmail($this->buscarPorId($id)['email']);
            
            // Verificar senha atual
            if (!password_verify($senhaAtual, $usuario['senha'])) {
                throw new Exception("Senha atual incorreta");
            }
            
            // Validar nova senha
            if (strlen($novaSenha) < 6) {
                throw new Exception("A nova senha deve ter no mínimo 6 caracteres");
            }
            
            $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            
            $query = "UPDATE {$this->table} SET senha = :senha WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':senha', $senhaHash);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Erro ao alterar senha: " . $e->getMessage());
            throw new Exception("Erro ao alterar senha");
        }
    }
    
    /**
     * Atualizar último acesso
     * 
     * @param int $id ID do usuário
     * @return bool Sucesso da operação
     */
    private function atualizarUltimoAcesso($id) {
        try {
            $query = "UPDATE {$this->table} SET ultimo_acesso = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao atualizar último acesso: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar login bem-sucedido
     * 
     * @param int $usuarioId ID do usuário
     * @return bool Sucesso da operação
     */
    private function registrarLogin($usuarioId) {
        try {
            $query = "INSERT INTO logs_acesso (usuario_id, acao, ip_address, user_agent) 
                      VALUES (:usuario_id, 'login', :ip, :user_agent)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao registrar login: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar tentativa de login falha
     * 
     * @param string $email Email usado na tentativa
     * @param int|null $usuarioId ID do usuário (se encontrado)
     * @return bool Sucesso da operação
     */
    private function registrarTentativaFalha($email, $usuarioId = null) {
        try {
            if (!$usuarioId) {
                // Buscar ID pelo email
                $usuario = $this->buscarPorEmail($email);
                $usuarioId = $usuario ? $usuario['id'] : 0;
            }
            
            $query = "INSERT INTO logs_acesso (usuario_id, acao, ip_address, user_agent) 
                      VALUES (:usuario_id, 'tentativa_falha', :ip, :user_agent)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao registrar tentativa falha: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar se é o último admin
     * 
     * @param int $id ID do usuário
     * @return bool True se for o último admin
     */
    private function isUltimoAdmin($id) {
        try {
            $usuario = $this->buscarPorId($id);
            
            if ($usuario['nivel'] !== 'admin') {
                return false;
            }
            
            $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE nivel = 'admin' AND ativo = 1";
            $stmt = $this->db->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] <= 1;
            
        } catch (PDOException $e) {
            error_log("Erro ao verificar último admin: " . $e->getMessage());
            return true; // Por segurança, assume que é o último
        }
    }
    
    /**
     * Verificar se email já existe
     * 
     * @param string $email Email a verificar
     * @param int|null $idExcluir ID do usuário a excluir da verificação
     * @return bool True se existe
     */
    private function emailExiste($email, $idExcluir = null) {
        try {
            $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE email = :email";
            
            if ($idExcluir) {
                $query .= " AND id != :id";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            
            if ($idExcluir) {
                $stmt->bindParam(':id', $idExcluir, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] > 0;
            
        } catch (PDOException $e) {
            error_log("Erro ao verificar email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validar dados do usuário
     * 
     * @param array $dados Dados a validar
     * @throws Exception Se validação falhar
     */
    private function validar($dados) {
        $erros = [];
        
        // Nome obrigatório
        if (empty($dados['nome'])) {
            $erros[] = "Nome é obrigatório";
        }
        
        // Email obrigatório e válido
        if (empty($dados['email'])) {
            $erros[] = "Email é obrigatório";
        } elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = "Email inválido";
        }
        
        // Senha obrigatória no cadastro
        if (empty($dados['senha'])) {
            $erros[] = "Senha é obrigatória";
        } elseif (strlen($dados['senha']) < 6) {
            $erros[] = "Senha deve ter no mínimo 6 caracteres";
        }
        
        // Nível válido
        if (!empty($dados['nivel']) && !in_array($dados['nivel'], ['admin', 'operador', 'visualizador'])) {
            $erros[] = "Nível de acesso inválido";
        }
        
        if (!empty($erros)) {
            throw new Exception(implode(", ", $erros));
        }
    }
}
