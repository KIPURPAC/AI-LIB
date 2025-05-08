<link rel="stylesheet" href="styles.css">
<?php
session_start();
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $is_editor = isset($_POST['is_editor']) ? 1 : 0; // Verifica se o usuário quer ser editor
    $editor_key = $_POST['editor_key'] ?? ''; // Chave de editor fornecida pelo usuário

    // Verifica se as senhas coincidem
    if ($password !== $confirm_password) {
        echo "As senhas não coincidem. Tente novamente.";
        exit();
    }

    // Valida a senha
    if (!validarSenha($password)) {
        echo "A senha deve ter pelo menos 8 caracteres, incluindo uma letra maiúscula, uma minúscula, um número e um caractere especial.";
        exit();
    }

    // Verifica se o usuário já existe
    $stmt = $conn->prepare("SELECT id FROM utilizadores WHERE username = :username");
    $stmt->execute(['username' => $username]);

    if ($stmt->rowCount() > 0) {
        echo "Nome de usuário já existe! Escolha outro.";
    } else {
        // Verifica se a chave de editor foi fornecida e está correta
        $chave_editor = null;
        if ($is_editor && !empty($editor_key)) {
            $chave_editor = bin2hex(random_bytes(16)); // Gera uma chave única
        }

        // Criptografa a senha
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insere o novo usuário no banco de dados
        $stmt = $conn->prepare("INSERT INTO utilizadores (username, password, is_editor, chave_editor) VALUES (:username, :password, :is_editor, :chave_editor)");
        $stmt->execute([
            'username' => $username,
            'password' => $hashed_password,
            'is_editor' => $is_editor,
            'chave_editor' => $chave_editor
        ]);

        echo "Registro realizado com sucesso! <a href='login.php'>Faça login</a>";

        // Se o usuário for um editor, exibe a chave gerada
        if ($is_editor && $chave_editor) {
            echo "<br>Sua chave de editor é: <strong>$chave_editor</strong>. Guarde-a com segurança!";
        }
    }
}

function validarSenha($senha) {
    $minimo_caracteres = 8;
    $tem_maiuscula = preg_match('/[A-Z]/', $senha);
    $tem_minuscula = preg_match('/[a-z]/', $senha);
    $tem_numero = preg_match('/[0-9]/', $senha);
    $tem_especial = preg_match('/[!@#$%^&*]/', $senha);

    if (strlen($senha) < $minimo_caracteres || !$tem_maiuscula || !$tem_minuscula || !$tem_numero || !$tem_especial) {
        return false; // Senha fraca
    }
    return true; // Senha forte
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Registrar</title>
    <style>
        .editor-field {
            display: none; /* Oculta o campo por padrão */
        }
        .senha-fraca {
            color: red;
            font-size: 0.9em;
        }
        .senha-forte {
            color: green;
            font-size: 0.9em;
        }
        .mostrar-senha {
            cursor: pointer;
            margin-left: 10px;
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

        function validarSenha(senha) {
            const minCaracteres = 8;
            const temMaiuscula = /[A-Z]/.test(senha);
            const temMinuscula = /[a-z]/.test(senha);
            const temNumero = /[0-9]/.test(senha);
            const temEspecial = /[!@#$%^&*]/.test(senha);

            return (
                senha.length >= minCaracteres &&
                temMaiuscula &&
                temMinuscula &&
                temNumero &&
                temEspecial
            );
        }

        function atualizarMensagemSenha() {
            const senha = document.getElementById('password').value;
            const mensagemSenha = document.getElementById('mensagem-senha');

            if (validarSenha(senha)) {
                mensagemSenha.textContent = "Senha forte!";
                mensagemSenha.className = "senha-forte";
            } else {
                mensagemSenha.textContent = "A senha deve ter pelo menos 8 caracteres, incluindo uma letra maiúscula, uma minúscula, um número e um caractere especial.";
                mensagemSenha.className = "senha-fraca";
            }
        }

        function toggleSenha() {
            const senhaInput = document.getElementById('password');
            const confirmSenhaInput = document.getElementById('confirm_password');
            const botaoMostrarSenha = document.getElementById('mostrar-senha');

            if (senhaInput.type === "password") {
                senhaInput.type = "text";
                confirmSenhaInput.type = "text";
                botaoMostrarSenha.textContent = "Ocultar Senha";
            } else {
                senhaInput.type = "password";
                confirmSenhaInput.type = "password";
                botaoMostrarSenha.textContent = "Mostrar Senha";
            }
        }

        function requestEditorKey() {
            const email = 'anitamaxvinn22@gmail.com';
            const subject = 'Pedido de Chave de Editor';
            const body = `Olá,\n\nGostaria de solicitar uma chave de editor para a Biblioteca de IAs.\n\nNome de usuário: [Insira seu nome de usuário aqui]\n\nAtenciosamente,\n[Seu nome]`;

            // Abre o cliente de e-mail padrão com o pedido pré-preenchido
            window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        }
    </script>
</head>
<body>
    <h1>Registrar</h1>
    <form method="POST">
        <label for="username">Nome de usuário:</label>
        <input type="text" id="username" name="username" required><br><br>

        <label for="password">Senha:</label>
        <input type="password" id="password" name="password" required oninput="atualizarMensagemSenha()">
        <button type="button" id="mostrar-senha" class="mostrar-senha" onclick="toggleSenha()">Mostrar Senha</button><br>
        <span id="mensagem-senha" class="senha-fraca"></span><br>

        <label for="confirm_password">Confirmar Senha:</label>
        <input type="password" id="confirm_password" name="confirm_password" required><br><br>

        <!-- Opção para ser editor -->
        <label>
            <input type="checkbox" name="is_editor" id="is_editor" onclick="toggleEditorField()">
            Desejo ser um editor
        </label><br><br>

        <!-- Campo para solicitar chave de editor (oculto por padrão) -->
        <div id="editor-field" class="editor-field">
            <p>
                Para obter uma chave de editor, clique no botão abaixo para enviar um e-mail de solicitação.
                <strong>Exemplo de e-mail:</strong><br>
                Assunto: Pedido de Chave de Editor<br>
                Corpo: Olá, gostaria de solicitar uma chave de editor para a Biblioteca de IAs. Meu nome de usuário é [Insira seu nome de usuário aqui]. Atenciosamente, [Seu nome].
            </p>
            <button type="button" onclick="requestEditorKey()">Solicitar Chave de Editor</button><br><br>
        </div>

        <button type="submit">Registrar</button>
    </form>
    <br>
    <a href="login.php">Já tem uma conta? Faça login</a>
</body>
</html>