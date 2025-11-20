<?php
require_once '../config/db.php';

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
    $stmt->execute([':id' => $_GET['id']]);
}

header('Location: utilisateurs.php');
exit;
