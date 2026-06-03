<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\Livraison;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    // =========================================================
    // ASSIGN LIVREUR  (déjà fait — conservé tel quel)
    // =========================================================

    public function assignLivreur(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'livreur_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $commande = Commande::find($id);

        if (!$commande) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Commande introuvable',
            ], 404);
        }

        $livreur = User::where('id', $request->livreur_id)
            ->where('role', 'livreur')
            ->first();

        if (!$livreur) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Livreur invalide',
            ], 404);
        }

        $livraisonExistante = Livraison::where('commande_id', $commande->id)->first();

        if ($livraisonExistante) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cette commande possède déjà une livraison',
            ], 409);
        }

        $livraison = Livraison::create([
            'commande_id'  => $commande->id,
            'livreur_id'   => $livreur->id,
            'statut_suivi' => 'assignee',
            'disponibilite' => true,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Livreur assigné avec succès',
            'data'    => $livraison,
        ]);
    }

    // =========================================================
    // USERS — liste + toggle actif
    // =========================================================

    /**
     * GET /api/admin/users
     * Paramètres optionnels : role, search, page
     */
    public function getUsers(Request $request)
    {
        $query = User::query();

        // Filtre par rôle
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Recherche nom / prénom / email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom',    'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('email',  'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')
                       ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data'   => $users,
        ]);
    }

    /**
     * PATCH /api/admin/users/{id}/toggle-active
     * Active ou désactive un compte utilisateur
     */
    public function toggleActive($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Utilisateur introuvable',
            ], 404);
        }

        // Empêcher de désactiver un admin
        if ($user->role === 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Impossible de modifier un compte administrateur',
            ], 403);
        }

        $user->est_actif = !$user->est_actif;
        $user->save();

        // Révoquer tous les tokens si désactivé
        if (!$user->est_actif) {
            $user->tokens()->delete();
        }

        return response()->json([
            'status'  => 'success',
            'message' => $user->est_actif ? 'Compte activé' : 'Compte désactivé',
            'data'    => [
                'id'       => $user->id,
                'est_actif' => $user->est_actif,
            ],
        ]);
    }

    // =========================================================
    // CATALOGUE — liste tous les produits + changer statut
    // =========================================================

    /**
     * GET /api/admin/catalogue
     * Paramètres optionnels : statut (en_attente|actif|masque), search, page
     */
    public function getCatalogue(Request $request)
    {
        $query = Produit::with(['categorie:id,nom', 'vendeur:id,nom,prenom']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('nom', 'like', "%{$search}%");
        }

        $produits = $query->orderBy('created_at', 'desc')
                          ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data'   => $produits,
        ]);
    }

    /**
     * PATCH /api/admin/catalogue/{id}/statut
     * Body : { "statut": "actif" | "masque" | "en_attente" }
     */
    public function updateStatutProduit(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:en_attente,actif,masque',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $produit = Produit::find($id);

        if (!$produit) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Produit introuvable',
            ], 404);
        }

        $produit->statut = $request->statut;
        $produit->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Statut du produit mis à jour',
            'data'    => [
                'id'     => $produit->id,
                'nom'    => $produit->nom,
                'statut' => $produit->statut,
            ],
        ]);
    }

    // =========================================================
    // COMMANDES — liste toutes les commandes
    // =========================================================

    /**
     * GET /api/admin/commandes
     * Paramètres optionnels : statut, search (nom client), page
     */
    public function getCommandes(Request $request)
    {
        $query = Commande::with([
            'user:id,nom,prenom,email',
            'livraison.livreur:id,nom,prenom',
        ]);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('nom',   'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('email',  'like', "%{$search}%");
            });
        }

        $commandes = $query->orderBy('created_at', 'desc')
                           ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data'   => $commandes,
        ]);
    }

    // =========================================================
    // STATS GLOBALES
    // =========================================================

    /**
     * GET /api/admin/stats
     * Retourne : CA total, nb commandes, nb users par rôle,
     *            nb produits par statut, ventes par mois (12 derniers mois),
     *            top 5 produits, commandes par statut
     */
    public function getStats()
    {
        // Chiffre d'affaires total (commandes livrées)
        $chiffreAffaires = Commande::where('statut', 'livree')
            ->sum('prix_total');

        // Nombre total de commandes
        $totalCommandes = Commande::count();

        // Commandes par statut
        $commandesParStatut = Commande::select('statut', DB::raw('count(*) as total'))
            ->groupBy('statut')
            ->get()
            ->keyBy('statut');

        // Utilisateurs par rôle
        $usersParRole = User::select('role', DB::raw('count(*) as total'))
            ->groupBy('role')
            ->get()
            ->keyBy('role');

        // Produits par statut
        $produitsParStatut = Produit::select('statut', DB::raw('count(*) as total'))
            ->groupBy('statut')
            ->get()
            ->keyBy('statut');

        // Ventes par mois (12 derniers mois)
        $ventesParMois = Commande::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as mois"),
                DB::raw('SUM(prix_total) as total'),
                DB::raw('COUNT(*) as nb_commandes')
            )
            ->where('statut', 'livree')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        // Top 5 produits les plus vendus
        $topProduits = DB::table('articles_commande')
            ->join('produits', 'articles_commande.produit_id', '=', 'produits.id')
            ->join('commandes', 'articles_commande.commande_id', '=', 'commandes.id')
            ->where('commandes.statut', 'livree')
            ->select(
                'produits.id',
                'produits.nom',
                'produits.image_url',
                DB::raw('SUM(articles_commande.quantite) as total_vendu'),
                DB::raw('SUM(articles_commande.quantite * articles_commande.prix_unitaire) as chiffre_affaires')
            )
            ->groupBy('produits.id', 'produits.nom', 'produits.image_url')
            ->orderByDesc('total_vendu')
            ->limit(5)
            ->get();

        // Livreurs disponibles (pour assignation)
        $livreurs = User::where('role', 'livreur')
            ->where('est_actif', true)
            ->select('id', 'nom', 'prenom', 'email', 'telephone')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'chiffre_affaires'      => round($chiffreAffaires, 2),
                'total_commandes'       => $totalCommandes,
                'commandes_par_statut'  => $commandesParStatut,
                'users_par_role'        => $usersParRole,
                'produits_par_statut'   => $produitsParStatut,
                'ventes_par_mois'       => $ventesParMois,
                'top_produits'          => $topProduits,
                'livreurs_disponibles'  => $livreurs,
            ],
        ]);
    }
}