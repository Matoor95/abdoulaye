<?php
require_once '../config/db.php';
require_once __DIR__ . '/../includes/notifications.php';
if(session_status()===PHP_SESSION_NONE) session_start();
include '../includes/header.php';

// Vérif rôle infirmier
if(!isset($_SESSION['utilisateur']['role']) || $_SESSION['utilisateur']['role']!=='infirmier'){
    header('Location: ../index.php');
    exit;
}

$infirmier_id = $_SESSION['utilisateur']['id'];
$consultation_id = (int)($_GET['id'] ?? 0);
if(!$consultation_id){ echo "<p style='color:red;'>ID consultation manquant.</p>"; exit; }

// Récup consultation + réponse
$stmt = $pdo->prepare("
    SELECT c.*, p.nom AS patient_nom, p.prenom AS patient_prenom,
           rm.diagnostic, rm.ordonnance, rm.fichier_ordonnance,
           u.nom AS medecin_nom, u.prenom AS medecin_prenom
    FROM consultations c
    JOIN patients p ON p.id=c.patient_id
    LEFT JOIN reponses_medicales rm ON rm.consultation_id=c.id
    LEFT JOIN utilisateurs u ON u.id=rm.medecin_id
    WHERE c.id=:id AND c.infirmier_id=:infirmier_id
");
$stmt->execute([':id'=>$consultation_id, ':infirmier_id'=>$infirmier_id]);
$consultation = $stmt->fetch();
if(!$consultation){ echo "<p style='color:red;'>Consultation introuvable.</p>"; exit; }
if(!$consultation['diagnostic']){ echo "<p style='color:red;'>Pas de réponse médicale.</p>"; exit; }

// Notification optionnelle consultation vue
ajouterNotification($pdo, $infirmier_id, "✅ Consultation #{$consultation_id} consultée par l'infirmier.");
?>

<h2>📄 Réponse du médecin</h2>
<p><strong>Patient :</strong> <?= htmlspecialchars($consultation['patient_prenom'].' '.$consultation['patient_nom']) ?></p>
<p><strong>Médecin :</strong> <?= htmlspecialchars($consultation['medecin_prenom'].' '.$consultation['medecin_nom']) ?></p>
<h3>Diagnostic</h3>
<div style="background:#f9f9f9;padding:10px;border-left:4px solid #2980b9;"><?= nl2br(htmlspecialchars($consultation['diagnostic'])) ?></div>
<h3>Ordonnance</h3>
<div style="background:#f9f9f9;padding:10px;border-left:4px solid #27ae60;"><?= nl2br(htmlspecialchars($consultation['ordonnance'])) ?></div>
<?php if(!empty($consultation['fichier_ordonnance'])): ?>
<p><strong>Fichier ordonnance :</strong> <a href="../uploads/<?= htmlspecialchars($consultation['fichier_ordonnance']) ?>" target="_blank">📎 Voir</a></p>
<?php endif; ?>

<!-- Notifications en direct -->
<div id="notif" style="display:none; position:fixed; top:20px; right:20px; 
background:#27ae60; color:white; padding:15px; border-radius:10px; 
box-shadow:0 3px 8px rgba(0,0,0,0.3); z-index:9999;"></div>
<audio id="sound"><source src="../assets/notification.mp3" type="audio/mpeg"></audio>

<script>
setInterval(()=>{
    fetch('../includes/notifications.php?check=1')
    .then(res=>res.json())
    .then(data=>{
        if(data.new && data.notifications && data.notifications.length>0){
            const zone=document.getElementById('notif');
            const son=document.getElementById('sound');
            zone.innerText = data.notifications.map(n=>n.message).join("\n");
            zone.style.display='block';
            son.play();
            setTimeout(()=>zone.style.display='none',6000);
        }
    }).catch(err=>console.error('Erreur notif:', err));
},5000);
</script>

<p><a href="consultations.php">← Retour</a></p>
<?php include '../includes/footer.php'; ?>
