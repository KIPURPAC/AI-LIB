<?php
session_start();
include 'conexao.php';

// Verifica se o utilizador está autenticado e é administrador (is_editor = 2, por exemplo)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['is_editor']) || $_SESSION['is_editor'] != 2) {
    echo "Acesso negado! Não é um administrador.";
    exit();
}

// Processa aprovação/rejeição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pedido_id = filter_var($_POST['pedido_id'], FILTER_VALIDATE_INT);
    $acao = $_POST['acao'];

    if ($pedido_id && in_array($acao, ['aprovar', 'rejeitar'])) {
        $stmt = $conn->prepare("SELECT utilizador_id FROM pedidos_editor WHERE id = :id AND estado = 'pendente'");
        $stmt->execute(['id' => $pedido_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pedido) {
            $estado = $acao == 'aprovar' ? 'aprovado' : 'rejeitado';
            $stmt = $conn->prepare("UPDATE pedidos_editor SET estado = :estado WHERE id = :id");
            $stmt->execute(['estado' => $estado, 'id' => $pedido_id]);

            if ($acao == 'aprovar') {
                $stmt = $conn->prepare("UPDATE utilizadores SET is_editor = 1 WHERE id = :utilizador_id");
                $stmt->execute(['utilizador_id' => $pedido['utilizador_id']]);
            }

            $_SESSION['mensagem_sucesso'] = "Pedido " . ($acao == 'aprovar' ? 'aprovado' : 'rejeitado') . " com sucesso!";
            header("Location: gerir_pedidos.php");
            exit();
        }
    }
}

// Obtém todos os pedidos
$stmt = $conn->query("
    SELECT p.*, u.username
    FROM pedidos_editor p
    JOIN utilizadores u ON p.utilizador_id = u.id
    ORDER BY p.data_pedido DESC
");
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Pedidos de Editor</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="categorias-container">
        <h1>Gerir Pedidos de Editor</h1>

        <!-- Mensagem de sucesso -->
        <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo $_SESSION['mensagem_sucesso']; unset($_SESSION['mensagem_sucesso']); ?>
            </div>
        <?php endif; ?>

        <!-- Lista de pedidos -->
        <?php if (empty($pedidos)): ?>
            <p>Nenhum pedido encontrado.</p>
        <?php else: ?>
            <table class="categorias-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utilizador</th>
                        <th>Data do Pedido</th>
                        <th>Estado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td><?php echo $pedido['id']; ?></td>
                            <td><?php echo htmlspecialchars($pedido['username']); ?></td>
                            <td><?php echo $pedido['data_pedido']; ?></td>
                            <td><?php echo $pedido['estado']; ?></td>
                            <td>
                                <?php if ($pedido['estado'] == 'pendente'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                        <button type="submit" name="acao" value="aprovar" class="btn-adicionar">Aprovar</button>
                                        <button type="submit" name="acao" value="rejeitar" class="btn-eliminar">Rejeitar</button>
                                    </form>
                                <?php endif; ?>
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