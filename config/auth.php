<?php
session_start();

if (!isset($_SESSION['utilisateur'])) {
    header('Location: ../login.php');
    exit;
}

// Fonction utile pour rediriger selon le rôle
function rediriger_par_role($role) {
    switch ($role) {
        case 'admin':
            header('Location: ../admin/dashboard.php');
            break;
        case 'medecin':
            header('Location: ../medecin/dashboard.php');
            break;
        case 'infirmier':
            header('Location: ../infirmier/dashboard.php');
            break;
        case 'patient':
            header('Location: ../patient/dashboard.php');
            break;
        default:
            header('Location: ../login.php');
            break;
    }
    exit;
}
?>
