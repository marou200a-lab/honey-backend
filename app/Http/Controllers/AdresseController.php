<?php

namespace App\Http\Controllers;

use App\Models\Adresse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdresseController extends Controller
{
    // ========== CONSULTER SES ADRESSES ==========
    public function index(Request $request)
    {
        $adresses = Adresse::where('user_id', $request->user()->id)
            ->orderBy('est_par_defaut', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $adresses
        ]);
    }

    // ========== AJOUTER UNE ADRESSE ==========
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rue'          => 'required|string|max:255',
            'ville'        => 'required|string|max:255',
            'code_postal'  => 'required|string|max:20',
            'est_par_defaut' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Si cette adresse est par défaut, retirer le défaut des autres
        if ($request->est_par_defaut) {
            Adresse::where('user_id', $request->user()->id)
                ->update(['est_par_defaut' => false]);
        }

        // Si c'est la première adresse, la mettre par défaut automatiquement
        $count = Adresse::where('user_id', $request->user()->id)->count();
        $estParDefaut = $count === 0 ? true : ($request->est_par_defaut ?? false);

        $adresse = Adresse::create([
            'user_id'        => $request->user()->id,
            'rue'            => $request->rue,
            'ville'          => $request->ville,
            'code_postal'    => $request->code_postal,
            'est_par_defaut' => $estParDefaut,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Adresse ajoutée avec succès.',
            'data'    => $adresse
        ], 201);
    }

    // ========== MODIFIER UNE ADRESSE ==========
    public function update(Request $request, $id)
    {
        $adresse = Adresse::where('user_id', $request->user()->id)
            ->find($id);

        if (!$adresse) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Adresse introuvable.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'rue'            => 'sometimes|string|max:255',
            'ville'          => 'sometimes|string|max:255',
            'code_postal'    => 'sometimes|string|max:20',
            'est_par_defaut' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Si on met cette adresse par défaut, retirer le défaut des autres
        if ($request->est_par_defaut) {
            Adresse::where('user_id', $request->user()->id)
                ->where('id', '!=', $id)
                ->update(['est_par_defaut' => false]);
        }

        $adresse->update($request->only([
            'rue', 'ville', 'code_postal', 'est_par_defaut'
        ]));

        return response()->json([
            'status'  => 'success',
            'message' => 'Adresse mise à jour avec succès.',
            'data'    => $adresse
        ]);
    }

    // ========== SUPPRIMER UNE ADRESSE ==========
    public function destroy(Request $request, $id)
    {
        $adresse = Adresse::where('user_id', $request->user()->id)
            ->find($id);

        if (!$adresse) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Adresse introuvable.'
            ], 404);
        }

        $estParDefaut = $adresse->est_par_defaut;
        $adresse->delete();

        // Si l'adresse supprimée était par défaut, mettre la première restante par défaut
        if ($estParDefaut) {
            $premiere = Adresse::where('user_id', $request->user()->id)->first();
            if ($premiere) {
                $premiere->est_par_defaut = true;
                $premiere->save();
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Adresse supprimée avec succès.'
        ]);
    }
}