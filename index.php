<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'conexao.php';

// Verifica se o utilizador está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Verifica se o utilizador é editor
$is_editor = isset($_SESSION['is_editor']) && $_SESSION['is_editor'] == 1;
$is_admin = isset($_SESSION['is_editor']) && $_SESSION['is_editor'] == 2;

// Filtros
$filtro_nome = $_GET['nome'] ?? '';
$filtro_modelo = $_GET['modelo'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';

// Query ajustada
$query = "
    SELECT ia.*, c.nome AS categoria_nome
    FROM inteligencias_artificiais ia
    LEFT JOIN categorias c ON ia.categoria_id = c.id
    WHERE 1=1
";
$params = [];
if (!empty($filtro_nome)) {
    $query .= " AND ia.nome LIKE :nome";
    $params[':nome'] = "%$filtro_nome%";
}
if (!empty($filtro_modelo)) {
    $query .= " AND ia.modelo LIKE :modelo";
    $params[':modelo'] = "%$filtro_modelo%";
}
if (!empty($filtro_categoria)) {
    $query .= " AND ia.categoria_id = :categoria";
    $params[':categoria'] = $filtro_categoria;
}
$stmt = $conn->prepare($query);
$stmt->execute($params);
$ias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca de IAs</title>
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
                echo "<li><a href='?categoria={$categoria['id']}'>{$categoria['nome']}</a></li>";
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

        <!-- Novo Header -->
        <header class="header">
            <div class="header-wrapper">
                <!-- Título e Subtítulo -->
                <div class="header-branding">
                    <h1 class="header-title">Biblioteca de IAs</h1>
                    <p class="header-subtitle">Explore o Futuro da Inteligência Artificial</p>
                </div>
                <!-- Ferramentas do Header -->
                <div class="header-actions">
                    <!-- Formulário de Pesquisa -->
                    <form method="GET" action="" class="search-form">
                        <div class="search-container">
                            <input type="text" name="nome" placeholder="Nome da IA..." value="<?php echo htmlspecialchars($filtro_nome); ?>">
                            <input type="text" name="modelo" placeholder="Modelo..." value="<?php echo htmlspecialchars($filtro_modelo); ?>">
                            <select name="categoria">
                                <option value="">Todas as Categorias</option>
                                <?php
                                $stmt = $conn->query("SELECT * FROM categorias");
                                $categorias = $stmt->fetchAll();
                                foreach ($categorias as $categoria) {
                                    $selected = $filtro_categoria == $categoria['id'] ? 'selected' : '';
                                    echo "<option value='{$categoria['id']}' $selected>{$categoria['nome']}</option>";
                                }
                                ?>
                            </select>
                            <button type="submit" class="search-btn">
                                <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                Pesquisar
                            </button>
                            <a href="index.php" class="clear-btn">
                                <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 4H8l-7 8 7 8h13a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"></path>
                                    <line x1="18" y1="9" x2="12" y2="15"></line>
                                    <line x1="12" y1="9" x2="18" y2="15"></line>
                                </svg>
                                Limpar
                            </a>
                        </div>
                    </form>
                    <!-- Seletor de Temas e Logout -->
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
        <?php
        if (isset($_SESSION['mensagem_sucesso'])) {
            echo '<div class="success-message">' . $_SESSION['mensagem_sucesso'] . '</div>';
            unset($_SESSION['mensagem_sucesso']);
        }
        ?>

        <!-- Galeria de IAs -->
        <div class="gallery">
            <?php if (empty($ias)): ?>
                <p>Nenhuma IA encontrada.</p>
            <?php else: ?>
                <?php foreach ($ias as $ia): ?>
                    <div class="gallery-item">
                        <div class="logo-container">
                            <?php if (!empty($ia['imagem_logotipo'])): ?>
                                <img src="<?php echo htmlspecialchars($ia['imagem_logotipo']); ?>" alt="<?php echo htmlspecialchars($ia['nome']); ?>" class="logo-ia">
                            <?php endif; ?>
                            <div class="overlay">
                                <div class="mini-descricao"><?php echo htmlspecialchars($ia['descricao']); ?></div>
                                <a href="detalhes_ia.php?id=<?php echo $ia['id']; ?>" class="saiba-mais">Saiba mais →</a>
                            </div>
                        </div>
                        <div class="nome-ia"><?php echo htmlspecialchars($ia['nome']); ?></div>
                    </div>
                <?php endforeach; ?>
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

        // Limitar texto das descrições
        function limitarTexto() {
            const descricoes = document.querySelectorAll('.mini-descricao');
            descricoes.forEach(descricao => {
                const alturaMaxima = 4.5 * parseFloat(getComputedStyle(descricao).fontSize);
                while (descricao.scrollHeight > alturaMaxima && descricao.textContent.length > 0) {
                    descricao.textContent = descricao.textContent.slice(0, -1);
                }
                if (descricao.textContent.length > 0) {
                    descricao.textContent = descricao.textContent.slice(0, -3) + '...';
                }
            });
        }

        // Seletor de temas
        const themeSelector = document.getElementById('theme-selector');
        const body = document.body;

        // Carregar tema salvo (se existir)
        const savedTheme = localStorage.getItem('theme') || 'light';
        body.classList.add(savedTheme);
        themeSelector.value = savedTheme;

        themeSelector.addEventListener('change', () => {
            const selectedTheme = themeSelector.value;
            body.classList.remove('light', 'dark', 'cosmic-blue', 'forest-green');
            body.classList.add(selectedTheme);
            localStorage.setItem('theme', selectedTheme);
            console.log('Tema alterado para:', selectedTheme); // Log para depuração
        });

        window.onload = limitarTexto;
    </script>
</body>
</html>