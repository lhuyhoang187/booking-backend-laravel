<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewImage extends Model
{
    protected $table = 'review_images';
    protected $fillable = ['review_id', 'image_url'];

    public function review()
    {
        return $this->belongsTo(Review::class, 'review_id', 'id');
    }
}
