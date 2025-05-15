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
            $imagem_login = $uploadedFiles['imagem_login'] ?? null;
            $imagem_interface = $uploadedFiles['imagem_interface'] ?? null;
            $imagem_resultados = $uploadedFiles['imagem_resultados'] ?? null;
            $imagem_historico = $uploadedFiles['imagem_historico'] ?? null;
            $imagem_logotipo = $uploadedFiles['imagem_logotipo'] ?? null;

            $stmt = $conn->prepare("
                INSERT INTO inteligencias_artificiais
                (nome, modelo, categoria_id, comportamento, audiencia, preco, descricao, imagem_login, imagem_interface, imagem_resultados, imagem_historico, imagem_logotipo)
                VALUES
                (:nome, :modelo, :categoria_id, :comportamento, :audiencia, :preco, :descricao, :imagem_login, :imagem_interface, :imagem_resultados, :imagem_historico, :imagem_logotipo)
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
                'imagem_logotipo' => $imagem_logotipo
            ]);

            $_SESSION['mensagem_sucesso'] = "IA adicionada com sucesso!";
            header("Location: index.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Nova IA</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="form-container">
        <h1>Adicionar Nova Inteligência Artificial</h1>
        <?php if (!empty($erro)): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo $erro; ?>
            </div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" required>

            <label for="modelo">Modelo:</label>
            <input type="text" id="modelo" name="modelo" required>

            <label for="categoria">Categoria:</label>
            <select id="categoria" name="categoria" required>
                <?php
                $stmt = $conn->query("SELECT * FROM categorias ORDER BY nome");
                $categorias = $stmt->fetchAll();
                foreach ($categorias as $categoria) {
                    echo "<option value='{$categoria['id']}'>{$categoria['nome']}</option>";
                }
                ?>
            </select>

            <label for="comportamento">Comportamento:</label>
            <input type="text" id="comportamento" name="comportamento" required>

            <label for="audiencia">Audiência:</label>
            <input type="text" id="audiencia" name="audiencia" required>

            <label for="preco">Preço (€):</label>
            <input type="number" step="0.01" id="preco" name="preco" required>

            <label for="descricao">Descrição:</label>
            <textarea id="descricao" name="descricao" required></textarea>

            <label for="imagem_logotipo">Imagem de Logotipo:</label>
            <input type="file" id="imagem_logotipo" name="imagem_logotipo" accept="image/*">

            <label for="imagem_login">Imagem de Login:</label>
            <input type="file" id="imagem_login" name="imagem_login" accept="image/*">

            <label for="imagem_interface">Imagem de Interface:</label>
            <input type="file" id="imagem_interface" name="imagem_interface" accept="image/*">

            <label for="imagem_resultados">Imagem de Resultados:</label>
            <input type="file" id="imagem_resultados" name="imagem_resultados" accept="image/*">

            <label for="imagem_historico">Imagem de Histórico:</label>
            <input type="file" id="imagem_historico" name="imagem_historico" accept="image/*">

            <button type="submit">Adicionar</button>
        </form>
        <a href="index.php" class="btn-voltar">Voltar</a>
    </div>
</body>
</html>