<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;




class ProductVariant extends Model
{
    use HasUuid;
    protected $keyType = 'string';
    public $incrementing = false;
        protected $fillable = [
        'image',
        'video_id',
        'price',
        'location',
    ];
    //
}
