<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <?php
        // Obtém o nome do arquivo atual
        $current_page = basename($_SERVER['PHP_SELF']);
        // Define o título com base na página
        $title = "Gestão de Estoque - Panificadora"; // Título padrão (para Dashboard)
        if ($current_page !== 'controle_estoque.php') {
            // Mapeia os nomes dos arquivos para os títulos das páginas
            $page_titles = [
                'registrar_venda.php' => 'Registrar Venda',
                'listar_produtos.php' => 'Listar Produtos',
                'adicionar_produto.php' => 'Adicionar Produto',
                'editar_produto.php' => 'Editar Produto',
                'planejamento_producao.php' => 'Planejamento de Produção',
                'gerenciar_promocoes.php' => 'Gerenciar Promoções',
                'editar_promocao.php' => 'Editar Promoção',
                'gerenciar_fornecedores.php' => 'Gerenciar Fornecedores',
                'editar_fornecedor.php' => 'Editar Fornecedor',
                'gerenciar_usuarios.php' => 'Gerenciar Usuários',
                'gerenciar_backups.php' => 'Gerenciar Backups',
                'relatorios.php' => 'Relatórios',
                'receitas.php' => 'Receitas',
                'desperdicio.php' => 'Desperdício',
                'historico_precos.php' => 'Histórico de Preços',
                'ver_logs.php' => 'Ver Logs',
                'exportar_dados.php' => 'Exportar Dados',
                'pesquisa_avancada.php' => 'Pesquisa Avançada',
            ];
            // Se a página atual estiver no mapeamento, usa o título correspondente
            $title = isset($page_titles[$current_page]) ? $page_titles[$current_page] : $title;
        }
        ?>
        <a class="navbar-brand" href="controle_estoque.php"><?php echo htmlspecialchars($title); ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="controle_estoque.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="registrar_venda.php">Registrar Venda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="listar_produtos.php">Listar Produtos</a>
                </li>
                <?php if (isset($_SESSION['perfil']) && in_array($_SESSION['perfil'], ['admin', 'gerente'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="gerenciamentoDropdown">Gerenciamento</a>
                        <ul class="dropdown-menu" aria-labelledby="gerenciamentoDropdown">
                            <li><a class="dropdown-item" href="adicionar_produto.php">Adicionar Produto</a></li>
                            <li><a class="dropdown-item" href="planejamento_producao.php">Planejamento de Produção</a></li>
                            <li><a class="dropdown-item" href="gerenciar_promocoes.php">Gerenciar Promoções</a></li>
                            <?php if (isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="gerenciar_fornecedores.php">Gerenciar Fornecedores</a></li>
                                <li><a class="dropdown-item" href="gerenciar_usuarios.php">Gerenciar Usuários</a></li>
                                <li><a class="dropdown-item" href="gerenciar_backups.php">Gerenciar Backups</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="relatoriosDropdown">Relatórios e Logs</a>
                        <ul class="dropdown-menu" aria-labelledby="relatoriosDropdown">
                            <li><a class="dropdown-item" href="relatorios.php">Relatórios</a></li>
                            <li><a class="dropdown-item" href="receitas.php">Receitas</a></li>
                            <li><a class="dropdown-item" href="desperdicio.php">Desperdício</a></li>
                            <li><a class="dropdown-item" href="historico_precos.php">Histórico de Preços</a></li>
                            <?php if (isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="ver_logs.php">Ver Logs</a></li>
                                <li><a class="dropdown-item" href="exportar_dados.php">Exportar Dados</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pesquisa_avancada.php">Pesquisa Avançada</a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Sair</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
    .navbar .dropdown:hover .dropdown-menu {
        display: block;
        opacity: 1;
        transform: translateY(0);
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .navbar .dropdown-menu {
        display: none;
        opacity: 0;
        transform: translateY(-10px);
        margin-top: 0; /* Remove margem padrão do Bootstrap */
        border-radius: 8px; /* Cantos arredondados */
        border: 1px solid #333; /* Borda preta minimalista */
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Sombra leve para profundidade */
        min-width: 200px; /* Garante uma largura mínima para o dropdown */
    }

    /* Alinha o dropdown com o texto */
    .navbar .dropdown {
        position: relative;
    }

    .navbar .dropdown-menu {
        left: 0; /* Alinha o dropdown à esquerda do elemento pai */
        transform-origin: top; /* Origem da transformação para a transição */
    }

    /* Garante que o dropdown não abra com clique */
    .navbar .dropdown-toggle::after {
        pointer-events: none; /* Impede que o clique no ícone abra o dropdown */
    }

    /* Ajusta o padding dos itens do dropdown para alinhar o texto com o fundo */
    .navbar {
        padding: 8px 16px; /* Ajusta o padding para melhor alinhamento */
    }
    .dropdown-item {
        width: 85%;
    }
</style>

<script>
// Garante que o dropdown funcione em dispositivos móveis (onde hover não é suportado)
document.querySelectorAll('.navbar .dropdown').forEach(dropdown => {
    dropdown.addEventListener('click', function (e) {
        if (window.innerWidth <= 991) { // Bootstrap's lg breakpoint
            const dropdownMenu = this.querySelector('.dropdown-menu');
            dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        }
    });
});
</script>