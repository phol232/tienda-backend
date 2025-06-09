<?php

namespace App\Http\Controllers\Seguridad;

use App\Models\Seguridad\UsuariosPerfil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UsuariosController extends Controller
{
    public function show($id)
    {
        $perfil = UsuariosPerfil::findOrFail($id);
        return response()->json($perfil);
    }

    public function update(Request $request, $id)
    {
        $perfil = UsuariosPerfil::findOrFail($id);

        // Log para debug
        Log::info('=== BACKEND UPDATE PERFIL ===');
        Log::info('Request data', $request->all());
        Log::info('Files', $request->allFiles());
        Log::info('Method received', ['method' => $request->method()]);
        Log::info('Content-Type received', ['content_type' => $request->header('Content-Type')]);

        $validated = $request->validate([
            'usrp_nombre'           => 'string|max:100',
            'usrp_apellido'         => 'string|max:100',
            'usrp_telefono'         => 'nullable|string|max:20',
            'usrp_direccion'        => 'nullable|string|max:500',
            'usrp_genero'           => 'nullable|string|max:20',
            'usrp_fecha_nacimiento' => 'nullable|date',
            'usrp_imagen'           => 'nullable|image|max:2048',
        ]);

        Log::info('Validated data', $validated);

        // Solo actualizar campos que no estén vacíos
        $fieldsToUpdate = array_filter($validated, function ($value) {
            return $value !== null && $value !== '';
        });

        Log::info('Fields to update', $fieldsToUpdate);

        $perfil->fill($fieldsToUpdate);

        if ($request->hasFile('usrp_imagen')) {
            $file     = $request->file('usrp_imagen');
            $filename = 'avatars/perfil_' . $perfil->usrp_id . '.' . $file->extension();
            Storage::disk('public')->put($filename, file_get_contents($file));
            $perfil->usrp_imagen = $filename;
        }

        $perfil->save();

        Log::info('Updated profile', $perfil->toArray());
        Log::info('===============================');

        return response()->json([
            'status'  => true,
            'message' => 'Perfil actualizado correctamente',
            'perfil'  => $perfil,
        ]);
    }
}
