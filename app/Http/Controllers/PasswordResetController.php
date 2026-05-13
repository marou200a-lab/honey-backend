<?php
    //recuperer mot de passe
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
    // ========== ÉTAPE 1 — ENVOYER LE LIEN ==========
    public function sendResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Toujours retourner 200 même si l'email n'existe pas (sécurité)
        if (!$user) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.'
            ]);
        }

        // Supprimer les anciens tokens pour cet email
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        // Générer un nouveau token
        $token = Str::random(64);

        // Stocker dans password_reset_tokens
        DB::table('password_reset_tokens')->insert([
            'email'      => $request->email,
            'token'      => Hash::make($token),
            'created_at' => now(),
        ]);

        // Construire le lien
        $resetLink = url("/api/password/reset/{$token}?email=" . urlencode($request->email));

        // Envoyer l'email (dans les logs en dev)
        Mail::raw(
            "Bonjour {$user->prenom},\n\n" .
            "Cliquez sur ce lien pour réinitialiser votre mot de passe :\n\n" .
            "{$resetLink}\n\n" .
            "Ce lien expire dans 60 minutes.\n\n" .
            "Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.",
            function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Réinitialisation de votre mot de passe — Khayrate Bladi');
            }
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.'
        ]);
    }

    // ========== ÉTAPE 2 — VALIDER LE TOKEN ==========
    public function validateToken(Request $request, $token)
    {
        $validator = Validator::make($request->query(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Paramètre email manquant.'
            ], 400);
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->query('email'))
            ->first();

        if (!$record) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lien invalide ou expiré.'
            ], 400);
        }

        // Vérifier le token
        if (!Hash::check($token, $record->token)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lien invalide ou expiré.'
            ], 400);
        }

        // Vérifier expiration (60 minutes)
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')
                ->where('email', $request->query('email'))
                ->delete();

            return response()->json([
                'status'  => 'error',
                'message' => 'Ce lien a expiré. Veuillez faire une nouvelle demande.'
            ], 400);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Token valide. Vous pouvez maintenant choisir un nouveau mot de passe.',
            'email'   => $request->query('email'),
            'token'   => $token,
        ]);
    }

    // ========== ÉTAPE 3 — METTRE À JOUR LE MOT DE PASSE ==========
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'      => 'required|email',
            'token'      => 'required|string',
            'password'   => [
                'required', 'string', 'min:8', 'max:255',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lien invalide ou expiré.'
            ], 400);
        }

        // Vérifier le token
        if (!Hash::check($request->token, $record->token)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lien invalide ou expiré.'
            ], 400);
        }

        // Vérifier expiration (60 minutes)
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            return response()->json([
                'status'  => 'error',
                'message' => 'Ce lien a expiré. Veuillez faire une nouvelle demande.'
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Utilisateur introuvable.'
            ], 404);
        }

        // Mettre à jour le mot de passe
        $user->password = Hash::make($request->password);

        // Si email non vérifié, le marquer comme vérifié (selon le diagramme)
        if ($user->email_verifie_le === null) {
            $user->email_verifie_le = now();
        }

        $user->save();

        // Supprimer le token utilisé
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        // Révoquer tous les tokens Sanctum (sécurité)
        $user->tokens()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Mot de passe mis à jour avec succès. Veuillez vous reconnecter.'
        ]);
    }
}