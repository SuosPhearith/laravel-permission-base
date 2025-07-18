<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Config extends Model
{
    use HasFactory, Notifiable;

    protected $table = 'config';

    protected $fillable = [
        'key',
        'value',
        'desctiption'
    ];

    protected function casts(): array
    {
        return [
            'value'         => 'json',
        ];
    }
}
