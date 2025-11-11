<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdCommentReply extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'ad_comment_id', 'advertiser_id', 'reply'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function comment() { return $this->belongsTo(AdComment::class, 'ad_comment_id'); }
    public function advertiser() { return $this->belongsTo(User::class, 'advertiser_id'); }
}