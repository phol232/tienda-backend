<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Inventario\Movimientos_Inventario;
use App\Models\Inventario\Tipos_Movimiento;
use App\Models\Productos_Proveedores\Productos as ProductoModel;
use App\Models\Productos_Proveedores\Proveedores as ProveedorModel;

class MovimientosController extends Controller
{
    /**
     * Listar todos los movimientos con sus relaciones
     */
    public function index()
    {
        $movimientos = Movimientos_Inventario::with([
            'tipoMovimiento',
            'usuario',
            'proveedor',
            'productos'
        ])->get();

        return response()->json($movimientos);
    }

    /**
     * Mostrar un movimiento específico
     */
    public function show($mov_id)
    {
        $movimiento = Movimientos_Inventario::with([
            'tipoMovimiento',
            'usuario',
            'proveedor',
            'productos'
        ])->findOrFail($mov_id);

        return response()->json($movimiento);
    }

    /**
     * Crear un nuevo movimiento y asociar productos, actualizando stock
     */
    public function store(Request $request)
    {
        $v = $request->validate([
            'tipmov_nombre'                => 'required|string|exists:Tipos_Movimiento,tipmov_nombre',
            'mov_fecha'                    => 'nullable|date',
            'mov_referencia'               => 'nullable|string|max:50',
            'mov_notas'                    => 'nullable|string',
            'usr_id'                       => 'required|string|exists:Usuarios,usr_id',
            'prov_nombre'                  => 'required|string|exists:Proveedores,prov_nombre',
            'productos'                    => 'required|array|min:1',
            'productos.*.prod_nombre'      => 'required|string|exists:Productos,pro_nombre',
            'productos.*.movprod_cantidad' => 'required|integer|min:1',
            'productos.*.movprod_costo_unitario' => 'required|numeric|min:0',
        ]);

        DB::transaction(function() use ($v, &$movimiento) {
            // Resolver tipo y proveedor
            $tipo      = Tipos_Movimiento::where('tipmov_nombre', $v['tipmov_nombre'])->firstOrFail();
            $prov      = ProveedorModel::where('prov_nombre', $v['prov_nombre'])->firstOrFail();
            $esEntrada = $tipo->tipmov_nombre === 'Entrada';

            // Crear encabezado
            $mov_id = $this->generateMovId();
            $movimiento = Movimientos_Inventario::create([
                'mov_id'         => $mov_id,
                'tipmov_id'      => $tipo->tipmov_id,
                'mov_fecha'      => $v['mov_fecha'] ?? now(),
                'mov_referencia' => $v['mov_referencia'] ?? null,
                'mov_notas'      => $v['mov_notas'] ?? null,
                'usr_id'         => $v['usr_id'],
                'prov_id'        => $prov->prov_id,
            ]);

            // Asociar productos y ajustar stock
            foreach ($v['productos'] as $item) {
                $prod = ProductoModel::where('pro_nombre', $item['prod_nombre'])->firstOrFail();

                $movimiento->productos()->attach($prod->pro_id, [
                    'movprod_cantidad'       => $item['movprod_cantidad'],
                    'movprod_costo_unitario' => $item['movprod_costo_unitario'],
                ]);

                if ($esEntrada) {
                    $prod->increment('pro_stock', $item['movprod_cantidad']);
                } else {
                    $prod->decrement('pro_stock', $item['movprod_cantidad']);
                }
            }
        });

        $fresh = Movimientos_Inventario::with([
            'tipoMovimiento','usuario','proveedor','productos'
        ])->findOrFail($movimiento->mov_id);

        return response()->json($fresh, Response::HTTP_CREATED);
    }

    /**
     * Actualizar un movimiento y sus productos, revirtiendo y aplicando stock
     */
    public function update(Request $request, $mov_id)
    {
        $movimiento = Movimientos_Inventario::with(['productos', 'tipoMovimiento'])
            ->findOrFail($mov_id);

        $v = $request->validate([
            'tipmov_nombre'                => 'sometimes|required|string|exists:Tipos_Movimiento,tipmov_nombre',
            'mov_fecha'                    => 'nullable|date',
            'mov_referencia'               => 'nullable|string|max:50',
            'mov_notas'                    => 'nullable|string',
            'usr_id'                       => 'sometimes|required|string|exists:Usuarios,usr_id',
            'prov_nombre'                  => 'nullable|string|exists:Proveedores,prov_nombre',
            'productos'                    => 'nullable|array',
            'productos.*.prod_nombre'      => 'required_with:productos|string|exists:Productos,pro_nombre',
            'productos.*.movprod_cantidad' => 'required_with:productos|integer|min:1',
            'productos.*.movprod_costo_unitario' => 'required_with:productos|numeric|min:0',
        ]);

        DB::transaction(function() use ($movimiento, $v) {
            $wasEntrada = $movimiento->tipoMovimiento->tipmov_nombre === 'Entrada';
            foreach ($movimiento->productos as $oldProd) {
                $oldQty = $oldProd->pivot->movprod_cantidad;
                if ($wasEntrada) {
                    // Si antes fue Entrada, quito lo que sumó
                    $oldProd->decrement('pro_stock', $oldQty);
                } else {
                    // Si antes fue Salida, repongo lo que restó
                    $oldProd->increment('pro_stock', $oldQty);
                }
            }

            if (isset($v['tipmov_nombre'])) {
                $tipo = Tipos_Movimiento::where('tipmov_nombre', $v['tipmov_nombre'])->firstOrFail();
                $movimiento->tipmov_id = $tipo->tipmov_id;
            } else {
                $tipo = $movimiento->tipoMovimiento;
            }

            if (isset($v['prov_nombre'])) {
                $prov = ProveedorModel::where('prov_nombre', $v['prov_nombre'])->firstOrFail();
                $movimiento->prov_id = $prov->prov_id;
            }

            foreach (['mov_fecha','mov_referencia','mov_notas','usr_id'] as $field) {
                if (array_key_exists($field, $v)) {
                    $movimiento->$field = $v[$field];
                }
            }

            $movimiento->save();

            if (!empty($v['productos'])) {
                $sync = [];
                foreach ($v['productos'] as $item) {
                    $prod = ProductoModel::where('pro_nombre', $item['prod_nombre'])->firstOrFail();
                    $sync[$prod->pro_id] = [
                        'movprod_cantidad'       => $item['movprod_cantidad'],
                        'movprod_costo_unitario' => $item['movprod_costo_unitario'],
                    ];
                }

                $movimiento->productos()->sync($sync);

                $movimiento->load('productos');

                $isEntrada = $tipo->tipmov_nombre === 'Entrada';
                foreach ($movimiento->productos as $newProd) {
                    $qty = $newProd->pivot->movprod_cantidad;
                    if ($isEntrada) {
                        // Para Entradas, sumo al stock
                        $newProd->increment('pro_stock', $qty);
                    } else {
                        // Para Salidas, resto del stock
                        $newProd->decrement('pro_stock', $qty);
                    }
                }
            }
        });

        // Devuelvo el movimiento actualizado con todas sus relaciones
        $fresh = $movimiento->refresh()
            ->load(['tipoMovimiento','usuario','proveedor','productos']);

        return response()->json($fresh);
    }

    /**
     * Eliminar un movimiento y revertir stock
     */
    public function destroy($mov_id)
    {
        $mov = Movimientos_Inventario::with(['productos','tipoMovimiento'])->findOrFail($mov_id);
        $esEntrada = $mov->tipoMovimiento->tipmov_nombre === 'Entrada';

        DB::transaction(function() use ($mov, $esEntrada) {
            foreach ($mov->productos as $prod) {
                $qty = $prod->pivot->movprod_cantidad;
                if ($esEntrada) {
                    // Si fue Entrada, quito lo agregado
                    $prod->decrement('pro_stock', $qty);
                } else {
                    // Si fue Salida, repongo lo quitado
                    $prod->increment('pro_stock', $qty);
                }
            }
            $mov->delete();
        });

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Genera un ID único para Movimientos_Inventario
     */
    private function generateMovId(): string
    {
        $last = Movimientos_Inventario::orderBy('mov_id', 'desc')->first();
        if ($last && preg_match('/MOV-(\d+)/', $last->mov_id, $m)) {
            $num = intval($m[1]) + 1;
        } else {
            $num = 1;
        }
        return 'MOV-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
