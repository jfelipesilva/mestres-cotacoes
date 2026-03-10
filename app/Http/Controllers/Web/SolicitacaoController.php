<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Corretor;
use App\Models\CotacaoSolicitacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SolicitacaoController extends Controller
{
    public function index(Request $request)
    {
        $query = CotacaoSolicitacao::with(['corretor', 'subSolicitacoes']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('corretor_id')) {
            $query->where('corretor_id', $request->corretor_id);
        }
        if ($request->filled('data_inicio')) {
            $query->whereDate('created_at', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('created_at', '<=', $request->data_fim);
        }

        $solicitacoes = $query->latest()->paginate(20);
        $corretores = Corretor::orderBy('name')->get();

        return view('solicitacoes.index', compact('solicitacoes', 'corretores'));
    }

    public function show(CotacaoSolicitacao $solicitacao)
    {
        $solicitacao->load(['corretor', 'subSolicitacoes.seguradora']);
        return view('solicitacoes.show', compact('solicitacao'));
    }

    public function downloadPdf(string $solicitacaoId, string $subId)
    {
        $path = "cotacoes/{$solicitacaoId}/{$subId}.pdf";

        if (!Storage::exists($path)) {
            abort(404, 'PDF não encontrado.');
        }

        return Storage::download($path);
    }
}
