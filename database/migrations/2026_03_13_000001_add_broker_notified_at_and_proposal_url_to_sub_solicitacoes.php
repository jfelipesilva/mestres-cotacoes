<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotacao_sub_solicitacoes', function (Blueprint $table) {
            $table->timestamp('broker_notified_at')->nullable()->after('completed_at');
            $table->string('proposal_url', 500)->nullable()->after('pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('cotacao_sub_solicitacoes', function (Blueprint $table) {
            $table->dropColumn(['broker_notified_at', 'proposal_url']);
        });
    }
};
