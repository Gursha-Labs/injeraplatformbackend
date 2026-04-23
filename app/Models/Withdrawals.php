<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Withdrawals extends Model
{
    use HasUuid;

    protected $table = 'withdrawals';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'wallet_id',
        'withdrawal_reference',
        'amount',
        'currency',
        'witdrawal_method',
        'account_number',
        'account_name',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'processor_reference',
        'processor_notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
