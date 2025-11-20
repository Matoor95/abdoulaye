<?php
require_once '../config/db.php';
include '../includes/header.php';
session_start();

$infirmier_id = $_SESSION['utilisateur']['id'];

if (!isset($_GET['id'])) {
    echo "<p style='color:red;'>ID manquant.</p>";
    exit;
}

$consultation_id = intval($_GET['id']);

// Vérifier l'appartenance + statut
$stmt = $pdo->prepare("SELECT * FROM consultations WHERE id = ? AND infirmier_id = ?");
$stmt->execute([$consultation_id, $infirmier_id]);
$consultation = $stmt->fetch();

if (!$consultation) {
    echo "<p style='color:red;'>Consultation introuvable ou non autorisée.</p>";
    exit;
}

if ($consultation['statut'] === 'traitée') {
    echo "<p style='color:red;'>Impossible de modifier une consultation déjà traitée.</p>";
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tension = $_POST['tension'];
    $temperature = $_POST['temperature'];
    $symptomes = $_POST['symptomes'];
    $observations = $_POST['observations'];
    $fichier = $consultation['fichier'];

    // Si un nouveau fichier est envoyé
    if (!empty($_FILES['fichier']['name'])) {
        $uploadDir = '../uploads/';
        $fichierName = time() . '_' . basename($_FILES['fichier']['name']);
        $uploadPath = $uploadDir . $fichierName;

        if (move_uploaded_file($_FILES['fichier']['tmp_name'], $uploadPath)) {
            $fichier = $fichierName;
        }
    }

    // Mise à jour
    $update = $pdo->prepare("
        UPDATE consultations SET 
            tension = :tension,
            temperature = :temperature,
            symptomes = :symptomes,
            observations = :observations,
            fichier = :fichier
        WHERE id = :id
    ");
    $update->execute([
        ':tension' => $tension,
        ':temperature' => $temperature,
        ':symptomes' => $symptomes,
        ':observations' => $observations,
        ':fichier' => $fichier,
        ':id' => $consultation_id
    ]);

    echo "<p style='color:green;'>Consultation mise à jour avec succès.</p>";
    echo "<a href='consultations.php'>&larr; Retour</a>";
    exit;
}
?>

<h2>✏️ Modifier la consultation du <?= htmlspecialchars($consultation['date_consultation']) ?></h2>

<form method="post" enctype="multipart/form-data">
    <label>Tension :</label><br>
    <input type="text" name="tension" value="<?= htmlspecialchars($consultation['tension']) ?>" required><br><br>

    <label>Température :</label><br>
    <input type="text" name="temperature" value="<?= htmlspecialchars($consultation['temperature']) ?>" required><br><br>

    <label>Symptômes :</label><br>
    <textarea name="symptomes" rows="4" required><?= htmlspecialchars($consultation['symptomes']) ?></textarea><br><br>

    <label>Observations :</label><br>
    <textarea name="observations" rows="4"><?= htmlspecialchars($consultation['observations']) ?></textarea><br><br>

    <label>Fichier actuel :</label>
    <?php if ($consultation['fichier']): ?>
        <a href="../uploads/<?= htmlspecialchars($consultation['fichier']) ?>" target="_blank">📎 Voir</a><br>
    <?php else: ?>
        Aucun fichier<br>
    <?php endif; ?>

    <label>Nouveau fichier (optionnel) :</label><br>
    <input type="file" name="fichier"><br><br>

    <button type="submit">💾 Mettre à jour</button>
</form>

<p><a href="consultations.php">⬅️ Retour aux consultations</a></p>
<br>
<a href="dashboard.php"><button>← Retour au dashboard</button></a>
<?php include '../includes/footer.php'; ?>
