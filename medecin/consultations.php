<?php
require_once '../config/db.php';
require_once __DIR__ . '/../includes/notifications.php';
if (session_status() === PHP_SESSION_NONE) session_start();
include '../includes/header.php';

// Vérifie le rôle
if (!isset($_SESSION['utilisateur']['role']) || $_SESSION['utilisateur']['role'] !== 'medecin') {
    header('Location: ../index.php');
    exit;
}

$medecin_id = $_SESSION['utilisateur']['id'];

// Récupération des consultations envoyées
$stmt = $pdo->prepare("
    SELECT c.*, 
           p.nom AS patient_nom, 
           p.prenom AS patient_prenom,
           p.sexe,
           p.date_naissance
    FROM consultations c
    JOIN patients p ON c.patient_id = p.id
    WHERE c.statut = 'envoyée' AND c.medecin_id = :medecin_id
    ORDER BY c.date_consultation DESC
");
$stmt->execute([':medecin_id' => $medecin_id]);
$consultations = $stmt->fetchAll();
?>

<h2>🩺 Consultations à traiter</h2>

<!-- 🔔 Notifications -->
<div id="notif" style="display:none; position:fixed; top:20px; right:20px; 
    background:#27ae60; color:white; padding:15px; border-radius:10px; box-shadow:0 3px 8px rgba(0,0,0,0.3); z-index:9999;"></div>
<audio id="sound"><source src="../assets/notification.mp3" type="audio/mpeg"></audio>

<?php if (count($consultations) === 0): ?>
    <p>Aucune consultation en attente.</p>
<?php else: ?>
<table border="1" cellpadding="10" cellspacing="0" width="100%">
    <thead style="background-color: #f1c40f; color: #000;">
        <tr>
            <th>Date</th>
            <th>Patient</th>
            <th>Sexe</th>
            <th>Température</th>
            <th>Tension</th>
            <th>Symptômes</th>
            <th>Fichier</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($consultations as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['date_consultation']) ?></td>
            <td><?= htmlspecialchars($c['patient_prenom'] . ' ' . $c['patient_nom']) ?></td>
            <td><?= htmlspecialchars($c['sexe']) ?></td>
            <td><?= htmlspecialchars($c['temperature']) ?></td>
            <td><?= htmlspecialchars($c['tension']) ?></td>
            <td><?= nl2br(htmlspecialchars($c['symptomes'])) ?></td>
            <td>
                <?php if (!empty($c['fichier'])): ?>
                    <a href="../uploads/<?= htmlspecialchars($c['fichier']) ?>" target="_blank">📎 Voir</a>
                <?php else: ?>Aucun<?php endif; ?>
            </td>
            <td>
                <button class="btn-traiter" data-id="<?= $c['id'] ?>">📝 Traiter</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- Modal traitement -->
<div id="modal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%);
     background:white; padding:20px; border-radius:8px; box-shadow:0 5px 15px rgba(0,0,0,0.3); z-index:10000; max-width:600px; width:90%;">
    <h3>🩺 Traiter la consultation</h3>
    <form id="form-traitement" enctype="multipart/form-data">
        <input type="hidden" name="consultation_id" id="consultation_id">
        <label>Diagnostic <span style="color:red;">*</span> :</label><br>
        <textarea name="diagnostic" id="diagnostic" rows="5" required></textarea><br><br>

        <label>Ordonnance :</label><br>
        <textarea name="ordonnance" id="ordonnance" rows="4"></textarea><br><br>

        <label>Fichier ordonnance (pdf, jpg, png) :</label><br>
        <input type="file" name="fichier_ordonnance" accept=".pdf,.jpg,.jpeg,.png"><br><br>

        <button type="submit" style="padding:10px 20px; background:#2980b9; color:white; border:none; border-radius:4px;">Enregistrer</button>
        <button type="button" id="close-modal" style="padding:10px 20px; background:#e74c3c; color:white; border:none; border-radius:4px;">Annuler</button>
    </form>
    <div id="modal-msg" style="margin-top:10px;"></div>
</div>

<script>
// Affiche la modal
document.querySelectorAll('.btn-traiter').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('consultation_id').value = btn.dataset.id;
        document.getElementById('diagnostic').value = '';
        document.getElementById('ordonnance').value = '';
        document.getElementById('modal-msg').innerHTML = '';
        document.getElementById('modal').style.display = 'block';
    });
});

// Fermer la modal
document.getElementById('close-modal').addEventListener('click', () => {
    document.getElementById('modal').style.display = 'none';
});

// Soumission AJAX
document.getElementById('form-traitement').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);
    fetch('ajax_traiter.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const msgDiv = document.getElementById('modal-msg');
        if(data.success){
            msgDiv.innerHTML = '<span style="color:green;">' + data.success + '</span>';
            document.getElementById('sound').play();
            setTimeout(()=>location.reload(), 1500); // actualise la page
        } else if(data.errors){
            msgDiv.innerHTML = '<span style="color:red;">' + data.errors.join('<br>') + '</span>';
        }
    })
    .catch(err => console.error(err));
});

// Notifications en direct
setInterval(() => {
    fetch('../includes/notifications.php?check=1')
        .then(res => res.json())
        .then(data => {
            if(data.new && data.message){
                const zone = document.getElementById('notif');
                const son = document.getElementById('sound');
                zone.innerText = data.message;
                zone.style.display = 'block';
                son.play();
                setTimeout(()=>zone.style.display='none', 6000);
            }
        })
        .catch(err => console.error('Erreur notif:', err));
}, 5000);
</script>

<?php include '../includes/footer.php'; ?>
