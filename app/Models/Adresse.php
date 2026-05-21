<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adresse extends Model
{
    protected $fillable = [
        'user_id', 'rue', 'ville', 
        'code_postal', 'est_par_defaut'
    ];

    protected function casts(): array
    {
        return [
            'est_par_defaut' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}