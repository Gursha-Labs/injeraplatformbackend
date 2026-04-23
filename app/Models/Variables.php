<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Variables extends Model
{
    use HasUuid;
    protected $fillable = [
        'point',
        'type',
        'value'

    ];
}
