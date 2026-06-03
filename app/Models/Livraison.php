<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Livraison extends Model
{
    protected $fillable = [
        'commande_id', 'livreur_id', 'statut_suivi',
        'disponibilite', 'date_livraison_estimee', 'livre_le'
    ];

    protected function casts(): array
    {
        return [
            'disponibilite'          => 'boolean',
            'date_livraison_estimee' => 'datetime',
            'livre_le'               => 'datetime',
        ];
    }

    public function commande()
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }

    public function livreur()
    {
        return $this->belongsTo(User::class, 'livreur_id');
    }
}
