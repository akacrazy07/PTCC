<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true ||
!in_array($_SESSION['perfil'], ['admin', 'gerente'])) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';
$mensagem = '';
$calculo = '';

// cadastrar receita
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['nome']) && isset($_POST['ingredientes'])) {
        $nome = $_POST['nome'];
        $ingredientes = json_encode($_POST['ingredientes']); // salvar como JSON
        if (empty($nome) || empty($_POST['ingredientes'])) {
            $mensagem = "Erro: Nome ou ingredientes vazios!";
        } else {
            $stmt = $conexao->prepare("INSERT INTO receitas (nome, ingredientes) VALUES (?, ?)");
            $stmt->bind_param("ss", $nome, $ingredientes);
            if ($stmt->execute()) {
                $mensagem = "Receita cadastrada com sucesso!";
            } else {
                $mensagem = "Erro ao cadastrar receita: " . $conexao->error;
            }
            $stmt->close();
        }
        // calcular insumos
    } elseif (isset($_POST['calcular']) && isset($_POST['receita_id']) && isset($_POST['quantidade'])) {
        $receita_id = intval($_POST['receita_id']);
        $quantidade = intval($_POST['quantidade']);
        if ($quantidade <= 0) {
            $calculo = "Erro: Quantidade inválida!";
        } else {
            $stmt = $conexao->prepare("SELECT nome, ingredientes FROM receitas WHERE id = ?");
            $stmt->bind_param("i", $receita_id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            if ($receita = $resultado->fetch_assoc()) {
                $ingredientes = json_decode($receita['ingredientes'], true);
                $calculo = "Para $quantidade unidades de " . htmlspecialchars($receita['nome']) . ":<br>";
                foreach ($ingredientes as $nome => $quant) {
                    $valor = floatval(preg_replace('/[^0-9.]/', '', $quant)); // extrai valor (número) do ingrediente
                    $unidade = preg_replace('/[0-9.]+/', '', $quant); // extrai unidade (kg, g, etc.)
                    $total = $valor * $quantidade;
                    $calculo .= "- " . htmlspecialchars($nome) . ": " . $total . $unidade . "<br>";
                }
            } else {
                $calculo = "Receita não encontrada!";
            }
            $stmt->close();
        }
    }
}
// listar receitas
$sql_receitas = "SELECT id, nome FROM receitas";
$resultado_receitas = $conexao->query($sql_receitas);
$receitas = [];
while ($row = $resultado_receitas->fetch_assoc()) {
    $receitas[] = $row;
}
// excluir a receita
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id_excluir = intval($_GET['excluir']);
    $stmt = $conexao->prepare("DELETE FROM receitas WHERE id = ?");
    $stmt->bind_param("i", $id_excluir);
    if ($stmt->execute()) {
        $mensagem = "Receita excluída com sucesso!";
    } else {
        $mensagem = "Erro ao excluir receita: " . $conexao->error;
    }
    $stmt->close();
}
$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Receitas - Panificadora (TCC Offline)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Gestão de Estoque - Panificadora</h1>
        <nav>
            <a href="controle_estoque.php">Dashboard</a>
            <?php if (in_array($_SESSION['perfil'], ['admin', 'gerente'])): ?>
                <a href="adicionar_produto.php">Adicionar Produto</a>
                <a href="planejamento_producao.php">Planejamento de Produção</a>
            <?php endif; ?>
            <a href="registrar_venda.php">Registrar Venda</a>
            <a href="listar_produtos.php">Listar Produtos</a>
            <?php if (in_array($_SESSION['perfil'], ['admin', 'gerente'])): ?>
                <a href="relatorios.php">Relatórios</a>
                <a href="receitas.php">Receitas</a>
                <a href="desperdicio.php">Desperdício</a>
                <a href="gerenciar_promocoes.php">Gerenciar Promoções</a>
            <?php endif; ?>
            <?php if ($_SESSION['perfil'] === 'admin'): ?>
                <a href="gerenciar_fornecedores.php">Gerenciar Fornecedores</a>
                <a href="gerenciar_usuarios.php">Gerenciar Usuários</a>
                <a href="ver_logs.php">Ver Logs</a>
                <a href="exportar_dados.php">Exportar Dados</a>
                <a href="gerenciar_backups.php">Gerenciar Backups</a>
            <?php endif; ?>
            <a href="logout.php">Sair</a>
        </nav>
    </header>
    <div class="container">
        <h2>Gestão de Receitas</h2>
        <h3>Cadastrar Receita</h3>
        <?php if (!empty($mensagem)): ?>
            <p class="mensagem"><?php echo $mensagem; ?></p>
        <?php endif; ?>
        <form action="receitas.php" method="post">
            <input type="text" name="nome" placeholder="Nome da Receita" required>
            <p>Ingredientes (por unidade):</p>
            <input type="text" name="ingredientes[farinha]" placeholder="Farinha (ex: 1kg)">
            <input type="text" name="ingredientes[sal]" placeholder="Sal (ex: 20g)">
            <input type="text" name="ingredientes[agua]" placeholder="Água (ex: 500ml)">
            <button type="submit">Cadastrar Receita</button>
        </form>
        <h3>Calcular Insumos</h3>
        <?php if (!empty($calculo)): ?>
            <p class="calculo"><?php echo $calculo; ?></p>
        <?php endif; ?>
        <form action="receitas.php" method="post">
            <select name="receita_id" required>
                <option value="">Selecione uma receita</option>
                <?php foreach ($receitas as $receita): ?>
                    <option value="<?php echo $receita['id']; ?>"><?php echo htmlspecialchars($receita['nome']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="quantidade" placeholder="Quantidade a produzir" required min="1">
            <button type="submit" name="calcular">Calcular</button>
        </form>
        <h3>Receitas Cadastradas</h3>
        <?php if (empty($receitas)): ?>
            <p>Nenhuma receita cadastrada ainda.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($receitas as $receita): ?>
                    <li><?php echo htmlspecialchars($receita['nome']); ?></li>
                    <a href="receitas.php?excluir=<?php echo $receita['id']; ?>" class="btn-excluir" onclick="return confirm('Tem certeza que quer excluir esta receita?');">Excluir</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>