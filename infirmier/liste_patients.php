<?php
require_once '../includes/init.php';  
include '../includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$centre_id = $_SESSION['utilisateur']['centre_id'] ?? null;
$message = '';
// ..

$stmt = $pdo->prepare("SELECT * FROM patients WHERE centre_id = :centre_id ORDER BY nom ASC");
$stmt->execute([':centre_id' => $centre_id]);
$patients = $stmt->fetchAll();
?>

<h2>📋 Liste des patients</h2>

<?php if (empty($patients)): ?>
    <p>Aucun patient trouvé pour ce centre.</p>
<?php else: ?>
<table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse: collapse;">
    <thead style="background: #2ecc71; color: white;">
        <tr>
            <th>ID</th>
            <th>Nom complet</th>
            <th>Date de naissance</th>
            <th>Sexe</th>
            <th>Téléphone</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($patients as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['id']) ?></td>
            <td><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></td>
            <td><?= htmlspecialchars($p['date_naissance']) ?></td>
            <td><?= htmlspecialchars($p['sexe']) ?></td>
            <td><?= htmlspecialchars($p['telephone']) ?></td>
            <td><?= htmlspecialchars($p['statut']) ?></td>
            <td>
        <a href="suivi_patient.php?id=<?= $p['id'] ?>">📖 Suivi</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<br>
<a href="dashboard.php"><button>← Retour au dashboard</button></a>

<?php include '../includes/footer.php'; ?>
