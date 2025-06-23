<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            margin-top: 0;
            border-radius: 8px;
            border: 1px solid #333;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            min-width: 200px;
        }

        .navbar .dropdown {
            position: relative;
        }

        .navbar .dropdown-menu {
            left: 0;
            transform-origin: top;
        }

        .navbar .dropdown-toggle::after {
            pointer-events: none;
        }

        .navbar {
            padding: 8px 16px;
        }

        .dropdown-item {
            width: 85%;
        }

        .switch-container {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            top: -1px;
            right: 25px;
            margin-left: auto;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #2196F3;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        .switch-label {
            color: #fff;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <?php
    // Garantir que a sessão está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Inicializar modo completo se não estiver definido
    if (!isset($_SESSION['modo_completo'])) {
        $_SESSION['modo_completo'] = false;
    }

    // Atualizar modo completo com base em requisição POST (do switch)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modo_completo'])) {
        $_SESSION['modo_completo'] = $_POST['modo_completo'] === 'true';
        // Responder com JSON para o AJAX
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    $isCompleteMode = $_SESSION['modo_completo'];
    ?>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            $title = "Gestão de Estoque - Panificadora";
            if ($current_page !== 'controle_estoque.php') {
                $page_titles = [
                    'registrar_venda.php' => 'Registrar Venda',
                    'listar_produtos.php' => 'Listar Produtos',
                    'adicionar_produto.php' => 'Adicionar Produto',
                    'planejamento_producao.php' => 'Planejamento de Produção',
                    'gerenciar_promocoes.php' => 'Gerenciar Promoções',
                    'gerenciar_fornecedores.php' => 'Gerenciar Fornecedores',
                    'gerenciar_usuarios.php' => 'Gerenciar Usuários',
                    'gerenciar_backups.php' => 'Gerenciar Backups',
                    'relatorios.php' => 'Relatórios',
                    'receitas.php' => 'Receitas',
                    'desperdicio.php' => 'Desperdício',
                    'historico_precos.php' => 'Histórico de Preços',
                    'ver_logs.php' => 'Ver Logs',
                    'exportar_dados.php' => 'Exportar Dados',
                    'pedido_personalizado.php' => 'Pedido Personalizado',
                    'pedidos_fornecedores.php' => 'Pedidos a Fornecedores',
                    'produtos_por_fornecedor.php' => 'Produtos por Fornecedor',
                    'editar_produto.php' => 'Editar Produto',
                ];
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
                    <?php if ($_SESSION['perfil'] === 'vendedor'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="registrar_venda.php">Registrar Venda</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="listar_produtos.php">Listar Produtos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pedido_personalizado.php">Pedido Personalizado</a>
                        </li>
                    <?php elseif ($_SESSION['perfil'] === 'gerente'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="listar_produtos.php">Listar Produtos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pedido_personalizado.php">Pedido Personalizado</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="gerenciamentoDropdown">Gerenciamento</a>
                            <ul class="dropdown-menu" aria-labelledby="gerenciamentoDropdown">
                                <li><a class="dropdown-item" href="adicionar_produto.php">Adicionar Produto</a></li>
                                <li><a class="dropdown-item" href="planejamento_producao.php">Planejamento de Produção</a></li>
                                <li><a class="dropdown-item" href="gerenciar_promocoes.php">Gerenciar Promoções</a></li>
                                <li><a class="dropdown-item" href="pedidos_fornecedores.php">Pedidos a Fornecedores</a></li>
                                <li><a class="dropdown-item" href="produtos_por_fornecedor.php">Produtos por Fornecedor</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="relatoriosDropdown">Relatórios e Logs</a>
                            <ul class="dropdown-menu" aria-labelledby="relatoriosDropdown">
                                <li><a class="dropdown-item" href="receitas.php">Receitas</a></li>
                                <li><a class="dropdown-item" href="desperdicio.php">Desperdício</a></li>
                                <li><a class="dropdown-item" href="historico_precos.php">Histórico de Preços</a></li>
                                <li><a class="dropdown-item" href="exportar_dados.php">Exportar Dados</a></li>
                            </ul>
                        </li>
                    <?php elseif ($_SESSION['perfil'] === 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="gerenciamentoDropdown">Gerenciamento</a>
                            <ul class="dropdown-menu" aria-labelledby="gerenciamentoDropdown">
                                <li><a class="dropdown-item" href="gerenciar_fornecedores.php">Gerenciar Fornecedores</a></li>
                                <li><a class="dropdown-item" href="gerenciar_usuarios.php">Gerenciar Usuários</a></li>
                                <li><a class="dropdown-item" href="gerenciar_backups.php">Gerenciar Backups</a></li>
                                <?php if ($isCompleteMode): ?>
                                    <li><a class="dropdown-item" href="adicionar_produto.php">Adicionar Produto</a></li>
                                    <li><a class="dropdown-item" href="planejamento_producao.php">Planejamento de Produção</a></li>
                                    <li><a class="dropdown-item" href="gerenciar_promocoes.php">Gerenciar Promoções</a></li>
                                    <li><a class="dropdown-item" href="pedidos_fornecedores.php">Pedidos a Fornecedores</a></li>
                                    <li><a class="dropdown-item" href="produtos_por_fornecedor.php">Produtos por Fornecedor</a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="relatoriosDropdown">Relatórios e Logs</a>
                            <ul class="dropdown-menu" aria-labelledby="relatoriosDropdown">
                                <li><a class="dropdown-item" href="ver_logs.php">Ver Logs</a></li>
                                <li><a class="dropdown-item" href="exportar_dados.php">Exportar Dados</a></li>
                                <?php if ($isCompleteMode): ?>
                                    <li><a class="dropdown-item" href="receitas.php">Receitas</a></li>
                                    <li><a class="dropdown-item" href="desperdicio.php">Desperdício</a></li>
                                    <li><a class="dropdown-item" href="historico_precos.php">Histórico de Preços</a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <?php if ($isCompleteMode): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="listar_produtos.php">Listar Produtos</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="pedido_personalizado.php">Pedido Personalizado</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if ($_SESSION['perfil'] === 'admin'): ?>
                        <div class="switch-container">
                            <label class="switch">
                                <input type="checkbox" id="modeSwitch" <?php echo $isCompleteMode ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <span class="switch-label" id="modeLabel">Modo Completo <?php echo $isCompleteMode ? 'On' : 'Off'; ?></span>
                        </div>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modeSwitch = document.getElementById('modeSwitch');
            const modeLabel = document.getElementById('modeLabel');

            if (modeSwitch && modeLabel) {
                modeSwitch.addEventListener('change', function() {
                    const isCompleteMode = this.checked;

                    // Enviar requisição AJAX para atualizar a sessão
                    fetch(window.location.pathname, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'modo_completo=' + isCompleteMode
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                modeLabel.textContent = isCompleteMode ? 'Modo Completo On' : 'Modo Completo Off';
                                // Recarregar a página para refletir as mudanças na navbar
                                window.location.reload();
                            }
                        })
                        .catch(error => console.error('Erro ao atualizar modo:', error));
                });
            }
        });
    </script>
</body>

</html>