<?php
require_once '../includes/init.php';

// Protéger la page aux infirmiers seulement (ou tu peux adapter le rôle)
checkRole('infirmier');

// Récupérer l'id patient passé en GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    exit('Patient invalide.');
}
$patient_id = (int) $_GET['id'];

// Récupérer les infos du patient (en s'assurant qu'il appartient au centre de l'infirmier)
$centreId = $_SESSION['utilisateur']['centre_id'];

$stmtPatient = $pdo->prepare("SELECT * FROM patients WHERE id = :id AND centre_id = :centre_id");
$stmtPatient->execute([':id' => $patient_id, ':centre_id' => $centreId]);
$patient = $stmtPatient->fetch();

if (!$patient) {
    exit('Patient non trouvé ou accès refusé.');
}

// Récupérer les consultations du patient
$stmtConsults = $pdo->prepare("SELECT * FROM consultations WHERE patient_id = :patient_id ORDER BY date_consultation DESC");
$stmtConsults->execute([':patient_id' => $patient_id]);
$consultations = $stmtConsults->fetchAll();

// Ici tu pourrais gérer l'ajout de nouveaux suivis via formulaire POST (optionnel)
// Par exemple, si tu as un formulaire en bas qui envoie une note de suivi...

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivi Patient - <?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; }
        h1 { color: #c3142c; }
        table { width: 100%; border-collapse: collapse; margin-top: 1em; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #e74c3c; color: white; }
        .info-patient { margin-bottom: 1.5em; }
        .info-patient strong { display: inline-block; width: 150px; }
    </style>
</head>
<body>

<h1>Suivi du patient</h1>

<div class="info-patient">
    <p><strong>Nom complet :</strong> <?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?></p>
    <p><strong>Date de naissance :</strong> <?= htmlspecialchars($patient['date_naissance']) ?></p>
    <p><strong>Sexe :</strong> <?= htmlspecialchars($patient['sexe']) ?></p>
    <p><strong>Téléphone :</strong> <?= htmlspecialchars($patient['telephone']) ?></p>
    <p><strong>Statut :</strong> <?= htmlspecialchars($patient['statut']) ?></p>
</div>

<h2>Consultations</h2>
<?php if (empty($consultations)): ?>
    <p>Aucune consultation enregistrée pour ce patient.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Infirmier</th>
                <th>Symptômes</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($consultations as $consult): ?>
                <tr>
                    <td><?= htmlspecialchars($consult['date_consultation']) ?></td>
                    <td>
                        <?php
                        // Optionnel : récupérer le nom de l'infirmier via requête (ou optimiser via jointure)
                        $stmtInf = $pdo->prepare("SELECT prenom, nom FROM utilisateurs WHERE id = :id");
                        $stmtInf->execute([':id' => $consult['infirmier_id']]);
                        $inf = $stmtInf->fetch();
                        echo $inf ? htmlspecialchars($inf['prenom'] . ' ' . $inf['nom']) : 'N/A';
                        ?>
                    </td>
                    <td><?= nl2br(htmlspecialchars($consult['symptomes'])) ?></td>
                    <td><?= htmlspecialchars($consult['statut']) ?></td>
                    <td>
                        <a href="voir_consultation.php?id=<?= $consult['id'] ?>">Voir</a>
                        <!-- Tu peux ajouter modifier/supprimer ici -->
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<!-- Optionnel : formulaire ajout suivi (consultation) -->
<br>
<a href="dashboard.php"><button>← Retour au dashboard</button></a>
</body>
</html>
