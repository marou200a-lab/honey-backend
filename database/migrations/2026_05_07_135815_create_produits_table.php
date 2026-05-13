<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->foreignId('categorie_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('vendeur_id')->constrained('users')->onDelete('cascade');
            $table->decimal('prix', 10, 2);
            $table->integer('quantite_stock')->default(0);
            $table->string('image_url')->nullable();
            $table->enum('statut', ['en_attente', 'actif', 'masque'])->default('en_attente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};