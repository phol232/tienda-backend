<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\AlertaStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class AlertaStockController extends Controller
{
    public function index(Request $request)
    {
        $query = AlertaStock::with(['producto', 'configuracionOrigen', 'resueltaPor', 'notificaciones']);

        if ($request->has('estado_alerta')) {
            $query->where('estado_alerta', $request->input('estado_alerta'));
        }
        if ($request->has('prod_id')) {
            $query->where('prod_id', $request->input('prod_id'));
        }

        $alertas = $query->orderBy('fecha_generacion', 'desc')->get();

        $alertasArray = [];

        foreach ($alertas as $alerta) {
            // Refresca el producto por si acaso
            if ($alerta->producto) {
                $alerta->setRelation('producto', $alerta->producto->fresh());
            }

            // Arma el comentario con el stock real actual
            $comentario_actual = "Stock actual: " . ($alerta->producto->pro_stock ?? 'N/A') .
                ". Umbral de configuración original (ID:{$alerta->config_alerta_id_origen}): {$alerta->umbral_evaluado}, Tipo config: " .
                ($alerta->configuracion_origen->config_tipo ?? 'N/A') . ".";

            $alertaArray = $alerta->toArray();
            $alertaArray['comentario_resolucion_actual'] = $comentario_actual;

            $alertasArray[] = $alertaArray;
        }

        return response()->json($alertasArray);
    }

    public function show($id)
    {
        $alerta = AlertaStock::with(['producto', 'configuracionOrigen', 'resueltaPor', 'notificaciones'])->findOrFail($id);

        if ($alerta->producto) {
            $alerta->setRelation('producto', $alerta->producto->fresh());
        }

        $comentario_actual = "Stock actual: " . ($alerta->producto->pro_stock ?? 'N/A') .
            ". Umbral de configuración original (ID:{$alerta->config_alerta_id_origen}): {$alerta->umbral_evaluado}, Tipo config: " .
            ($alerta->configuracion_origen->config_tipo ?? 'N/A') . ".";

        $alertaArray = $alerta->toArray();
        $alertaArray['comentario_resolucion_actual'] = $comentario_actual;

        return response()->json($alertaArray);
    }

    public function update(Request $request, $id)
    {
        $alerta = AlertaStock::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'estado_alerta' => ['required', Rule::in(['Activa', 'En Revision', 'Resuelta', 'Ignorada'])],
            'fecha_resolucion' => 'nullable|date|required_if:estado_alerta,Resuelta',
            'comentario_resolucion' => 'nullable|string|required_if:estado_alerta,Resuelta',
            'resuelta_por_usr_id' => 'nullable|string|max:12|exists:Usuarios,usr_id|required_if:estado_alerta,Resuelta',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $datosValidados = $validator->validated();

        if ($datosValidados['estado_alerta'] === 'Resuelta' && empty($datosValidados['fecha_resolucion'])) {
            $datosValidados['fecha_resolucion'] = now();
        }
        if ($datosValidados['estado_alerta'] !== 'Resuelta') {
            $datosValidados['fecha_resolucion'] = null;
            $datosValidados['comentario_resolucion'] = null;
            $datosValidados['resuelta_por_usr_id'] = null;
        }

        $alerta->update($datosValidados);
        $alerta->load(['producto', 'configuracionOrigen', 'resueltaPor']);

        if ($alerta->producto) {
            $alerta->setRelation('producto', $alerta->producto->fresh());
        }

        $comentario_actual = "Stock actual: " . ($alerta->producto->pro_stock ?? 'N/A') .
            ". Umbral de configuración original (ID:{$alerta->config_alerta_id_origen}): {$alerta->umbral_evaluado}, Tipo config: " .
            ($alerta->configuracion_origen->config_tipo ?? 'N/A') . ".";

        $alertaArray = $alerta->toArray();
        $alertaArray['comentario_resolucion_actual'] = $comentario_actual;

        return response()->json($alertaArray);
    }

    public function storeManualAlerta(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prod_id' => 'required|string|max:12|exists:Productos,pro_id',
            'config_alerta_id_origen' => 'nullable|integer|exists:Configuracion_Alertas,config_alerta_id',
            'stock_capturado' => 'required|integer',
            'umbral_evaluado' => 'required|integer',
            'alerta_tipo_generada' => 'required|string|max:50',
            'alerta_nivel_generado' => ['required', Rule::in(['INFO', 'ADVERTENCIA', 'CRITICO'])],
            'mensaje_automatico' => 'required|string',
            'estado_alerta' => ['sometimes', Rule::in(['Activa', 'En Revision', 'Resuelta', 'Ignorada'])],
            'creada_por_proceso' => 'sometimes|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $datosValidados = $validator->validated();
        $datosValidados['estado_alerta'] = $request->input('estado_alerta', 'Activa');
        $datosValidados['creada_por_proceso'] = $request->input('creada_por_proceso', 'MANUAL_API');

        $alerta = AlertaStock::create($datosValidados);
        $alerta->load(['producto', 'configuracionOrigen', 'resueltaPor']);

        if ($alerta->producto) {
            $alerta->setRelation('producto', $alerta->producto->fresh());
        }

        $comentario_actual = "Stock actual: " . ($alerta->producto->pro_stock ?? 'N/A') .
            ". Umbral de configuración original (ID:{$alerta->config_alerta_id_origen}): {$alerta->umbral_evaluado}, Tipo config: " .
            ($alerta->configuracion_origen->config_tipo ?? 'N/A') . ".";

        $alertaArray = $alerta->toArray();
        $alertaArray['comentario_resolucion_actual'] = $comentario_actual;

        return response()->json($alertaArray, Response::HTTP_CREATED);
    }

    public function destroy($id)
    {
        $alerta = AlertaStock::findOrFail($id);
        $alerta->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
