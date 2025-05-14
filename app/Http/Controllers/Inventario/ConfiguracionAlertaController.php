<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventario\ConfiguracionAlerta;
use Symfony\Component\HttpFoundation\Response;

class ConfiguracionAlertaController extends Controller
{
    public function index()
    {
        $configs = ConfiguracionAlerta::with('usuario')->get();
        return response()->json($configs);
    }

    public function show($id)
    {
        $config = ConfiguracionAlerta::with('usuario')->findOrFail($id);
        return response()->json($config);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'config_nombre'        => 'required|string|unique:Configuracion_Alertas,config_nombre',
            'config_tipo'          => 'required|string|max:20',
            'config_umbral'        => 'nullable|integer',
            'config_porcentaje'    => 'nullable|numeric',
            'config_periodo'       => 'nullable|integer',
            'config_estado'        => 'sometimes|string|max:20',
            'config_destinatarios' => 'nullable|string',
            'config_ultima_revision'=> 'nullable|date',
            'usr_id'               => 'required|string|exists:Usuarios,usr_id',
        ]);

        $config = ConfiguracionAlerta::create([
            'config_id'           => $this->generateConfigId(),
            'config_nombre'       => $v['config_nombre'],
            'config_tipo'         => $v['config_tipo'],
            'config_umbral'       => $v['config_umbral'] ?? null,
            'config_porcentaje'   => $v['config_porcentaje'] ?? null,
            'config_periodo'      => $v['config_periodo'] ?? null,
            'config_estado'       => $v['config_estado'] ?? 'Activa',
            'config_destinatarios'=> $v['config_destinatarios'] ?? null,
            'config_ultima_revision'=> $v['config_ultima_revision'] ?? now(),
            'usr_id'              => $v['usr_id'],
        ]);

        return response()->json($config, Response::HTTP_CREATED);
    }

    public function update(Request $request, $id)
    {
        $config = ConfiguracionAlerta::findOrFail($id);

        $v = $request->validate([
            'config_nombre'        => 'sometimes|required|string|unique:Configuracion_Alertas,config_nombre,'.$id.',config_id',
            'config_tipo'          => 'sometimes|required|string|max:20',
            'config_umbral'        => 'nullable|integer',
            'config_porcentaje'    => 'nullable|numeric',
            'config_periodo'       => 'nullable|integer',
            'config_estado'        => 'sometimes|string|max:20',
            'config_destinatarios' => 'nullable|string',
            'config_ultima_revision'=> 'nullable|date',
            'usr_id'               => 'sometimes|required|string|exists:Usuarios,usr_id',
        ]);

        $config->fill($v);
        $config->save();

        return response()->json($config);
    }

    public function destroy($id)
    {
        ConfiguracionAlerta::findOrFail($id)->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function generateConfigId(): string
    {
        $last = ConfiguracionAlerta::orderBy('config_id', 'desc')->first();
        if ($last && preg_match('/CONF-(\d+)/', $last->config_id, $m)) {
            $num = intval($m[1]) + 1;
        } else {
            $num = 1;
        }
        return 'CONF-'.str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
