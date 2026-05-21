<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdresseController;
use App\Http\Controllers\AvisController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
// Récupération mot de passe
Route::post('/password/email',         [PasswordResetController::class, 'sendResetLink']);
Route::get('/password/reset/{token}',  [PasswordResetController::class, 'validateToken']);
Route::post('/password/update',        [PasswordResetController::class, 'updatePassword']);

// Catalogue produits (public)
Route::get('/produits',            [ProduitController::class, 'index']);
Route::get('/produits/{id}',       [ProduitController::class, 'show']);
Route::get('/categories',          [ProduitController::class, 'categories']);
// Verification email
Route::get('/verify/{id}/{hash}', [ProfileController::class, 'verifyEmail'])
    ->name('verification.verify');

// Avis — public (voir les avis d'un produit)
Route::get('/product/{id}/review', [AvisController::class, 'index']);

// Routes protégées (token + compte actif)
Route::middleware(['auth:sanctum', 'active'])->group(function () {
   Route::post('/logout', [AuthController::class, 'logout']);
   Route::post('/profile/verify/send', [ProfileController::class, 'sendVerificationEmail']);
    Route::get('/cart',                          [CartController::class, 'index']);
    Route::post('/cart/add',                     [CartController::class, 'add']);
    Route::patch('/cart/update',                 [CartController::class, 'update']);
    Route::delete('/cart/remove/{id}',           [CartController::class, 'remove']);

    // Commandes
    Route::post('/checkout/process',             [OrderController::class, 'process']);
    Route::get('/orders/history',                [OrderController::class, 'history']);
    Route::get('/orders/{id}/tracking',          [OrderController::class, 'tracking']);
    Route::get('/orders/{id}/download-receipt',  [OrderController::class, 'downloadReceipt']);
    
    // Adresses
    Route::get('/profile/addresses',        [AdresseController::class, 'index']);
    Route::post('/profile/addresses/store', [AdresseController::class, 'store']);
    Route::put('/profile/addresses/{id}',   [AdresseController::class, 'update']);
    Route::delete('/profile/addresses/{id}',[AdresseController::class, 'destroy']);
   
    // Avis — protégé (soumettre un avis)
    Route::get('/product/{id}/review/form',   [AvisController::class, 'show']);
    Route::post('/product/{id}/review/store', [AvisController::class, 'store']);
    });