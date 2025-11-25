<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class AdVideo extends Model
{
    use HasUuid;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'advertiser_id', 'title', 'description', 'video_url', 'category_id', 'duration'
    ];

    public function advertiser()
    {
        return $this->belongsTo(User::class, 'advertiser_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'video_tags', 'video_id', 'tag_id');
    }

    public function comments()
    {
        return $this->hasMany(AdComment::class, 'ad_id');
    }

    public function views()
    {
        return $this->hasMany(AdView::class, 'ad_id', 'id');
    }
}