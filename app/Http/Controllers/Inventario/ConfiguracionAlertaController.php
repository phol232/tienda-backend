<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Inventario\ConfiguracionAlerta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ConfiguracionAlertaController extends Controller
{
    public function index()
    {
        $configs = ConfiguracionAlerta::with('creadoPor')->get();
        return response()->json($configs);
    }

    public function show($id)
    {
        $config = ConfiguracionAlerta::with('creadoPor')->findOrFail($id);
        return response()->json($config);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'config_nombre' => 'required|string|max:150|unique:Configuracion_Alertas,config_nombre',
            'config_tipo' => ['required', Rule::in(['STOCK_LLEGA_A_VALOR', 'STOCK_DEBAJO_DE_UMBRAL'])],
            'config_umbral_valor' => 'required|integer',
            'config_aplicabilidad' => ['required', Rule::in(['GENERAL', 'POR_CATEGORIA', 'POR_PRODUCTO_ESPECIFICO'])],
            'id_referencia_aplicabilidad' => [
                'nullable',
                'string',
                'max:12',
                function ($attribute, $value, $fail) use ($request) {
                    $aplicabilidad = $request->input('config_aplicabilidad');
                    if ($aplicabilidad === 'GENERAL' && $value !== null) {
                        $fail('Para aplicabilidad GENERAL, id_referencia_aplicabilidad debe ser nulo.');
                    }
                    if (in_array($aplicabilidad, ['POR_CATEGORIA', 'POR_PRODUCTO_ESPECIFICO']) && $value === null) {
                        $fail('Para aplicabilidad POR_CATEGORIA o POR_PRODUCTO_ESPECIFICO, id_referencia_aplicabilidad es requerido.');
                    }
                    // Aquí podrías añadir validación 'exists' si $value no es nulo
                    if ($value !== null) {
                        if ($aplicabilidad === 'POR_CATEGORIA' && !\App\Models\Productos_Proveedores\Categorias::where('cat_id', $value)->exists()) {
                            $fail('El id_referencia_aplicabilidad no es una categoría válida.');
                        } elseif ($aplicabilidad === 'POR_PRODUCTO_ESPECIFICO' && !\App\Models\Productos_Proveedores\Productos::where('pro_id', $value)->exists()) {
                            $fail('El id_referencia_aplicabilidad no es un producto válido.');
                        }
                    }
                },
            ],
            'config_descripcion' => 'nullable|string',
            'config_nivel_alerta_default' => ['required', Rule::in(['INFO', 'ADVERTENCIA', 'CRITICO'])],
            'config_estado' => ['sometimes', Rule::in(['Activa', 'Inactiva'])],
            'config_creado_por_usr_id' => 'required|string|max:12|exists:Usuarios,usr_id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $datosValidados = $validator->validated();
        $datosValidados['config_estado'] = $request->input('config_estado', 'Activa'); // Default si no viene

        $config = ConfiguracionAlerta::create($datosValidados);
        $config->load('creadoPor');

        return response()->json($config, Response::HTTP_CREATED);
    }

    public function update(Request $request, $id)
    {
        $config = ConfiguracionAlerta::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'config_nombre' => ['sometimes','required','string','max:150',Rule::unique('Configuracion_Alertas')->ignore($id, 'config_alerta_id')],
            'config_tipo' => ['sometimes','required', Rule::in(['STOCK_LLEGA_A_VALOR', 'STOCK_DEBAJO_DE_UMBRAL'])],
            'config_umbral_valor' => 'sometimes|required|integer',
            'config_aplicabilidad' => ['sometimes','required', Rule::in(['GENERAL', 'POR_CATEGORIA', 'POR_PRODUCTO_ESPECIFICO'])],
            'id_referencia_aplicabilidad' => [
                'nullable', // Permitir enviar null para actualizar a GENERAL
                'string',
                'max:12',
                function ($attribute, $value, $fail) use ($request, $config) {
                    // Si no se envía config_aplicabilidad, usar la existente. Si se envía, usar la nueva.
                    $aplicabilidad = $request->input('config_aplicabilidad', $config->config_aplicabilidad);

                    if ($aplicabilidad === 'GENERAL' && $value !== null && $request->has('id_referencia_aplicabilidad')) { // Solo falla si se intenta poner un valor
                        $fail('Para aplicabilidad GENERAL, id_referencia_aplicabilidad debe ser nulo.');
                    }
                    if (in_array($aplicabilidad, ['POR_CATEGORIA', 'POR_PRODUCTO_ESPECIFICO']) && $value === null && $request->has('id_referencia_aplicabilidad')) {
                        // Solo falla si se intenta poner null explícitamente y no se está cambiando a GENERAL
                        if (!($request->input('config_aplicabilidad') === 'GENERAL')) {
                            $fail('Para aplicabilidad POR_CATEGORIA o POR_PRODUCTO_ESPECIFICO, id_referencia_aplicabilidad es requerido.');
                        }
                    }
                    if ($value !== null) { // Solo valida si se provee un valor
                        if ($aplicabilidad === 'POR_CATEGORIA' && !\App\Models\Productos_Proveedores\Categorias::where('cat_id', $value)->exists()) {
                            $fail('El id_referencia_aplicabilidad no es una categoría válida.');
                        } elseif ($aplicabilidad === 'POR_PRODUCTO_ESPECIFICO' && !\App\Models\Productos_Proveedores\Productos::where('pro_id', $value)->exists()) {
                            $fail('El id_referencia_aplicabilidad no es un producto válido.');
                        }
                    }
                },
            ],
            'config_descripcion' => 'nullable|string',
            'config_nivel_alerta_default' => ['sometimes','required', Rule::in(['INFO', 'ADVERTENCIA', 'CRITICO'])],
            'config_estado' => ['sometimes', Rule::in(['Activa', 'Inactiva'])],
            // 'config_creado_por_usr_id' => 'sometimes|required|string|max:12|exists:Usuarios,usr_id', // Generalmente no se actualiza
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $datosValidados = $validator->validated();

        // Lógica especial para id_referencia_aplicabilidad si config_aplicabilidad cambia a GENERAL
        if ($request->has('config_aplicabilidad') && $request->input('config_aplicabilidad') === 'GENERAL') {
            $datosValidados['id_referencia_aplicabilidad'] = null;
        }


        $config->update($datosValidados);
        $config->load('creadoPor');

        return response()->json($config);
    }

    public function destroy($id)
    {
        ConfiguracionAlerta::findOrFail($id)->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
