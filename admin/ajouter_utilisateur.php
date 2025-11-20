<?php
require_once '../config/db.php';
include '../includes/header.php';  

$message = '';

// Récupération des centres (avec ville associée)
$centres = $pdo->query("SELECT centres.id, centres.nom, villes.nom AS ville 
                        FROM centres 
                        JOIN villes ON centres.ville_id = villes.id 
                        ORDER BY villes.nom, centres.nom")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $telephone = trim($_POST['telephone'] ?? '');
    $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
    $centre_id = !empty($_POST['centre_id']) ? $_POST['centre_id'] : null;

    $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, telephone, statut, centre_id)
                           VALUES (:nom, :prenom, :email, :mot_de_passe, :role, :telephone, 'actif', :centre_id)");
    try {
        $stmt->execute([
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':email' => $email,
            ':mot_de_passe' => $mot_de_passe,
            ':role' => $role,
            ':telephone' => $telephone,
            ':centre_id' => $centre_id
        ]);
        $message = "✅ Utilisateur ajouté avec succès.";
    } catch (PDOException $e) {
        $message = "❌ Erreur : " . $e->getMessage();
    }
}
?>

<h2>Ajouter un utilisateur</h2>

<form method="post" style="max-width:600px; background:#fff; padding:25px; border-radius:10px; box-shadow:0 0 12px rgba(0,0,0,0.1);">

    <label>Nom :</label><br>
    <input type="text" name="nom" required style="width:100%; padding:10px; margin-bottom:15px;"><br>

    <label>Prénom :</label><br>
    <input type="text" name="prenom" required style="width:100%; padding:10px; margin-bottom:15px;"><br>

    <label>Email :</label><br>
    <input type="email" name="email" required style="width:100%; padding:10px; margin-bottom:15px;"><br>

    <label>Téléphone :</label><br>
    <input type="text" name="telephone" placeholder="Ex : 77 123 45 67" style="width:100%; padding:10px; margin-bottom:15px;"><br>

    <label>Mot de passe :</label><br>
    <input type="password" name="mot_de_passe" required style="width:100%; padding:10px; margin-bottom:15px;"><br>

    <label>Rôle :</label><br>
    <select name="role" required style="width:100%; padding:10px; margin-bottom:15px;">
        <option value="admin">Admin</option>
        <option value="medecin">Médecin</option>
        <option value="infirmier">Infirmier</option>
        <option value="patient">Patient</option>
    </select><br>

    <label>Centre de santé :</label><br>
    <select name="centre_id" style="width:100%; padding:10px; margin-bottom:20px;">
        <option value="">-- Aucun / Non assigné --</option>
        <?php foreach ($centres as $c): ?>
            <option value="<?= $c['id'] ?>">
                <?= htmlspecialchars($c['nom']) ?> (<?= htmlspecialchars($c['ville']) ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" style="background:#27ae60; color:white; padding:12px 20px; border:none; border-radius:8px; cursor:pointer;">✅ Ajouter</button>

</form>

<?php if ($message): ?>
    <p style="color: green; margin-top:15px;"><?= $message ?></p>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
