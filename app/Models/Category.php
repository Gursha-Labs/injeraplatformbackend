<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class Category extends Model
{
    use HasUuid;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['name'];

    public function ads()
    {
        return $this->hasMany(AdVideo::class);
    }
}