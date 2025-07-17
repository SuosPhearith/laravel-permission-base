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
        'phone_number',
        'password',
        'role_id',
        'avatar',
        'is_active',
        'enable_2fa',
        'google2fa_secret',
        'two_factor_verified_at',
        'two_factor_key',
        'temp_2fa_secret',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
        'two_factor_key',
        'temp_2fa_secret'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'enable_2fa'         => 'boolean',
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
        $roleIds = UserRole::where('user_id', $this->id)
            ->whereHas('role', function ($query) {
                $query->where('is_active', true)
                    ->whereNull('deleted_at');
            })
            ->pluck('role_id');

        //::::::::::::::::::::::::::::::::::::: GET PERMISSION IDs FROM ROLES
        $permissionIdsFromRoles = PermissionRole::whereIn('role_id', $roleIds)->pluck('permission_id');

        //::::::::::::::::::::::::::::::::::::: GET PERMISSION IDs FROM USER
        $permissionIdsFromUser = UserPermission::where('user_id', $this->id)->pluck('permission_id');

        //::::::::::::::::::::::::::::::::::::: MERGE AND REMOVE DUPLICATES
        $allPermissionIds = $permissionIdsFromRoles->merge($permissionIdsFromUser)->unique();

        //::::::::::::::::::::::::::::::::::::: CHECK BY NAME & ACTIVE FLAGS
        return Permission::whereIn('id', $allPermissionIds)
            ->where('name', $permissionName)
            ->where('is_active', true)
            ->whereHas('module', function ($query) {
                $query->where('is_active', true);
            })
            ->exists();
    }


    //::::::::::::::::::::::::::::::::::::::::::::::::::::
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }
}
