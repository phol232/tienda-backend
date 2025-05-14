<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventario\AlertaStock;
use Symfony\Component\HttpFoundation\Response;

class AlertaStockController extends Controller
{
    public function index()
    {
        $alertas = AlertaStock::with(['producto', 'creador', 'resolucion'])->get();
        return response()->json($alertas);
    }

    public function show($id)
    {
        $alerta = AlertaStock::with(['producto', 'creador', 'resolucion'])
            ->findOrFail($id);
        return response()->json($alerta);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'alerta_tipo'            => 'required|string|max:20',
            'alerta_nivel'           => 'required|string|max:20',
            'alerta_mensaje'         => 'required|string',
            'alerta_fecha'           => 'nullable|date',
            'alerta_estado'          => 'sometimes|string|max:20',
            'alerta_fecha_resolucion'=> 'nullable|date',
            'alerta_comentario'      => 'nullable|string',
            'prod_id'                => 'required|string|exists:Productos,pro_id',
            'usr_id_creador'         => 'required|string|exists:Usuarios,usr_id',
            'usr_id_resolucion'      => 'nullable|string|exists:Usuarios,usr_id',
        ]);

        $alerta = AlertaStock::create([
            'alerta_id'             => $this->generateAlertaId(),
            'alerta_tipo'           => $v['alerta_tipo'],
            'alerta_nivel'          => $v['alerta_nivel'],
            'alerta_mensaje'        => $v['alerta_mensaje'],
            'alerta_fecha'          => $v['alerta_fecha'] ?? now(),
            'alerta_estado'         => $v['alerta_estado'] ?? 'Activa',
            'alerta_fecha_resolucion'=> $v['alerta_fecha_resolucion'] ?? null,
            'alerta_comentario'     => $v['alerta_comentario'] ?? null,
            'prod_id'               => $v['prod_id'],
            'usr_id_creador'        => $v['usr_id_creador'],
            'usr_id_resolucion'     => $v['usr_id_resolucion'] ?? null,
        ]);

        return response()->json($alerta, Response::HTTP_CREATED);
    }

    public function update(Request $request, $id)
    {
        $alerta = AlertaStock::findOrFail($id);

        $v = $request->validate([
            'alerta_tipo'            => 'sometimes|required|string|max:20',
            'alerta_nivel'           => 'sometimes|required|string|max:20',
            'alerta_mensaje'         => 'sometimes|required|string',
            'alerta_fecha'           => 'nullable|date',
            'alerta_estado'          => 'sometimes|string|max:20',
            'alerta_fecha_resolucion'=> 'nullable|date',
            'alerta_comentario'      => 'nullable|string',
            'prod_id'                => 'sometimes|required|string|exists:Productos,pro_id',
            'usr_id_creador'         => 'sometimes|required|string|exists:Usuarios,usr_id',
            'usr_id_resolucion'      => 'nullable|string|exists:Usuarios,usr_id',
        ]);

        $alerta->fill($v);
        $alerta->save();

        return response()->json($alerta);
    }

    public function destroy($id)
    {
        AlertaStock::findOrFail($id)->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function generateAlertaId(): string
    {
        $last = AlertaStock::orderBy('alerta_id', 'desc')->first();
        if ($last && preg_match('/ALT-(\d+)/', $last->alerta_id, $m)) {
            $num = intval($m[1]) + 1;
        } else {
            $num = 1;
        }
        return 'ALT-'.str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
