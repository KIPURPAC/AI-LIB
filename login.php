<?php
session_start();
include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_editor'] = $user['is_editor'];
        header("Location: index.php");
        exit();
    } else {
        $erro = "Utilizador ou senha inválidos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Biblioteca de IAs</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Novo Header -->
    <header class="header">
        <div class="header-wrapper">
            <!-- Título e Subtítulo -->
            <div class="header-branding">
                <h1 class="header-title">Biblioteca de IAs</h1>
                <p class="header-subtitle">Explore o Futuro da Inteligência Artificial</p>
            </div>
            <!-- Seletor de Temas -->
            <div class="header-actions">
                <div class="header-controls">
                    <select id="theme-selector" class="theme-selector">
                        <option value="light">Tema Claro</option>
                        <option value="dark">Tema Escuro</option>
                        <option value="cosmic-blue">Azul Cósmico</option>
                        <option value="forest-green">Verde Floresta</option>
                    </select>
                </div>
            </div>
        </div>
    </header>

    <!-- Conteúdo principal -->
    <div class="main-content">
        <div class="form-container">
            <h2>Login</h2>
            <?php if (isset($erro)): ?>
                <p style="color: #ef4444; text-align: center;"><?php echo htmlspecialchars($erro); ?></p>
            <?php endif; ?>
            <form method="POST" action="">
                <label for="username">Utilizador:</label>
                <input type="text" id="username" name="username" required>
                
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" required>
                
                <input type="submit" value="Entrar">
            </form>
        </div>
    </div>

    <script>
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