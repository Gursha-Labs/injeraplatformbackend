<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $fillable = [
        'endpoint',
        'method',
        'user_id',
        'ip_address',
        'status_code',
        'response_time',

    ];
}
