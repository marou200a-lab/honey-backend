<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;

class ProfileController extends Controller
{
    // ========== ENVOYER LIEN VERIFICATION ==========
    public function sendVerificationEmail(Request $request)
    {
        $user = $request->user();

        // Email déjà vérifié
        if ($user->email_verifie_le !== null) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Email deja verifie'
            ], 400);
        }

        // Envoyer le lien de vérification
        $user->sendEmailVerificationNotification();

        return response()->json([
            'status'  => 'success',
            'message' => 'Lien envoye ! Verifiez votre boite mail'
        ]);
    }

    // ========== VALIDER EMAIL ==========
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = \App\Models\User::findOrFail($id);

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