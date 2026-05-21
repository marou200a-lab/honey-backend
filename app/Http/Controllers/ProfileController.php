<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Mail;
class ProfileController extends Controller
{
    // ========== ENVOYER LIEN VERIFICATION ==========
   public function sendVerificationEmail(Request $request)
{
    $user = $request->user();

    if ($user->email_verifie_le !== null) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Email deja verifie'
        ], 400);
    }

    // Construire le lien vers le frontend
    $hash = sha1($user->email);
    $verifyUrl = "http://localhost:3000/verify/{$user->id}/{$hash}";

    // Envoyer l'email avec le lien frontend
    Mail::raw(
        "Bonjour {$user->prenom},\n\n" .
        "Cliquez sur ce lien pour vérifier votre adresse email :\n\n" .
        "{$verifyUrl}\n\n" .
        "Khayrate Bladi",
        function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Vérification de votre adresse email — Khayrate Bladi');
        }
    );

    return response()->json([
        'status'  => 'success',
        'message' => 'Lien envoye ! Verifiez votre boite mail'
    ]);
}

    // ========== VALIDER EMAIL ==========
    public function verifyEmail(Request $request, $id, $hash)
    {
       $user = \App\Models\User::find($id);

    if (!$user) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Lien de vérification invalide ou expiré.'
        ], 404);
    }

        // Vérifier que le hash correspond
        if (!hash_equals(
            sha1($user->getEmailForVerification()),
            (string) $hash
        )) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lien invalide'
            ], 400);
        }

        // Email déjà vérifié
        if ($user->email_verifie_le !== null) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Email deja verifie'
            ]);
        }

        // Mettre à jour email_verified_at
        $user->email_verifie_le = now();
        $user->save();

        event(new Verified($user));

        return response()->json([
            'status'  => 'success',
            'message' => 'E-mail valide avec succes !'
        ]);
    }
}