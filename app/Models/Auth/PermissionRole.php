<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class PermissionRole extends Model
{
    use HasFactory, Notifiable;

    public $timestamps = false;

    protected $table = 'permission_role';

    protected $fillable = [
        'permission_id',
        'role_id',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'permission_role',
            'permission_id',
            'role_id'
        );
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
