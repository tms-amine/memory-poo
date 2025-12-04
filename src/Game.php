<?php

class Game {
    private $cards = [];
    private $pairCount = 10;
    private $sessionKey = 'memory_game_state';

    public function __construct($pairCount = 10) {
        $this->pairCount = $pairCount;

        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [
                'cards'        => [],
                'flipped'      => [],
                'matchedCount' => 0,
                'attempts'     => 0,
                'startTime'    => time(),
                'gameActive'   => false,
                'pairCount'    => $pairCount
            ];
        } else {
            $this->pairCount = $_SESSION[$this->sessionKey]['pairCount'] ?? 10;
        }
    }

    /**
     * Crée et mélange un nouveau plateau
     */
    public function createBoard() {
        $pairs = [];
        for ($i = 1; $i <= $this->pairCount; $i++) {
            $pairs[] = $i;
        }

        // Duplique les valeurs et mélange
        $cards = array_merge($pairs, $pairs);
        shuffle($cards);

        $cardObjects = [];
        foreach ($cards as $idx => $value) {
            $cardObjects[] = [
                'id'      => $idx,
                'value'   => $value,
                'flipped' => false,
                'matched' => false
            ];
        }

        $_SESSION[$this->sessionKey] = [
            'cards'        => $cardObjects,
            'flipped'      => [],
            'matchedCount' => 0,
            'attempts'     => 0,
            'startTime'    => time(),
            'gameActive'   => true,
            'pairCount'    => $this->pairCount
        ];

        return $cardObjects;
    }

    public function getState() {
        return $_SESSION[$this->sessionKey] ?? [];
    }

    /**
     * Gestion d'un clic sur une carte
     */
    public function flipCard($cardIndex) {
        $state = $this->getState();

        if (!isset($state['cards'][$cardIndex])) {
            return ['success' => false, 'error' => 'Invalid card index'];
        }

        $card = $state['cards'][$cardIndex];
        if ($card['flipped'] || $card['matched']) {
            return ['success' => false, 'error' => 'Card already revealed'];
        }

        // Si deux cartes précédentes doivent être retournées
        if (isset($state['waitingFlip'])) {
            $state['cards'][$state['waitingFlip'][0]]['flipped'] = false;
            $state['cards'][$state['waitingFlip'][1]]['flipped'] = false;
            $state['flipped'] = [];
            unset($state['waitingFlip']);
        }

        // On flip la nouvelle carte
        $state['cards'][$cardIndex]['flipped'] = true;
        $state['flipped'][] = $cardIndex;

        // Deux cartes retournées ?
        if (count($state['flipped']) === 2) {
            $idx1  = $state['flipped'][0];
            $idx2  = $state['flipped'][1];
            $card1 = $state['cards'][$idx1];
            $card2 = $state['cards'][$idx2];

            $state['attempts']++;

            if ($card1['value'] === $card2['value']) {
                // Paire trouvée
                $state['cards'][$idx1]['matched'] = true;
                $state['cards'][$idx2]['matched'] = true;
                $state['matchedCount']++;
                $state['flipped'] = [];

                $pairCount = $state['pairCount'] ?? 10;
                if ($state['matchedCount'] === $pairCount) {
                    $state['gameActive'] = false;
                }
            } else {
                // Pas de match -> à retourner au prochain clic
                $state['waitingFlip'] = [$idx1, $idx2];
            }
        }

        $_SESSION[$this->sessionKey] = $state;
        return ['success' => true, 'state' => $state];
    }

    public function getElapsedTime() {
        $state = $this->getState();
        if (empty($state['startTime'])) {
            return 0;
        }
        return time() - $state['startTime'];
    }

    public function formatTime($seconds) {
        $minutes = floor($seconds / 60);
        $secs    = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $secs);
    }

    public function isGameWon() {
        $state     = $this->getState();
        $pairCount = $state['pairCount'] ?? $this->pairCount;
        return !($state['gameActive'] ?? false)
            && ($state['matchedCount'] ?? 0) === $pairCount
            && !empty($state['cards']);
    }

    public function reset() {
        unset($_SESSION[$this->sessionKey]);
    }
}
