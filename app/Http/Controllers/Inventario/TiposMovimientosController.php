<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\Tipos_Movimiento;
use Illuminate\Http\JsonResponse;

class TiposMovimientosController extends Controller
{
    /**
     * Listar todos los tipos de movimiento con su ID y nombre.
     *
     * GET /api/tipos-movimientos
     */
    public function index(): JsonResponse
    {
        // Trae un arreglo de objetos: [{ tipmov_id: "...", tipmov_nombre: "..." }, ...]
        $tipos = Tipos_Movimiento::select('tipmov_id', 'tipmov_nombre')->get();

        return response()->json($tipos);
    }

    /**
     * Mostrar un tipo de movimiento específico (ID + nombre).
     *
     * GET /api/tipos-movimientos/{id}
     */
    public function show(string $id): JsonResponse
    {
        $tipo = Tipos_Movimiento::select('tipmov_id', 'tipmov_nombre')
            ->findOrFail($id);

        return response()->json($tipo);
    }
}
