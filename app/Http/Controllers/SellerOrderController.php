<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\ArticleCommande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SellerOrderController extends Controller
{
    // ========== CONSULTER LES COMMANDES REÇUES ==========
    public function index(Request $request)
    {
        $vendeurId = $request->user()->id;

        $commandes = Commande::with(['articles.produit', 'user'])
            ->whereHas('articles', function ($q) use ($vendeurId) {
                $q->whereHas('produit', function ($q2) use ($vendeurId) {
                    $q2->where('vendeur_id', $vendeurId);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $commandes
        ]);
    }

    // ========== DÉTAIL D'UNE COMMANDE ==========
    public function show(Request $request, $id)
    {
        $vendeurId = $request->user()->id;

        $commande = Commande::with(['articles.produit', 'user'])
            ->whereHas('articles', function ($q) use ($vendeurId) {
                $q->whereHas('produit', function ($q2) use ($vendeurId) {
                    $q2->where('vendeur_id', $vendeurId);
                });
            })
            ->find($id);

        if (!$commande) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Commande introuvable.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $commande
        ]);
    }

    // ========== METTRE À JOUR LE STATUT ==========
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:en_attente,en_preparation,expediee,annulee',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $vendeurId = $request->user()->id;

        $commande = Commande::whereHas('articles', function ($q) use ($vendeurId) {
            $q->whereHas('produit', function ($q2) use ($vendeurId) {
                $q2->where('vendeur_id', $vendeurId);
            });
        })->find($id);

        if (!$commande) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Commande introuvable.'
            ], 404);
        }

        $commande->statut = $request->statut;
        $commande->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Statut de la commande mis à jour.',
            'data'    => $commande
        ]);
    }
}
