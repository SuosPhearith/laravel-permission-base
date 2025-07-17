<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Permission extends Model
{
    use HasFactory, Notifiable;

    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'is_active',
        'module_id'
    ];

    protected function casts(): array
    {
        return [
            'is_active'         => 'boolean',
        ];
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
