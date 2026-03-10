<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSeguradoraRequest;
use App\Http\Requests\UpdateSeguradoraRequest;
use App\Models\Seguradora;

class SeguradoraController extends Controller
{
    public function index()
    {
        $seguradoras = Seguradora::latest()->paginate(15);
        return view('seguradoras.index', compact('seguradoras'));
    }

    public function create()
    {
        return view('seguradoras.create');
    }

    public function store(StoreSeguradoraRequest $request)
    {
        Seguradora::create($request->validated());
        return redirect()->route('seguradoras.index')->with('success', 'Seguradora criada com sucesso.');
    }

    public function show(Seguradora $seguradora)
    {
        $seguradora->load('corretores');
        return view('seguradoras.show', compact('seguradora'));
    }

    public function edit(Seguradora $seguradora)
    {
        return view('seguradoras.edit', compact('seguradora'));
    }

    public function update(UpdateSeguradoraRequest $request, Seguradora $seguradora)
    {
        $seguradora->update($request->validated());
        return redirect()->route('seguradoras.index')->with('success', 'Seguradora atualizada com sucesso.');
    }

    public function destroy(Seguradora $seguradora)
    {
        $seguradora->delete();
        return redirect()->route('seguradoras.index')->with('success', 'Seguradora removida com sucesso.');
    }
}
