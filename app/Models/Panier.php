<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Panier extends Model
{
    protected $fillable = ['user_id', 'prix_total'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function articles()
    {
        return $this->hasMany(ArticlePanier::class, 'panier_id');
    }
}