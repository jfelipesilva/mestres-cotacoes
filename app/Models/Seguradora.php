<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seguradora extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'system_url',
        'prompt_instructions',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function corretores(): BelongsToMany
    {
        return $this->belongsToMany(Corretor::class, 'corretor_seguradora')
            ->using(CorretorSeguradora::class)
            ->withPivot(['login_username', 'login_password', 'extra_credentials', 'is_enabled'])
            ->withTimestamps();
    }

    public function subSolicitacoes(): HasMany
    {
        return $this->hasMany(CotacaoSubSolicitacao::class);
    }
}
