<?php
session_start();
include 'conexao.php';

// Verifica se o usuário está logado e é um editor
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['is_editor']) || $_SESSION['is_editor'] != 1) {
    echo "Acesso negado! Você não é um editor.";
    exit();
}

// Processa o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Diretório onde as imagens serão armazenadas
    $uploadDir = 'uploads/';

    // Tipos de arquivo permitidos
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

    // Tamanho máximo do arquivo (5MB)
    $maxFileSize = 5 * 1024 * 1024;

    // Array para armazenar os caminhos das imagens
    $uploadedFiles = [];

    // Processar cada arquivo enviado
    foreach ($_FILES as $key => $file) {
        if ($file['error'] === UPLOAD_ERR_OK) {
            // Verificar o tipo do arquivo
            $fileType = mime_content_type($file['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                echo "Tipo de arquivo não permitido para $key.";
                exit();
            }

            // Verificar o tamanho do arquivo
            if ($file['size'] > $maxFileSize) {
                echo "Arquivo $key excede o tamanho máximo permitido.";
                exit();
            }

            // Gerar um nome único para o arquivo
            $fileName = uniqid() . '_' . basename($file['name']);
            $destination = $uploadDir . $fileName;

            // Mover o arquivo para o diretório de uploads
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $uploadedFiles[$key] = $destination;
            } else {
                echo "Falha ao mover o arquivo $key.";
                exit();
            }
        }
    }

    // Dados do formulário
    $nome = $_POST['nome'];
    $modelo = $_POST['modelo'];
    $construcao = $_POST['construcao'];
    $comportamento = $_POST['comportamento'];
    $audiencia = $_POST['audiencia'];
    $preco = $_POST['preco'];
    $descricao = $_POST['descricao'];

    // Caminhos das imagens (se enviadas)
    $imagem_login = $uploadedFiles['imagem_login'] ?? null;
    $imagem_interface = $uploadedFiles['imagem_interface'] ?? null;
    $imagem_resultados = $uploadedFiles['imagem_resultados'] ?? null;
    $imagem_historico = $uploadedFiles['imagem_historico'] ?? null;
    $imagem_logotipo = $uploadedFiles['imagem_logotipo'] ?? null;

    // Inserir os dados no banco de dados
    $stmt = $conn->prepare("
    INSERT INTO inteligencias_artificiais 
    (nome, modelo, categoria_id, comportamento, audiencia, preco, descricao, imagem_login, imagem_interface, imagem_resultados, imagem_historico, imagem_logotipo) 
    VALUES 
    (:nome, :modelo, :categoria_id, :comportamento, :audiencia, :preco, :descricao, :imagem_login, :imagem_interface, :imagem_resultados, :imagem_historico, :imagem_logotipo)
    ");
    $stmt->execute([
        'nome' => $nome,
        'modelo' => $modelo,
        'categoria_id' => $_POST['categoria'], // Corrigido para 'categoria_id'
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

    // Redireciona para a página inicial após a inserção
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Nova IA</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="form-container">
        <h1>Adicionar Nova Inteligência Artificial</h1>
        <form method="POST" enctype="multipart/form-data">
            <table>
                <tr>
                    <td><label for="nome">Nome:</label></td>
                    <td><input type="text" id="nome" name="nome" required></td>
                </tr>
                <tr>
                    <td><label for="modelo">Modelo:</label></td>
                    <td><input type="text" id="modelo" name="modelo" required></td>
                </tr>
                <tr>
                 <td colspan="2">
              <label for="categoria">Categoria:</label>
         <select id="categoria" name="categoria" required>
                 <?php
                 $stmt = $conn->query("SELECT * FROM categorias");
                 $categorias = $stmt->fetchAll();
                 foreach ($categorias as $categoria) {
                     echo "<option value='{$categoria['id']}'>{$categoria['nome']}</option>";
                     }
                   ?>
        </select>
    </td>
</tr>
                    <td><label for="comportamento">Comportamento:</label></td>
                    <td><input type="text" id="comportamento" name="comportamento" required></td>
                </tr>
                <tr>
                    <td><label for="audiencia">Audiência:</label></td>
                    <td><input type="text" id="audiencia" name="audiencia" required></td>
                </tr>
                <tr>
                    <td><label for="preco">Preço:</label></td>
                    <td><input type="number" step="0.01" id="preco" name="preco" required></td>
                </tr>
                <tr>
                    <td><label for="descricao">Descrição:</label></td>
                    <td><textarea id="descricao" name="descricao" required></textarea></td>
                </tr>
                <tr>
                    <td><label for="imagem_logotipo">Imagem de Logotipo:</label></td>
                    <td><input type="file" id="imagem_logotipo" name="imagem_logotipo"></td>
                </tr>
                <tr>
                    <td><label for="imagem_login">Imagem de Login:</label></td>
                    <td><input type="file" id="imagem_login" name="imagem_login"></td>
                </tr>
                <tr>
                    <td><label for="imagem_interface">Imagem de Interface:</label></td>
                    <td><input type="file" id="imagem_interface" name="imagem_interface"></td>
                </tr>
                <tr>
                    <td><label for="imagem_resultados">Imagem de Resultados:</label></td>
                    <td><input type="file" id="imagem_resultados" name="imagem_resultados"></td>
                </tr>
                <tr>
                    <td><label for="imagem_historico">Imagem de Histórico:</label></td>
                    <td><input type="file" id="imagem_historico" name="imagem_historico"></td>
                </tr>
                
            </table>
            <button type="submit">Adicionar</button>
        </form>
        <br>
        <a href="index.php" class="btn-voltar">Voltar</a>
    </div>
</body>
</html>