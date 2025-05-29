<?php
session_start();
include 'conexao.php';

// Verifica se o utilizador está autenticado e é editor
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_editor']) || $_SESSION['is_editor'] < 1) {
    header("Location: index.php");
    exit();
}

// Verifica se o utilizador é admin
$is_admin = isset($_SESSION['is_editor']) && $_SESSION['is_editor'] == 2;

// Buscar todas as IAs
try {
    $stmt = $conn->prepare("
        SELECT ia.id, ia.nome, ia.modelo, ia.imagem_logotipo, c.nome AS categoria_nome
        FROM inteligencias_artificiais ia
        LEFT JOIN categorias c ON ia.categoria_id = c.id
        ORDER BY ia.nome
    ");
    $stmt->execute();
    $ias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $ias = [];
    $erro = "Erro ao buscar IAs: " . $e->getMessage();
}

// Buscar categorias para o sidebar
try {
    $stmt = $conn->prepare("SELECT id, nome FROM categorias");
    $stmt->execute();
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categorias = [];
    $erro = "Erro ao buscar categorias: " . $e->getMessage();
}

// Processar eliminação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    try {
        // Buscar imagens para eliminar arquivos
        $stmt = $conn->prepare("SELECT imagem_logotipo, imagem_login, imagem_interface, imagem_resultados, imagem_historico FROM inteligencias_artificiais WHERE id = :id");
        $stmt->execute(['id' => $delete_id]);
        $ia = $stmt->fetch(PDO::FETCH_ASSOC);

        // Eliminar arquivos de imagem, se existirem
        foreach (['imagem_logotipo', 'imagem_login', 'imagem_interface', 'imagem_resultados', 'imagem_historico'] as $field) {
            if (!empty($ia[$field]) && file_exists($ia[$field])) {
                unlink($ia[$field]);
            }
        }

        // Eliminar IA do banco de dados
        $stmt = $conn->prepare("DELETE FROM inteligencias_artificiais WHERE id = :id");
        $stmt->execute(['id' => $delete_id]);
        $_SESSION['mensagem_sucesso'] = "IA eliminada com sucesso!";
        header("Location: gerir_ias.php");
        exit();
    } catch (PDOException $e) {
        $erro = "Erro ao eliminar IA: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir IAs - Biblioteca de IAs</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="styles_mobile.css">
    <style>
        .ias-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
            max-width: 1400px;
            width: 100%;
            justify-items: center;
        }
        .ia-card {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            width: 100%;
            max-width: 300px;
        }
        .ia-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .ia-card-logo {
            width: 100%;
            height: 180px; /* Tamanho fixo para a imagem */
            background: #e2e8f0;
            overflow: hidden;
        }
        .ia-card-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Mantém a proporção da imagem */
        }
        .ia-card-info {
            padding: 15px;
            text-align: center;
        }
        .ia-card-info h3 {
            margin: 0 0 10px;
            font-size: 1.2rem;
        }
        .ia-card-info p {
            margin: 5px 0;
            font-size: 0.9rem;
        }
        .ia-card-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 10px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Sidebar com categorias -->
    <div id="sidebar" class="sidebar">
        <ul>
            <li><a href="index.php">Todas as Categorias</a></li>
            <?php foreach ($categorias as $categoria): ?>
                <li><a href="index.php?categoria=<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></a></li>
            <?php endforeach; ?>
        </ul>
        <?php if (isset($_SESSION['is_editor']) && $_SESSION['is_editor'] >= 1): ?>
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
                        <a href="logout.php" class="logout-btn" onclick="return confirm('Tem certeza que deseja sair?');">
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

        <!-- Gerir IAs -->
        <section class="manage-ias">
            <h2>Gerir IAs</h2>
            <?php if (isset($erro)): ?>
                <p class="error-message"><?php echo htmlspecialchars($erro); ?></p>
            <?php endif; ?>
            <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
                <p class="success-message"><?php echo htmlspecialchars($_SESSION['mensagem_sucesso']); ?></p>
                <?php unset($_SESSION['mensagem_sucesso']); ?>
            <?php endif; ?>
            <?php if (empty($ias)): ?>
                <p class="no-results">Nenhuma IA registada.</p>
            <?php else: ?>
                <div class="ias-grid">
                    <?php foreach ($ias as $ia): ?>
                        <div class="ia-card">
                            <div class="ia-card-logo">
                                <img src="<?php echo !empty($ia['imagem_logotipo']) && file_exists($ia['imagem_logotipo']) ? htmlspecialchars($ia['imagem_logotipo']) : 'images/default_logo.png'; ?>" alt="<?php echo htmlspecialchars($ia['nome']); ?>">
                            </div>
                            <div class="ia-card-info">
                                <h3><?php echo htmlspecialchars($ia['nome']); ?></h3>
                                <p><strong>Modelo:</strong> <?php echo htmlspecialchars($ia['modelo']); ?></p>
                                <p><strong>Categoria:</strong> <?php echo htmlspecialchars($ia['categoria_nome'] ?: 'Sem categoria'); ?></p>
                            </div>
                            <div class="ia-card-actions">
                                <a href="editar.php?id=<?php echo $ia['id']; ?>" class="action-btn edit-btn">Editar</a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja eliminar esta IA?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $ia['id']; ?>">
                                    <button type="submit" class="action-btn delete-btn">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <a href="index.php" class="action-btn back-btn">Voltar à Galeria</a>
        </section>
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