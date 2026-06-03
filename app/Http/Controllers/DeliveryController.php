<?php

namespace App\Http\Controllers;

use App\Models\Livraison;
use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeliveryController extends Controller
{
    // ========== VOIR LES LIVRAISONS ASSIGNÉES ==========
    public function assigned(Request $request)
    {
        $livraisons = Livraison::with(['commande.articles.produit', 'commande.user'])
            ->where('livreur_id', $request->user()->id)
            ->whereIn('statut_suivi', ['assignee', 'recuperee', 'en_cours'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $livraisons
        ]);
    }

    // ========== METTRE À JOUR LE STATUT ==========
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:recuperee,en_cours,non_livree',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $livraison = Livraison::where('livreur_id', $request->user()->id)
            ->find($id);

        if (!$livraison) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Livraison introuvable.'
            ], 404);
        }

        $livraison->statut_suivi = $request->statut;
        $livraison->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Statut mis à jour avec succès.',
            'data'    => $livraison
        ]);
    }

    // ========== CONFIRMER LA LIVRAISON AU CLIENT ==========
    public function confirm(Request $request, $id)
    {
        $livraison = Livraison::where('livreur_id', $request->user()->id)
            ->find($id);

        if (!$livraison) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Livraison introuvable.'
            ], 404);
        }

        $livraison->statut_suivi = 'livree';
        $livraison->livre_le     = now();
        $livraison->save();

        // Mettre à jour le statut de la commande
        $livraison->commande->statut = 'livree';
        $livraison->commande->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Livraison confirmée avec succès.',
            'data'    => $livraison
        ]);
    }

    // ========== HISTORIQUE DES TOURNÉES ==========
    public function history(Request $request)
    {
        $query = Livraison::with(['commande.articles.produit', 'commande.user'])
            ->where('livreur_id', $request->user()->id)
            ->where('statut_suivi', 'livree');

        // Filtrer par période
        if ($request->has('period') && $request->period !== '') {
            switch ($request->period) {
                case 'today':
                    $query->whereDate('livre_le', today());
                    break;
                case 'week':
                    $query->whereBetween('livre_le', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('livre_le', now()->month)
                          ->whereYear('livre_le', now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('livre_le', now()->subMonth()->month)
                          ->whereYear('livre_le', now()->subMonth()->year);
                    break;
            }
        }

        $livraisons = $query->orderBy('livre_le', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'livraisons'  => $livraisons,
                'total'       => $livraisons->count(),
            ]
        ]);
    }
}