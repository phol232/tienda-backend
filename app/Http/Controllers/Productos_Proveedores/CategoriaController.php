<?php

namespace App\Http\Controllers\Productos_Proveedores;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Productos_Proveedores\Categorias;

class CategoriaController extends Controller
{
    public function index()
    {
        return response()->json(Categorias::all());
    }

    public function show($id)
    {
        $categoria = Categorias::findOrFail($id);
        return response()->json($categoria);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cat_nombre' => 'required|string|max:255',
            'cat_descripcion' => 'nullable|string',
            'cat_color' => 'nullable|string',
            'imagen' => 'nullable|image|max:2048'
        ]);
        $lastCategoria = Categorias::orderBy('cat_id', 'desc')->first();
        if ($lastCategoria && preg_match('/CATP-(\d+)/', $lastCategoria->cat_id, $matches)) {
            $lastNumber = intval($matches[1]);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        $newCatId = 'CATP-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        $categoria = new Categorias();
        $categoria->cat_id = $newCatId;
        $categoria->cat_nombre = $request->cat_nombre;
        $categoria->cat_descripcion = $request->cat_descripcion;
        $categoria->cat_color = $request->cat_color;

        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('imagenes_categorias', 'public');
            $categoria->cat_imagen = '/storage/imagenes_categorias/' . basename($path);
        }

        $categoria->save();
        return response()->json($categoria, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'cat_nombre' => 'required|string|max:255',
            'cat_descripcion' => 'nullable|string',
            'cat_color' => 'nullable|string',
            'imagen' => 'nullable|image|max:2048'
        ]);

        $categoria = Categorias::findOrFail($id);
        $categoria->cat_nombre = $request->cat_nombre;
        $categoria->cat_descripcion = $request->cat_descripcion;
        $categoria->cat_color = $request->cat_color;

        if ($request->hasFile('imagen')) {
            if ($categoria->cat_imagen && file_exists(public_path($categoria->cat_imagen))) {
                unlink(public_path($categoria->cat_imagen));
            }
            $path = $request->file('imagen')->store('imagenes_categorias', 'public');
            $categoria->cat_imagen = '/storage/imagenes_categorias/' . basename($path);
        }

        $categoria->save();
        return response()->json($categoria);
    }

    public function destroy($id)
    {
        $categoria = Categorias::findOrFail($id);
        if ($categoria->cat_imagen && file_exists(public_path($categoria->cat_imagen))) {
            unlink(public_path($categoria->cat_imagen));
        }
        $categoria->delete();
        return response()->json(['message' => 'Categoría eliminada correctamente']);
    }
}
