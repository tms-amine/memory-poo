<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../src/Game.php';
require_once __DIR__ . '/../src/Database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'init') {
        $pairCount  = (int)($_POST['pairs'] ?? 10);
        $playerName = trim($_POST['player_name'] ?? '');

        if ($playerName === '') {
            throw new Exception('Nom du joueur requis');
        }
        if ($pairCount < 6 || $pairCount > 12) {
            throw new Exception('Nombre de paires invalide');
        }

        $_SESSION['player_pseudo'] = $playerName;
        $_SESSION['pair_count'] = $pairCount;

        $game = new Game($pairCount);

        $state = $game->getState();
        echo json_encode([
            'success'    => true,
            'state'      => $state,
            'attempts'   => $state['attempts'] ?? 0,
            'time'       => $game->getElapsedTime(),
            'gameActive' => $state['gameActive'] ?? false,
            'gameWon'    => $game->isGameWon(),
        ]);
    } elseif ($action === 'flip') {
        $cardIndex = (int)($_POST['card_index'] ?? -1);
        $game = new Game();
        $result = $game->flipCard($cardIndex);
        $state = $game->getState();

        echo json_encode([
            'success'    => $result['success'] ?? true,
            'state'      => $state,
            'attempts'   => $state['attempts'] ?? 0,
            'time'       => $game->getElapsedTime(),
            'gameActive' => $state['gameActive'] ?? false,
            'gameWon'    => $game->isGameWon(),
        ]);
    } elseif ($action === 'get_state') {
        $game = new Game();
        $state = $game->getState();

        echo json_encode([
            'success'    => true,
            'state'      => $state,
            'attempts'   => $state['attempts'] ?? 0,
            'time'       => $game->getElapsedTime(),
            'gameActive' => $state['gameActive'] ?? false,
            'gameWon'    => $game->isGameWon(),
        ]);
    } elseif ($action === 'save_score') {
        $game = new Game();
        if (!$game->isGameWon()) {
            throw new Exception('Jeu non terminé');
        }

        $playerName = $_SESSION['player_pseudo'] ?? '';
        if ($playerName === '') {
            throw new Exception('Nom du joueur manquant');
        }

        $db = new Database();
        $conn = $db->getConnection();
        if (!$conn) {
            throw new Exception('Connexion DB impossible');
        }

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

        $game->reset();
        unset($_SESSION['player_pseudo'], $_SESSION['pair_count']);

        echo json_encode([
            'success' => true,
            'message' => 'Score sauvegardé',
        ]);
    } elseif ($action === 'reset') {
        $game = new Game();
        $game->reset();
        unset($_SESSION['player_pseudo'], $_SESSION['pair_count']);

        echo json_encode([
            'success' => true,
            'message' => 'Jeu réinitialisé',
        ]);
    } else {
        throw new Exception('Action inconnue');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ]);
}
