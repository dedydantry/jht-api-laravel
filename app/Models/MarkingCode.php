<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarkingCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'marking_code_air',
        'marking_code_sea'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
