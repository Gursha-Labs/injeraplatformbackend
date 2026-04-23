<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
        use HasUuid;

    protected $fillable = [
        'endpoint',
        'method',
        'user_id',
        'ip_address',
        'response_status',
        'response_time',

    ];
}
