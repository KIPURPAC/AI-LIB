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

// Processa a exclusão de categoria
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM categorias WHERE id = :id");
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        $_SESSION['mensagem_sucesso'] = "Categoria excluída com sucesso!";
        header("Location: gerir_categorias.php");
        exit();
    } else {
        $erro = "Erro ao excluir categoria.";
    }
}

// Processa a adição/edição de categoria
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Edição
        $id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE categorias SET nome = :nome WHERE id = :id");
        $stmt->bindParam(':id', $id);
    } else {
        // Adição
        $stmt = $conn->prepare("INSERT INTO categorias (nome) VALUES (:nome)");
    }
    $stmt->bindParam(':nome', $nome);
    if ($stmt->execute()) {
        $_SESSION['mensagem_sucesso'] = "Categoria salva com sucesso!";
        header("Location: gerir_categorias.php");
        exit();
    } else {
        $erro = "Erro ao salvar categoria.";
    }
}

// Obtém todas as categorias
$stmt = $conn->query("SELECT * FROM categorias");
$categorias = $stmt->fetchAll();

// Carrega categoria para edição, se aplicável
$categoria_editar = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM categorias WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $categoria_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Categorias - Biblioteca de IAs</title>
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
            $categorias_menu = $stmt->fetchAll();
            foreach ($categorias_menu as $categoria) {
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

        <!-- Mensagem de sucesso -->
        <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
            <div class="success-message"><?php echo $_SESSION['mensagem_sucesso']; ?></div>
            <?php unset($_SESSION['mensagem_sucesso']); ?>
        <?php endif; ?>

        <!-- Formulário para adicionar/editar categoria -->
        <div class="form-container">
            <h2><?php echo $categoria_editar ? 'Editar Categoria' : 'Adicionar Categoria'; ?></h2>
            <?php if (isset($erro)): ?>
                <p style="color: #ef4444; text-align: center;"><?php echo htmlspecialchars($erro); ?></p>
            <?php endif; ?>
            <form method="POST" action="">
                <?php if ($categoria_editar): ?>
                    <input type="hidden" name="id" value="<?php echo $categoria_editar['id']; ?>">
                <?php endif; ?>
                <label for="nome">Nome da Categoria:</label>
                <input type="text" id="nome" name="nome" value="<?php echo $categoria_editar ? htmlspecialchars($categoria_editar['nome']) : ''; ?>" required>
                <input type="submit" value="<?php echo $categoria_editar ? 'Atualizar' : 'Adicionar'; ?>">
            </form>
        </div>

        <!-- Tabela de categorias -->
        <div class="table-container">
            <h2>Lista de Categorias</h2>
            <?php if (empty($categorias)): ?>
                <p>Nenhuma categoria encontrada.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($categoria['id']); ?></td>
                                <td><?php echo htmlspecialchars($categoria['nome']); ?></td>
                                <td>
                                    <a href="gerir_categorias.php?edit=<?php echo $categoria['id']; ?>" class="action-btn edit">
                                        <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                        Editar
                                    </a>
                                    <a href="gerir_categorias.php?delete=<?php echo $categoria['id']; ?>" class="action-btn delete" onclick="return confirm('Tem a certeza que deseja excluir esta categoria?');">
                                        <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg>
                                        Excluir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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