<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Borrow extends Model
{
    use HasFactory;

    protected $fillable = [
        'borrowing_start',
        'borrowing_end',
        'status',
        'book_id',
        'user_id',
    ];

    /**
     * book
     * 
     * @return void
     */
    public function book() 
    {
        return $this->belongsTo(Book::class);
    }

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
     * restore
     * 
     * @return void
     */
    public function restore() 
    {
        return $this->hasOne(Restore::class);
    }
}
