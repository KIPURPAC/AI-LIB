<?php
session_start();
include 'conexao.php';

// Verifica se o utilizador está autenticado e é editor
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_editor']) || $_SESSION['is_editor'] < 1) {
    header("Location: index.php");
    exit();
}

// Verifica se o ID da IA foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: gerir_ias.php");
    exit();
}

$ia_id = $_GET['id'];

// Buscar dados da IA
try {
    $stmt = $conn->prepare("SELECT * FROM inteligencias_artificiais WHERE id = :id");
    $stmt->execute(['id' => $ia_id]);
    $ia = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ia) {
        header("Location: gerir_ias.php");
        exit();
    }
} catch (PDOException $e) {
    die("Erro ao buscar IA: " . $e->getMessage());
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
    $imagem_logotipo = $ia['imagem_logotipo']; // Manter o valor atual por padrão
    $imagem_login = $ia['imagem_login'];
    $imagem_interface = $ia['imagem_interface'];
    $imagem_resultados = $ia['imagem_resultados'];
    $imagem_historico = $ia['imagem_historico'];

    if (empty($erro)) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $fields = ['imagem_logotipo', 'imagem_login', 'imagem_interface', 'imagem_resultados', 'imagem_historico'];

        foreach ($fields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES[$field]['tmp_name'];
                $file_type = mime_content_type($file_tmp);
                $file_name = uniqid() . '_' . basename($_FILES[$field]['name']);
                $file_path = $upload_dir . $file_name;

                if (in_array($file_type, $allowed_types)) {
                    if (move_uploaded_file($file_tmp, $file_path)) {
                        // Deletar a imagem antiga, se existir
                        if (!empty($ia[$field]) && file_exists($ia[$field])) {
                            unlink($ia[$field]);
                        }
                        $$field = $file_path; // Atualizar a variável com o novo caminho
                    } else {
                        $erro = "Erro ao fazer upload de $field.";
                    }
                } else {
                    $erro = "Tipo de arquivo inválido para $field. Use JPEG, PNG ou GIF.";
                }
            }
        }
    }

    // Atualizar no banco de dados
    if (empty($erro)) {
        try {
            $stmt = $conn->prepare("UPDATE inteligencias_artificiais SET 
                                    nome = :nome, 
                                    modelo = :modelo, 
                                    construcao = :construcao, 
                                    comportamento = :comportamento, 
                                    audiencia = :audiencia, 
                                    preco = :preco, 
                                    descricao = :descricao, 
                                    imagem_logotipo = :imagem_logotipo, 
                                    imagem_login = :imagem_login, 
                                    imagem_interface = :imagem_interface, 
                                    imagem_resultados = :imagem_resultados, 
                                    imagem_historico = :imagem_historico, 
                                    categoria_id = :categoria_id 
                                    WHERE id = :id");
            $stmt->execute([
                'nome' => $nome,
                'modelo' => $modelo,
                'construcao' => $construcao,
                'comportamento' => $comportamento,
                'audiencia' => $audiencia,
                'preco' => $preco,
                'descricao' => $descricao,
                'imagem_logotipo' => $imagem_logotipo,
                'imagem_login' => $imagem_login,
                'imagem_interface' => $imagem_interface,
                'imagem_resultados' => $imagem_resultados,
                'imagem_historico' => $imagem_historico,
                'categoria_id' => $categoria_id ?: null,
                'id' => $ia_id
            ]);
            $sucesso = "IA atualizada com sucesso!";
            // Atualizar os dados da IA para refletir as alterações
            $stmt = $conn->prepare("SELECT * FROM inteligencias_artificiais WHERE id = :id");
            $stmt->execute(['id' => $ia_id]);
            $ia = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $erro = "Erro ao atualizar IA: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar IA - Biblioteca de IAs</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="styles_mobile.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-wrapper">
            <div class="header-branding">
                <h1 class="header-title">Biblioteca de IAs</h1>
                <p class="header-subtitle">Explore o Futuro da Inteligência Artificial</p>
            </div>
            <div class="header-actions">
                <div class="header-controls">
                    <select class="theme-selector" id="theme-selector">
                        <option value="light">Tema Claro</option>
                        <option value="dark">Tema Escuro</option>
                        <option value="cosmic-blue">Azul Cósmico</option>
                        <option value="forest-green">Verde Floresta</option>
                    </select>
                    <a href="logout.php" class="logout-btn" onclick="return confirm('Tem a certeza que deseja sair?');">
                        <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <div id="sidebar" class="sidebar">
        <ul>
            <?php foreach ($categorias as $categoria): ?>
                <li><a href="index.php?categoria=<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></a></li>
            <?php endforeach; ?>
            <li><a href="adicionar.php">Adicionar Nova IA</a></li>
            <li><a href="gerir_categorias.php">Gerir Categorias</a></li>
            <li><a href="gerir_ias.php">Gerir IAs</a></li>
        </ul>
    </div>

    <!-- Botão para exibir/ocultar categorias -->
    <button id="btn-categorias" class="btn-categorias">☰</button>

    <!-- Conteúdo principal -->
    <main class="main-content">
        <div class="form-container editar-form">
            <h2>Editar IA</h2>
            <?php if ($erro): ?>
                <p style="color: #ef4444; text-align: center;"><?php echo htmlspecialchars($erro); ?></p>
            <?php endif; ?>
            <?php if ($sucesso): ?>
                <p style="color: #065f46; text-align: center;"><?php echo htmlspecialchars($sucesso); ?></p>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($ia['nome']); ?>" required>

                <label for="modelo">Modelo:</label>
                <input type="text" id="modelo" name="modelo" value="<?php echo htmlspecialchars($ia['modelo']); ?>" required>

                <label for="construcao">Construção:</label>
                <input type="text" id="construcao" name="construcao" value="<?php echo htmlspecialchars($ia['construcao']); ?>" required>

                <label for="comportamento">Comportamento:</label>
                <input type="text" id="comportamento" name="comportamento" value="<?php echo htmlspecialchars($ia['comportamento']); ?>" required>

                <label for="audiencia">Audiência:</label>
                <input type="text" id="audiencia" name="audiencia" value="<?php echo htmlspecialchars($ia['audiencia']); ?>" required>

                <label for="preco">Preço:</label>
                <input type="number" id="preco" name="preco" step="0.01" min="0" value="<?php echo htmlspecialchars($ia['preco']); ?>" required>

                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" required><?php echo htmlspecialchars($ia['descricao']); ?></textarea>

                <label for="categoria_id">Categoria:</label>
                <select id="categoria_id" name="categoria_id">
                    <option value="">Selecione uma categoria</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo $categoria['id']; ?>" <?php echo $ia['categoria_id'] == $categoria['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categoria['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="imagem_logotipo">Imagem do Logotipo:</label>
                <?php if (!empty($ia['imagem_logotipo'])): ?>
                    <div class="image-preview">
                        <img src="<?php echo htmlspecialchars($ia['imagem_logotipo']); ?>" alt="Logotipo Atual" style="max-width: 150px; max-height: 150px; margin-bottom: 10px;">
                        <p>Imagem atual. Carregue uma nova para substituir.</p>
                    </div>
                <?php endif; ?>
                <input type="file" id="imagem_logotipo" name="imagem_logotipo" accept="image/*">

                <label for="imagem_login">Imagem de Login:</label>
                <?php if (!empty($ia['imagem_login'])): ?>
                    <div class="image-preview">
                        <img src="<?php echo htmlspecialchars($ia['imagem_login']); ?>" alt="Imagem de Login Atual" style="max-width: 150px; max-height: 150px; margin-bottom: 10px;">
                        <p>Imagem atual. Carregue uma nova para substituir.</p>
                    </div>
                <?php endif; ?>
                <input type="file" id="imagem_login" name="imagem_login" accept="image/*">

                <label for="imagem_interface">Imagem da Interface:</label>
                <?php if (!empty($ia['imagem_interface'])): ?>
                    <div class="image-preview">
                        <img src="<?php echo htmlspecialchars($ia['imagem_interface']); ?>" alt="Imagem da Interface Atual" style="max-width: 150px; max-height: 150px; margin-bottom: 10px;">
                        <p>Imagem atual. Carregue uma nova para substituir.</p>
                    </div>
                <?php endif; ?>
                <input type="file" id="imagem_interface" name="imagem_interface" accept="image/*">

                <label for="imagem_resultados">Imagem de Resultados:</label>
                <?php if (!empty($ia['imagem_resultados'])): ?>
                    <div class="image-preview">
                        <img src="<?php echo htmlspecialchars($ia['imagem_resultados']); ?>" alt="Imagem de Resultados Atual" style="max-width: 150px; max-height: 150px; margin-bottom: 10px;">
                        <p>Imagem atual. Carregue uma nova para substituir.</p>
                    </div>
                <?php endif; ?>
                <input type="file" id="imagem_resultados" name="imagem_resultados" accept="image/*">

                <label for="imagem_historico">Imagem de Histórico:</label>
                <?php if (!empty($ia['imagem_historico'])): ?>
                    <div class="image-preview">
                        <img src="<?php echo htmlspecialchars($ia['imagem_historico']); ?>" alt="Imagem de Histórico Atual" style="max-width: 150px; max-height: 150px; margin-bottom: 10px;">
                        <p>Imagem atual. Carregue uma nova para substituir.</p>
                    </div>
                <?php endif; ?>
                <input type="file" id="imagem_historico" name="imagem_historico" accept="image/*">

                <input type="submit" value="Atualizar IA">
            </form>
            <a href="gerir_ias.php" class="action-btn" style="margin-top: 20px; display: inline-block;">Voltar</a>
        </div>
    </main>

    <script>
        // Sidebar toggle
        document.getElementById('btn-categorias').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const btnCategorias = this;

            sidebar.classList.toggle('sidebar-visible');
            btnCategorias.classList.toggle('sidebar-visible');
        });

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