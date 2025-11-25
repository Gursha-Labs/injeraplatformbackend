<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdView extends Model
{
    use HasUuids;

    protected $fillable = [
        'ad_id',
        'user_id',
        'watched_percentage',
        'rewarded',
        'viewed_at'
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'rewarded' => 'boolean',
    ];

    // Relationships
    public function ad(): BelongsTo
    {
        return $this->belongsTo(AdVideo::class, 'ad_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}