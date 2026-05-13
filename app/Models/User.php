<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'telephone',
        'role',
        'est_actif',
        'email_verifie_le',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'est_actif'        => 'boolean',
            'email_verifie_le' => 'datetime',
            
        ];
    }

   
}