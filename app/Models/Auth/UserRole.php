<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class UserRole extends Model
{
    use HasFactory, Notifiable;

    public $timestamps = false;

    protected $table = 'user_role';

    protected $fillable = [
        'role_id',
        'user_id',
    ];
}
