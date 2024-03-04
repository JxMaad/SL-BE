<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'nisn',
        'name',
        'gender', 
        'birthplace', 
        'date_of_birth', 
        'phone',
        'address',
        'image',
    ];

    // /**
    //  * member
    //  * 
    //  * @return void
    //  */
    // public function member() 
    // {
    //     return $this->belongsTo(Member::class, 'anggota_id');
    // }
}
