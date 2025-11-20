<?php
require_once '../config/db.php';
require_once __DIR__ . '/../includes/notifications.php';

if (session_status() === PHP_SESSION_NONE) session_start();
include '../includes/header.php';

// Vérification du rôle : infirmier
if (!isset($_SESSION['utilisateur']['role']) || $_SESSION['utilisateur']['role'] !== 'infirmier') {
    header('Location: ../index.php');
    exit;
}

$infirmier_id = $_SESSION['utilisateur']['id'];
$consultation_id = intval($_GET['id'] ?? 0);

if (!$consultation_id) {
    echo "<p style='color:red;'>ID de consultation manquant.</p>";
    exit;
}

// Récupération de la consultation et de la réponse
$stmt = $pdo->prepare("
    SELECT c.*, p.nom AS patient_nom, p.prenom AS patient_prenom, p.date_naissance,
           rm.diagnostic, rm.ordonnance, rm.fichier_ordonnance, u.nom AS medecin_nom, u.prenom AS medecin_prenom
    FROM consultations c
    JOIN patients p ON p.id = c.patient_id
    LEFT JOIN reponses_medicales rm ON rm.consultation_id = c.id
    LEFT JOIN utilisateurs u ON u.id = rm.medecin_id
    WHERE c.id = :id AND c.infirmier_id = :infirmier_id
");
$stmt->execute([':id' => $consultation_id, ':infirmier_id' => $infirmier_id]);
$consultation = $stmt->fetch();

if (!$consultation) {
    echo "<p style='color:red;'>Consultation introuvable ou accès refusé.</p>";
    exit;
}

if (!$consultation['diagnostic']) {
    echo "<p style='color:red;'>Aucune réponse médicale disponible pour cette consultation.</p>";
    exit;
}
?>

<div id="fiche-consultation" style="max-width:700px; margin:auto; padding:30px; border:1px solid #ccc; font-family:Arial;">
    <div style="text-align:center;">
        <img src="../assets/logo.png" alt="Logo" style="height:60px; margin-bottom:10px;">
        <h2 style="color:#2c3e50;">Fiche de Téléconsultation</h2>
        <p style="color:#888;">Plateforme Télésanté – <?= htmlspecialchars($consultation['date_consultation']) ?></p>
    </div>

    <hr style="margin:20px 0;">

    <div>
        <h3 style="color:#34495e;">Informations du Patient</h3>
        <p><strong>Nom :</strong> <?= htmlspecialchars($consultation['patient_prenom'].' '.$consultation['patient_nom']) ?></p>
        <p><strong>Date de naissance :</strong> <?= htmlspecialchars($consultation['date_naissance']) ?></p>
    </div>

    <div style="margin-top:20px;">
        <h3 style="color:#34495e;">Diagnostic</h3>
        <div style="background:#f9f9f9; padding:10px; border-left:4px solid #2980b9;">
            <?= nl2br(htmlspecialchars($consultation['diagnostic'])) ?>
        </div>
    </div>

    <div style="margin-top:20px;">
        <h3 style="color:#34495e;">Ordonnance</h3>
        <div style="background:#f9f9f9; padding:10px; border-left:4px solid #27ae60;">
            <?= nl2br(htmlspecialchars($consultation['ordonnance'])) ?>
        </div>
    </div>

    <?php if (!empty($consultation['fichier_ordonnance'])): ?>
        <p><strong>Fichier ordonnance :</strong> 
            <a href="../uploads/<?= htmlspecialchars($consultation['fichier_ordonnance']) ?>" target="_blank">📎 Voir le fichier</a>
        </p>
    <?php endif; ?>

    <p><strong>Médecin :</strong> <?= htmlspecialchars($consultation['medecin_prenom'].' '.$consultation['medecin_nom']) ?></p>

    <div style="margin-top:40px; text-align:right;">
        <p><strong>Signature médecin :</strong> ______________________</p>
    </div>
</div>

<div style="margin-top:30px; text-align:center;">
    <a href="teleconsultation_pdf.php?id=<?= $consultation['id'] ?>" target="_blank">
        <button style="background-color:#2980b9;color:white;padding:10px 15px;border:none;border-radius:6px;font-size:15px;cursor:pointer;">
            📄 Télécharger en PDF
        </button>
    </a>

    <button onclick="printFiche()" style="background-color:#27ae60;color:white;padding:10px 15px;border:none;border-radius:6px;font-size:15px;cursor:pointer;margin-left:10px;">
        🖨️ Imprimer
    </button>
</div>

<script>
function printFiche(){
    const content = document.getElementById('fiche-consultation').innerHTML;
    const printWindow = window.open('','', 'height=800,width=800');
    printWindow.document.write('<html><head><title>Impression Consultation</title>');
    printWindow.document.write('<style>body{font-family:Arial;padding:30px;} h2{text-align:center;} .section{margin-bottom:20px;} .border{border-left:4px solid #2980b9;padding:10px;background:#f9f9f9;}</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(content);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}
</script>

<br><br>
<a href="consultations.php">← Retour aux consultations</a> |
<a href="dashboard.php"><button>← Retour au dashboard</button></a>

<?php include '../includes/footer.php'; ?>
