<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'synopsis',
        'isbn',
        'writer',
        'page_amount',
        'stock_amount',
        'published',
        'publisher',
        'category',
        'image',
        'status',
    ];

    /**
     * user
     * 
     * @return void
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * book
     * 
     * @return void
     */
    public function borrow()
    {
        return $this->hasMany(Borrow::class);
    }

    /**
     * restore
     * 
     * @return void
     */
    public function restore()
    {
        return $this->hasOne(Restore::class);
    }

    /**
     * image
     * 
     * @return Attribute
     */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($image) => asset('/storage/books/' . $image),
        );
    }
}
