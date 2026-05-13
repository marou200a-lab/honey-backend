<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticlePanier extends Model
{   
    protected $table = 'articles_panier';
    protected $fillable = ['panier_id', 'produit_id', 'quantite', 'prix_unitaire'];

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public function panier()
    {
        return $this->belongsTo(Panier::class, 'panier_id');
    }
}