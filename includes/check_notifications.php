<?php
require_once '../config/db.php';

// Démarre la session seulement si elle n’est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['utilisateur']['id'])) {
    echo json_encode(['new' => false, 'notifications' => []]);
    exit;
}

$user_id = $_SESSION['utilisateur']['id'];
$role = $_GET['role'] ?? $_SESSION['utilisateur']['role'] ?? null;

// Vérifie le rôle
$allowed_roles = ['infirmier', 'medecin', 'admin'];
if (!in_array($role, $allowed_roles)) {
    echo json_encode(['new' => false, 'error' => 'Rôle non autorisé']);
    exit;
}

try {
    // Récupère toutes les notifications non vues pour l'utilisateur
    $stmt = $pdo->prepare("
        SELECT id, message, date_creation 
        FROM notifications 
        WHERE utilisateur_id = ? AND (vu = 0 OR vu IS NULL)
        ORDER BY date_creation DESC
    ");
    $stmt->execute([$user_id]);
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($notifs) {
        // Récupère les IDs pour marquer comme vues
        $notif_ids = array_column($notifs, 'id');

        // Prépare la requête IN dynamiquement
        $in = str_repeat('?,', count($notif_ids) - 1) . '?';
        $update = $pdo->prepare("UPDATE notifications SET vu = 1 WHERE id IN ($in)");
        $update->execute($notif_ids);

        echo json_encode([
            'new' => true,
            'count' => count($notifs),
            'notifications' => $notifs
        ]);
    } else {
        echo json_encode([
            'new' => false,
            'notifications' => []
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'new' => false,
        'error' => 'Erreur SQL : ' . $e->getMessage()
    ]);
}
