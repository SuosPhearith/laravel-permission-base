<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Auth\Permission;
use App\Models\Auth\PermissionRole;
use App\Models\Auth\Role;
use App\Models\Auth\UserPermission;
use App\Models\Auth\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function hasPermission(string $permissionName): bool
    {
        //::::::::::::::::::::::::::::::::::::: GET USER ROLE IDs
        $roleIds = UserRole::where('user_id', $this->id)->pluck('role_id');

        //::::::::::::::::::::::::::::::::::::: GET PERMISSION IDs FROM ROLES
        $permissionIdsFromRoles = PermissionRole::whereIn('role_id', $roleIds)->pluck('permission_id');

        //::::::::::::::::::::::::::::::::::::: GET PERMISSION IDs FROM USER
        $permissionIdsFromUser = UserPermission::where('user_id', $this->id)->pluck('permission_id');

        //::::::::::::::::::::::::::::::::::::: MERGE AND REMOVE DUPLICATES
        $allPermissionIds = $permissionIdsFromRoles->merge($permissionIdsFromUser)->unique();

        //::::::::::::::::::::::::::::::::::::: CHECK IF ANY MATCHES BY NAME
        return Permission::whereIn('id', $allPermissionIds)
            ->where('name', $permissionName)
            ->exists();
    }


    //::::::::::::::::::::::::::::::::::::::::::::::::::::
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }
}
