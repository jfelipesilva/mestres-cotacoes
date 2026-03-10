<?php

namespace App\Models;

use App\Enums\StatusSubSolicitacao;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotacaoSubSolicitacao extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cotacao_sub_solicitacoes';

    protected $fillable = [
        'cotacao_solicitacao_id',
        'seguradora_id',
        'status',
        'agent_log',
        'error_message',
        'pdf_path',
        'result_data',
        'started_at',
        'completed_at',
        'attempts',
    ];

    protected $casts = [
        'status' => StatusSubSolicitacao::class,
        'result_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(CotacaoSolicitacao::class, 'cotacao_solicitacao_id');
    }

    public function seguradora(): BelongsTo
    {
        return $this->belongsTo(Seguradora::class);
    }
}
