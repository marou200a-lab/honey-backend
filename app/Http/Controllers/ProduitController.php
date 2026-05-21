<?php

namespace App\Http\Controllers;

use App\Models\Produit;
use App\Models\Categorie;
use Illuminate\Http\Request;

class ProduitController extends Controller
{
    // ========== LISTE + SEARCH + FILTER + SORT ==========
    public function index(Request $request)
    {
        $query = Produit::with(['categorie', 'vendeur:id,nom,prenom'])
            ->where('statut', 'actif');

        // Recherche par mot-clé (nom ou description)
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Filtrage par catégorie
        if ($request->has('categorie_id') && $request->categorie_id !== '') {
            $query->where('categorie_id', $request->categorie_id);
        }

        // Filtrage par prix min
        if ($request->has('prix_min') && $request->prix_min !== '') {
            $query->where('prix', '>=', $request->prix_min);
        }

        // Filtrage par prix max
        if ($request->has('prix_max') && $request->prix_max !== '') {
            $query->where('prix', '<=', $request->prix_max);
        }

        // Tri
        $sortableFields = ['prix', 'nom', 'created_at'];
        $sortField = in_array($request->sort, $sortableFields) ? $request->sort : 'created_at';
        $sortOrder = $request->order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortField, $sortOrder);

        $produits = $query->withAvg('avis', 'note')->paginate(12);

        return response()->json([
            'status' => 'success',
            'data'   => $produits,
        ]);
    }

    // ========== DÉTAIL D'UN PRODUIT ==========
    public function show($id)
    {
        $produit = Produit::with(['categorie', 'vendeur:id,nom,prenom'])
            ->where('statut', 'actif')
            ->find($id);

        if (!$produit) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Produit introuvable.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $produit,
        ]);
    }

    // ========== LISTE DES CATÉGORIES ==========
    public function categories()
    {
        $categories = Categorie::withCount([
            'produits' => fn($q) => $q->where('statut', 'actif')
        ])->get();

        return response()->json([
            'status' => 'success',
            'data'   => $categories,
        ]);
    }
}