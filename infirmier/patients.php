<?php
require_once '../includes/init.php';
checkRole(['infirmier']);

// 🔹 Définit currentUser si non défini
if (!function_exists('currentUser')) {
    function currentUser() {
        return $_SESSION['utilisateur'] ?? null;
    }
}

$user = currentUser();
$centreId = $user['centre_id'] ?? null;

// Récupérer tous les patients du centre
$stmt = $pdo->prepare("
    SELECT id, prenom, nom, sexe, date_naissance, telephone, statut 
    FROM patients 
    WHERE centre_id = ? 
    ORDER BY nom ASC
");
$stmt->execute([$centreId]);
$patients = $stmt->fetchAll();

// Pour affichage
$userPrenom = htmlspecialchars($user['prenom'] ?? '');
$userNom = htmlspecialchars($user['nom'] ?? '');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Patients - Dashboard Infirmier</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
<style>
* {margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif;}
body {background:#f4f6f9; color:#2c3e50; transition:0.3s;}
body.dark{background:#1c1c1c; color:#e0e0e0;}
body.dark table, body.dark th, body.dark td {background:#2c2c2c; color:#f0f0f0;}
.sidebar{position:fixed; top:0; left:0; width:240px; height:100%; background:#f1c40f; padding:25px 20px; display:flex; flex-direction:column; gap:20px;}
.sidebar h2{color:#c0392b; text-align:center; font-size:24px;}
.sidebar a{display:flex; align-items:center; gap:10px; color:#2c3e50; padding:12px 15px; border-radius:10px; font-weight:600; text-decoration:none; transition:all 0.3s;}
.sidebar a:hover{background:#27ae60;color:white; transform:translateX(5px);}
.main{margin-left:260px; padding:30px;}
h1{margin-bottom:20px;}
table{width:100%; border-collapse:collapse; margin-bottom:20px;}
th, td{padding:12px 10px; border:1px solid #ddd; text-align:left;}
th{background:#27ae60; color:white;}
tr:nth-child(even){background:#f9f9f9;}
.actions a{margin-right:5px; padding:5px 12px; border-radius:6px; text-decoration:none; font-weight:600; color:white;}
.btn-view{background:#2980b9;}
.btn-view:hover{background:#1f6391;}
.btn-edit{background:#f1c40f; color:#2c3e50;}
.btn-edit:hover{background:#d4ac0d;}
.btn-delete{background:#e74c3c;}
.btn-delete:hover{background:#c0392b;}
.toggle-container{display:flex; align-items:center; gap:10px; margin-bottom:20px;}
@media(max-width:768px){.sidebar{display:none;}.main{margin-left:0; padding:20px;}}
</style>
</head>
<body>
<nav class="sidebar">
  <h2>Espace Infirmier</h2>
  <a href="dashboard.php">📊 Dashboard</a>
  <a href="ajouter_consultation.php">➕ Ajouter Consultation</a>
  <a href="consultations.php">📋 Mes Consultations</a>
  <a href="patients.php">🧑‍🤝‍🧑 Patients</a>
  <a href="../logout.php">🚪 Déconnexion</a>
</nav>

<main class="main">
<div class="toggle-container">
  <label for="darkMode">🌙 Mode sombre</label>
  <input type="checkbox" id="darkMode" aria-checked="false" role="switch" />
</div>

<h1>Liste des patients</h1>
<p>Bienvenue, <?= $userPrenom . ' ' . $userNom ?></p>

<?php if(empty($patients)): ?>
    <p>Aucun patient enregistré pour le moment.</p>
<?php else: ?>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Prénom</th>
            <th>Nom</th>
            <th>Sexe</th>
            <th>Date de naissance</th>
            <th>Téléphone</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($patients as $p): ?>
        <tr>
            <td><?= (int)$p['id'] ?></td>
            <td><?= htmlspecialchars($p['prenom']) ?></td>
            <td><?= htmlspecialchars($p['nom']) ?></td>
            <td><?= htmlspecialchars($p['sexe']) ?></td>
            <td><?= htmlspecialchars($p['date_naissance']) ?></td>
            <td><?= htmlspecialchars($p['telephone']) ?></td>
            <td><?= htmlspecialchars($p['statut']) ?></td>
            <td class="actions">
                <a href="voir_patient.php?id=<?= (int)$p['id'] ?>" class="btn-view">Voir</a>
                <a href="modifier_patient.php?id=<?= (int)$p['id'] ?>" class="btn-edit">Modifier</a>
                <a href="supprimer_patient.php?id=<?= (int)$p['id'] ?>" class="btn-delete" onclick="return confirm('Confirmer la suppression ?')">Supprimer</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</main>

<script>
const toggle = document.getElementById('darkMode');
toggle.addEventListener('change', () => {
    document.body.classList.toggle('dark');
    toggle.setAttribute('aria-checked', toggle.checked);
});
</script>
</body>
</html>
