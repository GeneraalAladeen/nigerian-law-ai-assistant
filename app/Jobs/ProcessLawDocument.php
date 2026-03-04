<?php

namespace App\Jobs;

use App\Models\LawChunk;
use App\Models\LawDocument;
use App\Services\PdfExtractorService;
use App\Services\TextChunkingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Embeddings;

class ProcessLawDocument implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $lawDocumentId)
    {
        $this->onQueue('law-ingestion');
    }

    public function handle(PdfExtractorService $extractor, TextChunkingService $chunker): void
    {
        $document = LawDocument::findOrFail($this->lawDocumentId);

        $document->update(['status' => 'processing']);

        try {
            $fullPath = Storage::path($document->file_path);
            $text = $extractor->extract($fullPath);
            $chunks = $chunker->chunk($text);

            if (empty($chunks)) {
                $document->update(['status' => 'failed']);

                return;
            }

            $response = Embeddings::for($chunks)
                ->generate('openai', 'text-embedding-3-small');  // Using OpenAI embeddings

            $rows = [];
            foreach ($chunks as $index => $chunk) {
                $rows[] = [
                    'law_document_id' => $document->id,
                    'chunk_index' => $index,
                    'content' => $chunk,
                    'embedding' => json_encode($response->embeddings[$index]),
                    'metadata' => json_encode(['chunk_index' => $index]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            LawChunk::insert($rows);

            $document->update([
                'status' => 'completed',
                'chunk_count' => count($chunks),
                'processed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to process law document {$document->id}: {$e->getMessage()}");
            $document->update(['status' => 'failed']);

            throw $e;
        }
    }
}
