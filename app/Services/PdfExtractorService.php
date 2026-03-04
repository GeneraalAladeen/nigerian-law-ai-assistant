<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class PdfExtractorService
{
    public function __construct(private Parser $parser) {}

    public function extract(string $filePath): string
    {
        $pdf = $this->parser->parseFile($filePath);

        return $pdf->getText();
    }
}
