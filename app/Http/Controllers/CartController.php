<?php

namespace App\Http\Controllers;

use App\Models\Panier;
use App\Models\ArticlePanier;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    // ========== VOIR LE PANIER ==========
    public function index(Request $request)
    {
        $panier = Panier::with(['articles.produit'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$panier) {
            return response()->json([
                'status' => 'success',
                'data'   => [
                    'articles'   => [],
                    'prix_total' => 0
                ]
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $panier
        ]);
    }

    // ========== AJOUTER UN PRODUIT ==========
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'produit_id' => 'required|integer|exists:produits,id',
            'quantite'   => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $produit = Produit::where('id', $request->produit_id)
            ->where('statut', 'actif')
            ->first();

        if (!$produit) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Produit introuvable ou non disponible.'
            ], 404);
        }

        // Vérifier stock
        if ($produit->quantite_stock < $request->quantite) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Stock insuffisant. Stock disponible : ' . $produit->quantite_stock
            ], 400);
        }

        // Récupérer ou créer le panier
        $panier = Panier::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['prix_total' => 0]
        );

        // Vérifier si le produit est déjà dans le panier
        $article = ArticlePanier::where('panier_id', $panier->id)
            ->where('produit_id', $request->produit_id)
            ->first();

        if ($article) {
            // Mettre à jour la quantité
            $article->quantite += $request->quantite;
            $article->save();
        } else {
            // Ajouter nouvel article
            ArticlePanier::create([
                'panier_id'    => $panier->id,
                'produit_id'   => $request->produit_id,
                'quantite'     => $request->quantite,
                'prix_unitaire' => $produit->prix,
            ]);
        }

        // Recalculer le prix total
        $this->recalculerTotal($panier);

        return response()->json([
            'status'  => 'success',
            'message' => 'Produit ajouté au panier.',
            'data'    => $panier->load('articles.produit')
        ]);
    }

    // ========== MODIFIER QUANTITÉ ==========
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'article_id' => 'required|integer|exists:articles_panier,id',
            'quantite'   => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $panier = Panier::where('user_id', $request->user()->id)->first();

        if (!$panier) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Panier introuvable.'
            ], 404);
        }

        $article = ArticlePanier::where('id', $request->article_id)
            ->where('panier_id', $panier->id)
            ->first();

        if (!$article) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Article introuvable dans votre panier.'
            ], 404);
        }

        // Vérifier stock
        if ($article->produit->quantite_stock < $request->quantite) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Stock insuffisant. Stock disponible : ' . $article->produit->quantite_stock
            ], 400);
        }

        $article->quantite = $request->quantite;
        $article->save();

        // Recalculer le prix total
        $this->recalculerTotal($panier);

        return response()->json([
            'status'  => 'success',
            'message' => 'Quantité mise à jour.',
            'data'    => $panier->load('articles.produit')
        ]);
    }

    // ========== SUPPRIMER UN ARTICLE ==========
    public function remove(Request $request, $id)
    {
        $panier = Panier::where('user_id', $request->user()->id)->first();

        if (!$panier) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Panier introuvable.'
            ], 404);
        }

        $article = ArticlePanier::where('id', $id)
            ->where('panier_id', $panier->id)
            ->first();

        if (!$article) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Article introuvable dans votre panier.'
            ], 404);
        }

        $article->delete();

        // Recalculer le prix total
        $this->recalculerTotal($panier);

        return response()->json([
            'status'  => 'success',
            'message' => 'Article supprimé du panier.',
            'data'    => $panier->load('articles.produit')
        ]);
    }

    // ========== RECALCULER LE TOTAL ==========
    private function recalculerTotal(Panier $panier)
    {
        $total = $panier->articles->sum(function ($article) {
            return $article->quantite * $article->prix_unitaire;
        });

        $panier->prix_total = $total;
        $panier->save();
    }
}