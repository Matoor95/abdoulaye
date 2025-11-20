<?php
// medecin/dashboard.php
require_once __DIR__ . '/../includes/init.php';
include __DIR__ . '/../includes/header.php';

// contrôle d'accès
if (function_exists('checkRole')) {
    checkRole(['medecin']);
} else {
    if (!isset($_SESSION['utilisateur']['role']) || $_SESSION['utilisateur']['role'] !== 'medecin') {
        header('Location: ../index.php');
        exit;
    }
}

$medecinId = $_SESSION['utilisateur']['id'] ?? null;
if (!$medecinId) {
    header('Location: ../login.php');
    exit;
}

// Statistiques
$totalPatients = (int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();

// consultations par statut pour ce médecin
$stmtConsults = $pdo->prepare("SELECT statut, COUNT(*) AS total FROM consultations WHERE medecin_id = ? GROUP BY statut");
$stmtConsults->execute([$medecinId]);
$consults = $stmtConsults->fetchAll(PDO::FETCH_KEY_PAIR);

// dernières consultations traitées (pour tableau / actions)
$stmtRecent = $pdo->prepare("
  SELECT c.id, c.date_consultation, c.patient_id, p.prenom AS patient_prenom, p.nom AS patient_nom, c.statut
  FROM consultations c
  LEFT JOIN patients p ON p.id = c.patient_id
  WHERE c.medecin_id = ? AND c.statut = 'traitée'
  ORDER BY c.date_consultation DESC
  LIMIT 6
");
$stmtRecent->execute([$medecinId]);
$recent = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

// timeline initiale (dernieres 10)
$stmtTimeline = $pdo->prepare("
  SELECT c.id, c.date_consultation, c.statut, p.prenom AS patient_prenom, p.nom AS patient_nom, rm.id AS reponse_id, u.prenom AS infirmier_prenom, u.nom AS infirmier_nom
  FROM consultations c
  JOIN patients p ON p.id = c.patient_id
  LEFT JOIN reponses_medicales rm ON rm.consultation_id = c.id
  LEFT JOIN utilisateurs u ON u.id = c.infirmier_id
  WHERE c.medecin_id = ?
  ORDER BY c.date_consultation DESC
  LIMIT 10
");
$stmtTimeline->execute([$medecinId]);
$timeline = $stmtTimeline->fetchAll(PDO::FETCH_ASSOC);

$userPrenom = htmlspecialchars($_SESSION['utilisateur']['prenom'] ?? '');
$userNom = htmlspecialchars($_SESSION['utilisateur']['nom'] ?? '');

// données pour chart (sécurisé via json_encode)
$consultEnvoyees = (int)($consults['envoyée'] ?? 0);
$consultTraitees  = (int)($consults['traitée'] ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Dashboard Médecin — Télésanté</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root{
  --bg:#f4f7fb; --card:#ffffff; --muted:#6b7280; --accent:#0b74de; --warn:#fbbf24; --success:#10b981; --danger:#ef4444;
  --glass: rgba(255,255,255,0.75);
}
*{box-sizing:border-box;font-family:'Inter',system-ui,Segoe UI,Roboto,'Helvetica Neue',Arial;}
html,body{height:100%;margin:0}
body{
  margin:0;
  background: linear-gradient(180deg, #f4f7fb 0%, #eef4fb 100%);
  color: #0f1724;
  transition:background .35s, color .25s;
  -webkit-font-smoothing:antialiased;
  -moz-osx-font-smoothing:grayscale;
  overflow-x:hidden;
}

/* animated SVG watermark (behind content) */
.bg-watermark {
  position:fixed;
  right: -60px;
  bottom: -20px;
  width:420px;
  height:420px;
  opacity:0.08;
  pointer-events:none;
  z-index:0;
  transform-origin:center;
  animation: float 10s ease-in-out infinite;
}
@keyframes float {
  0% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-10px) rotate(3deg); }
  100% { transform: translateY(0) rotate(0deg); }
}

/* wrapper */
.wrap { display:flex; min-height:100vh; position:relative; z-index:1; }

/* sidebar */
.sidebar {
  width:260px;
  background: linear-gradient(180deg, #052c6b 0%, #0b74de 100%);
  color:#fff;
  padding:28px 20px;
  flex-shrink:0;
  display:flex;
  flex-direction:column;
  gap:18px;
  box-shadow: 8px 0 24px rgba(11,116,222,0.08);
  position:fixed;
  left:0; top:0; bottom:0;
  transform: translateX(0);
  transition: transform .28s ease;
}
.sidebar .brand {
  display:flex; align-items:center; gap:12px;
}
.brand .logo {
  width:44px; height:44px; border-radius:10px; background:linear-gradient(135deg,#fff2, #fff1);
  display:flex;align-items:center;justify-content:center;font-weight:800;color:#052c6b;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.2);
}
.brand h2{margin:0;font-size:18px;font-weight:700}
.navlink {
  display:flex;align-items:center;gap:10px;padding:12px;border-radius:10px;color:#fff;text-decoration:none;font-weight:600;
  transition:all .18s ease;
}
.navlink:hover { transform: translateX(6px); background: rgba(255,255,255,0.06); }
.navlink.active { background: rgba(0,0,0,0.12); transform:none; }
.sidebar .spacer { margin-top:auto; font-size:13px; opacity:0.95; text-align:center; }

/* mobile menu button */
.menu-toggle {
  display:none;
  position:fixed; left:14px; top:14px; z-index:1200; border:none; background:var(--accent); color:#fff; padding:10px 12px; border-radius:8px; cursor:pointer;
  box-shadow: 0 6px 18px rgba(11,116,222,0.18);
}

/* main content */
.main { margin-left:260px; padding:28px 32px; flex:1; transition:margin-left .28s ease; min-height:100vh; }
.top { display:flex; justify-content:space-between; align-items:center; gap:18px; flex-wrap:wrap; margin-bottom:18px; }
.welcome { font-size:22px; font-weight:700; }
.meta { color:var(--muted); font-size:14px; }

/* cards */
.cards { display:flex; gap:18px; flex-wrap:wrap; margin-bottom:22px; }
.card { background:var(--card); border-radius:14px; padding:18px; box-shadow: 0 8px 30px rgba(16,24,40,0.06); flex:1 1 260px; min-width:220px; position:relative; overflow:hidden; }
.card h3{margin:0 0 8px;font-size:15px}
.card p{margin:0;font-size:20px;font-weight:700}
.badge { position:absolute; top:12px; right:12px; background:var(--danger); color:#fff; padding:6px 10px; border-radius:999px; font-weight:700; font-size:12px; }

/* table & timeline */
.table-responsive { background:var(--card); padding:12px; border-radius:12px; box-shadow:0 8px 20px rgba(16,24,40,0.04); }
table { width:100%; border-collapse:collapse; font-size:14px; }
th,td { padding:10px 12px; text-align:left; border-bottom:1px solid rgba(15,23,36,0.06); }
th { background: linear-gradient(90deg,var(--accent), #1e73c9); color: #fff; font-weight:700; border-radius:6px; }
.timeline { list-style:none; margin:0; padding:0; }
.timeline li { display:flex; justify-content:space-between; gap:12px; padding:10px 0; border-bottom:1px dashed rgba(15,23,36,0.04); }
.status-dot { padding:6px 10px; border-radius:8px; color:#fff; font-weight:700; }

/* buttons */
.btn { display:inline-block; padding:8px 12px; border-radius:10px; text-decoration:none; font-weight:700; }
.btn.green { background:var(--success); color:#fff; }
.btn.yellow { background:var(--warn); color:#111; }
.btn.blue { background:var(--accent); color:#fff; }

/* notification bubble */
#notif { display:none; position:fixed; right:20px; top:20px; background:var(--accent); color:#fff; padding:12px 16px; border-radius:12px; box-shadow: 0 10px 30px rgba(11,116,222,0.22); z-index:1400; white-space:pre-line; }

/* responsive */
@media (max-width: 940px) {
  .menu-toggle { display:block; }
  .sidebar { transform: translateX(-110%); position:fixed; z-index:1300; }
  .sidebar.open { transform: translateX(0); }
  .main { margin-left:0; padding:20px; }
  .bg-watermark { display:none; }
}

/* dark mode */
body.dark {
  background: linear-gradient(180deg,#071026 0%, #071433 100%);
  color:#e6eef6;
}
body.dark .card { background: rgba(255,255,255,0.03); box-shadow:none; border:1px solid rgba(255,255,255,0.02); }
body.dark .sidebar { background: linear-gradient(180deg,#03233f,#064e8b); }
</style>
</head>
<body>

<!-- SVG watermark (animated pulse in CSS via opacity/transform) -->
<div class="bg-watermark" aria-hidden="true">
  <!-- simple stylized doctor SVG (kept small and light) -->
  <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" width="420" height="420">
    <defs>
      <linearGradient id="g1" x1="0" x2="1">
        <stop offset="0" stop-color="#0b74de" stop-opacity="1"/>
        <stop offset="1" stop-color="#10b981" stop-opacity="1"/>
      </linearGradient>
      <filter id="f1" x="-20%" y="-20%" width="140%" height="140%">
        <feGaussianBlur stdDeviation="6" result="b"/>
        <feBlend in="SourceGraphic" in2="b"/>
      </filter>
    </defs>
    <g filter="url(#f1)" transform="translate(0,0)">
      <circle cx="100" cy="70" r="28" fill="url(#g1)" />
      <rect x="62" y="100" rx="12" ry="12" width="76" height="72" fill="url(#g1)" />
      <path d="M70 120 q30 18 60 0" fill="none" stroke="#fff5" stroke-width="8" stroke-linecap="round"/>
      <rect x="92" y="108" width="16" height="32" rx="6" fill="#fff5"/>
    </g>
  </svg>
</div>

<button class="menu-toggle" id="menuBtn" aria-label="Ouvrir le menu">☰</button>

<div class="wrap" role="application">
  <aside class="sidebar" id="sidebar" aria-label="Menu principal">
    <div>
      <div class="brand">
        <div class="logo" aria-hidden="true">TD</div>
        <div>
          <h2>Dr <?= $userPrenom . ' ' . $userNom ?></h2>
          <div style="font-size:12px;opacity:.9">TÉLÉDISPENSAIRE</div>
        </div>
      </div>

      <nav style="margin-top:18px" aria-label="Navigation principale">
        <a class="navlink active" href="dashboard.php">🏠 Tableau de bord</a>
        <a class="navlink" href="consultations.php">📋 Consultations</a>
        <a class="navlink" href="patients.php">👥 Patients</a>
        <a class="navlink" href="historique_consultations.php">📚 Historique</a>
      </nav>
    </div>

    <div class="spacer">
      <a class="navlink" href="../logout.php">🚪 Déconnexion</a>
    </div>
  </aside>

  <main class="main" role="main" aria-live="polite">
    <div class="top">
      <div>
        <div class="welcome">Bienvenue, Dr <?= $userPrenom ?></div>
        <div class="meta">Tableau de bord — Suivi des consultations et patients</div>
      </div>

      <div style="display:flex;align-items:center;gap:12px">
        <label for="darkToggle" style="font-weight:700;color:var(--muted)">🌙</label>
        <input id="darkToggle" type="checkbox" aria-label="Activer le mode sombre">
      </div>
    </div>

    <section class="cards" aria-label="Statistiques rapides">
      <div class="card" aria-labelledby="patientsTitle">
        <h3 id="patientsTitle">👥 Total patients</h3>
        <p><?= (int)$totalPatients ?></p>
      </div>

      <div class="card" aria-labelledby="consultsTitle">
        <h3 id="consultsTitle">📋 Consultations</h3>
        <p>Envoyées : <span style="color:var(--warn)"><?= $consultEnvoyees ?></span> — Traitées : <span style="color:var(--success)"><?= $consultTraitees ?></span></p>
        <?php if ($consultEnvoyees > 0): ?>
          <div class="badge"><?= $consultEnvoyees ?></div>
        <?php endif; ?>
      </div>

      <div class="card" aria-labelledby="recentTitle">
        <h3 id="recentTitle">🩺 Réponses récentes</h3>
        <?php if (empty($recent)): ?>
          <p class="meta">Aucune consultation traitée récemment.</p>
        <?php else: ?>
          <div class="table-responsive" role="region" aria-label="Réponses récentes">
            <table>
              <thead>
                <tr><th>Date</th><th>Patient</th><th>Action</th></tr>
              </thead>
              <tbody>
                <?php foreach ($recent as $r): ?>
                  <tr>
                    <td><?= htmlspecialchars($r['date_consultation']) ?></td>
                    <td><?= htmlspecialchars(trim(($r['patient_prenom'] ?? '') . ' ' . ($r['patient_nom'] ?? ''))) ?></td>
                    <td><a class="btn blue" href="voir_reponse.php?id=<?= (int)$r['id'] ?>">Voir</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <section style="display:flex;gap:18px;align-items:flex-start;flex-wrap:wrap">
      <div style="flex:1;min-width:300px">
        <canvas id="chartConsults" height="160" aria-label="Graphique des consultations"></canvas>
        <div style="margin-top:12px">
          <a class="btn blue" href="consultations.php">📋 Voir consultations</a>
          <a class="btn yellow" href="patients.php">👥 Dossiers patients</a>
        </div>
      </div>

      <div style="width:420px;max-width:100%">
        <div class="card" style="padding:14px 16px">
          <h3 style="margin-bottom:10px">⏱️ Timeline (dernières consultations)</h3>
          <ul id="timelineList" class="timeline" aria-live="polite" aria-relevant="additions">
            <?php if (empty($timeline)): ?>
              <li>Aucune consultation assignée récemment.</li>
            <?php else: foreach ($timeline as $t):
              $statusText = $t['statut'] === 'traitée' ? '✅ Traitée' : '🕐 En attente';
              $color = $t['statut'] === 'traitée' ? 'var(--success)' : 'var(--warn)';
              $link = $t['reponse_id'] ? "voir_reponse.php?id={$t['id']}" : "traiter_consultation.php?id={$t['id']}";
            ?>
              <li>
                <div>
                  <div style="font-weight:700"><?= htmlspecialchars($t['patient_prenom'].' '.$t['patient_nom']) ?></div>
                  <div style="font-size:13px;color:var(--muted)"><?= htmlspecialchars($t['date_consultation']) ?></div>
                </div>
                <div style="text-align:right">
                  <div class="status-dot" style="background:<?= $color ?>"><?= $statusText ?></div>
                  <div style="margin-top:8px"><a class="btn blue" href="<?= $link ?>">Voir</a></div>
                </div>
              </li>
            <?php endforeach; endif; ?>
          </ul>
        </div>
      </div>
    </section>

  </main>
</div>

<!-- notification bubble -->
<div id="notif" role="status" aria-live="polite"></div>
<audio id="sound"><source src="../assets/notification.mp3" type="audio/mpeg"></audio>

<script>
/* Sidebar toggle for mobile */
const sidebar = document.getElementById('sidebar');
const menuBtn = document.getElementById('menuBtn');
menuBtn.addEventListener('click', ()=> sidebar.classList.toggle('open'));

/* Dark mode auto + toggle */
const darkToggle = document.getElementById('darkToggle');
(function initDark() {
  const stored = localStorage.getItem('td_dark');
  if (stored === '1') {
    document.body.classList.add('dark');
    darkToggle.checked = true;
  } else if (stored === '0') {
    document.body.classList.remove('dark');
    darkToggle.checked = false;
  } else {
    // default auto: night hours
    const h = new Date().getHours();
    if (h >= 19 || h < 6) {
      document.body.classList.add('dark');
      darkToggle.checked = true;
    }
  }
})();
darkToggle.addEventListener('change', () => {
  document.body.classList.toggle('dark');
  localStorage.setItem('td_dark', document.body.classList.contains('dark') ? '1' : '0');
});

/* Chart: consultations */
const ctx = document.getElementById('chartConsults').getContext('2d');
new Chart(ctx, {
  type: 'doughnut',
  data: {
    labels: ['Envoyées','Traitées'],
    datasets: [{
      data: [<?= json_encode($consultEnvoyees) ?>, <?= json_encode($consultTraitees) ?>],
      backgroundColor: ['#fbbf24','#10b981']
    }]
  },
  options: { responsive:true, plugins:{ legend:{ position:'bottom' } } }
});

/* Notifications polling */
setInterval(()=> {
  fetch('../includes/check_notifications.php?role=medecin')
    .then(r => r.json())
    .then(data => {
      if (data.new && Array.isArray(data.notifications) && data.notifications.length) {
        const zone = document.getElementById('notif');
        const son = document.getElementById('sound');
        zone.textContent = data.notifications.map(n => '• ' + n.message).join('\\n\\n');
        zone.style.display = 'block';
        son.play().catch(()=>{});
        setTimeout(()=> zone.style.display = 'none', 6000);
      }
    }).catch(err => console.error('Notif error:', err));
}, 6000);

/* Timeline live refresh (uses includes/check_timeline_medecin.php) */
setInterval(()=> {
  fetch('../includes/check_timeline_medecin.php')
    .then(r => r.json())
    .then(data => {
      if (data.success && Array.isArray(data.consultations)) {
        const ul = document.getElementById('timelineList');
        ul.innerHTML = '';
        data.consultations.forEach(c => {
          const li = document.createElement('li');
          li.innerHTML = `
            <div>
              <div style="font-weight:700">${c.patient}</div>
              <div style="font-size:13px;color:var(--muted)">${c.date}</div>
            </div>
            <div style="text-align:right">
              <div class="status-dot" style="background:${c.statutColor};color:#fff;border-radius:8px;padding:6px 8px;font-weight:700">${c.statusText}</div>
              <div style="margin-top:8px"><a class="btn blue" href="${c.link}">Voir</a></div>
            </div>
          `;
          ul.appendChild(li);
        });
        if (data.consultations.length === 0) ul.innerHTML = '<li>Aucune consultation assignée récemment.</li>';
      }
    }).catch(err => console.error('Timeline error:', err));
}, 7000);
</script>
</body>
</html>
