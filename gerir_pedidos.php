<?php
session_start();
include 'conexao.php';

// Verifica se o utilizador está autenticado e é admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_editor']) || $_SESSION['is_editor'] != 2) {
    header("Location: login.php");
    exit();
}

$is_editor = $_SESSION['is_editor'] == 1;
$is_admin = $_SESSION['is_editor'] == 2;

// Processa a aprovação/rejeição de pedidos
if (isset($_GET['approve'])) {
    $id = $_GET['approve'];
    $stmt = $conn->prepare("UPDATE users SET is_editor = 1 WHERE id = :id AND is_editor = 0");
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        $stmt = $conn->prepare("DELETE FROM editor_requests WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $id);
        $stmt->execute();
        $_SESSION['mensagem_sucesso'] = "Pedido aprovado com sucesso!";
        header("Location: gerir_pedidos.php");
        exit();
    } else {
        $erro = "Erro ao aprovar pedido.";
    }
}

if (isset($_GET['reject'])) {
    $id = $_GET['reject'];
    $stmt = $conn->prepare("DELETE FROM editor_requests WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $id);
    if ($stmt->execute()) {
        $_SESSION['mensagem_sucesso'] = "Pedido rejeitado com sucesso!";
        header("Location: gerir_pedidos.php");
        exit();
    } else {
        $erro = "Erro ao rejeitar pedido.";
    }
}

// Obtém todos os pedidos pendentes
$stmt = $conn->query("SELECT er.*, u.username FROM editor_requests er JOIN users u ON er.user_id = u.id");
$pedidos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Pedidos de Editor - Biblioteca de IAs</title>
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
                                <line x1="21" y1="12" x2="9" y2