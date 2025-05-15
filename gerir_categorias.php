<?php
session_start();
include 'conexao.php';

// Verifica se o utilizador está autenticado e é editor
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['is_editor']) || $_SESSION['is_editor'] != 1) {
    echo "Acesso negado! Não é um editor.";
    exit();
}

// Obtém todas as IAs
$stmt = $conn->query("
    SELECT ia.*, c.nome AS categoria_nome
    FROM inteligencias_artificiais ia
    LEFT JOIN categorias c ON ia.categoria_id = c.id
    ORDER BY ia.nome
");
$ias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir IAs</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="categorias-container">
        <h1>Gerir Inteligências Artificiais</h1>

        <!-- Mensagem de sucesso -->
        <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo $_SESSION['mensagem_sucesso']; unset($_SESSION['mensagem_sucesso']); ?>
            </div>
        <?php endif; ?>

        <!-- Lista de IAs -->
        <?php if (empty($ias)): ?>
            <p>Nenhuma IA encontrada.</p>
        <?php else: ?>
            <table class="categorias-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Modelo</th>
                        <th>Preço (€)</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ias as $ia): ?>
                        <tr>
                            <td><?php echo $ia['id']; ?></td>
                            <td><?php echo htmlspecialchars($ia['nome']); ?></td>
                            <td><?php echo htmlspecialchars($ia['categoria_nome'] ?? 'Sem categoria'); ?></td>
                            <td><?php echo htmlspecialchars($ia['modelo']); ?></td>
                            <td><?php echo number_format($ia['preco'], 2, ',', '.'); ?></td>
                            <td>
                                <a href="editar.php?id=<?php echo $ia['id']; ?>" class="btn-editar">Editar</a>
                                <a href="excluir.php?id=<?php echo $ia['id']; ?>" class="btn-eliminar" onclick="return confirm('Tem certeza que deseja eliminar esta IA?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <a href="index.php" class="btn-voltar">Voltar</a>
    </div>
</body>
</html>