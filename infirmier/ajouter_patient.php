<?php
require_once '../includes/init.php';  
include '../includes/header.php';

// Démarrage de session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifie que l'utilisateur est bien connecté et que centre_id est défini
if (!isset($_SESSION['utilisateur']) || !isset($_SESSION['utilisateur']['centre_id'])) {
    // Rediriger vers la page de login si l'utilisateur n'est pas authentifié
    header('Location: ../login.php');
    exit;
}

$centre_id = $_SESSION['utilisateur']['centre_id'];
$message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("INSERT INTO patients (nom, prenom, date_naissance, sexe, telephone, centre_id, statut)
                               VALUES (:nom, :prenom, :date_naissance, :sexe, :telephone, :centre_id, 'actif')");
        $stmt->execute([
            ':nom' => $_POST['nom'],
            ':prenom' => $_POST['prenom'],
            ':date_naissance' => $_POST['date_naissance'],
            ':sexe' => $_POST['sexe'],
            ':telephone' => $_POST['telephone'],
            ':centre_id' => $centre_id
        ]);
        $message = "✅ Patient ajouté avec succès.";
    } catch (PDOException $e) {
        $message = "❌ Erreur lors de l'ajout du patient : " . $e->getMessage();
    }
}
?>

<h2>➕ Ajouter un patient</h2>

<?php if ($message): ?>
    <p style="color: <?= strpos($message, '✅') === 0 ? 'green' : 'red' ?>;">
        <?= htmlspecialchars($message) ?>
    </p>
<?php endif; ?>

<form method="post" style="max-width:400px;">
    <label>Nom :</label><br>
    <input type="text" name="nom" required><br><br>

    <label>Prénom :</label><br>
    <input type="text" name="prenom" required><br><br>

    <label>Date de naissance :</label><br>
    <input type="date" name="date_naissance" required><br><br>

    <label>Sexe :</label><br>
    <select name="sexe" required>
        <option value="Homme">Homme</option>
        <option value="Femme">Femme</option>
        <option value="Autre">Autre</option>
    </select><br><br>

    <label>Téléphone :</label><br>
    <input type="text" name="telephone"><br><br>

    <button type="submit">Ajouter</button>
</form>

<br>
<a href="dashboard.php"><button>← Retour au dashboard</button></a>

<?php include '../includes/footer.php'; ?>
