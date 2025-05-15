<?php
session_start();
include 'conexao.php';

// Verifica se o utilizador está autenticado e é editor
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_editor']) || $_SESSION['is_editor'] < 1) {
    header("Location: login.php");
    exit();
}

$is_editor = $_SESSION['is_editor'] == 1;
$is_admin = $_SESSION['is_editor'] == 2;

// Processa o formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $modelo = $_POST['modelo'];
    $categoria_id = $_POST['categoria'];
    $imagem_logotipo = $_POST['imagem_logotipo'];
    $descricao = $_POST['descricao'];

    $stmt = $conn->prepare("INSERT INTO inteligencias_artificiais (nome, modelo, categoria_id, imagem_logotipo, descricao) VALUES (:nome, :modelo, :categoria_id, :imagem_logotipo, :descricao)");
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':modelo', $modelo);
    $stmt->bindParam(':categoria_id', $categoria_id);
    $stmt->bindParam(':imagem_logotipo', $imagem_logotipo);
    $stmt->bindParam(':descricao', $descricao);
    
    if ($stmt->execute()) {
        $_SESSION['mensagem_sucesso'] = "IA adicionada com sucesso!";
        header("Location: index.php");
        exit();
    } else {
        $erro = "Erro ao adicionar IA.";
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
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Sidebar com categorias -->
    <div id="sidebar" class="sidebar">
        <ul>
            <li><a href="index.php">Todas as Categorias</a></li>
            <?php
            $stmt = $conn->query("SELECT * FROM categorias");
            $categorias = $stmt->fetchAll();
            foreach ($categorias as $categoria) {
                echo "<li><a href='index.php?categoria={$categoria['id']}'>{$categoria['nome']}</a></li>";
            }
            ?>
        </ul>
        <?php if ($is_editor || $is_admin): ?>
            <hr style="border: 1px solid rgba(255,255,255,0.2); margin: 20px 0;">
            <h3 style="color: #fff; margin: 0 0 10px 20px; font-size: 1.2rem;">Gestão (Editor)</h3>
            <ul>
                <li><a href="adicionar.php">Adicionar Nova IA</a></li>
                <li><a href="gerir_categorias.php">Gerir Categorias</a></li>
                <li><a href="gerir_ias.php">Gerir IAs</a></li>
                <?php if ($is_admin): ?>
                    <li><a href="gerir_pedidos.php">Gerir Pedidos de Editor</a></li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Conteúdo principal -->
    <div class="main-content">
        <!-- Botão para exibir/ocultar categorias -->
        <button id="btn-categorias" class="btn-categorias">☰</button>

        <!-- Header -->
        <header class="header">
            <div class="header-wrapper">
                <div class="header-branding">
                    <h1 class="header-title">Biblioteca de IAs</h1>
                    <p class="header-subtitle">Explore o Futuro da Inteligência Artificial</p>
                </div>
                <div class="header-actions">
                    <div class="header-controls">
                        <select id="theme-selector" class="theme-selector">
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

        <!-- Formulário para adicionar IA -->
        <div class="form-container">
            <h2>Adicionar Nova IA</h2>
            <?php if (isset($erro)): ?>
                <p style="color: #ef4444; text-align: center;"><?php echo htmlspecialchars($erro); ?></p>
            <?php endif; ?>
            <form method="POST" action="">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required>
                
                <label for="modelo">Modelo:</label>
                <input type="text" id="modelo" name="modelo" required>
                
                <label for="categoria">Categoria:</label>
                <select id="categoria" name="categoria" required>
                    <option value="">Selecione uma categoria</option>
                    <?php
                    $stmt = $conn->query("SELECT * FROM categorias");
                    $categorias = $stmt->fetchAll();
                    foreach ($categorias as $categoria) {
                        echo "<option value='{$categoria['id']}'>{$categoria['nome']}</option>";
                    }
                    ?>
                </select>
                
                <label for="imagem_logotipo">URL da Imagem do Logotipo:</label>
                <input type="text" id="imagem_logotipo" name="imagem_logotipo">
                
                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" required></textarea>
                
                <input type="submit" value="Adicionar IA">
            </form>
        </div>
    </div>

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
                console.log('Tema aplicado:', theme);
            } else {
                console.warn('Tema inválido:', theme);
            }
        }

        function loadTheme() {
            const savedTheme = localStorage.getItem('selectedTheme') || 'light';
            applyTheme(savedTheme);
            document.getElementById('theme-selector').value = savedTheme;
            console.log('Tema carregado:', savedTheme);
        }

        document.getElementById('theme-selector').addEventListener('change', function() {
            const selectedTheme = this.value;
            applyTheme(selectedTheme);
            console.log('Tema selecionado:', selectedTheme);
        });

        window.onload = loadTheme;
    </script>
</body>
</html>