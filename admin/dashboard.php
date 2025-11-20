<?php
require_once __DIR__ . '/../includes/init.php';

// Vérifie connexion
if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role']!=='admin') {
    header('Location: ../login.php');
    exit;
}

// Récupération données pour dashboard
try {
    $statsUsers = $pdo->query("SELECT role, COUNT(*) AS total FROM utilisateurs GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);
    $totalVilles = $pdo->query("SELECT COUNT(*) FROM villes")->fetchColumn();
    $totalCentres = $pdo->query("SELECT COUNT(*) FROM centres")->fetchColumn();
    $statsPatients = $pdo->query("SELECT statut, COUNT(*) AS total FROM patients GROUP BY statut")->fetchAll(PDO::FETCH_KEY_PAIR);
    $statsConsults = $pdo->query("SELECT statut, COUNT(*) AS total FROM consultations GROUP BY statut")->fetchAll(PDO::FETCH_KEY_PAIR);

    // Consultations par mois
    $rows = $pdo->query("
        SELECT DATE_FORMAT(date_consultation,'%Y-%m') AS mois, statut, COUNT(*) AS total
        FROM consultations
        GROUP BY mois, statut
        ORDER BY mois ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $moisLabels = [];
    $consultEnAttente = [];
    $consultTraitees = [];

    foreach($rows as $r){ if(!in_array($r['mois'],$moisLabels)) $moisLabels[]=$r['mois']; }
    foreach($moisLabels as $m){
        $en=0;$traite=0;
        foreach($rows as $r){
            if($r['mois']==$m){
                if($r['statut']=='envoyée') $en=$r['total'];
                if($r['statut']=='traitée') $traite=$r['total'];
            }
        }
        $consultEnAttente[]=$en;
        $consultTraitees[]=$traite;
    }
} catch(PDOException $e){
    die("Erreur DB : ".$e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<style>
:root {
    --primary: #27ae60;
    --secondary: #2980b9;
    --bg: #f4f6f9;
    --card: #ffffff;
}

body {
    font-family: 'Inter', sans-serif;
    background: var(--bg);
    margin:0; padding:0;
}

header.topbar {
    position:fixed; top:0; left:0; right:0;
    height:50px; background:var(--secondary);
    display:flex; align-items:center; justify-content:space-between;
    padding:0 20px; color:white; z-index:1200;
    box-shadow:0 4px 12px rgba(0,0,0,0.15);
}
header.topbar h2 {
    font-size:20px; font-weight:700;
}
header.topbar .profile { display:flex; align-items:center; gap:10px; }
header.topbar .profile img { width:32px; height:32px; border-radius:50%; }
header.topbar .profile a { background:var(--primary); color:white; padding:5px 12px; border-radius:6px; text-decoration:none; font-weight:600; }
header.topbar .profile a:hover { background:#1e8449; }

.sidebar {
    position:fixed; left:0; top:50px; width:220px; height:calc(100% - 50px);
    background:#34495e; color:white; display:flex; flex-direction:column; padding:20px;
}
.sidebar h2 { text-align:center; font-size:18px; margin-bottom:20px; }
.sidebar a { color:white; padding:12px; margin-bottom:8px; text-decoration:none; font-weight:600; border-radius:8px; transition:0.3s; display:flex; align-items:center; gap:8px; }
.sidebar a:hover { background:#2c3e50; }
.main-content { margin-left:240px; padding:80px 30px 30px 30px; }

.dashboard-container { display:flex; flex-wrap:wrap; gap:20px; }
.card {
    background:var(--card); border-radius:12px;
    box-shadow:0 4px 20px rgba(0,0,0,0.08); padding:20px; flex:1 1 280px;
}
.card h3 { font-size:18px; color:var(--secondary); margin-bottom:15px; }
.card ul { list-style:none; padding:0; margin:0; }
.card ul li { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #eee; }

.chart-container { background:var(--card); border-radius:12px; padding:20px; box-shadow:0 4px 20px rgba(0,0,0,0.08); flex:1 1 500px; }
.chart-container canvas { width:100%!important; height:300px!important; }

@media(max-width:768px){
    .sidebar{display:none;}
    .main-content{margin-left:0;padding:20px;}
    header.topbar{justify-content:center;}
}
</style>

<header class="topbar">
    <h2>⚙️ Dashboard Admin</h2>
    <div class="profile">
        <img src="../assets/images/default-user.png" alt="Profil">
        <span><?= htmlspecialchars($_SESSION['utilisateur']['prenom'].' '.$_SESSION['utilisateur']['nom']) ?></span>
        <a href="../logout.php">Déconnexion</a>
    </div>
</header>

<div class="sidebar">
    <h2>⚙️ Menu</h2>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="utilisateurs.php">👥 Utilisateurs</a>
    <a href="villes.php">🏙️ Villes</a>
    <a href="centres.php">🏥 Centres</a>
</div>

<div class="main-content">
    <h1>Bienvenue, <?= htmlspecialchars($_SESSION['utilisateur']['prenom'].' '.$_SESSION['utilisateur']['nom']) ?> 👋</h1>

    <div class="dashboard-container">
        <div class="card">
            <h3>Utilisateurs</h3>
            <ul>
                <li>Admins <strong><?= $statsUsers['admin'] ?? 0 ?></strong></li>
                <li>Médecins <strong><?= $statsUsers['medecin'] ?? 0 ?></strong></li>
                <li>Infirmiers <strong><?= $statsUsers['infirmier'] ?? 0 ?></strong></li>
                <li>Patients <strong><?= $statsUsers['patient'] ?? 0 ?></strong></li>
            </ul>
        </div>
        <div class="card">
            <h3>Localisation</h3>
            <ul>
                <li>Villes <strong><?= $totalVilles ?></strong></li>
                <li>Centres <strong><?= $totalCentres ?></strong></li>
            </ul>
        </div>
        <div class="card">
            <h3>Patients</h3>
            <ul>
                <li>Actifs <strong><?= $statsPatients['actif'] ?? 0 ?></strong></li>
                <li>Inactifs <strong style="color:#e74c3c;"><?= $statsPatients['inactif'] ?? 0 ?></strong></li>
            </ul>
        </div>
        <div class="card">
            <h3>Consultations</h3>
            <ul>
                <li>En attente <strong><?= $statsConsults['envoyée'] ?? 0 ?></strong></li>
                <li>Traitées <strong><?= $statsConsults['traitée'] ?? 0 ?></strong></li>
            </ul>
        </div>
    </div>

    <h2 style="margin-top:40px;">📈 Graphiques</h2>
    <div class="dashboard-container">
        <div class="chart-container">
            <h3>Répartition utilisateurs</h3>
            <canvas id="chartUsers"></canvas>
        </div>
        <div class="chart-container">
            <h3>Consultations (statut)</h3>
            <canvas id="chartConsults"></canvas>
        </div>
        <div class="chart-container" style="flex:1 1 100%;">
            <h3>Évolution consultations par mois</h3>
            <canvas id="chartEvolution"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chartUsers'), {
    type:'pie',
    data:{
        labels:['Admins','Médecins','Infirmiers','Patients'],
        datasets:[{
            data:[
                <?= $statsUsers['admin'] ?? 0 ?>,
                <?= $statsUsers['medecin'] ?? 0 ?>,
                <?= $statsUsers['infirmier'] ?? 0 ?>,
                <?= $statsUsers['patient'] ?? 0 ?>
            ],
            backgroundColor:['#2980b9','#27ae60','#f39c12','#e74c3c']
        }]
    }
});

new Chart(document.getElementById('chartConsults'), {
    type:'bar',
    data:{
        labels:['En attente','Traitées'],
        datasets:[{
            data:[
                <?= $statsConsults['envoyée'] ?? 0 ?>,
                <?= $statsConsults['traitée'] ?? 0 ?>
            ],
            backgroundColor:['#f39c12','#27ae60']
        }]
    },
    options:{
        responsive:true,
        plugins:{legend:{display:false}},
        scales:{y:{beginAtZero:true}}
    }
});

new Chart(document.getElementById('chartEvolution'), {
    type:'line',
    data:{
        labels:<?= json_encode($moisLabels) ?>,
        datasets:[
            {label:'En attente', data:<?= json_encode($consultEnAttente) ?>, borderColor:'#f39c12', backgroundColor:'rgba(243,156,18,0.2)', fill:true, tension:0.3},
            {label:'Traitées', data:<?= json_encode($consultTraitees) ?>, borderColor:'#27ae60', backgroundColor:'rgba(39,174,96,0.2)', fill:true, tension:0.3}
        ]
    },
    options:{
        responsive:true,
        plugins:{legend:{position:'bottom'}},
        scales:{y:{beginAtZero:true}}
    }
});
</script>
