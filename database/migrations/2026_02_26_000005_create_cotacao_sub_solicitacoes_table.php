<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotacao_sub_solicitacoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cotacao_solicitacao_id');
            $table->foreignId('seguradora_id')->constrained('seguradoras');
            $table->string('status')->default('pending');
            $table->longText('agent_log')->nullable();
            $table->text('error_message')->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->json('result_data')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->tinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->foreign('cotacao_solicitacao_id')->references('id')->on('cotacao_solicitacoes')->cascadeOnDelete();
            $table->index('cotacao_solicitacao_id');
            $table->index('seguradora_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotacao_sub_solicitacoes');
    }
};
