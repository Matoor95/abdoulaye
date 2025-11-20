<?php
require_once '../config/db.php';
require_once __DIR__ . '/../includes/notifications.php';
if(session_status()===PHP_SESSION_NONE) session_start();
include '../includes/header.php';

// Vérification rôle infirmier
if(!isset($_SESSION['utilisateur']['role']) || $_SESSION['utilisateur']['role']!=='infirmier'){
    header('Location: ../index.php');
    exit;
}

$infirmier_id = $_SESSION['utilisateur']['id'];

// Suppression d'une consultation
if (isset($_GET['delete'])) {
    $idToDelete = intval($_GET['delete']);
    $stmtCheck = $pdo->prepare("SELECT fichier FROM consultations WHERE id=? AND infirmier_id=?");
    $stmtCheck->execute([$idToDelete, $infirmier_id]);
    $consult = $stmtCheck->fetch();

    if ($consult) {
        if (!empty($consult['fichier']) && file_exists("../uploads/".$consult['fichier'])) {
            unlink("../uploads/".$consult['fichier']);
        }
        $pdo->prepare("DELETE FROM consultations WHERE id=?")->execute([$idToDelete]);
        echo '<p style="color:green;">✅ Consultation supprimée.</p>';
    } else {
        echo '<p style="color:red;">❌ Consultation introuvable.</p>';
    }
}

// Récupération des consultations + réponse médecin
$stmt = $pdo->prepare("
    SELECT c.*, 
           p.nom AS patient_nom, 
           p.prenom AS patient_prenom,
           rm.id AS reponse_id
    FROM consultations c
    JOIN patients p ON p.id=c.patient_id
    LEFT JOIN reponses_medicales rm ON rm.consultation_id=c.id
    WHERE c.infirmier_id=:infirmier_id
    ORDER BY c.date_consultation DESC
");
$stmt->execute([':infirmier_id'=>$infirmier_id]);
$consultations = $stmt->fetchAll();
?>

<h2>📁 Mes consultations envoyées</h2>

<!-- Zone de notification -->
<div id="notif" 
     style="display:none; position:fixed; top:20px; right:20px; 
            background:#27ae60; color:white; padding:15px; border-radius:10px; 
            box-shadow:0 3px 8px rgba(0,0,0,0.3); z-index:9999;">
</div>
<audio id="sound"><source src="../assets/notification.mp3" type="audio/mpeg"></audio>

<table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse: collapse;">
    <thead style="background: #2ecc71; color: white;">
        <tr>
            <th>Date</th>
            <th>Patient</th>
            <th>Tension</th>
            <th>Température</th>
            <th>Symptômes</th>
            <th>Fichier</th>
            <th>Statut</th>
            <th>Actions</th>
            <th>Réponse médecin</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($consultations) === 0): ?>
            <tr><td colspan="9" style="text-align:center;">Aucune consultation envoyée.</td></tr>
        <?php else: ?>
            <?php foreach ($consultations as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['date_consultation']) ?></td>
                    <td><?= htmlspecialchars($c['patient_prenom'].' '.$c['patient_nom']) ?></td>
                    <td><?= htmlspecialchars($c['tension']) ?></td>
                    <td><?= htmlspecialchars($c['temperature']) ?></td>
                    <td><?= nl2br(htmlspecialchars($c['symptomes'])) ?></td>
                    <td>
                        <?= !empty($c['fichier']) ? '<a href="../uploads/'.htmlspecialchars($c['fichier']).'" target="_blank">📎 Voir</a>' : '-' ?>
                    </td>
                    <td><?= $c['reponse_id'] ? '✅ Traitée' : '🕐 En attente' ?></td>
                    <td style="white-space:nowrap;">
                        <a href="envoyer_consultation.php?id=<?= $c['id'] ?>" title="Envoyer ou réassigner" style="margin:0 5px;">📤</a>
                        <a href="modifier_consultation.php?id=<?= $c['id'] ?>" title="Modifier" style="margin:0 5px;">✏️</a>
                        <a href="consultations.php?delete=<?= $c['id'] ?>" onclick="return confirm('Confirmer la suppression ?')" title="Supprimer" style="margin:0 5px;">🗑️</a>
                    </td>
                    <td>
                        <?= $c['reponse_id'] ? '<a href="voir_reponse.php?id='.$c['id'].'" title="Voir la réponse du médecin">👨‍⚕️ Voir réponse</a>' : '-' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Notifications temps réel -->
<script>
setInterval(() => {
    fetch('../includes/check_notifications.php?role=infirmier')
        .then(res => res.json())
        .then(data => {
            if(data.new && data.notifications && data.notifications.length>0){
                const zone = document.getElementById('notif');
                const son = document.getElementById('sound');
                zone.innerText = data.notifications.map(n => n.message).join("\n");
                zone.style.display = 'block';
                son.play();
                setTimeout(()=> zone.style.display='none', 6000);
            }
        })
        .catch(err=>console.error('Erreur notifications:', err));
}, 5000); // toutes les 5 secondes
</script>

<?php include '../includes/footer.php'; ?>
