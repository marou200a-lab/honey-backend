<?php

namespace App\Http\Controllers;

use App\Models\Produit;
use App\Models\Categorie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SellerProductController extends Controller
{
    // ========== CONSULTER SES PRODUITS ==========
    public function index(Request $request)
    {
        $produits = Produit::with(['categorie'])
            ->where('vendeur_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $produits
        ]);
    }

    // ========== AJOUTER UN PRODUIT ==========
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom'            => 'required|string|max:255',
            'description'    => 'nullable|string',
            'categorie_id'   => 'required|integer|exists:categories,id',
            'prix'           => 'required|numeric|min:0',
            'quantite_stock' => 'required|integer|min:0',
            'image_url'      => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $produit = Produit::create([
            'nom'            => $request->nom,
            'description'    => $request->description,
            'categorie_id'   => $request->categorie_id,
            'vendeur_id'     => $request->user()->id,
            'prix'           => $request->prix,
            'quantite_stock' => $request->quantite_stock,
            'image_url'      => $request->image_url,
            'statut'         => 'en_attente', // admin doit valider
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Produit ajouté avec succès. En attente de validation par l\'administrateur.',
            'data'    => $produit
        ], 201);
    }

    // ========== CONSULTER UN PRODUIT ==========
    public function show(Request $request, $id)
    {
        $produit = Produit::with(['categorie'])
            ->where('vendeur_id', $request->user()->id)
            ->find($id);

        if (!$produit) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Produit introuvable.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $produit
        ]);
    }

    // ========== MODIFIER UN PRODUIT ==========
    public function update(Request $request, $id)
    {
        $produit = Produit::where('vendeur_id', $request->user()->id)
            ->find($id);

        if (!$produit) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Produit introuvable.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nom'            => 'sometimes|string|max:255',
            'description'    => 'sometimes|nullable|string',
            'categorie_id'   => 'sometimes|integer|exists:categories,id',
            'prix'           => 'sometimes|numeric|min:0',
            'quantite_stock' => 'sometimes|integer|min:0',
            'image_url'      => 'sometimes|nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $produit->update($request->only([
            'nom', 'description', 'categorie_id',
            'prix', 'quantite_stock', 'image_url'
        ]));

        return response()->json([
            'status'  => 'success',
            'message' => 'Produit mis à jour avec succès.',
            'data'    => $produit
        ]);
    }

    // ========== SUPPRIMER UN PRODUIT ==========
    public function destroy(Request $request, $id)
    {
        $produit = Produit::where('vendeur_id', $request->user()->id)
            ->find($id);

        if (!$produit) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Produit introuvable.'
            ], 404);
        }

        $produit->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Produit supprimé du catalogue.'
        ]);
    }
}