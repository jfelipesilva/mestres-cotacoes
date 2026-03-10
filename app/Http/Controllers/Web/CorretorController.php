<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCorretorRequest;
use App\Http\Requests\UpdateCorretorRequest;
use App\Models\Corretor;
use App\Models\Seguradora;

class CorretorController extends Controller
{
    public function index()
    {
        $corretores = Corretor::latest()->paginate(15);
        return view('corretores.index', compact('corretores'));
    }

    public function create()
    {
        $seguradoras = Seguradora::where('is_active', true)->get();
        return view('corretores.create', compact('seguradoras'));
    }

    public function store(StoreCorretorRequest $request)
    {
        $corretor = Corretor::create($request->validated());
        $this->syncSeguradoras($corretor, $request);
        return redirect()->route('corretores.index')->with('success', 'Corretor criado com sucesso.');
    }

    public function show(Corretor $corretore)
    {
        $corretore->load('seguradoras');
        return view('corretores.show', ['corretor' => $corretore]);
    }

    public function edit(Corretor $corretore)
    {
        $corretore->load('seguradoras');
        $seguradoras = Seguradora::where('is_active', true)->get();
        return view('corretores.edit', ['corretor' => $corretore, 'seguradoras' => $seguradoras]);
    }

    public function update(UpdateCorretorRequest $request, Corretor $corretore)
    {
        $data = $request->validated();
        if (empty($data['claude_api_key'])) {
            unset($data['claude_api_key']);
        }
        $corretore->update($data);
        $this->syncSeguradoras($corretore, $request);
        return redirect()->route('corretores.index')->with('success', 'Corretor atualizado com sucesso.');
    }

    public function destroy(Corretor $corretore)
    {
        $corretore->delete();
        return redirect()->route('corretores.index')->with('success', 'Corretor removido com sucesso.');
    }

    private function syncSeguradoras(Corretor $corretor, $request): void
    {
        $vinculos = $request->input('seguradoras', []);
        $syncData = [];

        foreach ($vinculos as $seguradoraId => $dados) {
            if (!isset($dados['enabled'])) {
                continue;
            }
            $syncData[$seguradoraId] = [
                'login_username' => $dados['login_username'] ?? null,
                'login_password' => $dados['login_password'] ?? null,
                'extra_credentials' => isset($dados['extra_credentials']) ? json_decode($dados['extra_credentials'], true) : null,
                'is_enabled' => true,
            ];
        }

        $corretor->seguradoras()->sync($syncData);
    }
}
