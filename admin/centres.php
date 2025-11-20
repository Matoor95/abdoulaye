<?php
require_once '../includes/init.php';  // remonte d'un niveau puis dans includes/init.php
include '../includes/header.php';  

$errors = [];
$success = '';

// Récupération villes pour le select
$stmtVilles = $pdo->query("SELECT id, nom FROM villes ORDER BY nom ASC");
$villes = $stmtVilles->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $nom = trim($_POST['nom'] ?? '');
    $ville_id = $_POST['ville_id'] ?? '';

    if (!$nom) {
        $errors[] = "Le nom du centre est requis.";
    }
    if (!$ville_id) {
        $errors[] = "Veuillez sélectionner une ville.";
    }

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO centres (nom, ville_id) VALUES (?, ?)");
        $stmt->execute([$nom, $ville_id]);
        $success = "Centre ajouté avec succès.";
    }
}

$stmt = $pdo->query("SELECT centres.id, centres.nom, villes.nom AS ville_nom FROM centres JOIN villes ON centres.ville_id = villes.id ORDER BY centres.nom ASC");
$centres = $stmt->fetchAll();
?>

<h2>Gestion des centres</h2>

<?php if ($success): ?>
    <p style="color:green; font-weight:bold;"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<?php if ($errors): ?>
    <ul style="color:#e74c3c;">
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse:collapse; margin-bottom:20px;">
    <thead>
        <tr style="background:#27ae60; color:white;">
            <th>ID</th>
            <th>Nom</th>
            <th>Ville</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($centres as $centre): ?>
            <tr>
                <td><?= $centre['id'] ?></td>
                <td><?= htmlspecialchars($centre['nom']) ?></td>
                <td><?= htmlspecialchars($centre['ville_nom']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3>Ajouter un centre</h3>
<form method="post" action="">
    <input type="text" name="nom" placeholder="Nom du centre" required>
    <select name="ville_id" required>
        <option value="">-- Sélectionnez une ville --</option>
        <?php foreach ($villes as $ville): ?>
            <option value="<?= $ville['id'] ?>"><?= htmlspecialchars($ville['nom']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" name="ajouter">Ajouter</button>
</form>

<?php include '../includes/footer.php'; ?>
 