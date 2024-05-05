<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class Restore extends Model
{
    use HasFactory;

    protected $fillable = [
        'returndate',
        'fine',
        'book_id',
        'user_id',
        'borrow_id',
        'status',
    ];

    /**
     * book
     * 
     * @return void
     */
    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    /**
     * user
     * 
     * @return void
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * borrow
     * 
     * @return void
     */
    public function borrow()
    {
        return $this->belongsTo(Borrow::class, 'borrow_id');
    }
}
