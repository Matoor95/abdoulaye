<?php
// includes/header.php
ob_start(); // Évite "headers already sent"

// Chargement des dépendances
require_once __DIR__ . '/init.php';

// Sécurité : redirection si non connecté
if (!isset($_SESSION['utilisateur'])) {
    header("Location: ../login.php");
    exit;
}

// Définition de la fonction checkRole si absente
if (!function_exists('checkRole')) {
    function checkRole(array $roles) {
        if (!isset($_SESSION['utilisateur'])) {
            header('Location: ../login.php');
            exit;
        }
        $u = $_SESSION['utilisateur'];
        if (!in_array($u['role'], $roles)) {
            header('Location: ../unauthorized.php');
            exit;
        }
    }
}

$user_id = $_SESSION['utilisateur']['id'] ?? null;

// ---- Gestion du changement de police ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['police'])) {
    $nouvelle_police = trim($_POST['police']);
    $stmt = $pdo->prepare("UPDATE utilisateurs SET police = ? WHERE id = ?");
    $stmt->execute([$nouvelle_police, $user_id]);
    $_SESSION['utilisateur']['police'] = $nouvelle_police;
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$policeActuelle = $_SESSION['utilisateur']['police'] ?? 'Segoe UI';

// Liste des polices disponibles
$polices = [
    "Segoe UI"           => "Segoe UI (par défaut)",
    "BentonSans-Book"    => "Benton Sans Book",
    "BentonSans-Bold"    => "Benton Sans Bold",
    "BentonSans-Black"   => "Benton Sans Black",
    "Inter"              => "Inter (Google)"
];

$fontDir = '../assets/fonts/Benton_Sans/';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Télédispensaire</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
/* --- FONTS --- */
<?php if (file_exists(__DIR__ . '/../assets/fonts/Benton_Sans/BentonSans-Book.otf')): ?>
@font-face { font-family: 'BentonSans-Book'; src: url('<?= $fontDir ?>BentonSans-Book.otf') format('opentype'); }
<?php endif; ?>
<?php if (file_exists(__DIR__ . '/../assets/fonts/Benton_Sans/BentonSans-Bold.otf')): ?>
@font-face { font-family: 'BentonSans-Bold'; src: url('<?= $fontDir ?>BentonSans-Bold.otf') format('opentype'); }
<?php endif; ?>
<?php if (file_exists(__DIR__ . '/../assets/fonts/Benton_Sans/BentonSans-Black.otf')): ?>
@font-face { font-family: 'BentonSans-Black'; src: url('<?= $fontDir ?>BentonSans-Black.otf') format('opentype'); }
<?php endif; ?>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

body {
    font-family: <?= json_encode($policeActuelle) ?>, 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    margin:0;
    padding:0;
    background:#f5f7fa;
}

/* --- HEADER / NAV --- */
header { background-color:#2ecc71; color:white; padding:20px; text-align:center; }
nav { background-color:#27ae60; padding:10px; text-align:center; }
nav a { color:white; text-decoration:none; margin:0 15px; font-weight:bold; }
nav a:hover { text-decoration:underline; }
main { padding:20px; }
footer { background-color:#b0b832; color:white; text-align:center; padding:15px; margin-top:30px; }
select { padding:5px; border-radius:5px; border:1px solid #ccc; }

/* --- RESPONSIVE GLOBAL --- */
@media (max-width:768px) {
    body { font-size:14px; }
    nav { text-align:center; }
    main { padding:15px; }
    table, tbody, tr, td, th { display:block; width:100%; }
    tr { margin-bottom:15px; border-bottom:1px solid #ccc; }
    td, th { text-align:right; padding-left:50%; position:relative; }
    td::before, th::before { position:absolute; left:10px; width:45%; white-space:nowrap; font-weight:bold; }
}

/* --- CARDS / SIDEBAR --- */
.card { background:white; padding:20px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1); margin-bottom:20px; }
.cards { display:flex; flex-wrap:wrap; gap:20px; }
.card h3 { margin-bottom:10px; font-size:18px; }

/* --- DARK MODE --- */
body.dark { background:#1c1c1c; color:#e0e0e0; }
body.dark header, body.dark nav, body.dark footer { background:#2c2c2c; }
body.dark .card { background:#2c2c2c; box-shadow:0 6px 20px rgba(0,0,0,0.4); }
</style>
</head>
<body>

<header>
    <h1>Télédispensaire - Plateforme médicale</h1>
</header>

<nav>
    <a href="../logout.php">Déconnexion</a>
</nav>

<main>
<!-- Sélecteur de police -->
<form method="POST" id="police-form" style="margin-bottom:15px;">
    <label for="police">🖋️ Police du texte :</label>
    <select name="police" id="police" onchange="this.form.submit()">
        <?php foreach ($polices as $key => $label): ?>
            <option value="<?= htmlspecialchars($key) ?>" <?= ($policeActuelle === $key) ? 'selected' : '' ?>>
                <?= htmlspecialchars($label) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>
