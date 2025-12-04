<?php

class Card {
    private $id;
    private $value;
    private $isMatched;
    private $isFlipped;

    public function __construct($id, $value) {
        $this->id = $id;
        $this->value = $value;
        $this->isMatched = false;
        $this->isFlipped = false;
    }

    public function getId() {
        return $this->id;
    }

    public function getValue() {
        return $this->value;
    }

    public function isMatched() {
        return $this->isMatched;
    }

    public function setMatched($matched) {
        $this->isMatched = $matched;
    }

    public function isFlipped() {
        return $this->isFlipped;
    }

    public function flip() {
        $this->isFlipped = !$this->isFlipped;
    }
}
