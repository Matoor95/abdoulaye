<?php
require_once '../includes/init.php';
checkRole(['infirmier']);

$user = $_SESSION['utilisateur'] ?? [];
$infirmierId = $user['id'] ?? null;
$centreId = $user['centre_id'] ?? null;

// --- Statistiques patients ---
$stmtPatients = $pdo->prepare("SELECT statut, COUNT(*) FROM patients WHERE centre_id = ? GROUP BY statut");
$stmtPatients->execute([$centreId]);
$patients = $stmtPatients->fetchAll(PDO::FETCH_KEY_PAIR);

// --- Statistiques consultations ---
$stmtConsults = $pdo->prepare("SELECT statut, COUNT(*) FROM consultations WHERE infirmier_id = ? GROUP BY statut");
$stmtConsults->execute([$infirmierId]);
$consults = $stmtConsults->fetchAll(PDO::FETCH_KEY_PAIR);

// --- Réponses récentes ---
$stmtReponses = $pdo->prepare("
    SELECT c.id, c.date_consultation 
    FROM consultations c 
    WHERE c.infirmier_id = ? AND statut = 'traitée'
    ORDER BY c.date_consultation DESC
    LIMIT 5
");
$stmtReponses->execute([$infirmierId]);
$reponses = $stmtReponses->fetchAll();

// --- Timeline ---
$stmtTimeline = $pdo->prepare("
    SELECT c.id, c.date_consultation, c.statut, p.prenom, p.nom, rm.id AS reponse_id
    FROM consultations c
    JOIN patients p ON p.id = c.patient_id
    LEFT JOIN reponses_medicales rm ON rm.consultation_id = c.id
    WHERE c.infirmier_id = ?
    ORDER BY c.date_consultation DESC
    LIMIT 10
");
$stmtTimeline->execute([$infirmierId]);
$timeline = $stmtTimeline->fetchAll();

// --- Symptômes par mois ---
$stmtSymptoms = $pdo->prepare("
    SELECT DATE_FORMAT(date_consultation, '%Y-%m') AS mois, symptomes
    FROM consultations
    WHERE infirmier_id = ?
");
$stmtSymptoms->execute([$infirmierId]);
$symptomDataRaw = $stmtSymptoms->fetchAll(PDO::FETCH_ASSOC);

$symptomeCounts = [];
$moisLabels = [];
foreach($symptomDataRaw as $row){
    $mois = $row['mois'];
    $symptomes = array_map('trim', explode(',', $row['symptomes']));
    if(!in_array($mois, $moisLabels)) $moisLabels[] = $mois;
    foreach($symptomes as $sym){
        $symptomeCounts[$sym][$mois] = ($symptomeCounts[$sym][$mois] ?? 0) + 1;
    }
}

$colors = ['#3498db','#f1c40f','#1abc9c','#9b59b6','#e74c3c','#16a085','#2ecc71'];
$datasets = [];
$i = 0;
foreach($symptomeCounts as $sym => $data){
    $datasets[] = [
        'label' => $sym,
        'data' => array_values($data),
        'borderColor' => $colors[$i % count($colors)],
        'tension' => 0.3,
        'fill' => false
    ];
    $i++;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>TELEDISPENSAIRE - Tableau de bord</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{
  background:linear-gradient(120deg,#f4f6f9,#e9eff5);
  background-image:url('https://cdn-icons-png.flaticon.com/512/2966/2966480.png');
  background-repeat:no-repeat;background-position:right bottom;background-size:300px;
  transition:background 0.5s,color 0.5s;
  color:#2c3e50;
}
body.dark{
  background:#121212 url('https://cdn-icons-png.flaticon.com/512/2966/2966480.png') no-repeat right bottom/300px;
  color:#e0e0e0;
}
header{
  position:fixed;top:0;left:0;width:100%;height:60px;
  background:#2e86de;display:flex;align-items:center;justify-content:space-between;
  padding:0 25px;color:white;font-weight:700;font-size:20px;letter-spacing:1px;
  box-shadow:0 4px 12px rgba(0,0,0,0.1);z-index:1000;
}
header .menu-btn{display:none;cursor:pointer;font-size:26px;}
.sidebar{
  position:fixed;top:60px;left:0;width:250px;height:calc(100% - 60px);
  background:#f1c40f;display:flex;flex-direction:column;padding:25px 15px;
  transition:transform 0.4s ease;
}
.sidebar.hidden{transform:translateX(-260px);}
.sidebar a{
  text-decoration:none;color:#2c3e50;padding:12px 15px;margin:5px 0;
  border-radius:10px;transition:0.3s;font-weight:600;
}
.sidebar a:hover{background:#27ae60;color:#fff;transform:translateX(5px);}
.main{
  margin-left:270px;margin-top:80px;padding:30px;transition:margin 0.4s ease;
}
.main.full{margin-left:0;}
.card{
  background:white;padding:25px;border-radius:16px;
  box-shadow:0 5px 20px rgba(0,0,0,0.1);margin-bottom:25px;
  transition:transform 0.3s, box-shadow 0.3s;
}
.card:hover{transform:translateY(-5px);box-shadow:0 10px 30px rgba(0,0,0,0.15);}
.card h3{margin-bottom:15px;font-size:18px;}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:25px;}
.action-buttons{display:flex;flex-wrap:wrap;gap:15px;margin-top:15px;}
.action-buttons a{
  flex:1 1 160px;text-align:center;padding:12px 18px;border-radius:10px;
  color:white;font-weight:600;text-decoration:none;transition:0.3s;
}
.btn-green{background:#27ae60;} .btn-yellow{background:#f1c40f;color:#2c3e50;}
.btn-red{background:#c0392b;}
.btn-green:hover{background:#219150;} .btn-yellow:hover{background:#d4ac0d;}
.btn-red:hover{background:#a93226;}
.toggle-container{display:flex;align-items:center;gap:8px;}
.toggle-container label{cursor:pointer;font-weight:600;}
@media(max-width:900px){
  header .menu-btn{display:block;}
  .sidebar{transform:translateX(-260px);}
  .sidebar.show{transform:translateX(0);}
  .main{margin:80px 15px;}
}
</style>
</head>
<body>
<header>
  <span class="menu-btn" onclick="toggleSidebar()">☰</span>
  TELEDISPENSAIRE
  <div class="toggle-container">
    <label for="darkMode">🌙</label>
    <input type="checkbox" id="darkMode">
  </div>
</header>

<nav class="sidebar" id="sidebar">
  <a href="dashboard.php">📊 Tableau de bord</a>
  <a href="ajouter_consultation.php">➕ Nouvelle consultation</a>
  <a href="consultations.php">📋 Mes consultations</a>
  <a href="patients.php">🧑‍🤝‍🧑 Patients</a>
  <a href="../logout.php">🚪 Déconnexion</a>
</nav>

<main class="main" id="main">
  <h2>Bienvenue <?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?></h2>

  <div class="cards">
    <div class="card">
      <h3>👥 Patients</h3>
      <canvas id="patientsChart"></canvas>
    </div>

    <div class="card">
      <h3>📋 Consultations</h3>
      <canvas id="consultsChart"></canvas>
    </div>

    <div class="card">
      <h3>🩺 Réponses récentes</h3>
      <?php if(empty($reponses)): ?><p>Aucune réponse disponible.</p>
      <?php else: ?><ul><?php foreach($reponses as $r): ?>
        <li><?= htmlspecialchars($r['date_consultation']) ?> — <a href="voir_reponse.php?id=<?= $r['id'] ?>">Voir</a></li>
      <?php endforeach; ?></ul><?php endif; ?>
    </div>

    <div class="card">
      <h3>⏱️ Timeline</h3>
      <ul>
      <?php if(empty($timeline)) echo "<li>Aucune consultation.</li>";
      else foreach($timeline as $t):
        $status = $t['statut']==='traitée'?'✅ Traitée':'🕐 En attente';
        echo "<li>$status — {$t['prenom']} {$t['nom']}</li>";
      endforeach; ?>
      </ul>
    </div>

    <div class="card">
      <h3>📈 Évolution des symptômes</h3>
      <canvas id="symptomesChart"></canvas>
    </div>
  </div>

  <section class="card">
    <h3>⚡ Actions rapides</h3>
    <div class="action-buttons">
      <a href="ajouter_consultation.php" class="btn-green">Ajouter consultation</a>
      <a href="consultations.php" class="btn-yellow">Voir mes consultations</a>
      <a href="ajouter_patient.php" class="btn-green">Ajouter patient</a>
      <a href="liste_patients.php" class="btn-red">Liste patients</a>
    </div>
  </section>
</main>

<script>
const sidebar=document.getElementById('sidebar');
const main=document.getElementById('main');
function toggleSidebar(){
  sidebar.classList.toggle('show');
  main.classList.toggle('full');
}
document.getElementById('darkMode').addEventListener('change',e=>{
  document.body.classList.toggle('dark',e.target.checked);
});

new Chart(document.getElementById('patientsChart'),{
  type:'doughnut',
  data:{labels:['Actifs','Inactifs'],datasets:[{data:[<?= (int)($patients['actif']??0) ?>,<?= (int)($patients['inactif']??0) ?>],backgroundColor:['#27ae60','#c0392b']}]},
  options:{plugins:{legend:{position:'bottom'}},responsive:true}
});
new Chart(document.getElementById('consultsChart'),{
  type:'doughnut',
  data:{labels:['Envoyées','Traitées'],datasets:[{data:[<?= (int)($consults['envoyée']??0) ?>,<?= (int)($consults['traitée']??0) ?>],backgroundColor:['#f1c40f','#27ae60']}]},
  options:{plugins:{legend:{position:'bottom'}},responsive:true}
});
new Chart(document.getElementById('symptomesChart'),{
  type:'line',
  data:{labels:<?= json_encode(array_keys(reset($symptomeCounts) ?: [])) ?>,datasets:<?= json_encode($datasets) ?>},
  options:{responsive:true,plugins:{legend:{position:'bottom'}},scales:{y:{beginAtZero:true}}}
});
</script>
</body>
</html>
