<?php

namespace App\Providers;

use App\Models\CotacaoSubSolicitacao;
use App\Observers\CotacaoSubSolicitacaoObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        CotacaoSubSolicitacao::observe(CotacaoSubSolicitacaoObserver::class);
    }
}
