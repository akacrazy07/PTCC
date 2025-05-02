<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

$sql_produtos = "SELECT COUNT(*) as total_produtos, SUM(quantidade) as total_estoque FROM produtos";
$resultado_produtos = $conexao->query($sql_produtos);
$dados_produtos = $resultado_produtos->fetch_assoc();

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

// Promoções ativas (apenas admin e gerente)
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
            <?php if (in_array($_SESSION['perfil'], ['admin', 'gerente'])): ?>
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
        <?php if (!empty($produtos_baixo)): ?>
            <div class="alertas-estoque">
                <h3>Alertas de Estoque Baixo</h3>
                <ul>
                    <?php foreach ($produtos_baixo as $produto): ?>
                        <li><?php echo htmlspecialchars($produto['nome_produto']) . ": " . $produto['quantidade'] . " (mínimo: " . $produto['estoque_minimo'] . ")"; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (!empty($produtos_validade)): ?>
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
                // Não mostra se o backup foi marcado como "Feito" hoje
                return;
            }

            if (ultimaAcao === 'lembrar') {
                const ultimaHora = new Date(localStorage.getItem('backupHora'));
                const horasPassadas = (agora - ultimaHora) / (1000 * 60 * 60); // Diferença em horas
                if (horasPassadas < 4) {
                    // Não mostra se "Lembrar mais tarde" foi clicado há menos de 4 horas
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

        // Mostrar o aviso ao carregar a página
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

    <!-- Overlay para escurecer e borrar o fundo -->
    <div id="modalOverlay" class="modal-overlay"></div>

    <!-- Modal para Termos de Usabilidade -->
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
                    <li>Respeitar as políticas de privacidade e proteção de dados.</li>
                </ul>
                A RBS Ware não se responsabiliza por usos indevidos do sistema. Para mais informações, entre em contato conosco.
            </p>
        </div>
    </div>

    <!-- Modal para Sobre -->
    <div id="aboutModal" class="custom-modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('aboutModal')">×</span>
            <h2>Sobre</h2>
            <p>
                A RBS Ware é uma empresa fictícia especializada em soluções de software para gestão de negócios. Fundada em 2020, nossa missão é simplificar processos e aumentar a eficiência de pequenas e médias empresas, como panificadoras, através de tecnologia inovadora.
                Nosso sistema de Gestão de Estoque foi projetado para ajudar você a gerenciar produtos, vendas e fornecedores de forma prática e segura.
            </p>
        </div>
    </div>

    <!-- Modal para Contatos -->
    <div id="contactModal" class="custom-modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('contactModal')">×</span>
            <h2>Contatos</h2>
            <p>
                Entre em contato com a RBS Ware:<br>
                <strong>Telefone:</strong> (11) 98765-4321<br>
                <strong>E-mail:</strong> contato@rbsware.net<br>
                <strong>Site:</strong> <a href="http://www.rbsware.net" target="_blank">www.rbsware.net</a>
            </p>
        </div>
    </div>

    <style>
        /* Estilo do footer */
        footer {
            background-color: #343a40;
            color: #343a40;
            position: relative;
        }
        .footer-link {
            cursor: pointer;
            color: #000000;
            text-decoration: none;
        }
        .footer-link:hover {
            text-decoration: underline;
        }

        /* Estilo do overlay (fundo escurecido e borrado) */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px); /* Efeito de blur no fundo */
            z-index: 1000;
        }

        /* Estilo do modal */
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
            border-radius: 15px; /* Bordas arredondadas */
            max-width: 500px;
            width: 90%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        /* Estilo do botão de fechar (X) */
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

        /* Estilo do conteúdo do modal */
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
        // Função para abrir o modal
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            document.getElementById('modalOverlay').style.display = 'block';
        }

        // Função para fechar o modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.getElementById('modalOverlay').style.display = 'none';
        }

        // Fechar o modal ao clicar no overlay
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