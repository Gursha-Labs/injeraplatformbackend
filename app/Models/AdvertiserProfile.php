<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class AdvertiserProfile extends Model
{
    use HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'company_name',
        'business_email',
        'phone_number',
        'website',
        'logo',
        'profile_picture',
        'cover_image',
        'description',
        'country',
        'city',
        'address',
        'social_media_links',
        'total_ads_uploaded',
        'total_ad_views',
        'total_spent',
        'subscription_plan',
        'subscription_active',
        'notifications_enabled',
        'email_notifications',
        'is_active',
        'last_active_at',
    ];

    protected $casts = [
        'social_media_links' => 'array',
        'total_spent' => 'decimal:2',
        'subscription_active' => 'boolean',
        'notifications_enabled' => 'boolean',
        'email_notifications' => 'boolean',
        'is_active' => 'boolean',
        'last_active_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function incrementAdStats($type)
    {
        match ($type) {
            'upload' => $this->increment('total_ads_uploaded'),
            'view'   => $this->increment('total_ad_views'),
        };
    }
}