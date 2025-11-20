<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// Vérification des rôles
function checkRole(array $roles) {
    if (!isset($_SESSION['utilisateur'])) {
        header('Location: ../login.php');
        exit;
    }
    if (!in_array($_SESSION['utilisateur']['role'], $roles)) {
        header('Location: ../unauthorized.php');
        exit;
    }
}

checkRole(['infirmier', 'medecin']);
$infirmierId = $_SESSION['utilisateur']['id'] ?? null;

$error = '';
$success = '';

// Récupérer les patients actifs
$stmt = $pdo->prepare("SELECT id, nom, prenom FROM patients WHERE statut='actif' ORDER BY nom, prenom");
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Symptômes prédéfinis
$symptomes_predefinis = [
    "Maux de tête",
    "Fièvre",
    "Toux",
    "Fatigue",
    "Maux de ventre",
    "Difficulté à respirer",
    "Douleur thoracique",
    "Perte d’appétit",
    "Nausées",
    "Vertiges",
    "Douleurs musculaires",
    "Frissons",
    "Troubles du sommeil",
    "Diarrhée",
    "Autre"
];

$selectedSymptomes = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = !empty($_POST['patient_id']) ? intval($_POST['patient_id']) : null;
    $tension = trim($_POST['tension'] ?? '');
    $temperature = trim($_POST['temperature'] ?? '');
    $selectedSymptomes = $_POST['symptomes'] ?? [];
    $symptomes_text = is_array($selectedSymptomes) ? implode(', ', $selectedSymptomes) : '';
    $observations = trim($_POST['observations'] ?? '');
    $fichier_nom = null;

    // Création d'un nouveau patient si nécessaire
    if (!$patient_id && !empty($_POST['nom']) && !empty($_POST['prenom'])) {
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $sexe = $_POST['sexe'] ?? null;
        $date_naissance = $_POST['date_naissance'] ?? null;
        $telephone = trim($_POST['telephone'] ?? '');

        if (strlen($nom) < 2 || strlen($prenom) < 2) {
            $error = "Le nom et le prénom doivent contenir au moins 2 caractères.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO patients (nom, prenom, sexe, date_naissance, telephone, statut) VALUES (?, ?, ?, ?, ?, 'actif')");
            $stmt->execute([$nom, $prenom, $sexe, $date_naissance, $telephone]);
            $patient_id = $pdo->lastInsertId();
        }
    }

    // Upload fichier
    if (!$error && isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $fileType = mime_content_type($_FILES['fichier']['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            $error = "Type de fichier non autorisé. Seuls JPG, PNG et PDF sont acceptés.";
        } else {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $fichier_nom = uniqid('upload_', true) . '.' . pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION);
            if (!move_uploaded_file($_FILES['fichier']['tmp_name'], $uploadDir . $fichier_nom)) {
                $error = "Erreur lors de l'upload du fichier.";
            }
        }
    }

    // Insertion consultation
    if (!$error) {
        if ($patient_id && $tension !== '' && $temperature !== '' && $symptomes_text !== '') {
            $stmt = $pdo->prepare("INSERT INTO consultations (patient_id, infirmier_id, tension, temperature, symptomes, observations, fichier, statut) VALUES (?, ?, ?, ?, ?, ?, ?, 'envoyée')");
            $stmt->execute([$patient_id, $infirmierId, $tension, $temperature, $symptomes_text, $observations, $fichier_nom]);
            $success = "✅ Consultation enregistrée avec succès.";
            $selectedSymptomes = [];
        } else {
            $error = "Veuillez remplir tous les champs obligatoires.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Nouvelle consultation</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body { font-family:sans-serif; background:#f7f9fc; padding:20px; display:flex; justify-content:center; }
form { background:#fff; padding:25px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1); width:100%; max-width:700px; }
h2,h3 { color:#2c3e50; margin-bottom:20px; }
label { display:block; margin-top:12px; font-weight:600; }
input, select, textarea { width:100%; padding:10px; border-radius:8px; border:1px solid #ccc; font-size:16px; box-sizing:border-box; resize:vertical; }
select[multiple] { height:160px; }
textarea { min-height:80px; }
button { margin-top:20px; background:#27ae60; color:white; border:none; padding:14px 0; width:100%; border-radius:8px; font-size:18px; font-weight:700; cursor:pointer; }
button:hover { background:#1e8e4f; }
.message { margin-bottom:15px; font-weight:700; font-size:16px; }
.error { color:#e74c3c; } .success { color:#27ae60; }
.btn-return { display:inline-block; margin-top:30px; padding:10px 20px; background:#2980b9; color:white; border-radius:8px; text-decoration:none; font-weight:600; }
.btn-return:hover { background:#1f6391; }
p.separator { text-align:center; margin:30px 0; font-weight:600; color:#555; }
</style>
</head>
<body>

<form method="POST" enctype="multipart/form-data">
<h2>Nouvelle consultation</h2>

<?php if ($error): ?><div class="message error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="message success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<label for="patient_id">Choisir un patient existant :</label>
<select name="patient_id" id="patient_id">
    <option value="">-- Aucun --</option>
    <?php foreach ($patients as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= (isset($patient_id) && $patient_id==$p['id'])?'selected':'' ?>>
            <?= htmlspecialchars($p['nom'].' '.$p['prenom']) ?>
        </option>
    <?php endforeach; ?>
</select>

<p class="separator">-- OU --</p>

<h3>Ajouter un nouveau patient</h3>
<label for="nom">Nom :</label>
<input type="text" name="nom" id="nom" minlength="2">

<label for="prenom">Prénom :</label>
<input type="text" name="prenom" id="prenom" minlength="2">

<label for="sexe">Sexe :</label>
<select name="sexe" id="sexe">
<option value="">-- Sélectionnez --</option>
<option value="M">Masculin</option>
<option value="F">Féminin</option>
</select>

<label for="date_naissance">Date de naissance :</label>
<input type="date" name="date_naissance" id="date_naissance">

<label for="telephone">Téléphone :</label>
<input type="tel" name="telephone" id="telephone" pattern="^\+?[0-9\s\-]{6,15}$" placeholder="+33 6 12 34 56 78">

<h3>Détails de la consultation</h3>

<label for="tension">Tension :</label>
<input type="text" name="tension" id="tension" required>

<label for="temperature">Température (°C) :</label>
<input type="number" step="0.1" name="temperature" id="temperature" required>

<label for="symptomes">Symptômes :</label>
<select name="symptomes[]" id="symptomes" multiple required>
<?php foreach ($symptomes_predefinis as $sym): ?>
<option value="<?= htmlspecialchars($sym) ?>" <?= in_array($sym, $selectedSymptomes)?'selected':'' ?>><?= htmlspecialchars($sym) ?></option>
<?php endforeach; ?>
</select>
<small>(Maintenez <b>Ctrl</b> ou <b>Cmd</b> pour en sélectionner plusieurs)</small>

<label for="observations">Observations :</label>
<textarea name="observations" id="observations"><?= htmlspecialchars($_POST['observations'] ?? '') ?></textarea>

<label for="fichier">Joindre un fichier (JPG, PNG, PDF) :</label>
<input type="file" name="fichier" id="fichier" accept=".jpg,.jpeg,.png,.pdf">

<button type="submit">Enregistrer la consultation</button>
</form>

<a href="/telesante/infirmier/dashboard.php" class="btn-return">← Retour au dashboard</a>
</body>
</html>
