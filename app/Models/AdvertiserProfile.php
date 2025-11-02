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
        'industry',
        'business_type',
        'logo',
        'profile_picture',
        'cover_image',
        'description',
        'tagline',
        'country',
        'city',
        'address',
        'postal_code',
        'contact_person_name',
        'contact_person_title',
        'contact_person_phone',
        'contact_person_email',
        'social_media_links',
        'business_license',
        'tax_id',
        'is_verified',
        'verified_at',
        'subscription_plan',
        'subscription_start_date',
        'subscription_end_date',
        'subscription_active',
        'total_ads_uploaded',
        'total_ad_views',
        'total_ad_likes',
        'total_ad_shares',
        'total_spent',
        'notifications_enabled',
        'email_notifications',
        'notification_preferences',
        'is_active',
        'account_status',
        'suspension_reason',
        'last_active_at',
    ];

    protected $casts = [
        'social_media_links' => 'array',
        'notification_preferences' => 'array',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'subscription_start_date' => 'datetime',
        'subscription_end_date' => 'datetime',
        'subscription_active' => 'boolean',
        'total_spent' => 'decimal:2',
        'notifications_enabled' => 'boolean',
        'email_notifications' => 'boolean',
        'is_active' => 'boolean',
        'last_active_at' => 'datetime',
    ];

    /**
     * Get the user that owns the profile
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if subscription is active and not expired
     */
    public function hasActiveSubscription()
    {
        return $this->subscription_active && 
               $this->subscription_end_date && 
               $this->subscription_end_date->isFuture();
    }

    /**
     * Check if advertiser is verified
     */
    public function isVerified()
    {
        return $this->is_verified;
    }

    /**
     * Check if account is active
     */
    public function isAccountActive()
    {
        return $this->account_status === 'active' && $this->is_active;
    }

    /**
     * Increment ad statistics
     */
    public function incrementAdStats($type)
    {
        switch ($type) {
            case 'upload':
                $this->increment('total_ads_uploaded');
                break;
            case 'view':
                $this->increment('total_ad_views');
                break;
            case 'like':
                $this->increment('total_ad_likes');
                break;
            case 'share':
                $this->increment('total_ad_shares');
                break;
        }
    }
}
