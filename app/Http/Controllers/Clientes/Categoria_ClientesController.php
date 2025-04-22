<?php

namespace App\Http\Controllers\Clientes;

use App\Http\Controllers\Controller;
use App\Models\Clientes\Categorias_Clientes;
use Illuminate\Http\Request;

class Categoria_ClientesController extends Controller
{
    public function index()
    {
        return response()->json(Categorias_Clientes::all());
    }
    public function show($id)
    {
        $categoria = Categorias_Clientes::findOrFail($id);
        return response()->json($categoria);
    }
    public function store(Request $request)
    {
        $request->validate([
            'cli_cat_nombre'       => 'required|string|max:50',
            'cli_cat_descripcion'  => 'nullable|string',
            'cli_color'            => 'nullable|string',
        ]);

        // Generar un nuevo ID tipo CLICAT-### auto‑incremental
        $lastCategoria = Categorias_Clientes::orderBy('cli_cat_id', 'desc')->first();
        if ($lastCategoria && preg_match('/CLICAT-(\d+)/', $lastCategoria->cli_cat_id, $matches)) {
            $lastNumber = intval($matches[1]);
            $newNumber  = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        $newCatId = 'CLICAT-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        $categoria = new Categorias_Clientes();
        $categoria->cli_cat_id          = $newCatId;
        $categoria->cli_cat_nombre      = $request->cli_cat_nombre;
        $categoria->cli_cat_descripcion = $request->cli_cat_descripcion;
        $categoria->cli_cat_estado      = 'Activo';
        $categoria->cli_color           = $request->cli_color;
        $categoria->save();

        return response()->json($categoria, 201);
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'cli_cat_nombre'       => 'required|string|max:50',
            'cli_cat_descripcion'  => 'nullable|string',
            'cli_color'            => 'nullable|string',
            'cli_cat_estado'       => 'nullable|string|max:20',
        ]);

        $categoria = Categorias_Clientes::findOrFail($id);
        $categoria->cli_cat_nombre      = $request->cli_cat_nombre;
        $categoria->cli_cat_descripcion = $request->cli_cat_descripcion;
        if ($request->has('cli_cat_estado')) {
            $categoria->cli_cat_estado = $request->cli_cat_estado;
        }
        $categoria->cli_color           = $request->cli_color;
        $categoria->save();

        return response()->json($categoria);
    }
    public function destroy($id)
    {
        $categoria = Categorias_Clientes::findOrFail($id);
        $categoria->delete();
        return response()->json([
            'message' => 'Categoría de cliente eliminada correctamente'
        ]);
    }
}
