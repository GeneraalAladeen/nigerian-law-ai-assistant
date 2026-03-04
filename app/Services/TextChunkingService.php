<?php

namespace App\Services;

class TextChunkingService
{
    private const CHUNK_SIZE = 500;
    private const OVERLAP = 100;

    /**
     * Split text into overlapping chunks by approximate token count.
     * Uses word count as a proxy for tokens (~1 token per word).
     *
     * @return array<int, string>
     */
    public function chunk(string $text): array
    {
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);

        if (empty($words)) {
            return [];
        }

        $chunks = [];
        $totalWords = count($words);
        $step = self::CHUNK_SIZE - self::OVERLAP;
        $i = 0;

        while ($i < $totalWords) {
            $slice = array_slice($words, $i, self::CHUNK_SIZE);
            $chunks[] = implode(' ', $slice);
            $i += $step;
        }

        return $chunks;
    }
}
