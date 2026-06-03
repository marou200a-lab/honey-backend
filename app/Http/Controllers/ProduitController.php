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

    // ========== SUGGESTIONS DE RECHERCHE ==========
   public function suggestions(Request $request)
{
    if (!$request->has('search') || strlen($request->search) < 1) {
        return response()->json([
            'status' => 'success',
            'data'   => []
        ]);
    }

    $search = $request->search;

    $suggestions = Produit::where('statut', 'actif')
        ->where('nom', 'LIKE', '%' . $search . '%')
        ->select('id', 'nom', 'prix', 'image_url')
        ->orderByRaw(
            'CASE WHEN nom LIKE ? THEN 1 WHEN nom LIKE ? THEN 2 ELSE 3 END, nom ASC',
            [$search . '%', '%' . $search . '%']
        )
        ->get();

    if ($suggestions->isEmpty()) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Aucun produit trouvé pour "' . $search . '".'
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'data'   => $suggestions
    ]);
}

    // ========== PAGE D'ACCUEIL ==========
public function home()
{
    // Catégories avec nombre de produits
    $categories = Categorie::withCount([
        'produits' => fn($q) => $q->where('statut', 'actif')
    ])->get();

    // 8 derniers produits ajoutés
    $produits_vedettes = Produit::with(['categorie', 'vendeur:id,nom,prenom'])
        ->where('statut', 'actif')
        ->withAvg('avis', 'note')
        ->orderBy('created_at', 'desc')
        ->limit(8)
        ->get();

    // 8 produits les mieux notés
    $produits_populaires = Produit::with(['categorie', 'vendeur:id,nom,prenom'])
        ->where('statut', 'actif')
        ->withAvg('avis', 'note')
        ->orderByDesc('avis_avg_note')
        ->limit(8)
        ->get();

    return response()->json([
        'status' => 'success',
        'data'   => [
            'categories'          => $categories,
            'produits_vedettes'   => $produits_vedettes,
            'produits_populaires' => $produits_populaires,
        ]
    ]);
} 
}