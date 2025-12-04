<?php
// public/leaderboard.php
require_once __DIR__ . '/../src/Database.php';

$db = new Database();
$conn = $db->getConnection();
$rows = [];
$dbError = '';

if ($conn) {
    try {
        $query = "
            SELECT player_name, attempts, time_seconds, created_at
            FROM scores
            ORDER BY time_seconds ASC, attempts ASC
            LIMIT 10
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $dbError = $e->getMessage();
    }
} else {
    $dbError = "Impossible de se connecter à la base de données.";
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Classement - Memory Football</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
<div class="container">
    <div class="nav">
        <a href="index.php"><i>⚽</i> Jeu</a>
        <a href="leaderboard.php" class="active"><i>🏆</i> Classement</a>
        <a href="info.php"><i>ℹ️</i> Info</a>
    </div>

    <h1>Classement - Top 10</h1>

    <?php if (!empty($dbError)): ?>
        <div class="leaderboard">
            <p>Erreur lors de la lecture du classement :
                <strong><?= htmlspecialchars($dbError); ?></strong>
            </p>
            <p>La table <code>scores</code> semble manquer. Vous pouvez la créer automatiquement :</p>
            <form method="post" action="create_schema.php">
                <button type="submit" class="primary">Créer la table scores</button>
            </form>
            <p style="margin-top:12px"><a href="index.php">Retour au jeu</a></p>
        </div>
    <?php else: ?>
        <?php if (empty($rows)): ?>
            <p>Aucun score enregistré pour le moment.</p>
            <p><a href="index.php">Jouer une partie</a></p>
        <?php else: ?>
            <table style="width:100%;background:#fff;color:#000;border-collapse:collapse">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Joueur</th>
                    <th>Temps</th>
                    <th>Tentatives</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $i => $r): ?>
                    <tr style="border-top:1px solid #ccc">
                        <td><?= $i + 1; ?></td>
                        <td><?= htmlspecialchars($r['player_name']); ?></td>
                        <td>
                            <?php
                            $s = (int)$r['time_seconds'];
                            echo intdiv($s, 60) . ':' . str_pad($s % 60, 2, '0', STR_PAD_LEFT);
                            ?>
                        </td>
                        <td><?= (int)$r['attempts']; ?></td>
                        <td><?= htmlspecialchars($r['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top:12px"><a href="index.php">Retour au jeu</a></div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
