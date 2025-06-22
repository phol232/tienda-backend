<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_requests', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->text('message')->nullable();      // opcional: nombre completo
            $table->string('password');               // hash de la contraseña
            $table->boolean('approved')->default(false);
            $table->timestamps();

            // si quieres, puedes añadir índices
            $table->index('approved');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_requests');
    }
};
