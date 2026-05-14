<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Models\UserSubscription;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'duration_days',
        'video_upload_limit',
        'max_video_duration_seconds',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'video_upload_limit' => 'integer',
        'max_video_duration_seconds' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function userSubscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }
}
