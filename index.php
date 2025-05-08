<?php
session_start();
include 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Verifica se o usuário é um editor
$is_editor = isset($_SESSION['is_editor']) && $_SESSION['is_editor'] == 1;

// Filtros
$filtro_nome = $_GET['nome'] ?? '';
$filtro_modelo = $_GET['modelo'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';

// Query base
$query = "
SELECT * 
FROM inteligencias_artificiais
WHERE 1=1
";

// Aplicar filtros (PARTE MODIFICADA)
if (!empty($filtro_nome)) {
$query .= " AND nome LIKE :nome";
}
if (!empty($filtro_modelo)) {
$query .= " AND modelo LIKE :modelo";
}
if (!empty($filtro_categoria)) {
$query .= " AND categoria_id = :categoria"; // Usar categoria_id
}
$stmt = $conn->prepare($query);

if (!empty($filtro_nome)) {
    $stmt->bindValue(':nome', "%$filtro_nome%");
}
if (!empty($filtro_modelo)) {
    $stmt->bindValue(':modelo', "%$filtro_modelo%");
}
if (!empty($filtro_categoria)) {
    $stmt->bindValue(':categoria', $filtro_categoria); // Sem wildcards (%)
}

$stmt->execute();
$ias = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Filtro por categoria
$filtro_categoria = $_GET['categoria'] ?? '';

// Query ajustada:
$query = "
    SELECT ia.*, c.nome AS categoria_nome 
    FROM inteligencias_artificiais ia
    LEFT JOIN categorias c ON ia.categoria_id = c.id
    WHERE 1=1
";

if (!empty($filtro_categoria)) {
    $query .= " AND c.id = :categoria";
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca de IAs</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Sidebar com categorias -->
    <div id="sidebar" class="sidebar">
    <ul>
        <?php
        $stmt = $conn->query("SELECT * FROM categorias");
        $categorias = $stmt->fetchAll();
        foreach ($categorias as $categoria) {
            echo "<li><a href='?categoria={$categoria['id']}'>{$categoria['nome']}</a></li>";
        }
        ?>
    </ul>
</div>

    <!-- Conteúdo principal -->
    <div class="main-content">
        <!-- Botão para exibir/ocultar categorias -->
        <button id="btn-categorias" class="btn-categorias">☰</button>

        <!-- Header com título, pesquisa e botão Sair -->
        <header class="header">
            <h1>Biblioteca de Inteligências Artificiais</h1>
            <div class="header-content">
                <form method="GET" action="" class="search-form">
                    <div class="search-container">
                        <input type="text" id="nome" name="nome" placeholder="Pesquisar por nome" value="<?php echo $filtro_nome; ?>">
                        <button type="submit" class="search-button">🔍</button>
                        <a href="index.php" class="btn-limpar">Limpar Filtros</a>
                    </div>
                </form>
                <a href="logout.php" class="btn-sair">Sair</a>
            </div>
        </header>

        <!-- Galeria de IAs -->
        <div class="gallery">
    <?php foreach ($ias as $ia): ?>
        <div class="gallery-item">
            <div class="logo-container">
                <!-- Exibe o logotipo da IA no hover -->
                <?php if (!empty($ia['imagem_logotipo'])): ?>
                    <img src="<?php echo $ia['imagem_logotipo']; ?>" alt="<?php echo $ia['nome']; ?>" class="logo-ia">
                <?php endif; ?>

                <!-- Overlay individual para cada item -->
                <div class="overlay">
                    <div class="mini-descricao">
                        <?php echo $ia['descricao']; ?>
                    </div>
                    <a href="detalhes_ia.php?id=<?php echo $ia['id']; ?>" class="saiba-mais">Saiba mais →</a>
                </div>
            </div>
            <div class="nome-ia"><?php echo $ia['nome']; ?></div>
        </div>
    <?php endforeach; ?>
</div>
        <!-- Botão "Adicionar Nova IA" (apenas para editores) -->
        <?php if ($is_editor): ?>
    <a href="adicionar.php" class="btn-adicionar">Adicionar Nova IA</a>
<?php endif; ?>

    <script>
         document.getElementById('btn-categorias').addEventListener('click', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    this.classList.toggle('sidebar-visible');
    sidebar.classList.toggle('sidebar-visible');
    mainContent.classList.toggle('sidebar-visible');
});
        // Função para exibir/ocultar a sidebar
        document.getElementById('btn-categorias').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            if (sidebar.classList.contains('sidebar-visible')) {
                sidebar.classList.remove('sidebar-visible');
                mainContent.classList.remove('sidebar-visible');
            } else {
                sidebar.classList.add('sidebar-visible');
                mainContent.classList.add('sidebar-visible');
            }
        });
       
    </script>
    <script>
    // Função para limitar o texto a 3 linhas
    function limitarTexto() {
        // Seleciona todas as mini descrições
        const descricoes = document.querySelectorAll('.mini-descricao');

        descricoes.forEach(descricao => {
            // Define a altura máxima como 4.5em (3 linhas de 1.5em cada)
            const alturaMaxima = 4.5 * parseFloat(getComputedStyle(descricao).fontSize);

            // Verifica se a altura do texto ultrapassa a altura máxima
            while (descricao.scrollHeight > alturaMaxima) {
                // Remove o último caractere do texto
                descricao.textContent = descricao.textContent.slice(0, -1);
            }

            // Adiciona "..." ao final do texto cortado
            descricao.textContent += '...';
        });
    }
    // Executa a função ao carregar a página
    window.onload = limitarTexto;
</script>
</body>
</html>