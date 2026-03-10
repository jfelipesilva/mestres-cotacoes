<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Corretor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'corretores';

    protected $fillable = [
        'name',
        'phone',
        'claude_api_key',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'claude_api_key',
    ];

    public function setClaudeApiKeyAttribute(?string $value): void
    {
        $this->attributes['claude_api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getClaudeApiKeyAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function seguradoras(): BelongsToMany
    {
        return $this->belongsToMany(Seguradora::class, 'corretor_seguradora')
            ->using(CorretorSeguradora::class)
            ->withPivot(['id', 'login_username', 'login_password', 'extra_credentials', 'is_enabled'])
            ->withTimestamps();
    }

    public function seguradorasHabilitadas(): BelongsToMany
    {
        return $this->seguradoras()->wherePivot('is_enabled', true);
    }

    public function solicitacoes(): HasMany
    {
        return $this->hasMany(CotacaoSolicitacao::class);
    }
}
