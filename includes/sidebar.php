<?php
/**
 * Sidebar Reutilizável
 * 
 * Menu lateral de navegação
 * 
 * @package GestorClima
 * @version 1.0.0
 */

// Detectar página atual
$paginaAtual = basename($_SERVER['PHP_SELF'], '.php');

// Função para verificar se o link está ativo
function isLinkAtivo($pagina) {
    global $paginaAtual;
    $urlAtual = $_SERVER['REQUEST_URI'];
    if ($pagina === 'climatizadores' && (strpos($urlAtual, 'climatizadores') !== false || strpos($urlAtual, 'manutencoes') !== false)) {
        return 'active';
    }
    return $paginaAtual === $pagina ? 'active' : '';
}

// Detectar se está em subpasta (views)
$basePath = strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '';
?>
<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <h1 class="sidebar-brand-title">
            <i class="fas fa-snowflake"></i> Gestor Clima
        </h1>
        <p class="sidebar-brand-subtitle">Sistema de Locações</p>
    </div>
    
    <nav>
        <ul class="sidebar-nav">
            <li class="sidebar-nav-item">
                <a href="<?php echo $basePath; ?>index.php" class="sidebar-nav-link <?php echo isLinkAtivo('index'); ?>">
                    <i class="sidebar-nav-icon fas fa-chart-line"></i>
                    <span class="sidebar-nav-text">Dashboard</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="<?php echo $basePath; ?>views/clientes.php" class="sidebar-nav-link <?php echo isLinkAtivo('clientes'); ?>">
                    <i class="sidebar-nav-icon fas fa-users"></i>
                    <span class="sidebar-nav-text">Clientes</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="<?php echo $basePath; ?>views/climatizadores.php" class="sidebar-nav-link <?php echo isLinkAtivo('climatizadores'); ?>">
                    <i class="sidebar-nav-icon fas fa-fan"></i>
                    <span class="sidebar-nav-text">Climatizadores</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="<?php echo $basePath; ?>views/locacoes.php" class="sidebar-nav-link <?php echo isLinkAtivo('locacoes'); ?>">
                    <i class="sidebar-nav-icon fas fa-file-contract"></i>
                    <span class="sidebar-nav-text">Locações</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
