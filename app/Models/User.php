<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'availability', 'api_token', 'api_token_created_at'
    ];

    protected $hidden = [
        'password', 'api_token'
    ];

    protected $casts = [
        'availability' => 'array'
    ];
}
