<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestBook extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'class',
        'departemen',
        'email',
        'goals',
        'telp',
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
}
