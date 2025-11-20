<?php
require_once '../config/db.php';
require_once __DIR__ . '/../includes/notifications.php';
if(session_status()===PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if(!isset($_SESSION['utilisateur']['role']) || $_SESSION['utilisateur']['role']!=='medecin'){
    echo json_encode(['errors'=>['Accès non autorisé']]);
    exit;
}

$medecin_id = $_SESSION['utilisateur']['id'];
$consultation_id = intval($_POST['consultation_id'] ?? 0);
$diagnostic = trim($_POST['diagnostic'] ?? '');
$ordonnance = trim($_POST['ordonnance'] ?? '');
$fichierOrdonnance = null;

$errors = [];

if(!$consultation_id || $diagnostic===''){
    $errors[] = "Tous les champs obligatoires doivent être remplis.";
}

// Vérifie consultation
$stmt = $pdo->prepare("SELECT * FROM consultations WHERE id=? AND medecin_id=?");
$stmt->execute([$consultation_id,$medecin_id]);
$consultation = $stmt->fetch();
if(!$consultation){
    $errors[] = "Consultation introuvable.";
}

// Upload fichier
if(isset($_FILES['fichier_ordonnance']) && $_FILES['fichier_ordonnance']['error']===UPLOAD_ERR_OK){
    $allowed=['pdf','jpg','jpeg','png'];
    $fileName = $_FILES['fichier_ordonnance']['name'];
    $fileTmp = $_FILES['fichier_ordonnance']['tmp_name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if(in_array($ext,$allowed)){
        $newFileName='ordonnance_'.$consultation_id.'_'.time().'.'.$ext;
        if(!move_uploaded_file($fileTmp,"../uploads/$newFileName")){
            $errors[]="Erreur upload fichier.";
        } else $fichierOrdonnance = $newFileName;
    } else $errors[]="Type de fichier non autorisé (pdf,jpg,png).";
}

if($errors){
    echo json_encode(['errors'=>$errors]);
    exit;
}

// Insert réponse
$stmtInsert=$pdo->prepare("
    INSERT INTO reponses_medicales (consultation_id, medecin_id, diagnostic, ordonnance, fichier_ordonnance)
    VALUES (?,?,?,?,?)
");
$stmtInsert->execute([$consultation_id,$medecin_id,$diagnostic,$ordonnance,$fichierOrdonnance]);

// Met à jour consultation
$stmtUpdate=$pdo->prepare("UPDATE consultations SET statut='traitée' WHERE id=?");
$stmtUpdate->execute([$consultation_id]);

// Notification à l'infirmier
$infirmier_id = $consultation['infirmier_id'];
$msg = "💊 Réponse du médecin pour la consultation #{$consultation_id}.";
ajouterNotification($pdo,$infirmier_id,$msg);

echo json_encode(['success'=>"✅ Consultation traitée avec succès."]);
