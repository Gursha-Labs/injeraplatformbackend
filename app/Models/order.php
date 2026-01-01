<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'video_id',
        'quantity',
        'total_price',
    ];

    public function adVideo()
    {
        return $this->belongsTo(AdVideo::class, 'video_id');
    }
}
