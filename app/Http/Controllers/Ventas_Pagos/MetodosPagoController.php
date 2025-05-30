<?php

namespace App\Http\Controllers\Ventas_Pagos;

use Illuminate\Http\Request;
use App\Models\ventas_Pagos\MetodosPago;

class MetodosPagoController extends Controller
{
    public function index()
    {
        return response()->json(MetodosPago::all(), 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'met_nombre' => 'required|string|max:50',
            'met_descripcion' => 'nullable|string',
            'met_estado' => 'nullable|string|max:20',
            'met_tipo' => 'nullable|string|max:20',
            'met_banco' => 'nullable|string|max:50',
        ]);

        $ultimo = MetodosPago::orderBy('met_id', 'desc')->first();
        
        if (!$ultimo) {
            $nuevoId = 'MET-001';
        } else {
            $ultimoNumero = (int) substr($ultimo->met_id, 4);
            $nuevoId = 'MET-' . str_pad($ultimoNumero + 1, 3, '0', STR_PAD_LEFT);
        }

        $metodo = MetodosPago::create([
            'met_id' => $nuevoId,
            'met_nombre' => $request->met_nombre,
            'met_descripcion' => $request->met_descripcion,
            'met_estado' => $request->met_estado ?? 'Activo',
            'met_tipo' => $request->met_tipo,
            'met_banco' => $request->met_banco,
        ]);

        return response()->json($metodo, 201);
    }


    public function show($id)
    {
        $metodo = MetodosPago::find($id);

        if (!$metodo) {
            return response()->json(['message' => 'Método de pago no encontrado'], 404);
        }

        return response()->json($metodo, 200);
    }

    public function update(Request $request, $id)
    {
        $metodo = MetodosPago::find($id);

        if (!$metodo) {
            return response()->json(['message' => 'Método de pago no encontrado'], 404);
        }

        $request->validate([
            'met_nombre' => 'sometimes|required|string|max:50',
            'met_estado' => 'nullable|string|max:20',
            'met_tipo' => 'nullable|string|max:20',
            'met_banco' => 'nullable|string|max:50',
        ]);

        $metodo->update($request->all());

        return response()->json($metodo, 200);
    }

    public function destroy($id)
    {
        $metodo = MetodosPago::find($id);

        if (!$metodo) {
            return response()->json(['message' => 'Método de pago no encontrado'], 404);
        }

        $metodo->delete();

        return response()->json(['message' => 'Método de pago eliminado correctamente'], 200);
    }
}
