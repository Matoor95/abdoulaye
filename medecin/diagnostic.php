<?php
require_once '../config/db.php';
session_start();
include '../includes/header.php';

// Vérification session et rôle médecin ici (à ajouter selon ton auth)

if (!isset($_GET['id'])) {
    echo "ID de consultation manquant.";
    exit;
}

$consultation_id = intval($_GET['id']);

// Récupérer la consultation + infos patient et infirmier
$stmt = $pdo->prepare("
    SELECT c.*, 
           p.nom AS patient_nom, p.prenom AS patient_prenom,
           u.nom AS infirmier_nom, u.prenom AS infirmier_prenom
    FROM consultations c
    JOIN patients p ON c.patient_id = p.id
    JOIN utilisateurs u ON c.infirmier_id = u.id
    WHERE c.id = ?
");
$stmt->execute([$consultation_id]);
$consultation = $stmt->fetch();

if (!$consultation) {
    echo "Consultation non trouvée.";
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnostic = trim($_POST['diagnostic'] ?? '');
    $ordonnance = trim($_POST['ordonnance'] ?? '');

    if ($diagnostic === '') {
        $errors[] = "Le diagnostic est obligatoire.";
    }

    // Gestion upload fichier ordonnance
    $fichierOrdonnance = $consultation['fichier_ordonnance'] ?? null;
    if (isset($_FILES['fichier_ordonnance']) && $_FILES['fichier_ordonnance']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        $fileName = $_FILES['fichier_ordonnance']['name'];
        $fileTmp = $_FILES['fichier_ordonnance']['tmp_name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $newFileName = 'ordonnance_' . $consultation_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($fileTmp, "../uploads/$newFileName")) {
                $fichierOrdonnance = $newFileName;
            } else {
                $errors[] = "Erreur lors de l'upload du fichier.";
            }
        } else {
            $errors[] = "Type de fichier non autorisé (pdf, jpg, jpeg, png uniquement).";
        }
    }

    if (empty($errors)) {
        $sql = "UPDATE consultations SET diagnostic = :diagnostic, ordonnance = :ordonnance, statut = 'traitée', fichier_ordonnance = :fichier_ordonnance WHERE id = :id";
        $stmtUpdate = $pdo->prepare($sql);
        $stmtUpdate->execute([
            ':diagnostic' => $diagnostic,
            ':ordonnance' => $ordonnance,
            ':fichier_ordonnance' => $fichierOrdonnance,
            ':id' => $consultation_id
        ]);
        $success = "Diagnostic et ordonnance enregistrés avec succès.";
        // Recharger les données
        $stmt->execute([$consultation_id]);
        $consultation = $stmt->fetch();
    }
}
?>

<h2>Traitement de la consultation</h2>

<?php if ($errors): ?>
    <div style="color:red;">
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color:green;"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<table>
    <tr><th>Patient :</th><td><?= htmlspecialchars($consultation['patient_prenom'] . ' ' . $consultation['patient_nom']) ?></td></tr>
    <tr><th>Infirmier :</th><td><?= htmlspecialchars($consultation['infirmier_prenom'] . ' ' . $consultation['infirmier_nom']) ?></td></tr>
    <tr><th>Date :</th><td><?= htmlspecialchars($consultation['date_consultation']) ?></td></tr>
    <tr><th>Tension :</th><td><?= htmlspecialchars($consultation['tension']) ?></td></tr>
    <tr><th>Température :</th><td><?= htmlspecialchars($consultation['temperature']) ?></td></tr>
    <tr><th>Symptômes :</th><td><?= nl2br(htmlspecialchars($consultation['symptomes'])) ?></td></tr>
    <tr><th>Observations :</th><td><?= nl2br(htmlspecialchars($consultation['observations'])) ?></td></tr>
    <tr><th>Fichier joint :</th>
        <td>
            <?php if (!empty($consultation['fichier'])): ?>
                <a href="../uploads/<?= htmlspecialchars($consultation['fichier']) ?>" target="_blank">📎 Voir fichier</a>
            <?php else: ?>
                -
            <?php endif; ?>
        </td>
    </tr>
</table>

<form method="post" enctype="multipart/form-data" style="margin-top:20px; max-width:600px;">
    <label for="diagnostic">Diagnostic <span style="color:red">*</span> :</label><br>
    <textarea name="diagnostic" id="diagnostic" rows="6" style="width:100%" required><?= htmlspecialchars($consultation['diagnostic'] ?? '') ?></textarea><br><br>

    <label for="ordonnance">Ordonnance (texte) :</label><br>
    <textarea name="ordonnance" id="ordonnance" rows="5" style="width:100%"><?= htmlspecialchars($consultation['ordonnance'] ?? '') ?></textarea><br><br>

    <label for="fichier_ordonnance">Fichier ordonnance (pdf, jpg, jpeg, png) :</label><br>
    <input type="file" name="fichier_ordonnance" id="fichier_ordonnance" accept=".pdf,.jpg,.jpeg,.png"><br>
    <?php if (!empty($consultation['fichier_ordonnance'])): ?>
        <p>Fichier existant : <a href="../uploads/<?= htmlspecialchars($consultation['fichier_ordonnance']) ?>" target="_blank">📎 Voir</a></p>
    <?php endif; ?>
    <br>
    <button type="submit" style="background:#2980b9; color:white; padding:10px 20px; border:none; border-radius:6px; cursor:pointer;">
        <?= $consultation['statut'] === 'traitée' ? 'Mettre à jour' : 'Enregistrer' ?>
    </button>
</form>

<p><a href="consultations.php">← Retour à la liste des consultations</a></p>

<?php include '../includes/footer.php'; ?>
