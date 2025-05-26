<?php

namespace App\Http\Controllers\Productos_Proveedores;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Productos_Proveedores\Proveedores;
use App\Models\Productos_Proveedores\Proveedor_Categoria as ProvCatPivot;
use App\Models\Productos_Proveedores\Categorias_Proveedores;

class ProveedoresController extends Controller
{
    public function index()
    {
        return response()->json(Proveedores::all());
    }

    public function show(string $id)
    {
        $prov = Proveedores::findOrFail($id);
        return response()->json($prov);
    }

    public function store(Request $request)
    {
        $request->validate([
            'prov_nombre'    => 'required|string|max:100',
            'prov_contacto'  => 'nullable|string|max:100',
            'prov_telefono'  => 'nullable|string|max:20',
            'prov_email'     => 'nullable|email|max:100',
            'prov_direccion' => 'nullable|string',
            'prov_rfc'       => 'nullable|string|max:20',
            'prov_notas'     => 'nullable|string',
            'prov_sitio_web' => 'nullable|url|max:255',
            'categorias'     => 'nullable|array',
            'categorias.*'   => 'string|exists:Categorias_Proveedores,prov_cat_nombre',
        ]);

        $lastProv = Proveedores::orderBy('prov_id', 'desc')->first();
        $numProv  = $lastProv && preg_match('/PROV-(\d+)/', $lastProv->prov_id, $m)
            ? intval($m[1]) + 1
            : 1;
        $newProvId = 'PROV-' . str_pad($numProv, 3, '0', STR_PAD_LEFT);

        $prov = Proveedores::create([
            'prov_id'        => $newProvId,
            'prov_nombre'    => $request->prov_nombre,
            'prov_contacto'  => $request->prov_contacto,
            'prov_telefono'  => $request->prov_telefono,
            'prov_email'     => $request->prov_email,
            'prov_direccion' => $request->prov_direccion,
            'prov_rfc'       => $request->prov_rfc,
            'prov_notas'     => $request->prov_notas,
            'prov_sitio_web' => $request->prov_sitio_web,
        ]);

        if ($request->filled('categorias')) {
            // extraer el máximo numérico actual
            $max = DB::table('Proveedor_Categoria')
                ->selectRaw("MAX(CAST(SUBSTRING(prov_cat_map_id, 6) AS UNSIGNED)) AS max_num")
                ->value('max_num');
            $mapCounter = $max ? $max + 1 : 1;

            foreach ($request->categorias as $catNombre) {
                $cat = Categorias_Proveedores::where('prov_cat_nombre', $catNombre)->first();

                ProvCatPivot::create([
                    'prov_cat_map_id' => 'PCAT-' . str_pad($mapCounter, 3, '0', STR_PAD_LEFT),
                    'prov_id'         => $prov->prov_id,
                    'prov_cat_id'     => $cat->prov_cat_id,
                ]);
                $mapCounter++;
            }
        }

        $prov->load('categorias');

        return response()->json($prov, 201);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'prov_nombre'    => 'required|string|max:100',
            'prov_contacto'  => 'nullable|string|max:100',
            'prov_telefono'  => 'nullable|string|max:20',
            'prov_email'     => 'nullable|email|max:100',
            'prov_direccion' => 'nullable|string',
            'prov_rfc'       => 'nullable|string|max:20',
            'prov_notas'     => 'nullable|string',
            'prov_estado'    => 'nullable|string|max:20',
            'prov_sitio_web' => 'nullable|url|max:255',
            'categorias'     => 'nullable|array',
            'categorias.*'   => 'string|exists:Categorias_Proveedores,prov_cat_nombre',
        ]);

        $prov = Proveedores::findOrFail($id);

        $prov->update([
            'prov_nombre'    => $request->prov_nombre,
            'prov_contacto'  => $request->prov_contacto,
            'prov_telefono'  => $request->prov_telefono,
            'prov_email'     => $request->prov_email,
            'prov_direccion' => $request->prov_direccion,
            'prov_rfc'       => $request->prov_rfc,
            'prov_notas'     => $request->prov_notas,
            'prov_sitio_web' => $request->prov_sitio_web,
            'prov_estado'    => $request->prov_estado ?? $prov->prov_estado,
        ]);

        if ($request->filled('categorias')) {
            ProvCatPivot::where('prov_id', $prov->prov_id)->delete();

            $max = DB::table('Proveedor_Categoria')
                ->selectRaw("MAX(CAST(SUBSTRING(prov_cat_map_id, 6) AS UNSIGNED)) AS max_num")
                ->value('max_num');
            $mapCounter = $max ? $max + 1 : 1;

            foreach ($request->categorias as $catNombre) {
                $cat = Categorias_Proveedores::where('prov_cat_nombre', $catNombre)->first();

                ProvCatPivot::create([
                    'prov_cat_map_id' => 'PCAT-' . str_pad($mapCounter, 3, '0', STR_PAD_LEFT),
                    'prov_id'         => $prov->prov_id,
                    'prov_cat_id'     => $cat->prov_cat_id,
                ]);
                $mapCounter++;
            }
        }

        $prov->load('categorias');
        return response()->json($prov);
    }

    public function destroy(string $id)
    {
        $prov = Proveedores::findOrFail($id);
        $prov->delete();

        return response()->json([
            'message' => 'Proveedor y sus asociaciones eliminados correctamente'
        ]);
    }
}
