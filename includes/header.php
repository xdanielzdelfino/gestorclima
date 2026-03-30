<?php
/**
 * Header Reutilizável
 * 
 * Componente de cabeçalho com informações do usuário logado
 * 
 * @package GestorClima
 * @version 1.0.0
 */

// Requer que o usuário esteja definido
if (!isset($usuario) || !is_array($usuario)) {
    $usuario = isset($_SESSION['usuario']) && is_array($_SESSION['usuario']) ? $_SESSION['usuario'] : [
        'nome' => '',
        'nivel' => ''
    ];
}

// Tradução dos níveis
$niveisTraducao = [
    'admin' => 'Administrador',
    'operador' => 'Operador',
    'visualizador' => 'Visualizador'
];

$nivelRaw = $usuario['nivel'] ?? '';
$nivelUsuario = $niveisTraducao[$nivelRaw] ?? ($nivelRaw !== '' ? ucfirst($nivelRaw) : '');
?>
<!-- HEADER -->
<header class="header">
    <div class="header-left">
        <button class="menu-toggle" id="menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="logo">
            <i class="logo-icon fas fa-snowflake"></i>
            <span class="logo-text">Gestor Clima</span>
        </div>
    </div>
    
    <div class="header-right">
        <div class="header-user">
            <div class="header-user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="header-user-info">
                <span class="header-user-name"><?php echo htmlspecialchars($usuario['nome'] ?? ''); ?></span>
                <span class="header-user-role user-role-badge <?php echo htmlspecialchars($usuario['nivel'] ?? ''); ?>">
                    <?php echo $nivelUsuario; ?>
                </span>
            </div>
        </div>
        <button class="btn-logout" onclick="fazerLogout()" title="Sair do Sistema">
            <i class="fas fa-sign-out-alt"></i>
        </button>
    </div>
</header>
