<link rel="stylesheet" href="styles.css">
<?php
session_start();
include 'conexao.php';

// Verifica se o usuário já excedeu o número máximo de tentativas
if (!isset($_SESSION['tentativas_login'])) {
    $_SESSION['tentativas_login'] = 0;
}

if ($_SESSION['tentativas_login'] >= 3) {
    echo "Você excedeu o número máximo de tentativas. Tente novamente mais tarde.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $editor_key = $_POST['editor_key'] ?? ''; // Chave de editor fornecida pelo usuário

    // Consulta o usuário no banco de dados
    $stmt = $conn->prepare("SELECT * FROM utilizadores WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Verifica se a chave de editor foi fornecida e está correta
        if (!empty($editor_key) && $editor_key === $user['chave_editor']) {
            $_SESSION['is_editor'] = 1; // Usuário é um editor
        } else {
            $_SESSION['is_editor'] = 0; // Usuário não é um editor
        }

        $_SESSION['tentativas_login'] = 0; // Reseta o contador
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        header("Location: index.php");
        exit();
    } else {
        // Senha incorreta
        $_SESSION['tentativas_login']++; // Incrementa o contador
        echo "Senha incorreta. Tentativas restantes: " . (3 - $_SESSION['tentativas_login']);
    }
}

// Verifica se o usuário já solicitou uma chave de editor
if (isset($_GET['username'])) {
    $username = $_GET['username'];
    $stmt = $conn->prepare("SELECT chave_solicitada FROM utilizadores WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['chave_solicitada'] == 0) {
        // Atualiza o status para "chave solicitada"
        $stmt = $conn->prepare("UPDATE utilizadores SET chave_solicitada = 1 WHERE username = :username");
        $stmt->execute(['username' => $username]);

        // Abre o cliente de e-mail padrão com o pedido pré-preenchido
        $email = 'anitamaxvinn22@gmail.com';
        $subject = 'Pedido de Chave de Editor';
        $body = "Olá,\n\nGostaria de solicitar uma chave de editor para a Biblioteca de IAs.\n\nNome de usuário: $username\n\nAtenciosamente,\n[Seu nome]";
        header("Location: mailto:$email?subject=" . urlencode($subject) . "&body=" . urlencode($body));
        exit();
    } else {
        echo "Você já solicitou uma chave de editor anteriormente.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        .editor-field {
            display: none; /* Oculta o campo por padrão */
        }
        .mostrar-senha {
            cursor: pointer;
            margin-left: 10px;
        }
        .mensagem-suporte {
            font-size: 0.9em;
            color: #666;
            margin-top: 10px;
        }
    </style>
    <script>
        function toggleEditorField() {
            const editorField = document.getElementById('editor-field');
            if (editorField.style.display === 'none') {
                editorField.style.display = 'block'; // Mostra o campo
            } else {
                editorField.style.display = 'none'; // Oculta o campo
            }
        }

        function toggleSenha() {
            const senhaInput = document.getElementById('password');
            const botaoMostrarSenha = document.getElementById('mostrar-senha');

            if (senhaInput.type === "password") {
                senhaInput.type = "text";
                botaoMostrarSenha.textContent = "Ocultar Senha";
            } else {
                senhaInput.type = "password";
                botaoMostrarSenha.textContent = "Mostrar Senha";
            }
        }
    </script>
</head>
<body>
    <h1>Login</h1>
    <form method="POST">
        <label for="username">Nome de usuário:</label>
        <input type="text" id="username" name="username" required><br><br>

        <label for="password">Senha:</label>
        <input type="password" id="password" name="password" required>
        <button type="button" id="mostrar-senha" class="mostrar-senha" onclick="toggleSenha()">Mostrar Senha</button><br><br>

        <!-- Botão para mostrar/ocultar o campo da chave de editor -->
        <button type="button" onclick="toggleEditorField()">Sou um editor</button><br><br>

        <!-- Campo para introduzir a chave de editor (oculto por padrão) -->
        <div id="editor-field" class="editor-field">
            <label for="editor_key">Chave de Editor:</label>
            <input type="password" id="editor_key" name="editor_key"><br><br>

            <!-- Mensagem sobre a chave não ser recuperável -->
            <div class="mensagem-suporte">
                <strong>Atenção:</strong> A chave de editor não é recuperável. Em caso de perda, entre em contato com o suporte:
                <a href="mailto:anitamaxvinn22@gmail.com">Linha de suporte</a>.
            </div>
        </div>

        <!-- Botão de login (sempre visível) -->
        <button type="submit">Entrar</button>
    </form>
    <br>
    <a href="registrar.php">Não tem uma conta? Registre-se</a>
</body>
</html>