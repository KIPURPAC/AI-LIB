<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

// Obtém o ID da IA a ser editada
$ia_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if ($ia_id === false) {
    echo "ID inválido.";
    exit();
}

// Consulta os detalhes da IA
$stmt = $conn->prepare("SELECT * FROM inteligencias_artificiais WHERE id = :id");
$stmt->execute(['id' => $ia_id]);
$ia = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($ia)) {
    echo "IA não encontrada.";
    exit();
}

// Inicializa variável de erro
$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $uploadDir = 'Uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxFileSize = 5 * 1024 * 1024;
    $uploadedFiles = [];

    foreach ($_FILES as $key => $file) {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $fileType = mime_content_type($file['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                $erro = "Tipo de ficheiro não permitido para $key.";
                break;
            }

            if ($file['size'] > $maxFileSize) {
                $erro = "Ficheiro $key excede o tamanho máximo permitido.";
                break;
            }

            $fileName = uniqid() . '_' . basename($file['name']);
            $destination = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $uploadedFiles[$key] = $destination;
            } else {
                $erro = "Falha ao mover o ficheiro $key.";
                break;
            }
        }
    }

    if (empty($erro)) {
        $nome = htmlspecialchars(trim($_POST['nome']));
        $modelo = htmlspecialchars(trim($_POST['modelo']));
        $comportamento = htmlspecialchars(trim($_POST['comportamento']));
        $audiencia = htmlspecialchars(trim($_POST['audiencia']));
        $preco = filter_var($_POST['preco'], FILTER_VALIDATE_FLOAT);
        $descricao = htmlspecialchars(trim($_POST['descricao']));
        $categoria_id = filter_var($_POST['categoria'], FILTER_VALIDATE_INT);

        if (empty($nome) || empty($modelo) || empty($comportamento) || empty($audiencia) || $preco === false || $categoria_id === false) {
            $erro = "Por favor, preencha todos os campos corretamente.";
        } else {
            $imagem_login = $uploadedFiles['imagem_login'] ?? $ia['imagem_login'];
            $imagem_interface = $uploadedFiles['imagem_interface'] ?? $ia['imagem_interface'];
            $imagem_resultados = $uploadedFiles['imagem_resultados'] ?? $ia['imagem_resultados'];
            $imagem_historico = $uploadedFiles['imagem_historico'] ?? $ia['imagem_historico'];
            $imagem_logotipo = $uploadedFiles['imagem_logotipo'] ?? $ia['imagem_logotipo'];

            $stmt = $conn->prepare("
                UPDATE inteligencias_artificiais
                SET nome = :nome, modelo = :modelo, categoria_id = :categoria_id,
                    comportamento = :comportamento, audiencia = :audiencia, preco = :preco,
                    descricao = :descricao, imagem_login = :imagem_login,
                    imagem_interface = :imagem_interface, imagem_resultados = :imagem_resultados,
                    imagem_historico = :imagem_historico, imagem_logotipo = :imagem_logotipo
                WHERE id = :id
            ");
            $stmt->execute([
                'nome' => $nome,
                'modelo' => $modelo,
                'categoria_id' => $categoria_id,
                'comportamento' => $comportamento,
                'audiencia' => $audiencia,
                'preco' => $preco,
                'descricao' => $descricao,
                'imagem_login' => $imagem_login,
                'imagem_interface' => $imagem_interface,
                'imagem_resultados' => $imagem_resultados,
                'imagem_historico' => $imagem_historico,
                'imagem_logotipo' => $imagem_logotipo,
                'id' => $ia_id
            ]);

            $_SESSION['mensagem_sucesso'] = "IA atualizada com sucesso!";
            header("Location: detalhes_ia.php?id=$ia_id");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar IA</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="form-container">
        <h1>Editar Inteligência Artificial</h1>
        <?php if (!empty($erro)): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo $erro; ?>
            </div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($ia['nome']); ?>" required>

            <label for="modelo">Modelo:</label>
            <input type="text" id="modelo" name="modelo" value="<?php echo htmlspecialchars($ia['modelo']); ?>" required>

            <label for="categoria">Categoria:</label>
            <select id="categoria" name="categoria" required>
                <?php
                $stmt = $conn->query("SELECT * FROM categorias ORDER BY nome");
                $categorias = $stmt->fetchAll();
                foreach ($categorias as $categoria) {
                    $selected = $ia['categoria_id'] == $categoria['id'] ? 'selected' : '';
                    echo "<option value='{$categoria['id']}' $selected>{$categoria['nome']}</option>";
                }
                ?>
            </select>

            <label for="comportamento">Comportamento:</label>
            <input type="text" id="comportamento" name="comportamento" value="<?php echo htmlspecialchars($ia['comportamento']); ?>" required>

            <label for="audiencia">Audiência:</label>
            <input type="text" id="audiencia" name="audiencia" value="<?php echo htmlspecialchars($ia['audiencia']); ?>" required>

            <label for="preco">Preço (€):</label>
            <input type="number" step="0.01" id="preco" name="preco" value="<?php echo $ia['preco']; ?>" required>

            <label for="descricao">Descrição:</label>
            <textarea id="descricao" name="descricao" required><?php echo htmlspecialchars($ia['descricao']); ?></textarea>

            <label for="imagem_logotipo">Imagem de Logotipo (atual: <?php echo $ia['imagem_logotipo'] ?: 'Nenhuma'; ?>):</label>
            <input type="file" id="imagem_logotipo" name="imagem_logotipo" accept="image/*">

            <label for="imagem_login">Imagem de Login (atual: <?php echo $ia['imagem_login'] ?: 'Nenhuma'; ?>):</label>
            <input type="file" id="imagem_login" name="imagem_login" accept="image/*">

            <label for="imagem_interface">Imagem de Interface (atual: <?php echo $ia['imagem_interface'] ?: 'Nenhuma'; ?>):</label>
            <input type="file" id="imagem_interface" name="imagem_interface" accept="image/*">

            <label for="imagem_resultados">Imagem de Resultados (atual: <?php echo $ia['imagem_resultados'] ?: 'Nenhuma'; ?>):</label>
            <input type="file" id="imagem_resultados" name="imagem_resultados" accept="image/*">

            <label for="imagem_historico">Imagem de Histórico (atual: <?php echo $ia['imagem_historico'] ?: 'Nenhuma'; ?>):</label>
            <input type="file" id="imagem_historico" name="imagem_historico" accept="image/*">

            <button type="submit">Guardar Alterações</button>
        </form>
        <a href="detalhes_ia.php?id=<?php echo $ia_id; ?>" class="btn-voltar">Cancelar</a>
    </div>
</body>
</html>