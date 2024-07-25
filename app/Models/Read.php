<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Read extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'writer',
        'page_amount',
        'published',
        'publisher',
        'category',
        'image',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * image
     * 
     * @return Attribute
     */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($image) => asset('/storage/readbook/' . $image),
        );
    }
}
