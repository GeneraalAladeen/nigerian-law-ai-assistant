<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LawDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'jurisdiction',
        'source',
        'year',
        'file_path',
        'status',
        'chunk_count',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'year' => 'integer',
        'chunk_count' => 'integer',
    ];

    public function chunks(): HasMany
    {
        return $this->hasMany(LawChunk::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
