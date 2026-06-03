<?php

namespace App\Http\Controllers;

use App\Models\Produit;
use App\Models\ArticleCommande;
use App\Models\Commande;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB; 

class SellerStatsController extends Controller
{
    // ========== STATISTIQUES DE VENTE ==========
    public function index(Request $request)
    {
        $vendeurId = $request->user()->id;

        // Total des ventes
        $totalVentes = ArticleCommande::whereHas('produit', function ($q) use ($vendeurId) {
            $q->where('vendeur_id', $vendeurId);
        })->whereHas('commande', function ($q) {
            $q->whereNotIn('statut', ['annulee']);
        })->sum(\DB::raw('quantite * prix_unitaire'));

        // Nombre de commandes
        $nombreCommandes = Commande::whereHas('articles', function ($q) use ($vendeurId) {
            $q->whereHas('produit', function ($q2) use ($vendeurId) {
                $q2->where('vendeur_id', $vendeurId);
            });
        })->count();

        // Nombre de produits
        $nombreProduits = Produit::where('vendeur_id', $vendeurId)->count();

        // Top 5 produits les plus vendus
        $topProduits = ArticleCommande::with('produit')
            ->whereHas('produit', function ($q) use ($vendeurId) {
                $q->where('vendeur_id', $vendeurId);
            })
            ->selectRaw('produit_id, SUM(quantite) as total_vendu, SUM(quantite * prix_unitaire) as total_revenu')
            ->groupBy('produit_id')
            ->orderByDesc('total_vendu')
            ->limit(5)
            ->get();

        // Ventes par mois (6 derniers mois)
        $ventesParMois = ArticleCommande::whereHas('produit', function ($q) use ($vendeurId) {
            $q->where('vendeur_id', $vendeurId);
        })
        ->selectRaw('MONTH(created_at) as mois, YEAR(created_at) as annee, SUM(quantite * prix_unitaire) as total')
        ->groupBy('mois', 'annee')
        ->orderByDesc('annee')
        ->orderByDesc('mois')
        ->limit(6)
        ->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'total_ventes'    => round($totalVentes, 2),
                'nombre_commandes' => $nombreCommandes,
                'nombre_produits'  => $nombreProduits,
                'top_produits'     => $topProduits,
                'ventes_par_mois'  => $ventesParMois,
            ]
        ]);
    }

    // ========== TÉLÉCHARGER STATISTIQUES PDF ==========
    public function downloadPdf(Request $request)
    {
        $vendeurId = $request->user()->id;
        $user      = $request->user();

        $totalVentes = ArticleCommande::whereHas('produit', function ($q) use ($vendeurId) {
            $q->where('vendeur_id', $vendeurId);
        })->sum(\DB::raw('quantite * prix_unitaire'));

        $nombreCommandes = Commande::whereHas('articles', function ($q) use ($vendeurId) {
            $q->whereHas('produit', function ($q2) use ($vendeurId) {
                $q2->where('vendeur_id', $vendeurId);
            });
        })->count();

        $topProduits = ArticleCommande::with('produit')
            ->whereHas('produit', function ($q) use ($vendeurId) {
                $q->where('vendeur_id', $vendeurId);
            })
            ->selectRaw('produit_id, SUM(quantite) as total_vendu, SUM(quantite * prix_unitaire) as total_revenu')
            ->groupBy('produit_id')
            ->orderByDesc('total_vendu')
            ->limit(5)
            ->get();

        $pdf = Pdf::loadView('seller.statistics', [
            'user'             => $user,
            'total_ventes'     => round($totalVentes, 2),
            'nombre_commandes' => $nombreCommandes,
            'top_produits'     => $topProduits,
            'date'             => now()->format('d/m/Y'),
        ]);

        return $pdf->download('statistiques_vendeur_' . $user->id . '.pdf');
    }
}