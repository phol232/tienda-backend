<?php

namespace App\Http\Controllers\Productos_Proveedores;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Productos_Proveedores\Productos;
use App\Models\Productos_Proveedores\Producto_Detalles;
use App\Models\Productos_Proveedores\Categorias;
use App\Models\Productos_Proveedores\Proveedores;

class ProductosController extends Controller
{
    public function create()
    {
        $categoriasProductos = Categorias::all();
        $proveedores = Proveedores::whereHas('categorias')
            ->with('categorias')
            ->get();

        return response()->json(compact('categoriasProductos', 'proveedores'));
    }

    public function index()
    {
        $productos = Productos::with([
            'detalles',
            'categoria',
            'proveedores.categorias',
        ])->get();

        return response()->json($productos);
    }

    public function show($id)
    {
        $producto = Productos::with([
            'detalles',
            'categoria',
            'proveedores.categorias',
        ])->findOrFail($id);

        return response()->json($producto);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'pro_nombre'           => 'required|string|max:100',
            'pro_precio_venta'     => 'required|numeric',
            'stock_inicial'        => 'required|integer',
            'pro_estado'           => 'required|string|max:20',
            'cat_id'               => 'required|string|exists:Categorias,cat_id',
            'descripcion'          => 'nullable|string',
            'precio_costo'         => 'required|numeric',
            'stock_minimo'         => 'required|integer',
            'stock_maximo'         => 'nullable|integer',
            'prod_fecha_caducidad' => 'nullable|date',
            'prod_imagen'          => 'nullable|image|max:2048',
            'prov_id'              => 'required|string|exists:Proveedores,prov_id',
        ]);

        DB::transaction(function() use ($v, $request, &$newId) {
            // 1) Crear Producto
            $newId = $this->generateProId();
            $producto = Productos::create([
                'pro_id'           => $newId,
                'pro_nombre'       => $v['pro_nombre'],
                'pro_precio_venta' => $v['pro_precio_venta'],
                'pro_stock'        => $v['stock_inicial'],
                'pro_estado'       => $v['pro_estado'],
                'cat_id'           => $v['cat_id'],
            ]);

            // 2) Crear Detalles
            $detalleData = [
                'prod_id'             => $newId,
                'prod_descripcion'    => $v['descripcion'] ?? null,
                'prod_precio_compra'  => $v['precio_costo'],
                'prod_stock_minimo'   => $v['stock_minimo'],
                'prod_stock_maximo'   => $v['stock_maximo'] ?? null,
                'prod_fecha_caducidad'=> $v['prod_fecha_caducidad'] ?? null,
            ];
            if ($request->hasFile('prod_imagen')) {
                $path = $request->file('prod_imagen')->store('productos','public');
                // Usa asset() para generar la URL completa
                $detalleData['prod_imagen'] = asset("storage/{$path}");
            }
            Producto_Detalles::create($detalleData);

            // 3) Adjuntar Proveedor (pivot)
            $pivotId = $this->generateProdProvId();
            $producto->proveedores()->attach($v['prov_id'], [
                'prod_prov_id' => $pivotId,
                'estado'       => 'Activo',
            ]);
        });

        $fresh = Productos::with([
            'detalles',
            'categoria',
            'proveedores.categorias',
        ])->findOrFail($newId);

        return response()->json($fresh, Response::HTTP_CREATED);
    }

    public function update(Request $request, $id)
    {
        $producto = Productos::findOrFail($id);

        $v = $request->validate([
            'pro_nombre'           => 'sometimes|required|string|max:100',
            'pro_precio_venta'     => 'sometimes|required|numeric',
            'stock_inicial'        => 'sometimes|required|integer',
            'pro_estado'           => 'sometimes|required|string|max:20',
            'cat_id'               => 'sometimes|required|string|exists:Categorias,cat_id',
            'descripcion'          => 'nullable|string',
            'precio_costo'         => 'sometimes|required|numeric',
            'stock_minimo'         => 'sometimes|required|integer',
            'stock_maximo'         => 'nullable|integer',
            'prod_fecha_caducidad' => 'nullable|date',
            'prod_imagen'          => 'nullable|image|max:2048',
            'prov_id'              => 'sometimes|required|string|exists:Proveedores,prov_id',
        ]);

        DB::transaction(function() use ($v, $request, $producto) {
            // Actualizar campos de Productos
            foreach (['pro_nombre','pro_precio_venta','pro_estado','cat_id'] as $f) {
                if (isset($v[$f])) {
                    $producto->$f = $v[$f];
                }
            }
            if (isset($v['stock_inicial'])) {
                $producto->pro_stock = $v['stock_inicial'];
            }
            $producto->save();

            // Actualizar detalles
            $det = $producto->detalles;
            foreach ([
                         'descripcion'          => 'prod_descripcion',
                         'precio_costo'         => 'prod_precio_compra',
                         'stock_minimo'         => 'prod_stock_minimo',
                         'stock_maximo'         => 'prod_stock_maximo',
                         'prod_fecha_caducidad' => 'prod_fecha_caducidad',
                     ] as $input => $col) {
                if (array_key_exists($input, $v)) {
                    $det->$col = $v[$input];
                }
            }
            if ($request->hasFile('prod_imagen')) {
                // Eliminar imagen antigua si existe
                if ($det->prod_imagen && file_exists(public_path(parse_url($det->prod_imagen, PHP_URL_PATH)))) {
                    unlink(public_path(parse_url($det->prod_imagen, PHP_URL_PATH)));
                }
                $path = $request->file('prod_imagen')->store('productos','public');
                $det->prod_imagen = asset("storage/{$path}");
            }
            $det->prod_actualizado_en = now();
            $det->save();

            // Actualizar relación pivot de proveedor
            if (isset($v['prov_id'])) {
                $producto->proveedores()->detach();
                $pivotId = $this->generateProdProvId();
                $producto->proveedores()->attach($v['prov_id'], [
                    'prod_prov_id' => $pivotId,
                    'estado'       => 'Activo',
                ]);
            }
        });

        $fresh = $producto->load([
            'detalles',
            'categoria',
            'proveedores.categorias',
        ]);

        return response()->json($fresh);
    }

    public function destroy($id)
    {
        Productos::findOrFail($id)->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function generateProId(): string
    {
        $last = Productos::orderBy('pro_id','desc')->first();
        if ($last && preg_match('/PROD-(\d+)/', $last->pro_id, $m)) {
            $num = intval($m[1]) + 1;
        } else {
            $num = 1;
        }
        return 'PROD-'.str_pad($num, 3, '0', STR_PAD_LEFT);
    }

    private function generateProdProvId(): string
    {
        $last = DB::table('Producto_Proveedor')
            ->orderBy('prod_prov_id','desc')
            ->value('prod_prov_id');

        if ($last && preg_match('/PP-(\d+)/', $last, $m)) {
            $num = intval($m[1]) + 1;
        } else {
            $num = 1;
        }
        return 'PP-'.str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
