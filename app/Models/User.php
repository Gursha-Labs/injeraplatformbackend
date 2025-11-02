<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasUuid;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'username',
        'email',
        'password',
        'type',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the user's profile (polymorphic relationship)
     */
    public function profile()
    {
        if ($this->type === 'user') {
            return $this->hasOne(UserProfile::class);
        } elseif ($this->type === 'advertiser') {
            return $this->hasOne(AdvertiserProfile::class);
        }
        return null;
    }

    /**
     * Get user profile
     */
    public function userProfile()
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Get advertiser profile
     */
    public function advertiserProfile()
    {
        return $this->hasOne(AdvertiserProfile::class);
    }

    // Role helpers
    public function isAdmin(): bool
    {
        return $this->type === 'admin';
    }

    public function isAdvertiser(): bool
    {
        return $this->type === 'advertiser';
    }

    public function isUser(): bool
    {
        return $this->type === 'user';
    }

    /**
     * Get the appropriate profile based on user type
     */
    public function getProfileAttribute()
    {
        if ($this->isUser()) {
            return $this->userProfile;
        } elseif ($this->isAdvertiser()) {
            return $this->advertiserProfile;
        }
        return null;
    }
}