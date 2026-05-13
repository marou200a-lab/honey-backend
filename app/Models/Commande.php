<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    protected $fillable = ['user_id', 'prix_total', 'statut', 'receipt_name'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function articles()
    {
        return $this->hasMany(ArticleCommande::class, 'commande_id');
    }
}