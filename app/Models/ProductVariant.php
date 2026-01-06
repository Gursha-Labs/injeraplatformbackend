<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;




class ProductVariant extends Model
{
        protected $fillable = [
        'image',
        'video_id',
        'price',
        'location',
    ];
    //
}
