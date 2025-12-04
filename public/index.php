<?php
// public/index.php
session_start();

require_once __DIR__ . '/../src/Game.php';
require_once __DIR__ . '/../src/Database.php';

$error = '';
$game = null;
$gameState = [];
$gameActive = false;
$gameWon = false;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'start') {
        $pairCount = (int)($_POST['pairs'] ?? 10);
        $pseudo = trim($_POST['player_pseudo'] ?? '');

        if ($pairCount < 6 || $pairCount > 12) {
            $error = 'Nombre de paires invalide.';
        } elseif ($pseudo === '') {
            $error = 'Entrez votre pseudo pour être dans le classement.';
        } else {
            $_SESSION['player_pseudo'] = $pseudo;
            $_SESSION['pair_count'] = $pairCount;

            $game = new Game($pairCount);
            $game->createBoard();
        }
    } elseif ($action === 'flip') {
        $game = new Game(); 
        $cardIndex = (int)($_POST['card_index'] ?? -1);

        if ($cardIndex >= 0) {
            $game->flipCard($cardIndex);
        }
    } elseif ($action === 'save_score') {
        $game = new Game();
        if ($game->isGameWon()) {
            $playerName = $_SESSION['player_pseudo'] ?? '';
            if ($playerName !== '') {
                $db = new Database();
                $conn = $db->getConnection();

                if ($conn) {
                    $state = $game->getState();
                    $attempts = $state['attempts'] ?? 0;
                    $timeSeconds = $game->getElapsedTime();

                    $query = "INSERT INTO scores (player_name, attempts, time_seconds)
                              VALUES (:name, :attempts, :time)";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([
                        ':name'     => $playerName,
                        ':attempts' => $attempts,
                        ':time'     => $timeSeconds,
                    ]);
                }

                $game->reset();
                $_SESSION['save_success'] = true;
                unset($_SESSION['player_pseudo'], $_SESSION['pair_count']);

                header('Location: leaderboard.php');
                exit;
            }
        }
    } elseif ($action === 'new_game') {
        $game = new Game(); //  reset()
        $game->reset();
        unset($_SESSION['player_pseudo'], $_SESSION['pair_count']);
        header('Location: index.php');
        exit;
    }
}


if ($game === null) {
    $pairCount = $_SESSION['pair_count'] ?? null;
    $game = new Game($pairCount);
}

// Récupération de l’état
$gameState = $game->getState();
$gameActive = $gameState['gameActive'] ?? false;
$gameWon = $game->isGameWon();

// Récupération du classement (top 10)
try {
    $db = new Database();
    $conn = $db->getConnection();
    $leaders = [];

    if ($conn) {
        $query = "SELECT player_name, attempts, time_seconds
                  FROM scores
                  ORDER BY attempts ASC, time_seconds ASC
                  LIMIT 10";
        $stmt = $conn->query($query);
        $leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $leaders = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Memory Football</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
<div class="container">

    <div class="nav">
        <a href="index.php" class="active"><i>⚽</i> Jeu</a>
        <a href="leaderboard.php"><i>🏆</i> Classement</a>
        <a href="info.php"><i>ℹ️</i> Info</a>
    </div>

    <div class="header">
        <h1>Memory Football ⚽</h1>

        <?php if (!$gameActive && !$gameWon): ?>
            <?php if ($error): ?>
                <div style="color:#d32f2f;background:#ffebee;padding:10px;border-radius:6px;margin:10px 0;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="controls">
                <input
                    name="player_pseudo"
                    type="text"
                    placeholder="Votre pseudo"
                    title="Votre pseudo pour le classement"
                    required
                    value="<?= htmlspecialchars($_SESSION['player_pseudo'] ?? '') ?>"
                >
                <label for="pairs">Paires:</label>
                <select name="pairs" id="pairs">
                    <?php
                    $currentPairs = $_SESSION['pair_count'] ?? 10;
                    foreach ([6, 8, 10, 12] as $p) {
                        $selected = ($p == $currentPairs) ? 'selected' : '';
                        echo "<option value='{$p}' {$selected}>{$p}</option>";
                    }
                    ?>
                </select>
                <button type="submit" name="action" value="start" class="primary">Démarrer</button>
            </form>
        <?php else: ?>
            <div style="color:#666;font-size:14px;margin-top:10px;">
                Pseudo :
                <strong><?= htmlspecialchars($_SESSION['player_pseudo'] ?? '') ?></strong>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($gameActive || $gameWon): ?>
        <div class="stats">
            <div>Temps :
                <span id="timer"><?= $game->formatTime($game->getElapsedTime()); ?></span>
            </div>
            <div>Tentatives :
                <span id="attempts"><?= $gameState['attempts'] ?? 0; ?></span>
            </div>
        </div>

        <?php if ($gameActive): ?>
            <div class="board">
                <?php foreach ($gameState['cards'] as $idx => $card): ?>
                    <form method="POST" style="display:inline;">
                        <button
                            type="submit"
                            name="action"
                            value="flip"
                            class="card <?= $card['matched'] ? 'matched' : '' ?> <?= $card['flipped'] ? 'flipped' : '' ?>"
                            <?= $card['matched'] ? 'disabled' : '' ?>
                        >
                            <div class="inner">
                                <?= ($card['flipped'] || $card['matched']) ? htmlspecialchars($card['value']) : '⚽'; ?>
                            </div>
                        </button>
                        <input type="hidden" name="card_index" value="<?= (int)$idx; ?>">
                    </form>
                <?php endforeach; ?>
            </div>
            <!-- rafraîchissement léger pour le timer -->
            <meta http-equiv="refresh" content="1">
        <?php endif; ?>

        <?php if ($gameWon): ?>
            <div class="board">
                <?php foreach ($gameState['cards'] as $card): ?>
                    <button class="card matched" disabled>
                        <div class="inner"><?= htmlspecialchars($card['value']); ?></div>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="footer">
                <div style="font-weight:800;margin-bottom:10px;">🎉 Partie terminée !</div>
                <div>Temps : <?= $game->formatTime($game->getElapsedTime()); ?></div>
                <div>Tentatives : <?= $gameState['attempts'] ?? 0; ?></div>

                <div style="margin-top:15px;">
                    <form method="POST" style="display:inline;">
                        <button type="submit" name="action" value="save_score" class="primary">
                            Sauvegarder et voir le classement
                        </button>
                    </form>
                    <form method="POST" style="display:inline;margin-left:10px;">
                        <button type="submit" name="action" value="new_game" class="primary" style="background:#999;">
                            Nouveau jeu
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="leaderboard" id="leaderboard">
        <h3>Classement (Top 10)</h3>
        <div id="leaders">
            <?php if (!empty($leaders)): ?>
                <table style="width:100%;border-collapse:collapse;">
                    <tr style="border-bottom:2px solid #ccc;">
                        <th style="padding:8px;text-align:left;">Rang</th>
                        <th style="padding:8px;text-align:left;">Joueur</th>
                        <th style="padding:8px;text-align:center;">Tentatives</th>
                        <th style="padding:8px;text-align:center;">Temps</th>
                    </tr>
                    <?php foreach ($leaders as $rank => $leader): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:8px;"><?= $rank + 1; ?></td>
                            <td style="padding:8px;"><?= htmlspecialchars($leader['player_name']); ?></td>
                            <td style="padding:8px;text-align:center;"><?= (int)$leader['attempts']; ?></td>
                            <td style="padding:8px;text-align:center;">
                                <?php
                                $s = (int)$leader['time_seconds'];
                                echo sprintf('%d:%02d', intdiv($s, 60), $s % 60);
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>Aucun score enregistré. Soyez le premier !</p>
            <?php endif; ?>
        </div>
    </div>

</div>
</body>
</html>
<?php