<?php
// Inclure la connexion
require_once __DIR__ . '/config/db.php';

// Vérifier si la table a bien la colonne statut, sinon l'ajouter
try {
    $pdo->exec("ALTER TABLE utilisateurs ADD statut TINYINT(1) DEFAULT 1");
} catch (Exception $e) {
    // On ignore l'erreur si la colonne existe déjà
}

// Préparer l'admin par défaut
$nom = "Admin";
$prenom = "Principal";
$email = "admin@telesante.com";
$mot_de_passe = password_hash("admin123", PASSWORD_DEFAULT); // mot de passe sécurisé
$role = "admin";
$statut = 1;

try {
    // Vérifier si l'admin existe déjà
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $existe = $stmt->fetch();

    if ($existe) {
        echo "✅ L'administrateur existe déjà avec l'email: $email";
    } else {
        $sql = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, statut) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nom, $prenom, $email, $mot_de_passe, $role, $statut]);

        echo "🎉 Administrateur ajouté avec succès !<br>";
        echo "👉 Email: $email<br>";
        echo "👉 Mot de passe: admin123";
    }
} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>
