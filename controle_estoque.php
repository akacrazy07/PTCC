<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

// Verificar o modo do admin (essencial ou completo)
$isCompleteMode = isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin' && isset($_GET['mode']) ? $_GET['mode'] === 'complete' : false;

// Consultas para dados essenciais
$sql_produtos = "SELECT COUNT(*) as total_produtos, SUM(quantidade) as total_estoque FROM produtos";
$resultado_produtos = $conexao->query($sql_produtos);
$dados_produtos = $resultado_produtos->fetch_assoc();

// Consultas que só aparecem no Modo Completo
if ($isCompleteMode || $_SESSION['perfil'] !== 'admin') {
    $sql_vendas = "SELECT SUM(quantidade_vendida * preco_unitario_venda) as total_vendas FROM vendas WHERE DATE(data_venda) = CURDATE()";
    $resultado_vendas = $conexao->query($sql_vendas);
    $dados_vendas = $resultado_vendas->fetch_assoc();

    // Produtos com estoque baixo
    $sql_estoque_baixo = "SELECT nome_produto, quantidade, estoque_minimo FROM produtos WHERE quantidade < estoque_minimo";
    $resultado_estoque_baixo = $conexao->query($sql_estoque_baixo);
    $produtos_baixo = [];
    while ($produto = $resultado_estoque_baixo->fetch_assoc()) {
        $produtos_baixo[] = $produto;
    }

    // Produtos próximos do vencimento (menos de 7 dias)
    $sql_validade = "SELECT nome_produto, data_validade FROM produtos WHERE data_validade IS NOT NULL AND DATEDIFF(data_validade, CURDATE()) <= 7 AND data_validade >= CURDATE()";
    $resultado_validade = $conexao->query($sql_validade);
    $produtos_validade = [];
    while ($produto = $resultado_validade->fetch_assoc()) {
        $produtos_validade[] = $produto;
    }
}

// Promoções ativas (visível para admin e gerente)
$promocoes_ativas = [];
if (in_array($_SESSION['perfil'], ['admin', 'gerente'])) {
    $sql_promocoes = "SELECT p.*, pr.nome_produto, c.nome as nome_categoria 
                      FROM promocoes p 
                      LEFT JOIN produtos pr ON p.produto_id = pr.id 
                      LEFT JOIN categorias c ON p.categoria_id = c.id 
                      WHERE p.ativa = 1 AND CURDATE() BETWEEN p.data_inicio AND p.data_fim";
    $resultado_promocoes = $conexao->query($sql_promocoes);
    while ($row = $resultado_promocoes->fetch_assoc()) {
        $promocoes_ativas[] = $row;
    }
}

// Listar pedidos pendentes para autorização (apenas admin)
$pedidos_pendentes = [];
if ($_SESSION['perfil'] === 'admin') {
    $sql_pedidos = "SELECT pf.*, f.nome AS nome_fornecedor, c.nome AS categoria_nome 
                    FROM pedidos_fornecedores pf 
                    JOIN fornecedores f ON pf.fornecedor_id = f.id 
                    LEFT JOIN categorias c ON pf.categoria_id = c.id 
                    WHERE pf.status = 'pendente' ORDER BY pf.criado_em DESC";
    $resultado_pedidos = $conexao->query($sql_pedidos);
    while ($row = $resultado_pedidos->fetch_assoc()) {
        // Calcular valor total do pedido
        $valor_total = ($row['quantidade'] * $row['preco_unitario']) + $row['imposto_distrital'] + $row['imposto_nacional'] + $row['taxa_entrega'] + $row['outras_taxas'];
        $row['valor_total'] = $valor_total;
        $pedidos_pendentes[] = $row;
    }

    // Confirmar chegada (apenas admin)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_chegada'])) {
        $pedido_id = intval($_POST['pedido_id']);
        $conexao->begin_transaction();
        try {
            $sql_pedido = "SELECT * FROM pedidos_fornecedores WHERE id = ?";
            $stmt_pedido = $conexao->prepare($sql_pedido);
            $stmt_pedido->bind_param("i", $pedido_id);
            $stmt_pedido->execute();
            $resultado_pedido = $stmt_pedido->get_result();
            if ($pedido = $resultado_pedido->fetch_assoc()) {
                $data_validade = $pedido['data_validade'] ?? null;
                $categoria_id = $pedido['categoria_id'] ?? null;

                // Verificar se o produto já existe na tabela produtos (mesmo nome e fornecedor)
                $sql_check = "SELECT id, quantidade FROM produtos WHERE nome_produto = ? AND fornecedor_id = ?";
                $stmt_check = $conexao->prepare($sql_check);
                $stmt_check->bind_param("si", $pedido['nome_produto'], $pedido['fornecedor_id']);
                $stmt_check->execute();
                $resultado_check = $stmt_check->get_result();
                $produto_existente = $resultado_check->fetch_assoc();
                $stmt_check->close();

                if ($produto_existente) {
                    // Produto existe: atualizar quantidade e data_validade
                    $nova_quantidade = $produto_existente['quantidade'] + $pedido['quantidade'];
                    $sql_update = "UPDATE produtos SET quantidade = ?, data_validade = ?, categoria_id = ? WHERE id = ?";
                    $stmt_update = $conexao->prepare($sql_update);
                    $stmt_update->bind_param("issi", $nova_quantidade, $data_validade, $categoria_id, $produto_existente['id']);
                    $stmt_update->execute();
                    $stmt_update->close();
                } else {
                    // Produto não existe: inserir novo
                    $sql_produto = "INSERT INTO produtos (nome_produto, quantidade, preco, fornecedor_id, data_validade, categoria_id) 
                                    VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt_produto = $conexao->prepare($sql_produto);
                    $stmt_produto->bind_param("sidssi", $pedido['nome_produto'], $pedido['quantidade'], $pedido['preco_unitario'], $pedido['fornecedor_id'], $data_validade, $categoria_id);
                    $stmt_produto->execute();
                    $stmt_produto->close();
                }

                // Atualizar status do pedido para 'confirmado'
                $sql_update_pedido = "UPDATE pedidos_fornecedores SET status = 'confirmado' WHERE id = ?";
                $stmt_update_pedido = $conexao->prepare($sql_update_pedido);
                $stmt_update_pedido->bind_param("i", $pedido_id);
                $stmt_update_pedido->execute();
                $stmt_update_pedido->close();

                $conexao->commit();
                $mensagem = "Chegada do pedido autorizada! Produto atualizado no estoque.";
            } else {
                throw new Exception("Pedido não encontrado!");
            }
            $stmt_pedido->close();
        } catch (Exception $e) {
            $conexao->rollback();
            $mensagem = "Erro ao autorizar chegada: " . $e->getMessage();
        }
    }

    // Recusar pedido (apenas admin)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recusar_pedido'])) {
        $pedido_id = intval($_POST['pedido_id']);
        $sql_update = "UPDATE pedidos_fornecedores SET status = 'negado' WHERE id = ?";
        $stmt_update = $conexao->prepare($sql_update);
        $stmt_update->bind_param("i", $pedido_id);
        if ($stmt_update->execute()) {
            $mensagem = "Pedido recusado com sucesso.";
        } else {
            $mensagem = "Erro ao recusar pedido: " . $conexao->error;
        }
        $stmt_update->close();
    }
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Estoque Panificadora</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h2>Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['nome_usuario']); ?>!</h2>
        <div class="dashboard-info">
            <p>Total de Produtos Cadastrados: <?php echo $dados_produtos['total_produtos']; ?></p>
            <p>Quantidade em Estoque: <?php echo $dados_produtos['total_estoque'] ?? 0; ?></p>
            <?php if ($isCompleteMode || $_SESSION['perfil'] !== 'admin'): ?>
                <p>Vendas Hoje: R$ <?php echo number_format($dados_vendas['total_vendas'] ?? 0, 2, ',', '.'); ?></p>
            <?php endif; ?>
        </div>
        <?php if (in_array($_SESSION['perfil'], ['admin', 'gerente']) && !empty($promocoes_ativas)): ?>
            <div class="alertas-promocoes">
                <h3>Promoções Ativas</h3>
                <ul>
                    <?php foreach ($promocoes_ativas as $promocao): ?>
                        <li>
                            <?php echo htmlspecialchars($promocao['nome']); ?>:
                            <?php
                            if ($promocao['tipo'] === 'percentual') {
                                echo "Desconto de {$promocao['valor']}%";
                            } else {
                                echo "Leve {$promocao['valor']}, Pague " . ($promocao['valor'] - 1);
                            }
                            if ($promocao['nome_produto']) {
                                echo " em " . htmlspecialchars($promocao['nome_produto']);
                            } elseif ($promocao['nome_categoria']) {
                                echo " na categoria " . htmlspecialchars($promocao['nome_categoria']);
                            }
                            echo " (até " . date('d/m/Y', strtotime($promocao['data_fim'])) . ")";
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (($isCompleteMode || $_SESSION['perfil'] !== 'admin') && !empty($produtos_baixo)): ?>
            <div class="alertas-estoque">
                <h3>Alertas de Estoque Baixo</h3>
                <ul>
                    <?php foreach ($produtos_baixo as $produto): ?>
                        <li><?php echo htmlspecialchars($produto['nome_produto']) . ": " . $produto['quantidade'] . " (mínimo: " . $produto['estoque_minimo'] . ")"; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (($isCompleteMode || $_SESSION['perfil'] !== 'admin') && !empty($produtos_validade)): ?>
            <div class="alertas-validade">
                <h3>Alertas de Validade Próxima</h3>
                <ul>
                    <?php foreach ($produtos_validade as $produto): ?>
                        <?php $dias_restantes = (new DateTime())->diff(new DateTime($produto['data_validade']))->days; ?>
                        <li><?php echo htmlspecialchars($produto['nome_produto']) . ": Vence em " . date('d/m/Y', strtotime($produto['data_validade'])) . " (faltam $dias_restantes dias)"; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($_SESSION['perfil'] === 'admin' && !empty($pedidos_pendentes)): ?>
            <h3 class="mt-5">Autorizar Chegada de Produtos</h3>
            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($mensagem); ?></div>
            <?php endif; ?>
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Fornecedor</th>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Quantidade</th>
                        <th>Preço Unitário</th>
                        <th>Imposto Distrital</th>
                        <th>Imposto Nacional</th>
                        <th>Taxa de Entrega</th>
                        <th>Outras Taxas</th>
                        <th>Valor Total</th>
                        <th>Data de Validade</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos_pendentes as $pedido): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pedido['nome_fornecedor']); ?></td>
                            <td><?php echo htmlspecialchars($pedido['nome_produto']); ?></td>
                            <td><?php echo htmlspecialchars($pedido['categoria_nome'] ?? 'Sem categoria'); ?></td>
                            <td><?php echo $pedido['quantidade']; ?></td>
                            <td>R$ <?php echo number_format($pedido['preco_unitario'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($pedido['imposto_distrital'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($pedido['imposto_nacional'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($pedido['taxa_entrega'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($pedido['outras_taxas'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
                            <td><?php echo $pedido['data_validade'] ? date('d/m/Y', strtotime($pedido['data_validade'])) : 'Não definida'; ?></td>
                            <td>
                                <form method="POST" action="controle_estoque.php" style="display:inline;">
                                    <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                    <button type="submit" name="confirmar_chegada" class="btn btn-success btn-sm">Confirmar Chegada</button>
                                </form>
                                <form method="POST" action="controle_estoque.php" style="display:inline;">
                                    <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                    <button type="submit" name="recusar_pedido" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja recusar este pedido?')">Recusar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if ($_SESSION['perfil'] === 'admin'): ?>
        <div class="overlay" id="backupOverlay">
            <div class="popup">
                <h3>Lembrete de Backup</h3>
                <p>Fez backup do sistema hoje?</p>
                <button class="btn-feito" onclick="marcarFeito()">Feito</button>
                <button class="btn-lembrar" onclick="lembrarMaisTarde()">Lembrar mais tarde</button>
            </div>
        </div>

        <script>
            function mostrarAvisoBackup() {
                const overlay = document.getElementById('backupOverlay');
                const ultimaAcao = localStorage.getItem('backupAcao');
                const ultimaData = localStorage.getItem('backupData');
                const agora = new Date();
                const hoje = agora.toISOString().split('T')[0]; // Formato YYYY-MM-DD

                if (ultimaAcao === 'feito' && ultimaData === hoje) {
                    return;
                }

                if (ultimaAcao === 'lembrar') {
                    const ultimaHora = new Date(localStorage.getItem('backupHora'));
                    const horasPassadas = (agora - ultimaHora) / (1000 * 60 * 60);
                    if (horasPassadas < 4) {
                        return;
                    }
                }

                overlay.style.display = 'flex';
            }

            function marcarFeito() {
                const overlay = document.getElementById('backupOverlay');
                const hoje = new Date().toISOString().split('T')[0];
                localStorage.setItem('backupAcao', 'feito');
                localStorage.setItem('backupData', hoje);
                overlay.style.display = 'none';
            }

            function lembrarMaisTarde() {
                const overlay = document.getElementById('backupOverlay');
                localStorage.setItem('backupAcao', 'lembrar');
                localStorage.setItem('backupHora', new Date().toISOString());
                overlay.style.display = 'none';
            }

            window.onload = function() {
                mostrarAvisoBackup();
            };
        </script>
    <?php endif; ?>
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <span class="footer-link mx-3" onclick="openModal('termsModal')">Termos de Usabilidade</span>
            <span class="footer-link mx-3" onclick="openModal('aboutModal')">Sobre</span>
            <span class="footer-link mx-3" onclick="openModal('contactModal')">Contatos</span>
        </div>
    </footer>

    <div id="modalOverlay" class="modal-overlay"></div>

    <div id="termsModal" class="custom-modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('termsModal')">×</span>
            <h2>Termos de Usabilidade</h2>
            <p>
                Bem-vindo aos Termos de Usabilidade da RBS Ware. Ao utilizar este sistema, você concorda em:
            <ul>
                <li>Não compartilhar suas credenciais de login com terceiros.</li>
                <li>Utilizar o sistema apenas para fins autorizados pela sua organização.</li>
                <li>Relatar qualquer problema ou bug ao suporte imediatamente.</li>
                <li>Manter a confidencialidade das informações acessadas através do sistema.</li>
                <li>Não realizar tentativas de acesso não autorizado a outras contas ou sistemas.</li>
                <li>Não utilizar o sistema para atividades ilegais ou não éticas.</li>
                <li>Respeitar as políticas de segurança e privacidade da sua organização.</li>
            </ul>
            A RBS Ware não se responsabiliza por usos indevidos do sistema. Para mais informações, entre em contato conosco.
            </p>
        </div>
    </div>

    <div id="aboutModal" class="custom-modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('aboutModal')">×</span>
            <h2>Sobre</h2>
            <p>
                A RBS Ware é uma empresa especializada em soluções de software para gestão de negócios. Fundada em 2021, nossa missão é simplificar processos e aumentar a eficiência de pequenas e médias empresas, através da nossa tecnologia web.
                Nosso sistema de Gestão de Estoque foi projetado para ajudar você a gerenciar produtos, vendas e fornecedores de forma prática e segura.
            </p>
        </div>
    </div>

    <div id="contactModal" class="custom-modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('contactModal')">×</span>
            <h2>Contatos</h2>
            <p>
                Entre em contato com a RBS Ware:<br>
                <strong>Telefone:</strong> (61) 98765-4321<br>
                <strong>E-mail:</strong> contato@rbsware.net<br>
                <strong>Site:</strong> <a href="http://www.rbsware.net" target="_blank">www.rbsware.net</a>
            </p>
        </div>
    </div>

    <style>
        footer {
            background-color: #343a40;
            color: #343a40;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
        }

        .footer-link {
            cursor: pointer;
            color: #000000;
            text-decoration: none;
        }

        .footer-link:hover {
            text-decoration: underline;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }

        .custom-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1001;
        }

        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #333;
        }

        .close-btn:hover {
            color: #ff0000;
        }

        .modal-content h2 {
            margin-top: 0;
            margin-bottom: 15px;
        }

        .modal-content p {
            margin: 0;
            line-height: 1.6;
        }
    </style>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            document.getElementById('modalOverlay').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.getElementById('modalOverlay').style.display = 'none';
        }

        document.getElementById('modalOverlay').addEventListener('click', function() {
            document.querySelectorAll('.custom-modal').forEach(modal => {
                modal.style.display = 'none';
            });
            this.style.display = 'none';
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>

</html>