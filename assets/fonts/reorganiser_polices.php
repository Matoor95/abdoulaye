<?php
$fontDir = __DIR__ . '/Benton_Sans';

if (!is_dir($fontDir)) {
    die("Le dossier $fontDir n'existe pas.\n");
}

// Fonction pour déplacer tous les fichiers d'un dossier vers le dossier parent
function moveFilesToParent($dir) {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_file($path)) {
            $newPath = dirname($dir) . '/' . $item;
            if (!file_exists($newPath)) {
                rename($path, $newPath);
                echo "✅ Déplacé : $item\n";
            } else {
                echo "⚠️ Fichier déjà existant : $item\n";
            }
        } elseif (is_dir($path)) {
            // Déplacer récursivement les fichiers du sous-dossier
            moveFilesToParent($path);
            // Tenter de supprimer le sous-dossier
            @rmdir($path);
            echo "🗑 Supprimé dossier vide : $item\n";
        }
    }
}

// Parcourir le dossier Benton_Sans
$items = scandir($fontDir);
foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $path = $fontDir . '/' . $item;
    if (is_dir($path)) {
        moveFilesToParent($path);
        @rmdir($path);
        echo "🗑 Tentative suppression dossier : $item\n";
    }
}

echo "\n📂 Contenu final du dossier Benton_Sans :\n";
$finalItems = scandir($fontDir);
foreach ($finalItems as $f) {
    if ($f === '.' || $f === '..') continue;
    echo "- $f\n";
}

echo "\n✅ Réorganisation terminée.\n";
