<?php
session_start();
require_once '../config/db.php';
include '../includes/header.php';

if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'infirmier') {
    header("Location: ../login.php");
    exit;
}

$infirmier_id = $_SESSION['utilisateur']['id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p>Consultation introuvable.</p>";
    exit;
}

$consultation_id = intval($_GET['id']);
$modifier = isset($_GET['modifier']);

$stmt = $pdo->prepare("SELECT * FROM consultations WHERE id = ? AND infirmier_id = ?");
$stmt->execute([$consultation_id, $infirmier_id]);
$consultation = $stmt->fetch();

if (!$consultation) {
    echo "<p>Consultation introuvable ou non autorisée.</p>";
    exit;
}

if ($consultation['statut'] === 'traitée') {
    echo "<p style='color:red;'>Cette consultation a déjà été traitée.</p>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['medecin_id'])) {
    $medecin_id = intval($_POST['medecin_id']);
    $stmt = $pdo->prepare("UPDATE consultations SET medecin_id = :medecin_id, statut = 'envoyée' WHERE id = :id");
    $stmt->execute([':medecin_id' => $medecin_id, ':id' => $consultation_id]);

    require_once __DIR__ . '/../includes/notifications.php';

    $msg_medecin = "🩺 Nouvelle consultation (ID #{$consultation_id}) à traiter.";
    ajouterNotification($pdo, $medecin_id, $msg_medecin);

    $msg_infirmier = "📤 Consultation #{$consultation_id} envoyée au médecin avec succès.";
    ajouterNotification($pdo, $infirmier_id, $msg_infirmier);

    echo "<p style='color:green;'>✅ Consultation envoyée avec succès au médecin.</p>";
    echo '<audio autoplay><source src="../assets/notification.mp3" type="audio/mpeg"></audio>';
    echo '<a href="consultations.php">⬅️ Retour à la liste</a>';
    exit;
}

$medecins = $pdo->query("SELECT id, nom, prenom FROM utilisateurs WHERE role = 'medecin'")->fetchAll();
?>
<h2><?= $modifier ? "🔄 Changer le médecin" : "📤 Envoyer la consultation au médecin" ?></h2>
<p><strong>Patient :</strong> <?= htmlspecialchars($consultation['patient_id']) ?></p>
<p><strong>Symptômes :</strong> <?= nl2br(htmlspecialchars($consultation['symptomes'])) ?></p>

<form method="post">
    <label>Médecin :</label><br>
    <select name="medecin_id" required>
        <option value="">-- Choisir un médecin --</option>
        <?php foreach ($medecins as $m): ?>
            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['prenom'].' '.$m['nom']) ?></option>
        <?php endforeach; ?>
    </select><br><br>
    <button type="submit"><?= $modifier ? 'Changer' : 'Envoyer' ?></button>
</form>

<a href="dashboard.php"><button>← Retour</button></a>
<?php include '../includes/footer.php'; ?>
