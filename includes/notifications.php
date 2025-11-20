<?php
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

/* ==========================================================
   🔔 Fonction : Ajouter une notification
========================================================== */
function ajouterNotification($pdo, $utilisateur_id, $message)
{
    $stmt = $pdo->prepare("
        INSERT INTO notifications (utilisateur_id, message, date_creation, vu, lu)
        VALUES (:utilisateur_id, :message, NOW(), 0, 0)
    ");
    $stmt->execute([
        ':utilisateur_id' => $utilisateur_id,
        ':message' => $message
    ]);
}

/* ==========================================================
   🔍 Vérifie les nouvelles notifications (appel AJAX)
========================================================== */
if (isset($_GET['check'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['utilisateur']['id'])) {
        echo json_encode(['new' => false]);
        exit;
    }

    $user_id = $_SESSION['utilisateur']['id'];

    // Sélectionne les notifications non vues
    $stmt = $pdo->prepare("
        SELECT id, message
        FROM notifications
        WHERE utilisateur_id = ? AND vu = 0
        ORDER BY date_creation DESC
    ");
    $stmt->execute([$user_id]);
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($notifs) {
        // Marque comme vues après récupération
        $ids = array_column($notifs, 'id');
        $in = str_repeat('?,', count($ids) - 1) . '?';
        $pdo->prepare("UPDATE notifications SET vu = 1 WHERE id IN ($in)")->execute($ids);

        echo json_encode([
            'new' => true,
            'notifications' => $notifs
        ]);
    } else {
        echo json_encode(['new' => false]);
    }
    exit;
}
