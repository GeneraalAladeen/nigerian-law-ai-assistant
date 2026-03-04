<?php

namespace App\Agents;

use App\Models\LawChunk;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\SimilaritySearch;

#[Provider('openai')]
#[Model('gpt-4o')]
class NigerianLegalAgent implements Agent, Conversational
{
    use Promptable;
    use RemembersConversations;

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
        You are a Nigerian legal assistant specializing in Nigerian law.
        Answer questions ONLY based on the legal context retrieved from the knowledge base.
        Always cite the specific law name and section number when providing answers.
        If the answer cannot be found in the provided context, clearly state that
        the information is not available in the current knowledge base.
        Be concise, accurate, and use plain language that non-lawyers can understand.
        INSTRUCTIONS;
    }

    public function tools(): iterable
    {
        return [
            SimilaritySearch::usingModel(
                model: LawChunk::class,
                column: 'embedding',
                minSimilarity: 0.5,
                limit: 5,
            ),
        ];
    }
}
