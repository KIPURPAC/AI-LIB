<?php
session_start();
include 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Obtém o ID da IA a ser exibida
$ia_id = $_GET['id'];

// Consulta os detalhes da IA (com JOIN para categoria)
$stmt = $conn->prepare("
    SELECT ia.*, c.nome AS categoria_nome 
    FROM inteligencias_artificiais ia
    LEFT JOIN categorias c ON ia.categoria_id = c.id
    WHERE ia.id = :id
");
$stmt->execute(['id' => $ia_id]);
$ia = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($ia)) {
    echo "IA não encontrada.";
    exit();
}

$is_editor = isset($_SESSION['is_editor']) && $_SESSION['is_editor'] == 1;
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Detalhes da IA</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        function confirmarRemocao() {
            return confirm("Tem certeza que deseja remover esta IA?");
        }
    </script>
</head>
<body>
    <div class="detalhes-container">
        <h1><?php echo $ia['nome']; ?></h1>

        <?php if (!empty($ia['imagem_logotipo'])): ?>
            <div class="logotipo-container">
                <img src="<?php echo $ia['imagem_logotipo']; ?>" alt="Logotipo da IA" width="150">
            </div>
        <?php endif; ?>

        <div class="ia-detalhes">
            <p><strong>Modelo:</strong> <?php echo $ia['modelo']; ?></p>
            <p><strong>Categoria:</strong> 
        <?php 
            if (!empty($ia['categoria_nome'])) {
                echo $ia['categoria_nome'];
            } else {
                echo "<em>Sem categoria definida</em>";
            }
        ?>
    </p>
            <p><strong>Comportamento:</strong> <?php echo $ia['comportamento']; ?></p>
            <p><strong>Audiência:</strong> <?php echo $ia['audiencia']; ?></p>
            <p><strong>Preço:</strong> <?php echo $ia['preco']; ?></p>
            <p><strong>Descrição:</strong> <?php echo $ia['descricao']; ?></p>
        </div>


        <h2>Imagens da IA</h2>
        <div class="ia-imagens">
            <?php if (!empty($ia['imagem_login'])): ?>
                <div class="imagem-container">
                    <img src="<?php echo $ia['imagem_login']; ?>" alt="Imagem de Login">
                    <p>Imagem de Login</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($ia['imagem_interface'])): ?>
                <div class="imagem-container">
                    <img src="<?php echo $ia['imagem_interface']; ?>" alt="Imagem de Interface">
                    <p>Imagem de Interface</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($ia['imagem_resultados'])): ?>
                <div class="imagem-container">
                    <img src="<?php echo $ia['imagem_resultados']; ?>" alt="Imagem de Resultados">
                    <p>Imagem de Resultados</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($ia['imagem_historico'])): ?>
                <div class="imagem-container">
                    <img src="<?php echo $ia['imagem_historico']; ?>" alt="Imagem de Histórico">
                    <p>Imagem de Histórico</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Botões de Edição e Remoção (apenas para editores) -->
        <?php if ($is_editor): ?>
            <a href="editar.php?id=<?php echo $ia['id']; ?>" class="btn-editar">Editar IA</a>
            <a href="excluir.php?id=<?php echo $ia['id']; ?>" class="btn-eliminar" onclick="return confirmarRemocao();">Remover IA</a>
        <?php endif; ?>

        <a href="index.php" class="btn-voltar">Voltar</a>
    </div>
</body>
</html>z