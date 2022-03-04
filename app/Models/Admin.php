<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use HasFactory, HasApiTokens, HasRoles, HasPermissions;

    protected $table = 'admins';

    public $guard_name = 'admin';

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'sign_at',
    ];

    protected $guard = 'admin';

    /**
     * The attributes that should be hidden for serialization
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast
     *
     * @var array
     */
    protected $casts = [
        'sign_at' => 'datetime'
    ];
}
