<?php
require_once '../config/db.php';
session_start();
include '../includes/header.php';

// Vérification du rôle (médecin par exemple)
if (!isset($_SESSION['utilisateur']['role']) || $_SESSION['utilisateur']['role'] !== 'medecin') {
    header('Location: ../index.php');
    exit;
}

$medecin_id = $_SESSION['utilisateur']['id'];

// Récupérer l'historique des consultations traitées par ce médecin
$stmt = $pdo->prepare("
    SELECT c.id AS consultation_id,
           c.date_consultation,
           p.nom AS patient_nom,
           p.prenom AS patient_prenom,
           p.sexe,
           p.date_naissance,
           r.diagnostic,
           r.ordonnance,
           r.fichier_ordonnance,
           r.created_at AS date_reponse
    FROM consultations c
    JOIN patients p ON c.patient_id = p.id
    JOIN reponses_medicales r ON r.consultation_id = c.id
    WHERE c.medecin_id = :medecin_id
      AND c.statut = 'traitée'
    ORDER BY r.created_at DESC
");
$stmt->execute([':medecin_id' => $medecin_id]);
$historique = $stmt->fetchAll();
?>

<h2>📜 Historique des consultations traitées</h2>

<?php if (empty($historique)): ?>
    <p>Aucune consultation traitée pour l’instant.</p>
<?php else: ?>
    <table border="1" cellpadding="10" cellspacing="0" width="100%">
        <thead style="background-color: #dcdcdc; color: #000;">
            <tr>
                <th>ID</th>
                <th>Date consultation</th>
                <th>Patient</th>
                <th>Sexe</th>
                <th>Naissance</th>
                <th>Diagnostic</th>
                <th>Ordonnance (texte)</th>
                <th>Fichier ordonnance</th>
                <th>Date réponse</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($historique as $h): ?>
                <tr>
                    <td><?= htmlspecialchars($h['consultation_id']) ?></td>
                    <td><?= htmlspecialchars($h['date_consultation']) ?></td>
                    <td><?= htmlspecialchars($h['patient_prenom'] . ' ' . $h['patient_nom']) ?></td>
                    <td><?= htmlspecialchars($h['sexe']) ?></td>
                    <td><?= htmlspecialchars($h['date_naissance']) ?></td>
                    <td><?= nl2br(htmlspecialchars($h['diagnostic'])) ?></td>
                    <td><?= nl2br(htmlspecialchars($h['ordonnance'])) ?></td>
                    <td>
                        <?php if (!empty($h['fichier_ordonnance'])): ?>
                            <a href="../uploads/<?= htmlspecialchars($h['fichier_ordonnance']) ?>" target="_blank">📎 Voir</a>
                        <?php else: ?>
                            Aucun
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($h['date_reponse']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p><a href="dashboard.php">← Retour au tableau de bord</a></p>

<?php include '../includes/footer.php'; ?>
