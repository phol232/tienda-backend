<?php

namespace App\Http\Controllers\Pedidos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\Rule;
use App\Models\Productos_Proveedores\Productos;

class PedidosController extends Controller
{
    public function index()
    {
        try {
            $pedidos = DB::select('CALL sp_listar_pedidos()');

            // Convertir detalles de string a array JSON
            foreach ($pedidos as &$pedido) {
                if (is_string($pedido->detalles)) {
                    $pedido->detalles = json_decode($pedido->detalles);
                }
            }

            return response()->json($pedidos, Response::HTTP_OK);
        } catch (\Exception $e) {
            \Log::error('Error al listar pedidos: ' . $e->getMessage());
            return response()->json([
                'message' => 'No se pudieron obtener los pedidos.',
                'error'   => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function show($ped_id)
    {
        try {
            $resultado = DB::select('CALL sp_obtener_pedido(?)', [$ped_id]);

            if (empty($resultado)) {
                return response()->json([
                    'message' => "El pedido con ID {$ped_id} no existe."
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json($resultado, Response::HTTP_OK);
        } catch (\Exception $e) {
            \Log::error('Error al obtener pedido: ' . $e->getMessage());
            return response()->json([
                'message' => 'No se pudo obtener el pedido.',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        // 1) Validaciones
        $request->validate([
            'cliente_nombre'          => 'required|string|max:100',
            'usr_id'                  => 'required|exists:Usuarios,usr_id',
            'forma_entrega'           => 'nullable|string|max:30',
            'notas'                   => 'nullable|string',
            'items'                   => 'required|array|min:1',
            'items.*.prod_id'         => [
                'required',
                Rule::exists((new Productos)->getTable(), (new Productos)->getKeyName())
            ],
            'items.*.cantidad'        => 'required|integer|min:1',
            'items.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        // 2) Convertir el array de items a JSON
        $itemsJson = json_encode($request->input('items'));

        try {
            // 3) Llamar al procedimiento almacenado sp_insertar_pedido
            $resultado = DB::select(
                'CALL sp_insertar_pedido(?, ?, ?, ?, ?)',
                [
                    $request->input('cliente_nombre'),
                    $request->input('usr_id'),
                    $request->input('forma_entrega', null),
                    $request->input('notas', null),
                    $itemsJson
                ]
            );

            // 4) Validar que el procedimiento devolvió el nuevo ped_id
            if (!empty($resultado) && isset($resultado[0]->nuevo_ped_id)) {
                return response()->json([
                    'message'   => 'Pedido creado exitosamente.',
                    'pedido_id' => $resultado[0]->nuevo_ped_id,
                ], Response::HTTP_CREATED);
            }

            return response()->json([
                'message' => 'El procedimiento no devolvió el nuevo ID del pedido.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            \Log::error('Error al llamar sp_insertar_pedido: ' . $e->getMessage());
            return response()->json([
                'message' => 'Ocurrió un error al crear el pedido.',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar un pedido existente (usa sp_actualizar_pedido).
     */
    public function update(Request $request, $ped_id)
    {
        // 1) Validaciones
        $request->validate([
            'cliente_nombre'          => 'required|string|max:100',
            'usr_id'                  => 'required|exists:Usuarios,usr_id',
            'forma_entrega'           => 'nullable|string|max:30',
            'notas'                   => 'nullable|string',
            'items'                   => 'required|array|min:1',
            'items.*.prod_id'         => [
                'required',
                Rule::exists((new Productos)->getTable(), (new Productos)->getKeyName())
            ],
            'items.*.cantidad'        => 'required|integer|min:1',
            'items.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        // 2) Convertir items a JSON
        $itemsJson = json_encode($request->input('items'));

        try {
            // 3) Llamar al procedimiento sp_actualizar_pedido
            DB::statement(
                'CALL sp_actualizar_pedido(?, ?, ?, ?, ?, ?)',
                [
                    $ped_id,
                    $request->input('cliente_nombre'),
                    $request->input('usr_id'),
                    $request->input('forma_entrega', null),
                    $request->input('notas', null),
                    $itemsJson
                ]
            );

            return response()->json([
                'message'   => 'Pedido actualizado exitosamente.',
                'pedido_id' => $ped_id,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            \Log::error('Error al llamar sp_actualizar_pedido: ' . $e->getMessage());
            return response()->json([
                'message' => 'Ocurrió un error al actualizar el pedido.',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar (lógicamente o físicamente) un pedido (usa sp_eliminar_pedido).
     */
    public function destroy($ped_id)
    {
        try {
            DB::statement('CALL sp_eliminar_pedido(?)', [$ped_id]);

            return response()->json([
                'message' => 'Pedido eliminado correctamente.'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            \Log::error('Error al llamar sp_eliminar_pedido: ' . $e->getMessage());
            return response()->json([
                'message' => 'No se pudo eliminar el pedido.',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
