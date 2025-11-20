<?php
require_once '../includes/init.php';
checkRole('medecin');
include '../includes/header.php';

$consultation_id = $_GET['id'] ?? null;
$medecin_id = $_SESSION['utilisateur']['id'];

if (!$consultation_id) {
    echo "<p style='color:red;'>❌ Consultation non spécifiée.</p>";
    exit;
}

// Récupérer les détails de la consultation + réponse
$stmt = $pdo->prepare("
    SELECT c.*, 
           p.nom AS patient_nom, 
           p.prenom AS patient_prenom,
           p.sexe, 
           p.date_naissance,
           r.diagnostic, 
           r.ordonnance, 
           r.fichier_ordonnance
    FROM consultations c
    JOIN patients p ON c.patient_id = p.id
    JOIN reponses_medicales r ON r.consultation_id = c.id
    WHERE c.id = :id AND r.medecin_id = :medecin_id AND c.statut = 'traitée'
");
$stmt->execute([
    ':id' => $consultation_id,
    ':medecin_id' => $medecin_id
]);
$consultation = $stmt->fetch();

if (!$consultation) {
    echo "<p style='color:red;'>❌ Consultation introuvable, non traitée ou accès non autorisé.</p>";
    exit;
}
?>

<h2>🩺 Consultation complète du <?= htmlspecialchars($consultation['date_consultation']) ?></h2>

<p><strong>👤 Patient :</strong> <?= htmlspecialchars($consultation['patient_prenom'] . ' ' . $consultation['patient_nom']) ?></p>
<p><strong>📅 Naissance :</strong> <?= htmlspecialchars($consultation['date_naissance']) ?> | 
   <strong>Sexe :</strong> <?= htmlspecialchars($consultation['sexe']) ?></p>
<p><strong>🕒 Date consultation :</strong> <?= htmlspecialchars($consultation['date_consultation']) ?></p>
<p><strong>❤️ Tension :</strong> <?= htmlspecialchars($consultation['tension']) ?></p>
<p><strong>🌡️ Température :</strong> <?= htmlspecialchars($consultation['temperature']) ?></p>
<p><strong>📝 Symptômes :</strong><br><?= nl2br(htmlspecialchars($consultation['symptomes'])) ?></p>
<p><strong>📄 Observations infirmier :</strong><br><?= nl2br(htmlspecialchars($consultation['observations'])) ?></p>

<hr>

<h3>✅ Réponse médicale</h3>
<p><strong>🔍 Diagnostic :</strong><br><?= nl2br(htmlspecialchars($consultation['diagnostic'])) ?></p>
<p><strong>💊 Ordonnance (texte) :</strong><br><?= nl2br(htmlspecialchars($consultation['ordonnance'])) ?></p>

<?php if (!empty($consultation['fichier_ordonnance'])): ?>
    <p><strong>📎 Fichier ordonnance :</strong> 
        <a href="../uploads/<?= htmlspecialchars($consultation['fichier_ordonnance']) ?>" target="_blank">Voir le fichier</a>
    </p>
<?php endif; ?>

<p><a href="historique_consultations.php">← Retour à l'historique</a></p>

<?php include '../includes/footer.php'; ?>
