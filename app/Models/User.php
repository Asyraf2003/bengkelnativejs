<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'username',
        'password_hash',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Laravel Auth akan membaca password dari sini.
     * Kita tetap menyimpan hash di kolom password_hash sesuai blueprint.
     */
    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }
}
