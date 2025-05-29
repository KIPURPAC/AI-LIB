<?php
session_start();
include 'conexao.php';

// Verifica se o utilizador está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Verifica se o ID da IA foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$ia_id = $_GET['id'];

// Buscar dados da IA
try {
    $stmt = $conn->prepare("
        SELECT ia.*, c.nome AS categoria_nome
        FROM inteligencias_artificiais ia
        LEFT JOIN categorias c ON ia.categoria_id = c.id
        WHERE ia.id = :id
    ");
    $stmt->execute(['id' => $ia_id]);
    $ia = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ia) {
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    die("Erro ao buscar IA: " . $e->getMessage());
}

// Buscar categorias para o sidebar
try {
    $stmt = $conn->prepare("SELECT id, nome FROM categorias");
    $stmt->execute();
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categorias = [];
    echo "Erro ao buscar categorias: " . $e->getMessage();
}

// Verifica se o utilizador é editor
$is_editor = isset($_SESSION['is_editor']) && $_SESSION['is_editor'] == 1;
$is_admin = isset($_SESSION['is_editor']) && $_SESSION['is_editor'] == 2;

// Preparar imagens para o carrossel, com a imagem do logotipo em primeiro
$images = [];
// Adicionar a imagem do logotipo como primeira, se existir
if (!empty($ia['imagem_logotipo']) && file_exists($ia['imagem_logotipo'])) {
    $images[] = ['src' => $ia['imagem_logotipo'], 'alt' => 'Logotipo de ' . htmlspecialchars($ia['nome'])];
}
// Adicionar as outras imagens
$other_images = array_filter([
    ['src' => $ia['imagem_login'], 'alt' => 'Imagem de Login'],
    ['src' => $ia['imagem_interface'], 'alt' => 'Imagem da Interface'],
    ['src' => $ia['imagem_resultados'], 'alt' => 'Imagem de Resultados'],
    ['src' => $ia['imagem_historico'], 'alt' => 'Imagem de Histórico']
], function($image) {
    return !empty($image['src']) && file_exists($image['src']);
});
$images = array_merge($images, $other_images);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($ia['nome']); ?> - Biblioteca de IAs</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="styles_mobile.css">
    <style>
        .ia-image-gallery {
            margin: 20px 0;
            text-align: center;
        }
        .carousel-container {
            position: relative;
            display: inline-block;
            width: 600px; /* Tamanho fixo */
            height: 400px; /* Tamanho fixo */
        }
        .carousel-image {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100%;
            background: #e2e8f0; /* Fundo para imagens menores */
        }
        .carousel-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .carousel-text {
            position: absolute;
            bottom: 10px;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 1.2rem;
            text-align: center;
        }
        .carousel-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            font-size: 1.5rem;
        }
        .carousel-prev {
            left: 10px;
        }
        .carousel-next {
            right: 10px;
        }
        .carousel-arrow:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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

        <!-- Detalhes da IA -->
        <section class="ia-details">
            <!-- Hero Section (apenas título e categoria) -->
            <div class="ia-hero">
                <div class="ia-hero-info">
                    <h1 class="ia-title"><?php echo htmlspecialchars($ia['nome']); ?></h1>
                    <p class="ia-category">Categoria: <?php echo htmlspecialchars($ia['categoria_nome'] ?: 'Sem categoria'); ?></p>
                </div>
            </div>

            <!-- Carrossel de Imagens (com logotipo em primeiro) -->
            <?php if (!empty($images)): ?>
                <div class="ia-image-gallery">
                    <h2>Galeria de Imagens</h2>
                    <div class="carousel-container">
                        <button class="carousel-arrow carousel-prev" aria-label="Imagem anterior">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>
                        <div class="carousel-image">
                            <img src="<?php echo htmlspecialchars($images[0]['src']); ?>" alt="<?php echo htmlspecialchars($images[0]['alt']); ?>">
                            <div class="carousel-text">
                                <p><?php echo htmlspecialchars($ia['nome']); ?></p>
                                <p>Categoria: <?php echo htmlspecialchars($ia['categoria_nome'] ?: 'Sem categoria'); ?></p>
                            </div>
                        </div>
                        <button class="carousel-arrow carousel-next" aria-label="Próxima imagem">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- Dados das imagens para JavaScript -->
                <script>
                    const carouselImages = <?php echo json_encode($images); ?>;
                    const carouselImage = document.querySelector('.carousel-image img');
                    const carouselText = document.querySelector('.carousel-text');
                    const prevButton = document.querySelector('.carousel-prev');
                    const nextButton = document.querySelector('.carousel-next');
                    let currentIndex = 0;

                    function updateCarousel(index) {
                        carouselImage.src = carouselImages[index].src;
                        carouselImage.alt = carouselImages[index].alt;
                        carouselText.innerHTML = `
                            <p>${<?php echo json_encode(htmlspecialchars($ia['nome'])); ?>}</p>
                            <p>Categoria: ${<?php echo json_encode(htmlspecialchars($ia['categoria_nome'] ?: 'Sem categoria')); ?>}</p>
                        `;
                        prevButton.disabled = index === 0;
                        nextButton.disabled = index === carouselImages.length - 1;
                    }

                    prevButton.addEventListener('click', () => {
                        if (currentIndex > 0) {
                            currentIndex--;
                            updateCarousel(currentIndex);
                        }
                    });

                    nextButton.addEventListener('click', () => {
                        if (currentIndex < carouselImages.length - 1) {
                            currentIndex++;
                            updateCarousel(currentIndex);
                        }
                    });

                    // Inicializar estado dos botões
                    prevButton.disabled = true;
                    nextButton.disabled = carouselImages.length === 1;
                </script>
            <?php endif; ?>

            <!-- Detalhes Principais -->
            <div class="ia-info-card">
                <h2>Detalhes</h2>
                <div class="ia-details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Modelo:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($ia['modelo']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Construção:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($ia['construcao']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Comportamento:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($ia['comportamento']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Audiência:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($ia['audiencia']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Preço:</span>
                        <span class="detail-value"><?php echo htmlspecialchars(number_format($ia['preco'], 2, ',', '.')); ?> €</span>
                    </div>
                    <div class="detail-item full-width">
                        <span class="detail-label">Descrição:</span>
                        <span class="detail-value"><?php echo nl2br(htmlspecialchars($ia['descricao'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Botão Voltar -->
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