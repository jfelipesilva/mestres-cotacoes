<?php

namespace App\Models;

use App\Enums\StatusSolicitacao;
use App\Enums\StatusSubSolicitacao;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CotacaoSolicitacao extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cotacao_solicitacoes';

    protected $fillable = [
        'corretor_id',
        'whatsapp_message_id',
        'raw_message',
        'vehicle_data',
        'client_data',
        'status',
    ];

    protected $casts = [
        'vehicle_data' => 'array',
        'client_data' => 'array',
        'status' => StatusSolicitacao::class,
    ];

    public function corretor(): BelongsTo
    {
        return $this->belongsTo(Corretor::class);
    }

    public function subSolicitacoes(): HasMany
    {
        return $this->hasMany(CotacaoSubSolicitacao::class, 'cotacao_solicitacao_id');
    }

    public function recalcularStatus(): void
    {
        $subs = $this->subSolicitacoes()->get();

        if ($subs->isEmpty()) {
            return;
        }

        $allCompleted = $subs->every(fn ($s) => $s->status === StatusSubSolicitacao::Completed);
        $allFailed = $subs->every(fn ($s) => $s->status === StatusSubSolicitacao::Failed);
        $anyRunning = $subs->contains(fn ($s) => in_array($s->status, [StatusSubSolicitacao::Running, StatusSubSolicitacao::Retrying, StatusSubSolicitacao::Pending]));
        $anyCompleted = $subs->contains(fn ($s) => $s->status === StatusSubSolicitacao::Completed);

        if ($allCompleted) {
            $this->status = StatusSolicitacao::Completed;
        } elseif ($allFailed) {
            $this->status = StatusSolicitacao::Failed;
        } elseif ($anyRunning) {
            $this->status = StatusSolicitacao::Processing;
        } elseif ($anyCompleted) {
            $this->status = StatusSolicitacao::Partial;
        }

        $this->save();
    }
}
