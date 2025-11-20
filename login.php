<?php
require_once 'includes/init.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = :email AND statut='actif' LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Créer session avec la police de l'utilisateur
            setCurrentUser([
                'id' => $user['id'],
                'prenom' => $user['prenom'],
                'nom' => $user['nom'],
                'email' => $user['email'],
                'role' => $user['role'],
                'centre_id' => $user['centre_id'],
                'police' => $user['police'] ?? 'Benton Sans' // police par défaut
            ], true);

            // Redirection selon rôle
            switch ($user['role']) {
                case 'admin': header('Location: admin/dashboard.php'); break;
                case 'infirmier': header('Location: infirmier/dashboard.php'); break;
                case 'medecin': header('Location: medecin/dashboard.php'); break;
                case 'patient': header('Location: patient/dashboard.php'); break;
                default: header('Location: login.php'); break;
            }
            exit;
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}

// Police pour affichage dynamique
$font = $_SESSION['utilisateur']['police'] ?? 'Segoe UI';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Télésanté - Connexion</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Exemple d'intégration de police custom Benton Sans -->
    <style>
        @font-face {
            font-family: 'Benton Sans';
            src: url('assets/fonts/Benton_Sans/BentonSans-Book.otf') format('opentype');
            font-weight: normal;
            font-style: normal;
        }
        body {
            font-family: '<?= htmlspecialchars($font) ?>', sans-serif;
            background: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-box {
            background: white;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.1);
            width: 320px;
        }
        h2 {
            margin-bottom: 20px;
            color: #27ae60;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }
        button {
            background-color: #27ae60;
            color: white;
            border: none;
            padding: 12px 0;
            width: 100%;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 600;
        }
        button:hover { background-color: #1e8e4f; }
        .error {
            color: #e74c3c;
            margin-bottom: 15px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Connexion Télésanté</h2>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" action="">
        <input type="email" name="email" placeholder="Email" required autofocus>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button type="submit">Se connecter</button>
    </form>
</div>

</body>
</html>
