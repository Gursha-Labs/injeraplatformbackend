<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class AdComment extends Model
{
    use HasUuid;
    
    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $table = 'ad_comments';
    
    protected $fillable = ['ad_id', 'user_id', 'comment'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function adVideo()
    {
        return $this->belongsTo(AdVideo::class, 'ad_id');
    }
}