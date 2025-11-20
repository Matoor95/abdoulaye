<?php
require_once '../config/db.php';
require_once __DIR__ . '/../includes/notifications.php';

if (session_status() === PHP_SESSION_NONE) session_start();
include '../includes/header.php';

// Vérif rôle médecin
if (!isset($_SESSION['utilisateur']['role']) || $_SESSION['utilisateur']['role'] !== 'medecin') {
    header('Location: ../index.php');
    exit;
}

$medecin_id = $_SESSION['utilisateur']['id'] ?? null;

// Vérifie ID consultation
$consultation_id = (int)($_GET['id'] ?? 0);
if (!$consultation_id) {
    echo "<p style='color:red;'>ID de consultation invalide.</p>";
    exit;
}

// Récupère consultation
$stmt = $pdo->prepare("SELECT * FROM consultations WHERE id = ?");
$stmt->execute([$consultation_id]);
$consultation = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$consultation) { echo "<p style='color:red;'>Consultation non trouvée.</p>"; exit; }
if ($consultation['statut'] === 'traitée') { echo "<p style='color:red;'>Consultation déjà traitée.</p>"; exit; }

// Vérifie réponse existante
$stmt = $pdo->prepare("SELECT id FROM reponses_medicales WHERE consultation_id=?");
$stmt->execute([$consultation_id]);
if ($stmt->fetch()) { echo "<p style='color:red;'>Cette consultation a déjà une réponse.</p>"; exit; }

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnostic = trim($_POST['diagnostic'] ?? '');
    $ordonnance = trim($_POST['ordonnance'] ?? '');
    $fichierOrdonnance = null;

    if ($diagnostic === '') $errors[] = "Le diagnostic est obligatoire.";

    // Upload fichier
    if (!empty($_FILES['fichier_ordonnance']['name']) && $_FILES['fichier_ordonnance']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['pdf','jpg','jpeg','png'];
        $ext = strtolower(pathinfo($_FILES['fichier_ordonnance']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $newFile = 'ordonnance_'.$consultation_id.'_'.time().'.'.$ext;
            $uploadPath = "../uploads/$newFile";
            if (move_uploaded_file($_FILES['fichier_ordonnance']['tmp_name'], $uploadPath)) {
                $fichierOrdonnance = $newFile;
            } else { $errors[] = "Erreur upload fichier."; }
        } else { $errors[] = "Type fichier non autorisé."; }
    }

    if (empty($errors)) {
        // Insère réponse médicale
        $stmt = $pdo->prepare("INSERT INTO reponses_medicales (consultation_id, medecin_id, diagnostic, ordonnance, fichier_ordonnance) VALUES (?,?,?,?,?)");
        $stmt->execute([$consultation_id, $medecin_id, $diagnostic, $ordonnance, $fichierOrdonnance]);

        // Met à jour statut consultation
        $pdo->prepare("UPDATE consultations SET statut='traitée', medecin_id=? WHERE id=?")
            ->execute([$medecin_id, $consultation_id]);

        // Notification vers l'infirmier
        if (!empty($consultation['infirmier_id'])) {
            $infirmier_id = (int)$consultation['infirmier_id'];
            $patient = htmlspecialchars($consultation['patient_nom'] ?? 'un patient');
            $msg = "💊 Le médecin a répondu à la consultation du patient : $patient.";
            $stmtNotif = $pdo->prepare("INSERT INTO notifications (utilisateur_id, message, date_creation, vu) VALUES (?,?,NOW(),0)");
            $stmtNotif->execute([$infirmier_id, $msg]);
        }

        $success = "✅ Consultation traitée et notification envoyée à l’infirmier.";
    }
}
?>

<h2>🩺 Traitement de la consultation</h2>

<?php if ($errors): ?>
<div style="color:red;">
    <ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color:green;"><?= $success ?></p>
    <a href="consultations.php">← Retour à la liste</a>
    <?php include '../includes/footer.php'; exit; ?>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" style="max-width:600px;">
    <label>Diagnostic <span style="color:red;">*</span> :</label><br>
    <textarea name="diagnostic" rows="5" required><?= htmlspecialchars($_POST['diagnostic'] ?? '') ?></textarea><br><br>

    <label>Ordonnance :</label><br>
    <textarea name="ordonnance" rows="4"><?= htmlspecialchars($_POST['ordonnance'] ?? '') ?></textarea><br><br>

    <label>Fichier ordonnance :</label><br>
    <input type="file" name="fichier_ordonnance" accept=".pdf,.jpg,.jpeg,.png"><br><br>

    <button type="submit" style="padding:10px 20px; background:#27ae60; color:white; border:none; border-radius:6px; cursor:pointer;">
        💾 Enregistrer
    </button>
</form>

<p><a href="consultations.php">← Retour</a></p>
<?php include '../includes/footer.php'; ?>
