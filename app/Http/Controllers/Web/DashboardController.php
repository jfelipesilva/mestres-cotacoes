<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Corretor;
use App\Models\CotacaoSolicitacao;
use App\Models\Seguradora;
use App\Enums\StatusSubSolicitacao;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalCorretores = Corretor::count();
        $totalSeguradoras = Seguradora::count();
        $solicitacoesHoje = CotacaoSolicitacao::whereDate('created_at', today())->count();

        $totalSubs = \App\Models\CotacaoSubSolicitacao::count();
        $subsCompleted = \App\Models\CotacaoSubSolicitacao::where('status', StatusSubSolicitacao::Completed)->count();
        $taxaSucesso = $totalSubs > 0 ? round(($subsCompleted / $totalSubs) * 100, 1) : 0;

        $ultimasSolicitacoes = CotacaoSolicitacao::with('corretor')
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard', compact(
            'totalCorretores',
            'totalSeguradoras',
            'solicitacoesHoje',
            'taxaSucesso',
            'ultimasSolicitacoes',
        ));
    }
}
