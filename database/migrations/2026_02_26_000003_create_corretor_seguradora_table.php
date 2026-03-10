<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corretor_seguradora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corretor_id')->constrained('corretores')->cascadeOnDelete();
            $table->foreignId('seguradora_id')->constrained('seguradoras')->cascadeOnDelete();
            $table->string('login_username', 500)->nullable();
            $table->string('login_password', 500)->nullable();
            $table->json('extra_credentials')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['corretor_id', 'seguradora_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corretor_seguradora');
    }
};
