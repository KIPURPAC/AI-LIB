<?php
session_start();
include 'conexao.php';

$erro = '';
$sucesso = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $is_editor = isset($_POST['is_editor']) && $_POST['is_editor'] === 'on';

    // Validação do nome de utilizador
    if (empty($username)) {
        $erro = "O nome de utilizador é obrigatório.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $erro = "O nome de utilizador deve conter apenas letras, números e underscores.";
    }

    // Validação da senha
    if (empty($password) || empty($confirm_password)) {
        $erro = "As senhas são obrigatórias.";
    } elseif ($password !== $confirm_password) {
        $erro = "As senhas não coincidem.";
    } elseif (!validarSenha($password)) {
        $erro = "A senha deve ter no mínimo 8 caracteres, incluindo maiúsculas, minúsculas, números e caracteres especiais (!@#$%^&*).";
    }

    // Verifica se o utilizador já existe
    if (empty($erro)) {
        try {
            $stmt = $conn->prepare("SELECT id FROM utilizadores WHERE username = :username");
            $stmt->execute(['username' => $username]);

            if ($stmt->rowCount() > 0) {
                $erro = "Nome de usuário já existe! Escolha outro.";
            } else {
                // Se for editor, apenas marca como editor sem chave
                $chave_editor = null;
                if ($is_editor) {
                    $chave_editor = bin2hex(random_bytes(16)); // Gera uma chave única
                }

                if (empty($erro)) {
                    // Criptografa a senha
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Insere o novo utilizador no banco de dados
                    $stmt = $conn->prepare("INSERT INTO utilizadores (username, password, is_editor, chave_editor) VALUES (:username, :password, :is_editor, :chave_editor)");
                    $stmt->execute([
                        'username' => $username,
                        'password' => $hashed_password,
                        'is_editor' => $is_editor ? 1 : 0,
                        'chave_editor' => $chave_editor
                    ]);

                    $sucesso = "Registro realizado com sucesso! <a href='login.php'>Faça login</a>";

                    // Se o utilizador for um editor, exibe a chave gerada
                    if ($is_editor && $chave_editor) {
                        $sucesso .= "<br>Sua chave de editor é: <strong>$chave_editor</strong>. Guarde-a com segurança!";
                    }
                }
            }
        } catch (PDOException $e) {
            $erro = "Erro ao processar o registro: " . $e->getMessage();
        }
    }
}

function validarSenha($senha) {
    $minimo_caracteres = 8;
    $tem_maiuscula = preg_match('/[A-Z]/', $senha);
    $tem_minuscula = preg_match('/[a-z]/', $senha);
    $tem_numero = preg_match('/[0-9]/', $senha);
    $tem_especial = preg_match('/[!@#$%^&*]/', $senha);

    return strlen($senha) >= $minimo_caracteres && $tem_maiuscula && $tem_minuscula && $tem_numero && $tem_especial;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar - Biblioteca de IAs</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="styles.css">
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
            <h2>Registrar</h2>
            <?php if ($erro): ?>
                <p style="color: #ef4444; text-align: center;"><?php echo htmlspecialchars($erro); ?></p>
            <?php endif; ?>
            <?php if ($sucesso): ?>
                <p style="color: #065f46; text-align: center;"><?php echo $sucesso; ?></p>
            <?php endif; ?>
            <form method="POST" action="">
                <label for="username">Nome de usuário:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                <label for="password">Senha:</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" required>
                    <button type="button" id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: none; background: none; cursor: pointer;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>

                <label for="confirm_password">Confirmar Senha:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>

                <div style="margin: 15px 0;">
                    <label>
                        <input type="checkbox" name="is_editor" id="is_editor" value="1"> Desejo ser um editor
                    </label>
                </div>

                <div id="editor-key-section" style="display: none; margin-bottom: 15px;">
                    <p>Para se registrar como editor, clique no botão abaixo para enviar um e-mail de solicitação.</p>
                    <p><strong>Exemplo de e-mail:</strong></p>
                    <p>Assunto: Pedido de Chave de Editor<br>
                    Corpo: Olá, gostaria de solicitar uma chave de editor para a Biblioteca de IAs. Meu nome de usuário é [Insira seu nome de utilizador aqui]. Atenciosamente, [Seu nome].</p>
                    <button type="button" id="requestEditorKey" class="register-btn" style="margin-top: 10px;">Solicitar Chave de Editor</button>
                </div>

                <input type="submit" value="Registrar" class="register-btn">
            </form>
            <div class="register-link">
                <p>Já tem uma conta? <a href="login.php">Faça login</a></p>
            </div>
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
            applyTheme(this.value);
        });

        // Mostrar/Ocultar senha
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.querySelector('svg').innerHTML = type === 'password' ? 
                '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>' : 
                '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>';
        });

        // Mostrar/Ocultar seção de chave de editor
        const isEditorCheckbox = document.getElementById('is_editor');
        const editorKeySection = document.getElementById('editor-key-section');
        isEditorCheckbox.addEventListener('change', function() {
            editorKeySection.style.display = this.checked ? 'block' : 'none';
        });

        // Ação de solicitação de chave
        document.getElementById('requestEditorKey').addEventListener('click', function() {
            const username = document.getElementById('username').value || '[Insira seu nome de utilizador aqui]';
            const subject = encodeURIComponent('Pedido de Chave de Editor');
            const body = encodeURIComponent(`Olá, gostaria de solicitar uma chave de editor para a Biblioteca de IAs.\n\nNome de usuário: ${username}\n\nAtenciosamente,\n[Seu nome]`);
            window.location.href = `mailto:anitamaxvinn22@gmail.com?subject=${subject}&body=${body}`;
        });

        window.onload = loadTheme;
    </script>
</body>
</html>