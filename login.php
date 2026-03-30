<?php
/**
 * Página de Login
 * 
 * Interface de autenticação do sistema
 * 
 * @package GestorClima
 * @version 1.0.0
 */

// Iniciar sessão
session_start();

// Se já está logado, redirecionar para dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// Verificar se houve timeout
$timeout = isset($_GET['timeout']) ? true : false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Gestão de Locações de Climatizadores - Login">
    <title>Login - Gestor Clima</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/components.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --cor-primaria: #3b82f6; /* Azul */
            --cor-primaria-hover: #2563eb;
            --cor-fundo: #f4f7f9;
            --cor-texto: #374151;
            --cor-texto-claro: #6b7280;
            --cor-borda: #d1d5db;
            --cor-card: #ffffff;
            --sombra-card: 0 10px 15px -3px rgba(0, 0, 0, 0.07), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--cor-fundo);
            /* Padrão de fundo sutil */
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23d1d5db' fill-opacity='0.3'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: var(--cor-card);
            border-radius: 16px;
            box-shadow: var(--sombra-card);
            max-width: 450px;
            width: 100%;
            padding: 40px;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-header .icon {
            font-size: 48px;
            color: var(--cor-primaria);
            margin-bottom: 16px;
        }

        .login-header h1 {
            font-size: 24px;
            font-weight: 600;
            color: var(--cor-texto);
        }

        .login-header p {
            color: var(--cor-texto-claro);
            font-size: 16px;
            margin-top: 8px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--cor-texto);
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--cor-borda);
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .form-control {
            width: 100%;
            padding: 12px 12px 12px 45px;
            border: 1px solid var(--cor-borda);
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background-color: #fff;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--cor-primaria);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        .form-control:focus + i {
            color: var(--cor-primaria);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--cor-texto-claro);
            font-size: 14px;
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: var(--cor-primaria);
        }

        .forgot-link {
            color: var(--cor-primaria);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: var(--cor-primaria-hover);
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background-color: var(--cor-primaria);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            background-color: var(--cor-primaria-hover);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
            font-size: 14px;
        }

        .alert.show { display: flex; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .credentials-info {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin-top: 24px;
            text-align: left;
        }

        .credentials-info h3 {
            color: var(--cor-texto);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .credentials-info ul { list-style: none; padding: 0; }
        .credentials-info li {
            color: var(--cor-texto-claro);
            font-size: 13px;
            margin-bottom: 8px;
        }
        .credentials-info li:last-child { margin-bottom: 0; }
        .credentials-info strong { color: var(--cor-texto); }

        @media (max-width: 480px) {
            .login-card {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-snowflake icon"></i>
            <h1>Gestor Clima</h1>
            <p>Acesse sua conta para continuar</p>
        </div>
        
        <!-- Alertas -->
        <div id="alert" class="alert"></div>
        
        <?php if ($timeout): ?>
        <div class="alert alert-warning show">
            <i class="fas fa-clock"></i>
            <span>Sua sessão expirou. Faça login novamente.</span>
        </div>
        <?php endif; ?>
        
        <!-- Formulário -->
        <form id="loginForm">
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-group">
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="seu@email.com"
                        required
                        autocomplete="email"
                    >
                    <i class="fas fa-envelope"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha</label>
                <div class="input-group">
                    <input 
                        type="password" 
                        id="senha" 
                        name="senha" 
                        class="form-control" 
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            
            <div class="form-options">
                <label class="checkbox-label">
                    <input type="checkbox" id="lembrar" name="lembrar">
                    <span>Lembrar-me</span>
                </label>
                <a href="#" class="forgot-link">Esqueceu a senha?</a>
            </div>
            
            <button type="submit" class="btn-login" id="btnLogin">
                <span id="btnText">Entrar</span>
                <div class="loading-spinner" id="loadingSpinner"></div>
            </button>
        </form>
    </div>
    
    <script>
        // Elementos
        const loginForm = document.getElementById('loginForm');
        const btnLogin = document.getElementById('btnLogin');
        const btnText = document.getElementById('btnText');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const alertDiv = document.getElementById('alert');
        
        // Função para mostrar alerta
        function showAlert(message, type = 'error') {
            alertDiv.className = `alert alert-${type} show`;
            
            const icon = type === 'error' ? 'fa-exclamation-circle' : 
                        type === 'success' ? 'fa-check-circle' : 'fa-info-circle';
            
            alertDiv.innerHTML = `
                <i class="fas ${icon}"></i>
                <span>${message}</span>
            `;
            
            // Auto-hide após 5 segundos
            setTimeout(() => {
                alertDiv.classList.remove('show');
            }, 5000);
        }
        
        // Processar login
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            const senha = document.getElementById('senha').value;
            
            // Validações
            if (!email || !senha) {
                showAlert('Por favor, preencha todos os campos', 'error');
                return;
            }
            
            // Loading
            btnLogin.disabled = true;
            btnText.style.display = 'none';
            loadingSpinner.style.display = 'block';
            
            try {
                console.log('🔐 Tentando fazer login...', { email });
                
                const response = await fetch('controllers/AuthController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ email, senha }),
                    credentials: 'same-origin' // Incluir cookies de sessão
                });
                
                console.log('📡 Resposta recebida:', {
                    status: response.status,
                    statusText: response.statusText,
                    contentType: response.headers.get('content-type')
                });
                
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('❌ Resposta não é JSON:', text);
                    throw new Error('Servidor retornou resposta inválida');
                }
                
                const data = await response.json();
                console.log('📦 Dados recebidos:', data);
                
                // Verificar sucesso
                if (data.sucesso || data.success) {
                    showAlert('Login realizado com sucesso! Redirecionando...', 'success');
                    
                    // Redirecionar imediatamente (sem setTimeout)
                    window.location.href = 'index.php';
                    return;
                }
                
                // Erro de autenticação
                const errorMessage = data.erro || data.message || data.error || 'Erro ao fazer login';
                showAlert(errorMessage, 'error');
                
                // Reset form
                btnLogin.disabled = false;
                btnText.style.display = 'block';
                loadingSpinner.style.display = 'none';
                
            } catch (error) {
                console.error('❌ Erro capturado:', error);
                showAlert('Erro ao conectar com o servidor: ' + error.message, 'error');
                
                // Reset form
                btnLogin.disabled = false;
                btnText.style.display = 'block';
                loadingSpinner.style.display = 'none';
            }
        });
        
        // Auto-focus no email
        document.getElementById('email').focus();
    </script>
</body>
</html>
