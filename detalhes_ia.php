<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'conexao.php';

// Verifica se o utilizador está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Obtém o ID da IA a ser exibida
$ia_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

// Verifica se o ID é válido
if ($ia_id === false) {
    echo "ID inválido.";
    exit();
}

// Consulta os detalhes da IA
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes de <?php echo htmlspecialchars($ia['nome']); ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="detalhes-container">
        <h1><?php echo htmlspecialchars($ia['nome']); ?></h1>
        <p><strong>Categoria:</strong> <?php echo htmlspecialchars($ia['categoria_nome'] ?? 'Sem categoria'); ?></p>
        <p><strong>Modelo:</strong> <?php echo htmlspecialchars($ia['modelo']); ?></p>
        <p><strong>Comportamento:</strong> <?php echo htmlspecialchars($ia['comportamento']); ?></p>
        <p><strong>Audiência:</strong> <?php echo htmlspecialchars($ia['audiencia']); ?></p>
        <p><strong>Preço:</strong> €<?php echo number_format($ia['preco'], 2, ',', '.'); ?></p>
        <p><strong>Descrição:</strong> <?php echo nl2br(htmlspecialchars($ia['descricao'])); ?></p>

        <?php if (!empty($ia['imagem_logotipo'])): ?>
            <img src="<?php echo htmlspecialchars($ia['imagem_logotipo']); ?>" alt="Logotipo de <?php echo htmlspecialchars($ia['nome']); ?>">
        <?php endif; ?>
        <?php if (!empty($ia['imagem_login'])): ?>
            <img src="<?php echo htmlspecialchars($ia['imagem_login']); ?>" alt="Tela de Login de <?php echo htmlspecialchars($ia['nome']); ?>">
        <?php endif; ?>
        <?php if (!empty($ia['imagem_interface'])): ?>
            <img src="<?php echo htmlspecialchars($ia['imagem_interface']); ?>" alt="Interface de <?php echo htmlspecialchars($ia['nome']); ?>">
        <?php endif; ?>
        <?php if (!empty($ia['imagem_resultados'])): ?>
            <img src="<?php echo htmlspecialchars($ia['imagem_resultados']); ?>" alt="Resultados de <?php echo htmlspecialchars($ia['nome']); ?>">
        <?php endif; ?>
        <?php if (!empty($ia['imagem_historico'])): ?>
            <img src="<?php echo htmlspecialchars($ia['imagem_historico']); ?>" alt="Histórico de <?php echo htmlspecialchars($ia['nome']); ?>">
        <?php endif; ?>

        <a href="index.php" class="btn-voltar">Voltar</a>
        <?php if ($is_editor): ?>
            <a href="editar.php?id=<?php echo $ia['id']; ?>" class="btn-editar">Editar</a>
            <a href="excluir.php?id=<?php echo $ia['id']; ?>" class="btn-eliminar" onclick="return confirm('Tem certeza que deseja eliminar esta IA?');">Eliminar</a>
        <?php endif; ?>
    </div>
</body>
</html>