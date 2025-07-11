<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class UserPermission extends Model
{
    use HasFactory, Notifiable;

    public $timestamps = false;

    protected $table = 'user_permission';

    protected $fillable = [
        'permission_id',
        'user_id',
    ];

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
