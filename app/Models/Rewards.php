<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Rewards extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'probability',
        'type',
        'value',
        'is_active'
    ];
}
