<?php

namespace App\Http\Controllers;

use App\Models\Panier;
use App\Models\Commande;
use App\Models\ArticleCommande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    // ========== PASSER COMMANDE ==========
    public function process(Request $request)
{
    $panier = Panier::with('articles.produit')
        ->where('user_id', $request->user()->id)
        ->first();

    if (!$panier || $panier->articles->isEmpty()) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Votre panier est vide.'
        ], 400);
    }

    // Vérifier le stock de chaque article
    foreach ($panier->articles as $article) {
        if ($article->produit->quantite_stock < $article->quantite) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Stock insuffisant pour : ' . $article->produit->nom
            ], 400);
        }
    }

    // Créer la commande
    $commande = Commande::create([
        'user_id'    => $request->user()->id,
        'prix_total' => $panier->prix_total,
        'statut'     => 'en_attente',
    ]);

    // Créer les articles de la commande + déduire le stock
    foreach ($panier->articles as $article) {
        ArticleCommande::create([
            'commande_id'   => $commande->id,
            'produit_id'    => $article->produit_id,
            'quantite'      => $article->quantite,
            'prix_unitaire' => $article->prix_unitaire,
        ]);

        $article->produit->quantite_stock -= $article->quantite;
        $article->produit->save();
    }

    // Générer le PDF
    $user = $request->user();
    $receiptName = 'recu_commande_' . $commande->id . '_' . time() . '.pdf';
    $receiptsDir = storage_path('app/receipts');

    if (!file_exists($receiptsDir)) {
        mkdir($receiptsDir, 0755, true);
    }

    $receiptPath = $receiptsDir . '/' . $receiptName;

    $commande->load('articles.produit');

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('receipts.commande', [
        'commande' => $commande,
        'articles' => $commande->articles,
        'user'     => $user,
    ]);

    $pdf->save($receiptPath);

    // Sauvegarder le nom du reçu
    $commande->receipt_name = $receiptName;
    $commande->save();

    // Vider le panier
    $panier->articles()->delete();
    $panier->prix_total = 0;
    $panier->save();

    // Envoyer email
    Mail::raw(
        "Bonjour {$user->prenom},\n\n" .
        "Votre commande #{$commande->id} a été passée avec succès.\n" .
        "Montant total : {$commande->prix_total} DH\n\n" .
        "Merci pour votre confiance — Khayrate Bladi",
        function ($message) use ($user, $receiptPath, $receiptName) {
            $message->to($user->email)
                    ->subject('Confirmation de votre commande — Khayrate Bladi')
                    ->attach($receiptPath, [
                        'as'   => $receiptName,
                        'mime' => 'application/pdf',
                    ]);
        }
    );

    return response()->json([
        'status'  => 'success',
        'message' => 'Commande passée avec succès ! Un reçu vous a été envoyé par email.',
        'data'    => [
            'commande_id' => $commande->id,
            'prix_total'  => $commande->prix_total,
            'statut'      => $commande->statut,
            'receipt'     => $receiptName,
        ]
    ]);
}
    // ========== HISTORIQUE DES COMMANDES ==========
    public function history(Request $request)
    {
        $commandes = Commande::with('articles.produit')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $commandes
        ]);
    }

    // ========== SUIVRE UNE COMMANDE ==========
    public function tracking(Request $request, $id)
    {
        $commande = Commande::with('articles.produit')
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$commande) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Commande introuvable.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'commande_id' => $commande->id,
                'statut'      => $commande->statut,
                'prix_total'  => $commande->prix_total,
                'articles'    => $commande->articles,
                'created_at'  => $commande->created_at,
            ]
        ]);
    }

    // ========== TÉLÉCHARGER LE REÇU PDF ==========
    public function downloadReceipt(Request $request, $id)
    {
        $commande = Commande::where('user_id', $request->user()->id)
            ->find($id);

        if (!$commande) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Commande introuvable.'
            ], 404);
        }

        if (!$commande->receipt_name) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Reçu non disponible.'
            ], 404);
        }

        $receiptPath = storage_path('app/receipts/' . $commande->receipt_name);

        if (!file_exists($receiptPath)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Fichier reçu introuvable.'
            ], 404);
        }

        return response()->download($receiptPath, $commande->receipt_name);
    }
}