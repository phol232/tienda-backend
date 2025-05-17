<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\NotificacionAlerta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class NotificacionAlertaController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificacionAlerta::with(['alertaStock', 'destinatario']);

        if ($request->has('alerta_stock_id')) {
            $query->where('alerta_stock_id', $request->input('alerta_stock_id'));
        }
        if ($request->has('usr_id_destinatario')) {
            $query->where('usr_id_destinatario', $request->input('usr_id_destinatario'));
        }
        if ($request->has('estado_notificacion')) {
            $query->where('estado_notificacion', $request->input('estado_notificacion'));
        }

        $notificaciones = $query->orderBy('fecha_intento_envio', 'desc')->get();
        return response()->json($notificaciones);
    }

    public function show($id)
    {
        $notificacion = NotificacionAlerta::with(['alertaStock', 'destinatario'])->findOrFail($id);
        return response()->json($notificacion);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alerta_stock_id' => 'required|integer|exists:Alertas_Stock,alerta_stock_id',
            'usr_id_destinatario' => 'required|string|max:12|exists:Usuarios,usr_id',
            'canal_notificacion' => ['required', Rule::in(['EMAIL', 'SMS', 'SISTEMA_INTERNO', 'PUSH'])],
            'estado_notificacion' => ['sometimes', Rule::in(['Pendiente', 'Enviada', 'Fallida', 'Leida'])],
            'asunto_mensaje' => 'nullable|string|max:255',
            'cuerpo_mensaje' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $datosValidados = $validator->validated();
        $datosValidados['estado_notificacion'] = $request->input('estado_notificacion', 'Pendiente');

        $notificacion = NotificacionAlerta::create($datosValidados);
        $notificacion->load(['alertaStock', 'destinatario']);

        return response()->json($notificacion, Response::HTTP_CREATED);
    }

    public function update(Request $request, $id)
    {
        $notificacion = NotificacionAlerta::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'estado_notificacion' => ['required', Rule::in(['Pendiente', 'Enviada', 'Fallida', 'Leida'])],
            'datos_respuesta_canal' => 'nullable|string', // Para registrar errores o IDs de envío
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $notificacion->update($validator->validated());
        $notificacion->load(['alertaStock', 'destinatario']);

        return response()->json($notificacion);
    }
}
