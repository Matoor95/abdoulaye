<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connexion BDD
require_once __DIR__ . '/../config/db.php';

// Définir fonctions globales UNE SEULE FOIS
if (!function_exists('checkRole')) {
    function checkRole(array $roles) {
        if (!isset($_SESSION['utilisateur'])) {
            header('Location: ../login.php');
            exit;
        }
        $utilisateur = $_SESSION['utilisateur'];
        if (!in_array($utilisateur['role'], $roles)) {
            header('Location: ../unauthorized.php');
            exit;
        }
    }
}

if (!function_exists('setCurrentUser')) {
    function setCurrentUser(array $user, bool $regenerate = false) {
        if ($regenerate) {
            session_regenerate_id(true);
        }
        $_SESSION['utilisateur'] = $user;
    }
}

if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        return $_SESSION['utilisateur'] ?? null;
    }
}

if (!function_exists('logout')) {
    function logout() {
        session_destroy();
        header('Location: ../login.php');
        exit;
    }
}
