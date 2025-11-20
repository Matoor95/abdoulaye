<?php
require_once '../includes/init.php';  // remonte d'un niveau puis dans includes/init.php
include '../includes/header.php';  

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $nom = trim($_POST['nom'] ?? '');

    if (!$nom) {
        $errors[] = "Le nom de la ville est requis.";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM villes WHERE nom = ?");
        $stmt->execute([$nom]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Cette ville existe déjà.";
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO villes (nom) VALUES (?)");
        $stmt->execute([$nom]);
        $success = "Ville ajoutée avec succès.";
    }
}

$stmt = $pdo->query("SELECT * FROM villes ORDER BY nom ASC");
$villes = $stmt->fetchAll();
?>

<h2>Gestion des villes</h2>

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
        </tr>
    </thead>
    <tbody>
        <?php foreach ($villes as $ville): ?>
            <tr>
                <td><?= $ville['id'] ?></td>
                <td><?= htmlspecialchars($ville['nom']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3>Ajouter une ville</h3>
<form method="post" action="">
    <input type="text" name="nom" placeholder="Nom de la ville" required>
    <button type="submit" name="ajouter">Ajouter</button>
</form>

<?php include '../includes/footer.php'; ?>
