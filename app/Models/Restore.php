<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restore extends Model
{
    use HasFactory;

    protected $fillable = [
        'returndate',
        'status',
        'fine',
        'book_id',
        'user_id',
        'borrow_id',
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
     * borrow
     * 
     * @return void
     */
    public function borrow() 
    {
        return $this->belongsTo(Borrow::class);
    }
}
