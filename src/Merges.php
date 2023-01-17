<?php

namespace Gioni06\Gpt3Tokenizer;
class Merges {
    private array $merges;

    public function __construct(string $path = __DIR__ . '/pretrained_vocab_files/merges.txt')
    {
        $this->merges = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    public function lines()
    {
        return $this->merges;
    }
}
