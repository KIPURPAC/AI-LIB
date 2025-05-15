<?php
session_start();
include 'conexao.php';

// Verifica se o utilizador está autenticado e é editor
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['is_editor']) || $_SESSION['is_editor'] != 1) {
    echo "Acesso negado! Não é um editor.";
    exit();
}

// Obtém o ID da IA a ser excluída
$ia_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if ($ia_id === false) {
    echo "ID inválido.";
    exit();
}

// Verifica se a IA existe
$stmt = $conn->prepare("SELECT * FROM inteligencias_artificiais WHERE id = :id");
$stmt->execute(['id' => $ia_id]);
$ia = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($ia)) {
    echo "IA não encontrada.";
    exit();
}

// Exclui a IA
$stmt = $conn->prepare("DELETE FROM inteligencias_artificiais WHERE id = :id");
$stmt->execute(['id' => $ia_id]);

// Remove imagens associadas (opcional)
$imagens = [$ia['imagem_login'], $ia['imagem_interface'], $ia['imagem_resultados'], $ia['imagem_historico'], $ia['imagem_logotipo']];
foreach ($imagens as $imagem) {
    if (!empty($imagem) && file_exists($imagem)) {
        unlink($imagem);
    }
}

$_SESSION['mensagem_sucesso'] = "IA excluída com sucesso!";
header("Location: index.php");
exit();
?>