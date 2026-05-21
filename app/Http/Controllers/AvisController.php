<?php

namespace App\Http\Controllers;

use App\Models\Avis;
use App\Models\Commande;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AvisController extends Controller
{
    // ========== VOIR LE FORMULAIRE D'AVIS ==========
    public function show(Request $request, $id)
    {
        $produit = Produit::where('statut', 'actif')->find($id);

        if (!$produit) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Produit introuvable.'
            ], 404);
        }

        // Vérifier si le client a acheté ce produit
        $aAchete = Commande::where('user_id', $request->user()->id)
            ->whereHas('articles', function ($q) use ($id) {
                $q->where('produit_id', $id);
            })
            ->exists();

        if (!$aAchete) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Vous devez acheter ce produit pour laisser un avis.'
            ], 403);
        }

        // Vérifier si le client a déjà laissé un avis
        $avisExistant = Avis::where('client_id', $request->user()->id)
            ->where('produit_id', $id)
            ->first();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'produit'        => $produit,
                'avis_existant'  => $avisExistant,
                'peut_commenter' => $avisExistant === null,
            ]
        ]);
    }

    // ========== SOUMETTRE UN AVIS ==========
    public function store(Request $request, $id)
    {
        $produit = Produit::where('statut', 'actif')->find($id);

        if (!$produit) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Produit introuvable.'
            ], 404);
        }

        // Vérifier si le client a acheté ce produit
        $aAchete = Commande::where('user_id', $request->user()->id)
            ->whereHas('articles', function ($q) use ($id) {
                $q->where('produit_id', $id);
            })
            ->exists();

        if (!$aAchete) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Vous devez acheter ce produit pour laisser un avis.'
            ], 403);
        }

        // Vérifier si le client a déjà laissé un avis
        $avisExistant = Avis::where('client_id', $request->user()->id)
            ->where('produit_id', $id)
            ->first();

        if ($avisExistant) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Vous avez déjà laissé un avis pour ce produit.'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'note'        => 'required|integer|min:1|max:5',
            'commentaire' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $avis = Avis::create([
            'client_id'  => $request->user()->id,
            'produit_id' => $id,
            'note'       => $request->note,
            'commentaire' => $request->commentaire,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Merci ! Votre avis a été publié.',
            'data'    => $avis
        ], 201);
    }

    // ========== VOIR LES AVIS D'UN PRODUIT ==========
    public function index($id)
    {
        $produit = Produit::where('statut', 'actif')->find($id);

        if (!$produit) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Produit introuvable.'
            ], 404);
        }

        $avis = Avis::with('client:id,nom,prenom')
            ->where('produit_id', $id)
            ->orderBy('date_publication', 'desc')
            ->get();

        $moyenneNote = $avis->avg('note');

        return response()->json([
            'status' => 'success',
            'data'   => [
                'avis'         => $avis,
                'moyenne_note' => round($moyenneNote, 1),
                'total_avis'   => $avis->count(),
            ]
        ]);
    }
}