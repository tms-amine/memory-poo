<?php
// public/save_score.php
require_once __DIR__ . '/../src/Database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        throw new Exception('JSON invalide');
    }

    $playerName  = isset($data['player_name']) ? trim($data['player_name']) : '';
    $attempts    = isset($data['attempts']) ? (int)$data['attempts'] : 0;
    $timeSeconds = isset($data['time_seconds']) ? (int)$data['time_seconds'] : 0;

    if ($playerName === '') {
        throw new Exception('player_name manquant');
    }

    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) {
        throw new Exception('Connexion DB impossible');
    }

    $query = "INSERT INTO scores (player_name, attempts, time_seconds)
              VALUES (:name, :attempts, :time)";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':name'     => $playerName,
        ':attempts' => $attempts,
        ':time'     => $timeSeconds,
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>