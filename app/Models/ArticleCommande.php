<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleCommande extends Model
{   
    protected $table = 'articles_commande';
    protected $fillable = ['commande_id', 'produit_id', 'quantite', 'prix_unitaire'];

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public function commande()
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }
}