<?php
require_once '../includes/init.php';
include '../includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    echo "<p style='color:red;'>ID utilisateur invalide.</p>";
    exit;
}

// Récupérer les infos utilisateur
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    echo "<p style='color:red;'>Utilisateur introuvable.</p>";
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $statut = $_POST['statut'] ?? '';
    $telephone = trim($_POST['telephone'] ?? '');

    if (!$prenom || !$nom || !$email || !$role || !$statut) {
        $errors[] = "Tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide.";
    } else {
        // Vérifier si email est déjà utilisé par un autre utilisateur
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = ? AND id != ?");
        $stmtCheck->execute([$email, $id]);
        if ($stmtCheck->fetchColumn() > 0) {
            $errors[] = "Email déjà utilisé par un autre utilisateur.";
        }
    }

    if (!$errors) {
        $stmtUpdate = $pdo->prepare("UPDATE utilisateurs SET prenom = ?, nom = ?, email = ?, role = ?, statut = ?, telephone = ? WHERE id = ?");
        $stmtUpdate->execute([$prenom, $nom, $email, $role, $statut, $telephone, $id]);
        $success = "Utilisateur modifié avec succès.";

        // Recharger les infos mises à jour
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
    }
}
?>

<h2>Modifier utilisateur #<?= $user['id'] ?></h2>

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

<form method="post" style="max-width:600px; background:#fff; padding:20px; border-radius:10px; box-shadow:0 0 12px rgba(0,0,0,0.1);">
    <input type="text" name="prenom" placeholder="Prénom" value="<?= htmlspecialchars($user['prenom']) ?>" required style="width:100%; padding:10px; margin-bottom:10px;">
    <input type="text" name="nom" placeholder="Nom" value="<?= htmlspecialchars($user['nom']) ?>" required style="width:100%; padding:10px; margin-bottom:10px;">
    <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($user['email']) ?>" required style="width:100%; padding:10px; margin-bottom:10px;">
    <input type="text" name="telephone" placeholder="Téléphone (ex: 77 123 45 67)" value="<?= htmlspecialchars($user['telephone']) ?>" style="width:100%; padding:10px; margin-bottom:10px;">

    <select name="role" required style="width:100%; padding:10px; margin-bottom:10px;">
        <option value="">-- Sélectionnez un rôle --</option>
        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
        <option value="medecin" <?= $user['role'] === 'medecin' ? 'selected' : '' ?>>Médecin</option>
        <option value="infirmier" <?= $user['role'] === 'infirmier' ? 'selected' : '' ?>>Infirmier</option>
        <option value="patient" <?= $user['role'] === 'patient' ? 'selected' : '' ?>>Patient</option>
    </select>

    <select name="statut" required style="width:100%; padding:10px; margin-bottom:15px;">
        <option value="actif" <?= $user['statut'] === 'actif' ? 'selected' : '' ?>>Actif</option>
        <option value="inactif" <?= $user['statut'] === 'inactif' ? 'selected' : '' ?>>Inactif</option>
    </select>

    <button type="submit" name="modifier" style="background:#27ae60; color:white; padding:10px 20px; border:none; border-radius:8px; cursor:pointer;">Modifier</button>
</form>

<p style="margin-top:15px;"><a href="utilisateurs.php" style="color:#2980b9;">← Retour à la liste des utilisateurs</a></p>

<?php include '../includes/footer.php'; ?>
