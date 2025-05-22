<?php
session_start();
include 'conexao.php';

// Verifica se o utilizador está autenticado e é editor
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_editor']) || $_SESSION['is_editor'] < 1) {
    header("Location: index.php");
    exit();
}

// Buscar categorias para o dropdown
try {
    $stmt = $conn->prepare("SELECT id, nome FROM categorias");
    $stmt->execute();
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categorias = [];
    echo "Erro ao buscar categorias: " . $e->getMessage();
}

$erro = '';
$sucesso = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $construcao = trim($_POST['construcao'] ?? '');
    $comportamento = trim($_POST['comportamento'] ?? '');
    $audiencia = trim($_POST['audiencia'] ?? '');
    $preco = trim($_POST['preco'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $categoria_id = $_POST['categoria_id'] ?? '';

    // Validações
    if (empty($nome) || empty($modelo) || empty($construcao) || empty($comportamento) || empty($audiencia) || empty($preco) || empty($descricao)) {
        $erro = "Todos os campos obrigatórios devem ser preenchidos.";
    } elseif (!is_numeric($preco) || $preco < 0) {
        $erro = "O preço deve ser um número maior ou igual a 0.";
    }

    // Processar uploads de imagens
    $imagem_login = null;
    $imagem_interface = null;
    $imagem_resultados = null;
    $imagem_historico = null;

    if (empty($erro)) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        foreach (['imagem_login', 'imagem_interface', 'imagem_resultados', 'imagem_historico'] as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES[$field]['tmp_name'];
                $file_type = mime_content_type($file_tmp);
                $file_name = uniqid() . '_' . basename($_FILES[$field]['name']);
                $file_path = $upload_dir . $file_name;

                if (in_array($file_type, $allowed_types)) {
                    if (move_uploaded_file($file_tmp, $file_path)) {
                        $$field = $file_path;
                    } else {
                        $erro = "Erro ao fazer upload de $field.";
                    }
                } else {
                    $erro = "Tipo de arquivo inválido para $field. Use JPEG, PNG ou GIF.";
                }
            }
        }
    }

    // Inserir no banco de dados
    if (empty($erro)) {
        try {
            $stmt = $conn->prepare("INSERT INTO inteligencias_artificiais (nome, modelo, construcao, comportamento, audiencia, preco, descricao, imagem_login, imagem_interface, imagem_resultados, imagem_historico, categoria_id) 
                                    VALUES (:nome, :modelo, :construcao, :comportamento, :audiencia, :preco, :descricao, :imagem_login, :imagem_interface, :imagem_resultados, :imagem_historico, :categoria_id)");
            $stmt->execute([
                'nome' => $nome,
                'modelo' => $modelo,
                'construcao' => $construcao,
                'comportamento' => $comportamento,
                'audiencia' => $audiencia,
                'preco' => $preco,
                'descricao' => $descricao,
                'imagem_login' => $imagem_login,
                'imagem_interface' => $imagem_interface,
                'imagem_resultados' => $imagem_resultados,
                'imagem_historico' => $imagem_historico,
                'categoria_id' => $categoria_id ?: null
            ]);
            $sucesso = "IA adicionada com sucesso!";
        } catch (PDOException $e) {
            $erro = "Erro ao adicionar IA: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Nova IA - Biblioteca de IAs</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #00aaff;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .header-branding {
            font-weight: 700;
        }

        .header-title {
            font-size: 24px;
            margin: 0;
        }

        .header-subtitle {
            font-size: 12px;
            font-weight: 400;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .theme-selector {
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
            background-color: white;
        }

        .logout-btn {
            background-color: #ff4444;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .sidebar {
            position: fixed;
            top: 60px;
            left: 0;
            width: 250px;
            height: calc(100vh - 60px);
            background-color: #005566;
            color: white;
            padding: 20px 0;
            z-index: 900;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            padding: 10px 20px;
            cursor: pointer;
        }

        .sidebar ul li:hover {
            background-color: #007799;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
        }

        .main-content {
            margin-left: 250px;
            margin-top: 60px;
            padding: 20px;
            min-height: calc(100vh - 60px);
            background-color: #f0f4f8;
            position: relative;
            z-index: 1;
        }

        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .form-container label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-container input,
        .form-container select,
        .form-container textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .form-container input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }

        .form-container input[type="submit"]:hover {
            background-color: #0056b3;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-250px);
            }
            .main-content {
                margin-left: 0;
            }
        }

        .light { background-color: #f0f4f8; color: #333; }
        .dark { background-color: #1a202c; color: #e2e8f0; }
        .cosmic-blue { background-color: #1e3a8a; color: #e0f2fe; }
        .forest-green { background-color: #14532d; color: #d1fae5; }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-branding">
            <h1 class="header-title">Biblioteca de IAs</h1>
            <p class="header-subtitle">Explore o Futuro da Inteligência Artificial</p>
        </div>
        <div class="header-actions">
            <select class="theme-selector" id="theme-selector">
                <option value="light">Tema Claro</option>
                <option value="dark">Tema Escuro</option>
                <option value="cosmic-blue">Azul Cósmico</option>
                <option value="forest-green">Verde Floresta</option>
            </select>
            <form method="POST" action="index.php">
                <button type="submit" name="logout" class="logout-btn">Sair</button>
            </form>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="sidebar">
        <ul>
            <?php foreach ($categorias as $categoria): ?>
                <li><?php echo htmlspecialchars($categoria['nome']); ?></li>
            <?php endforeach; ?>
            <li><a href="adicionar.php">Adicionar Nova IA</a></li>
            <li><a href="gerir_categorias.php">Gerir Categorias</a></li>
            <li><a href="gerir_ias.php">Gerir IAs</a></li>
        </ul>
    </aside>

    <!-- Conteúdo principal -->
    <main class="main-content">
        <div class="form-container">
            <h2>Adicionar Nova IA</h2>
            <?php if ($erro): ?>
                <p style="color: #ef4444; text-align: center;"><?php echo htmlspecialchars($erro); ?></p>
            <?php endif; ?>
            <?php if ($sucesso): ?>
                <p style="color: #065f46; text-align: center;"><?php echo htmlspecialchars($sucesso); ?></p>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required>

                <label for="modelo">Modelo:</label>
                <input type="text" id="modelo" name="modelo" required>

                <label for="construcao">Construção:</label>
                <input type="text" id="construcao" name="construcao" required>

                <label for="comportamento">Comportamento:</label>
                <input type="text" id="comportamento" name="comportamento" required>

                <label for="audiencia">Audiência:</label>
                <input type="text" id="audiencia" name="audiencia" required>

                <label for="preco">Preço:</label>
                <input type="number" id="preco" name="preco" step="0.01" min="0" required>

                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" required></textarea>

                <label for="categoria_id">Categoria:</label>
                <select id="categoria_id" name="categoria_id">
                    <option value="">Selecione uma categoria</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="imagem_logotipo">Imagem de Logotipo:</label>
                <input type="file" id="imagem_logotipo" name="imagem_logotipo" accept="image/*">

                <label for="imagem_login">Imagem de Login:</label>
                <input type="file" id="imagem_login" name="imagem_login" accept="image/*">

                <label for="imagem_interface">Imagem da Interface:</label>
                <input type="file" id="imagem_interface" name="imagem_interface" accept="image/*">

                <label for="imagem_resultados">Imagem de Resultados:</label>
                <input type="file" id="imagem_resultados" name="imagem_resultados" accept="image/*">

                <label for="imagem_historico">Imagem de Histórico:</label>
                <input type="file" id="imagem_historico" name="imagem_historico" accept="image/*">

                <input type="submit" value="Adicionar IA">
            </form>
        </div>
    </main>

    <script>
        // Sistema de temas
        function applyTheme(theme) {
            const body = document.body;
            const validThemes = ['light', 'dark', 'cosmic-blue', 'forest-green'];
            validThemes.forEach(t => body.classList.remove(t));
            if (validThemes.includes(theme)) {
                body.classList.add(theme);
                localStorage.setItem('selectedTheme', theme);
            }
        }

        function loadTheme() {
            const savedTheme = localStorage.getItem('selectedTheme') || 'light';
            applyTheme(savedTheme);
            document.getElementById('theme-selector').value = savedTheme;
        }

        document.getElementById('theme-selector').addEventListener('change', function() {
            applyTheme(this.value);
        });

        window.onload = loadTheme;
    </script>
</body>
</html>