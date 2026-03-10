<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotacao_solicitacoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('corretor_id')->constrained('corretores');
            $table->string('whatsapp_message_id')->nullable();
            $table->text('raw_message');
            $table->json('vehicle_data')->nullable();
            $table->json('client_data')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index('corretor_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotacao_solicitacoes');
    }
};
