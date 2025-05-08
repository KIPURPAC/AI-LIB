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
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $modelo = $_POST['modelo'];
    $categoria_id = $_POST['categoria_id'];
    $comportamento = $_POST['comportamento'];
    $audiencia = $_POST['audiencia'];
    $preco = $_POST['preco'];
    $descricao = $_POST['descricao'];

    // Caminhos das imagens (se enviadas)
    $imagem_login = $uploadedFiles['imagem_login'] ?? $_POST['imagem_login_old'];
    $imagem_interface = $uploadedFiles['imagem_interface'] ?? $_POST['imagem_interface_old'];
    $imagem_resultados = $uploadedFiles['imagem_resultados'] ?? $_POST['imagem_resultados_old'];
    $imagem_historico = $uploadedFiles['imagem_historico'] ?? $_POST['imagem_historico_old'];
    $imagem_logotipo = $uploadedFiles['imagem_logotipo'] ?? $_POST['imagem_logotipo_old'];

    // Atualizar os dados no banco de dados
    $stmt = $conn->prepare("
    UPDATE inteligencias_artificiais 
    SET 
        nome = :nome, 
        modelo = :modelo, 
        categoria_id = :categoria_id,
        comportamento = :comportamento, 
        audiencia = :audiencia, 
        preco = :preco, 
        descricao = :descricao, 
        imagem_login = :imagem_login, 
        imagem_interface = :imagem_interface, 
        imagem_resultados = :imagem_resultados, 
        imagem_historico = :imagem_historico,
        imagem_logotipo = :imagem_logotipo
    WHERE id = :id
");
    $stmt->execute([
        'id' => $id,
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

    // Redireciona para a página inicial após a atualização
    header("Location: index.php");
    exit();
}

// Obtém o ID da IA a ser editada
$id = $_GET['id'];

// Consulta os detalhes da IA
$stmt = $conn->prepare("SELECT * FROM inteligencias_artificiais WHERE id = :id");
$stmt->execute(['id' => $id]);
$ia = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($ia)) {
    echo "IA não encontrada.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar IA</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Editar Inteligência Artificial</h1>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $ia['id']; ?>">

        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" value="<?php echo $ia['nome']; ?>" required><br><br>

        <label for="modelo">Modelo:</label>
        <input type="text" id="modelo" name="modelo" value="<?php echo $ia['modelo']; ?>" required><br><br>

        <label for="categoria">Categoria:</label>
        <select id="categoria" name="categoria_id" required>
        <?php
            // Busca categorias do banco de dados
            $stmt = $conn->query("SELECT * FROM categorias");
            $categorias = $stmt->fetchAll();
            foreach ($categorias as $cat) {
                $selected = ($cat['id'] == $ia['categoria_id']) ? 'selected' : '';
                echo "<option value='{$cat['id']}' $selected>{$cat['nome']}</option>";
            }
            ?>
            </select><br><br>

        <label for="comportamento">Comportamento:</label>
        <input type="text" id="comportamento" name="comportamento" value="<?php echo $ia['comportamento']; ?>" required><br><br>

        <label for="audiencia">Audiência:</label>
        <input type="text" id="audiencia" name="audiencia" value="<?php echo $ia['audiencia']; ?>" required><br><br>

        <label for="preco">Preço:</label>
        <input type="number" step="0.01" id="preco" name="preco" value="<?php echo $ia['preco']; ?>" required><br><br>

        <label for="descricao">Descrição:</label>
        <textarea id="descricao" name="descricao" required><?php echo $ia['descricao']; ?></textarea><br><br>

        <!-- Campos ocultos para armazenar os caminhos antigos das imagens -->
        <input type="hidden" name="imagem_login_old" value="<?php echo $ia['imagem_login']; ?>">
        <input type="hidden" name="imagem_interface_old" value="<?php echo $ia['imagem_interface']; ?>">
        <input type="hidden" name="imagem_resultados_old" value="<?php echo $ia['imagem_resultados']; ?>">
        <input type="hidden" name="imagem_historico_old" value="<?php echo $ia['imagem_historico']; ?>">
        <input type="hidden" name="imagem_logotipo_old" value="<?php echo $ia['imagem_logotipo']; ?>">

        <!-- Campos para upload de novas imagens -->
        <label for="imagem_login">Imagem de Login:</label>
        <input type="file" id="imagem_login" name="imagem_login"><br>
        <?php if (!empty($ia['imagem_login'])): ?>
            <img src="<?php echo $ia['imagem_login']; ?>" alt="Imagem de Login" width="100"><br>
        <?php endif; ?><br>

        <label for="imagem_interface">Imagem de Interface:</label>
        <input type="file" id="imagem_interface" name="imagem_interface"><br>
        <?php if (!empty($ia['imagem_interface'])): ?>
            <img src="<?php echo $ia['imagem_interface']; ?>" alt="Imagem de Interface" width="100"><br>
        <?php endif; ?><br>

        <label for="imagem_resultados">Imagem de Resultados:</label>
        <input type="file" id="imagem_resultados" name="imagem_resultados"><br>
        <?php if (!empty($ia['imagem_resultados'])): ?>
            <img src="<?php echo $ia['imagem_resultados']; ?>" alt="Imagem de Resultados" width="100"><br>
        <?php endif; ?><br>

        <label for="imagem_historico">Imagem de Histórico:</label>
        <input type="file" id="imagem_historico" name="imagem_historico"><br>
        <?php if (!empty($ia['imagem_historico'])): ?>
            <img src="<?php echo $ia['imagem_historico']; ?>" alt="Imagem de Histórico" width="100"><br>
        <?php endif; ?><br>

        <label for="imagem_logotipo">Imagem de Logotipo:</label>
        <input type="file" id="imagem_logotipo" name="imagem_logotipo"><br>
        <?php if (!empty($ia['imagem_logotipo'])): ?>
            <img src="<?php echo $ia['imagem_logotipo']; ?>" alt="Imagem de Logotipo" width="100"><br>
        <?php endif; ?><br>
       
        <button type="submit">Salvar Alterações</button>
    </form>
    <br>
    <a href="index.php">Voltar</a>
</body>
</html>