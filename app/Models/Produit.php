<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    protected $fillable = [
        'nom', 'description', 'categorie_id',
        'vendeur_id', 'prix', 'quantite_stock',
        'image_url', 'statut',
    ];

    protected function casts(): array
    {
        return [
            'prix'           => 'decimal:2',
            'quantite_stock' => 'integer',
        ];
    }

    public function categorie()
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function vendeur()
    {
        return $this->belongsTo(User::class, 'vendeur_id');
    }
    
    public function avis()
{
    return $this->hasMany(Avis::class, 'produit_id');
}
}