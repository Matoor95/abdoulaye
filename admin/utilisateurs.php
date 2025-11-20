<?php
require_once '../includes/init.php';
include '../includes/header.php';

// Gestion formulaire ajout utilisateur
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $telephone = trim($_POST['telephone'] ?? '');

    // Validation
    if (!$prenom || !$nom || !$email || !$role || !$mot_de_passe) {
        $errors[] = "Tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide.";
    } else {
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = ?");
        $stmtCheck->execute([$email]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            $errors[] = "Email déjà utilisé.";
        }
    }

    // Insertion si pas d'erreurs
    if (empty($errors)) {
        $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
        $stmtInsert = $pdo->prepare("
            INSERT INTO utilisateurs 
            (prenom, nom, email, role, mot_de_passe, telephone, statut) 
            VALUES (?, ?, ?, ?, ?, ?, 'actif')
        ");
        $stmtInsert->execute([$prenom, $nom, $email, $role, $hash, $telephone]);
        $success = "Utilisateur ajouté avec succès.";
    }
}

// Récupération utilisateurs pour affichage et filtre
$roleFilter = $_GET['role'] ?? '';
$params = [];
$query = "SELECT id, prenom, nom, email, role, statut, telephone FROM utilisateurs";
if ($roleFilter) {
    $query .= " WHERE role = ?";
    $params[] = $roleFilter;
}
$query .= " ORDER BY id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Gestion des utilisateurs</h2>

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

<!-- Filtre par rôle -->
<form method="get" style="margin-bottom:15px;">
    <label>Filtrer par rôle :</label>
    <select name="role" onchange="this.form.submit()">
        <option value="">-- Tous --</option>
        <option value="admin" <?= $roleFilter==='admin' ? 'selected' : '' ?>>Admin</option>
        <option value="medecin" <?= $roleFilter==='medecin' ? 'selected' : '' ?>>Médecin</option>
        <option value="infirmier" <?= $roleFilter==='infirmier' ? 'selected' : '' ?>>Infirmier</option>
        <option value="patient" <?= $roleFilter==='patient' ? 'selected' : '' ?>>Patient</option>
    </select>
</form>

<!-- Tableau des utilisateurs -->
<table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
    <thead>
        <tr style="background:#c0392b; color:white; text-align:left;">
            <th>ID</th>
            <th>Prénom</th>
            <th>Nom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Rôle</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($utilisateurs as $user): ?>
            <tr style="border-bottom:1px solid #ddd;">
                <td><?= (int)$user['id'] ?></td>
                <td><?= htmlspecialchars($user['prenom']) ?></td>
                <td><?= htmlspecialchars($user['nom']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['telephone']) ?></td>
                <td><?= htmlspecialchars($user['role']) ?></td>
                <td><?= htmlspecialchars($user['statut']) ?></td>
                <td>
                    <a href="modifier_utilisateur.php?id=<?= (int)$user['id'] ?>" class="btn-modifier">✏️ Modifier</a>
                    <a href="supprimer_utilisateur.php?id=<?= (int)$user['id'] ?>" class="btn-supprimer" onclick="return confirm('Confirmer la suppression ?')">🗑️ Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Formulaire ajout utilisateur -->
<h3>Ajouter un utilisateur</h3>
<form method="post" style="max-width:600px; background:#fff; padding:20px; border-radius:10px; box-shadow:0 0 12px rgba(0,0,0,0.1);">
    <input type="text" name="prenom" placeholder="Prénom" required style="width:100%; padding:10px; margin-bottom:10px;">
    <input type="text" name="nom" placeholder="Nom" required style="width:100%; padding:10px; margin-bottom:10px;">
    <input type="email" name="email" placeholder="Email" required style="width:100%; padding:10px; margin-bottom:10px;">
    <input type="text" name="telephone" placeholder="Téléphone (ex : 77 123 45 67)" style="width:100%; padding:10px; margin-bottom:10px;">
    <select name="role" required style="width:100%; padding:10px; margin-bottom:10px;">
        <option value="">-- Sélectionnez un rôle --</option>
        <option value="admin">Admin</option>
        <option value="medecin">Médecin</option>
        <option value="infirmier">Infirmier</option>
        <option value="patient">Patient</option>
    </select>
    <input type="password" name="mot_de_passe" placeholder="Mot de passe" required style="width:100%; padding:10px; margin-bottom:15px;">
    <button type="submit" name="ajouter" style="background:#27ae60; color:white; padding:10px 20px; border:none; border-radius:8px; cursor:pointer;">Ajouter</button>
</form>

<style>
.btn-modifier, .btn-supprimer {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: bold;
    text-decoration: none;
    color: white;
    margin-right: 5px;
    display: inline-block;
}
.btn-modifier { background-color: #2980b9; }
.btn-modifier:hover { background-color: #1f6391; }
.btn-supprimer { background-color: #e74c3c; }
.btn-supprimer:hover { background-color: #c0392b; }

@media (max-width: 768px) {
    table, tbody, tr, td, th { display:block; width:100%; }
    tr { margin-bottom:15px; }
    td, th { text-align:right; padding-left:50%; position:relative; }
    td::before, th::before { position:absolute; left:10px; width:45%; white-space:nowrap; text-align:left; font-weight:bold; }
    td:nth-of-type(1)::before { content:"ID"; }
    td:nth-of-type(2)::before { content:"Prénom"; }
    td:nth-of-type(3)::before { content:"Nom"; }
    td:nth-of-type(4)::before { content:"Email"; }
    td:nth-of-type(5)::before { content:"Téléphone"; }
    td:nth-of-type(6)::before { content:"Rôle"; }
    td:nth-of-type(7)::before { content:"Statut"; }
    td:nth-of-type(8)::before { content:"Actions"; }
}
</style>

<?php include '../includes/footer.php'; ?>
