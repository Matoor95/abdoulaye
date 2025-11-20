<?php
require_once '../config/db.php';
session_start();

if (!isset($_GET['id'])) {
    echo "ID de consultation manquant.";
    exit;
}

$consultation_id = intval($_GET['id']);
$infirmier_id = $_SESSION['utilisateur']['id'];

// Récupération de la consultation
$stmt = $pdo->prepare("SELECT * FROM consultations WHERE id = ? AND infirmier_id = ?");
$stmt->execute([$consultation_id, $infirmier_id]);
$consultation = $stmt->fetch();

if (!$consultation) {
    echo "Consultation introuvable ou non autorisée.";
    exit;
}

if ($consultation['statut'] === 'traitée') {
    echo "❌ Impossible de supprimer une consultation déjà traitée.";
    exit;
}

// Suppression du fichier lié s’il existe
if (!empty($consultation['fichier'])) {
    $chemin = '../uploads/' . $consultation['fichier'];
    if (file_exists($chemin)) {
        unlink($chemin); // supprime le fichier
    }
}

// Suppression de la consultation
$delete = $pdo->prepare("DELETE FROM consultations WHERE id = ?");
$delete->execute([$consultation_id]);

header("Location: consultations.php?message=supprimée");
exit;
