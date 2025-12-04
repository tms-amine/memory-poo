<?php
// public/create_schema.php
require_once __DIR__ . '/../src/Database.php';

header('Content-Type: text/html; charset=utf-8');

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    echo "<!doctype html><html lang='fr'><head><meta charset='utf-8'><title>Erreur</title></head><body>";
    echo "<h2>Erreur</h2>";
    echo "<p>Impossible de se connecter à la base de données.</p>";
    echo "<p><a href='index.php'>Retour au jeu</a></p>";
    echo "</body></html>";
    exit;
}

// Si on arrive ici, la table est déjà créée via ensureSchema
header('Location: leaderboard.php');
exit;
