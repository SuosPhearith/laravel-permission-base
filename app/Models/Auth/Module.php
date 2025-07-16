<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Module extends Model
{
    use HasFactory, Notifiable;

    protected $table = 'modules';

    protected $fillable = [
        'name',
        'is_active'
    ];

    protected function casts(): array
    {
        return [
            'is_active'         => 'boolean',
        ];
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}
