<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;
class Tag extends Model
{
    use HasUuid;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['name'];

    public function videos()
    {
        return $this->belongsToMany(AdVideo::class, 'video_tags', 'tag_id', 'video_id');
    }
}