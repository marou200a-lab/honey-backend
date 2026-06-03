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
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\SellerProductController;
use App\Http\Controllers\SellerOrderController;
use App\Http\Controllers\SellerStatsController;
use App\Http\Controllers\AdminController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
// Récupération mot de passe
Route::post('/password/email',         [PasswordResetController::class, 'sendResetLink']);
Route::get('/password/reset/{token}',  [PasswordResetController::class, 'validateToken']);
Route::post('/password/update',        [PasswordResetController::class, 'updatePassword']);

Route::get('/home', [ProduitController::class, 'home']);
// Catalogue produits (public)
Route::get('/produits/suggestions', [ProduitController::class, 'suggestions']);
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
    
    // Livreur
    Route::get('/deliveries/assigned',          [DeliveryController::class, 'assigned']);
    Route::patch('/deliveries/{id}/status',     [DeliveryController::class, 'updateStatus']);
    Route::post('/deliveries/{id}/confirm',     [DeliveryController::class, 'confirm']);
    Route::get('/deliveries/history',           [DeliveryController::class, 'history']);
    
    Route::get('/profile',        [ProfileController::class, 'getProfile']);
    Route::post('/profile/update', [ProfileController::class, 'updateProfile']);
    

    // Vendeur — Produits
    Route::get('/seller/products',          [SellerProductController::class, 'index']);
    Route::post('/seller/products/store',   [SellerProductController::class, 'store']);
    Route::get('/seller/products/{id}',     [SellerProductController::class, 'show']);
    Route::put('/seller/products/{id}',     [SellerProductController::class, 'update']);
    Route::delete('/seller/products/{id}',  [SellerProductController::class, 'destroy']);

    // Vendeur — Commandes
    Route::get('/seller/orders',                      [SellerOrderController::class, 'index']);
    Route::get('/seller/orders/{id}',                 [SellerOrderController::class, 'show']);
    Route::post('/seller/orders/{id}/update-status',  [SellerOrderController::class, 'updateStatus']);

    // Vendeur — Statistiques
    Route::get('/seller/statistics',              [SellerStatsController::class, 'index']);
    Route::get('/seller/statistics/download-pdf', [SellerStatsController::class, 'downloadPdf']);

    // Admin — Utilisateurs
    Route::get('/admin/users',                       [AdminController::class, 'getUsers']);
    Route::patch('/admin/users/{id}/toggle-active',  [AdminController::class, 'toggleActive']);
 
    // Admin — Catalogue
    Route::get('/admin/catalogue',                        [AdminController::class, 'getCatalogue']);
    Route::patch('/admin/catalogue/{id}/statut',          [AdminController::class, 'updateStatutProduit']);
 
    // Admin — Commandes
    Route::get('/admin/commandes',                              [AdminController::class, 'getCommandes']);
    Route::post('/admin/commandes/{id}/assigner-livreur',       [AdminController::class, 'assignLivreur']);
 
    // Admin — Statistiques globales
    Route::get('/admin/stats',  [AdminController::class, 'getStats']);
 
    });