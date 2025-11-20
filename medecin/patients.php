<?php
require_once '../includes/init.php';
checkRole(['medecin']);  // <-- corrigé pour passer un tableau
include '../includes/header.php';

$centreId = $_SESSION['utilisateur']['centre_id'];

// Récupérer les patients du centre
$stmt = $pdo->prepare("SELECT * FROM patients WHERE centre_id = :centre_id ORDER BY nom ASC");
$stmt->execute([':centre_id' => $centreId]);
$patients = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>👥 Patients</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f4f6f9;
    }

    .sidebar {
      position: fixed;
      top: 0; left: 0;
      width: 230px;
      height: 100%;
      background-color: rgb(213, 201, 20);
      color: white;
      padding: 20px;
    }

    .sidebar h2 {
      font-size: 22px;
      color: #fff;
    }

    .sidebar a {
      display: block;
      color: #fff;
      text-decoration: none;
      padding: 10px 0;
      font-weight: 500;
    }

    .sidebar a:hover {
      background-color: rgb(208, 38, 69);
      padding-left: 10px;
    }

    .main {
      margin-left: 250px;
      padding: 30px;
    }

    h1 {
      color: #c0392b;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-top: 20px;
    }

    th, td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    th {
      background-color: #e74c3c;
      color: white;
    }

    tr:hover {
      background-color: #f1f1f1;
    }

    @media (max-width: 768px) {
      .main {
        margin-left: 0;
        padding: 20px;
      }

      .sidebar {
        display: none;
      }
    }
  </style>
</head>
<body>

<div class="sidebar">
  <h2>Espace Médecin</h2>
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="historique_consultations.php">📋 Voir les consultations</a>
  <a href="patients.php">👤 Voir les patients</a>
  <a href="../logout.php">🚪 Déconnexion</a>
</div>

<div class="main">
  <h1>👤 Liste des patients</h1>

  <?php if (count($patients) === 0): ?>
    <p>Aucun patient trouvé pour ce centre.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Nom</th>
          <th>Prénom</th>
          <th>Sexe</th>
          <th>Date de naissance</th>
          <th>Statut</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($patients as $patient): ?>
          <tr>
            <td><?= htmlspecialchars($patient['nom']) ?></td>
            <td><?= htmlspecialchars($patient['prenom']) ?></td>
            <td><?= htmlspecialchars($patient['sexe']) ?></td>
            <td><?= htmlspecialchars($patient['date_naissance']) ?></td>
            <td><?= $patient['statut'] === 'actif' ? '✅ Actif' : '🚫 Inactif' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

</body>
</html>
