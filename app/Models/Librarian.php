<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Librarian extends Model
{
    use HasFactory;

    protected $fillable = [
        'nip',
        'pustakawan_id',
    ];

    /**
     * member
     * 
     * @return void
     */
    public function librarian() 
    {
        return $this->belongsTo(Librarian::class, 'pustakawan_id');
    }
}
