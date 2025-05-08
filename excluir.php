<link rel="stylesheet" href="styles.css">
<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['is_editor']) || $_SESSION['is_editor'] != 1) {
    echo "Acesso negado! Você não é um editor.";
    exit();
}
include 'conexao.php';

$id = $_GET['id'];
$stmt = $conn->prepare("DELETE FROM inteligencias_artificiais WHERE id = :id");
$stmt->execute(['id' => $id]);

header("Location: index.php");
exit();
?>