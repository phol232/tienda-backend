<?php

namespace App\Http\Controllers\Productos_Proveedores;

use App\Http\Controllers\Controller;
use App\Models\Productos_Proveedores\Categorias_Proveedores;
use Illuminate\Http\Request;

class Categoria_ProveedoresController extends Controller
{
    public function index()
    {
        return response()->json(Categorias_Proveedores::all());
    }

    public function show($id)
    {
        $categoria = Categorias_Proveedores::findOrFail($id);
        return response()->json($categoria);
    }

    public function store(Request $request)
    {
        $request->validate([
            'prov_cat_nombre'      => 'required|string|max:50',
            'prov_cat_descripcion' => 'nullable|string',
            'prov_cat_color'       => 'nullable|string',
        ]);
        $last = Categorias_Proveedores::orderBy('prov_cat_id','desc')->first();
        if ($last && preg_match('/PROVCAT-(\d+)/', $last->prov_cat_id, $m)) {
            $num = intval($m[1]) + 1;
        } else {
            $num = 1;
        }
        $newId = 'PROVCAT-' . str_pad($num, 3, '0', STR_PAD_LEFT);

        $cat = new Categorias_Proveedores();
        $cat->prov_cat_id          = $newId;
        $cat->prov_cat_nombre      = $request->prov_cat_nombre;
        $cat->prov_cat_descripcion = $request->prov_cat_descripcion;
        $cat->prov_cat_estado      = 'Activo';
        $cat->prov_cat_color       = $request->prov_cat_color;
        $cat->save();

        return response()->json($cat, 201);
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'prov_cat_nombre'      => 'required|string|max:50',
            'prov_cat_descripcion' => 'nullable|string',
            'prov_cat_color'       => 'nullable|string',
            'prov_cat_estado'      => 'nullable|string|max:20',
        ]);

        $cat = Categorias_Proveedores::findOrFail($id);
        $cat->prov_cat_nombre      = $request->prov_cat_nombre;
        $cat->prov_cat_descripcion = $request->prov_cat_descripcion;
        if ($request->has('prov_cat_estado')) {
            $cat->prov_cat_estado = $request->prov_cat_estado;
        }
        $cat->prov_cat_color       = $request->prov_cat_color;
        $cat->save();

        return response()->json($cat);
    }

    public function destroy($id)
    {
        $cat = Categorias_Proveedores::findOrFail($id);
        $cat->delete();

        return response()->json([
            'message' => 'Categoría de proveedor eliminada correctamente'
        ]);
    }
}
