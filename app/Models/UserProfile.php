<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class UserProfile extends Model
{
    use HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone_number',
        'date_of_birth',
        'gender',
        'profile_picture',
        'bio',
        'country',
        'city',
        'address',
        'points_balance',
        'money_balance',
        'total_earned',
        'payment_methods',
        'favorite_categories',
        'notifications_enabled',
        'email_notifications',
        'total_ads_watched',
        'total_games_played',
        'total_comments',
        'total_shares',
        'is_active',
        'last_active_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'points_balance' => 'decimal:2',
        'money_balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'payment_methods' => 'array',
        'favorite_categories' => 'array',
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
     * Get full name
     */
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Add points to balance
     */
    public function addPoints($amount)
    {
        $this->increment('points_balance', $amount);
    }

    /**
     * Deduct points from balance
     */
    public function deductPoints($amount)
    {
        if ($this->points_balance >= $amount) {
            $this->decrement('points_balance', $amount);
            return true;
        }
        return false;
    }

    /**
     * Add money to balance
     */
    public function addMoney($amount)
    {
        $this->increment('money_balance', $amount);
        $this->increment('total_earned', $amount);
    }

    /**
     * Deduct money from balance
     */
    public function deductMoney($amount)
    {
        if ($this->money_balance >= $amount) {
            $this->decrement('money_balance', $amount);
            return true;
        }
        return false;
    }
}
