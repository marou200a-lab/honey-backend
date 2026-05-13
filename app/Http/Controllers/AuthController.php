<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        if ($request->has('telephone')) {
            $request->merge([
                'telephone' => str_replace(' ', '', $request->telephone)
            ]);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|min:3|max:255',
            'prenom' => 'required|string|min:3|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required', 
                'string', 
                'min:8', 
                'max:255',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'c_password' => 'required|same:password',
            'telephone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^(0[0-9]{9}|\+212[0-9]{9}|\+212[0-9]{3}-[0-9]{6})$/'
            ],
        ]);

        $validator->after(function ($validator) use ($request) {
            $exists = User::where('nom', $request->nom)
                          ->where('prenom', $request->prenom)
                          ->exists();
            if ($exists) {
                $validator->errors()->add('nom', 'The combination of this Nom and Prenom already exists.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telephone' => $request->telephone,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Client registered successfully',
            'data' => $user
        ], 201);
    }


     // ========== LOGIN ==========
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Email introuvable
        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Email ou mot de passe incorrect'
            ], 401);
        }

        // Mot de passe incorrect
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Email ou mot de passe incorrect'
            ], 401);
        }

        // Compte bloqué (middleware)
        if (!$user->est_actif) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Votre compte a ete bloque. Contactez l administrateur.'
            ], 403);
        }

        // Révoquer les anciens tokens
        $user->tokens()->delete();

        // Créer nouveau token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Connexion reussie. Bienvenue ' . $user->prenom . ' !',
            'token'   => $token,
            'user'    => [
                'id'     => $user->id,
                'nom'    => $user->nom,
                'prenom' => $user->prenom,
                'email'  => $user->email,
                'role'   => $user->role,
            ]
        ]);
    }

// ========== LOGOUT ==========
public function logout(Request $request)
{
    // Révoquer le token actuel uniquement
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'status'  => 'success',
        'message' => 'Deconnexion reussie'
    ]);
}

}
